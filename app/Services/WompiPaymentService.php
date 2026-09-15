<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración con la API REST de Wompi El Salvador (docs.wompi.sv), no
 * confundir con Wompi Colombia: aquí la autenticación es OAuth2
 * client-credentials (App ID + API Secret) contra id.wompi.sv, y el cobro se
 * hace generando un "Enlace de Pago" hospedado por Wompi (POST
 * /EnlacePago) — el paciente termina en una pantalla de pago de Wompi, no en
 * un formulario de tarjeta propio.
 *
 * Las credenciales y el modo (sandbox/production) se leen siempre de
 * site_settings.payment.wompi, nunca de config/env — es lo que el super_admin
 * guarda desde /admin/payment-settings.
 */
class WompiPaymentService
{
    private const AUTH_URL = 'https://id.wompi.sv/connect/token';

    private const API_BASE = [
        'sandbox' => 'https://api.wompi.sv',
        'production' => 'https://api.wompi.sv',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function isConfigured(): bool
    {
        $wompi = $this->credentials();

        return filled($wompi['app_id']) && filled($wompi['api_secret']);
    }

    /**
     * Token de acceso, cacheado por debajo de los 3600s reales que dura
     * (con margen de 60s) para no pedir uno nuevo en cada request. La clave
     * de caché incluye el app_id para que cambiar de credenciales no sirva
     * un token viejo de otra cuenta.
     */
    public function accessToken(): string
    {
        $wompi = $this->credentials();

        if (! filled($wompi['app_id']) || ! filled($wompi['api_secret'])) {
            throw new RuntimeException('Wompi no tiene credenciales configuradas.');
        }

        return Cache::remember(
            "wompi:token:{$wompi['app_id']}",
            now()->addSeconds(3540),
            function () use ($wompi) {
                $response = Http::asForm()->post(self::AUTH_URL, [
                    'grant_type' => 'client_credentials',
                    'audience' => 'wompi_api',
                    'client_id' => $wompi['app_id'],
                    'client_secret' => $wompi['api_secret'],
                ]);

                if ($response->failed()) {
                    throw new RuntimeException('No se pudo autenticar con Wompi: '.$response->body());
                }

                return $response->json('access_token');
            }
        );
    }

    /**
     * Crea un enlace de pago por el monto indicado y devuelve la URL a la
     * que se debe redirigir al paciente. $reference identifica la cita en
     * nuestro sistema (se usa como identificador del comercio para este
     * enlace, útil para conciliar manualmente si hiciera falta).
     */
    public function createPaymentLink(float $amount, string $reference, string $productName, string $redirectUrl, string $webhookUrl): array
    {
        $token = $this->accessToken();
        $base = self::API_BASE[$this->credentials()['mode']] ?? self::API_BASE['sandbox'];

        $response = Http::withToken($token)->post("{$base}/EnlacePago", [
            'identificadorEnlaceComercio' => $reference,
            'monto' => round($amount, 2),
            'nombreProducto' => $productName,
            'configuracion' => [
                'urlRedirect' => $redirectUrl,
                'urlWebhook' => $webhookUrl,
                'esMontoEditable' => false,
                'esCantidadEditable' => false,
                'notificarTransaccionCliente' => false,
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('No se pudo crear el enlace de pago en Wompi: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Verifica que el header 'wompi_hash' de un webhook entrante coincida
     * con el HMAC-SHA256 del body crudo calculado con el API Secret — así
     * se confirma que la notificación viene realmente de Wompi antes de
     * confiar en su contenido para marcar una cita como pagada.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signatureHeader): bool
    {
        if (! $signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->credentials()['api_secret']);

        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Prueba de conexión desde el panel de configuración: solo intenta
     * obtener un token nuevo (sin cachear) para confirmar que las
     * credenciales son válidas, sin crear ningún enlace de pago real.
     */
    public function testConnection(): void
    {
        $wompi = $this->credentials();
        Cache::forget("wompi:token:{$wompi['app_id']}");

        $response = Http::asForm()->post(self::AUTH_URL, [
            'grant_type' => 'client_credentials',
            'audience' => 'wompi_api',
            'client_id' => $wompi['app_id'],
            'client_secret' => $wompi['api_secret'],
        ]);

        if ($response->failed() || ! $response->json('access_token')) {
            throw new RuntimeException($response->json('error_description') ?? $response->body() ?: 'Wompi rechazó las credenciales.');
        }
    }

    private function credentials(): array
    {
        $payment = $this->settings->get('payment', []);

        return array_replace(
            ['mode' => 'sandbox', 'app_id' => '', 'api_secret' => ''],
            $payment['wompi'] ?? []
        );
    }
}
