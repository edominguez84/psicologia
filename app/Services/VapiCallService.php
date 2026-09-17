<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración con la API de VAPI (vapi.ai) — llamadas telefónicas
 * automáticas con voz de IA, usadas para recordar/confirmar una cita
 * aprobada al paciente, al número de teléfono que dejó al registrarse.
 * Exclusiva de super_admin (ver Admin\VapiSettingsController).
 *
 * VAPI expone una API REST simple: POST https://api.vapi.ai/call crea una
 * llamada saliente indicando qué asistente usar (ya configurado en el
 * propio panel de VAPI, no aquí) y a qué número llamar, con variables
 * dinámicas (nombre del paciente, horario) que el asistente puede
 * referenciar en su guion con {{doble_llave}}.
 */
class VapiCallService
{
    private const API_URL = 'https://api.vapi.ai/call';

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function isConfigured(): bool
    {
        $credentials = $this->credentials();

        return filled($credentials['api_key']) && filled($credentials['assistant_id']) && filled($credentials['phone_number_id']);
    }

    /**
     * ¿Debe usarse la integración ahora mismo? Requiere el interruptor
     * manual activado además de las credenciales completas — mismo criterio
     * que ChatbotAiService::isUsable(), para poder desactivarla sin borrar
     * la configuración ya guardada.
     */
    public function isUsable(): bool
    {
        $credentials = $this->credentials();

        return (bool) $credentials['enabled'] && $this->isConfigured();
    }

    /**
     * Cuántas horas antes del horario de la cita se debe disparar la
     * llamada — configurable por el super_admin, usado por
     * App\Console\Commands\SendAppointmentCallReminders para calcular la
     * ventana de citas a llamar en cada ejecución del cron.
     */
    public function hoursBefore(): int
    {
        return $this->credentials()['hours_before'];
    }

    /**
     * Dispara la llamada saliente para una cita ya aprobada. $appointment
     * debe traer cargadas las relaciones user/appointmentSlot. Devuelve el
     * id de la llamada en VAPI (para guardarlo como vapi_call_id y poder
     * cruzarlo luego con el webhook de resultado).
     */
    public function callForAppointment(Appointment $appointment): string
    {
        $patient = $appointment->user;
        $slot = $appointment->appointmentSlot;

        return $this->callRaw(
            phoneNumber: $patient->phone_number,
            patientName: $patient->name,
            appointmentTime: $slot->starts_at->translatedFormat('l j \d\e F \a \l\a\s g:i A'),
            metadata: [
                // Recuperado tal cual en el webhook de fin de llamada, para
                // saber a qué cita corresponde sin depender de buscar por
                // número de teléfono (que no es único: un paciente puede
                // tener más de una cita).
                'appointment_id' => $appointment->id,
            ],
        );
    }

    /**
     * Dispara una llamada saliente con datos sueltos, sin necesitar un
     * Appointment/User reales — usado por el botón de demo del panel
     * (Admin\VapiSettingsController::sendTestCall()), que permite escribir
     * un nombre y teléfono cualquiera al momento para probar el guion del
     * asistente sin depender de que exista una cuenta con ese teléfono
     * cargado. $appointmentTime ya debe venir formateado como texto legible
     * (mismo formato que usa callForAppointment()).
     */
    public function callRaw(string $phoneNumber, string $patientName, string $appointmentTime, array $metadata = []): string
    {
        $credentials = $this->credentials();

        if (! $this->isConfigured()) {
            throw new RuntimeException('VAPI no tiene configuradas todas las credenciales necesarias.');
        }

        $response = Http::withToken($credentials['api_key'])->post(self::API_URL, [
            'assistantId' => $credentials['assistant_id'],
            'phoneNumberId' => $credentials['phone_number_id'],
            'customer' => [
                'number' => $this->normalizePhoneNumber($phoneNumber),
            ],
            'assistantOverrides' => [
                'variableValues' => [
                    'nombrePaciente' => $patientName,
                    'horarioCita' => $appointmentTime,
                ],
            ],
            'metadata' => $metadata,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('VAPI rechazó la solicitud de llamada: '.$response->body());
        }

        return $response->json('id');
    }

    /**
     * Confirma que la API key es válida — usado por el botón "Probar
     * conexión" del panel. GET /assistant es de lectura, no genera ninguna
     * llamada real ni cobra nada.
     */
    public function testConnection(): void
    {
        $credentials = $this->credentials();

        $response = Http::withToken($credentials['api_key'])->get('https://api.vapi.ai/assistant');

        if ($response->failed()) {
            throw new RuntimeException($response->json('message') ?? 'VAPI rechazó las credenciales.');
        }
    }

    /**
     * Compara el header 'X-Vapi-Secret' del webhook entrante contra el
     * secreto configurado — mismo criterio simple de secreto compartido que
     * ya usa TelegramBotService::verifySecretToken().
     */
    public function verifyWebhookSecret(?string $received): bool
    {
        $secret = $this->credentials()['webhook_secret'];

        if (! $received || ! filled($secret)) {
            return false;
        }

        return hash_equals($secret, $received);
    }

    /**
     * VAPI espera el número en formato E.164 (+50370000000). Los teléfonos
     * ya guardados en el sitio pueden venir sin el símbolo '+' o con
     * espacios/guiones (el registro no fuerza un formato estricto) — se
     * normaliza lo mejor posible antes de mandarlo; si ya trae un '+',
     * se respeta tal cual.
     */
    private function normalizePhoneNumber(string $phone): string
    {
        $digitsOnly = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($digitsOnly, '+')) {
            return $digitsOnly;
        }

        // Sin código de país explícito, se asume El Salvador (+503) — el
        // sitio opera principalmente ahí (ver ElSalvadorLocations).
        return '+503'.$digitsOnly;
    }

    private function credentials(): array
    {
        $vapi = $this->settings->get('vapi', []);

        return array_replace([
            'enabled' => false,
            'api_key' => '',
            'assistant_id' => '',
            'phone_number_id' => '',
            'webhook_secret' => '',
            'hours_before' => 24,
        ], $vapi);
    }
}
