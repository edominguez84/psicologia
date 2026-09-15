<?php

namespace Tests\Unit;

use App\Services\SiteSettingsService;
use App\Services\WompiPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class WompiPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private function configureCredentials(): void
    {
        app(SiteSettingsService::class)->set('payment', [
            'method' => 'wompi',
            'wompi' => [
                'mode' => 'sandbox',
                'app_id' => 'test-app-id',
                'api_secret' => 'test-api-secret',
            ],
        ]);
    }

    public function test_no_esta_configurado_sin_credenciales(): void
    {
        $service = app(WompiPaymentService::class);

        $this->assertFalse($service->isConfigured());
    }

    public function test_esta_configurado_con_credenciales_guardadas(): void
    {
        $this->configureCredentials();

        $this->assertTrue(app(WompiPaymentService::class)->isConfigured());
    }

    public function test_obtiene_un_token_de_acceso_y_lo_cachea(): void
    {
        $this->configureCredentials();
        Http::fake([
            'id.wompi.sv/*' => Http::response(['access_token' => 'abc123', 'expires_in' => 3600], 200),
        ]);

        $service = app(WompiPaymentService::class);
        $token1 = $service->accessToken();
        $token2 = $service->accessToken();

        $this->assertSame('abc123', $token1);
        $this->assertSame('abc123', $token2);
        Http::assertSentCount(1); // la segunda llamada usó la caché, no pidió otro token.
    }

    public function test_lanza_excepcion_si_wompi_rechaza_la_autenticacion(): void
    {
        $this->configureCredentials();
        Http::fake([
            'id.wompi.sv/*' => Http::response(['error' => 'invalid_client'], 400),
        ]);

        $this->expectException(RuntimeException::class);

        app(WompiPaymentService::class)->accessToken();
    }

    public function test_crea_un_enlace_de_pago(): void
    {
        $this->configureCredentials();
        Http::fake([
            'id.wompi.sv/*' => Http::response(['access_token' => 'abc123'], 200),
            'api.wompi.sv/EnlacePago' => Http::response([
                'idEnlace' => 999,
                'urlEnlace' => 'https://checkout.wompi.sv/enlace/999',
                'urlQrCodeEnlace' => 'https://checkout.wompi.sv/qr/999',
            ], 200),
        ]);

        $result = app(WompiPaymentService::class)->createPaymentLink(
            amount: 50.00,
            reference: 'cita-1',
            productName: 'Consulta',
            redirectUrl: 'https://studio84sv.com/perfil/citas',
            webhookUrl: 'https://studio84sv.com/webhooks/wompi',
        );

        $this->assertSame(999, $result['idEnlace']);
        $this->assertSame('https://checkout.wompi.sv/enlace/999', $result['urlEnlace']);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.wompi.sv/EnlacePago'
                && $request['monto'] === 50.0
                && $request['identificadorEnlaceComercio'] === 'cita-1';
        });
    }

    public function test_verifica_la_firma_del_webhook_correctamente(): void
    {
        $this->configureCredentials();
        $body = '{"IdTransaccion":1,"ResultadoTransaccion":"ExitosaAprobada"}';
        $validSignature = hash_hmac('sha256', $body, 'test-api-secret');

        $service = app(WompiPaymentService::class);

        $this->assertTrue($service->verifyWebhookSignature($body, $validSignature));
        $this->assertFalse($service->verifyWebhookSignature($body, 'firma-incorrecta'));
        $this->assertFalse($service->verifyWebhookSignature($body, null));
    }
}
