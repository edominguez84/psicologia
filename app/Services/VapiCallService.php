<?php

namespace App\Services;

use App\Models\Appointment;
use App\Support\PhoneNumber;
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
     * Registro de paciente por llamada de voz (ver
     * Admin\VoiceRegistrationSettingsController) — usa la misma API key y el
     * mismo phoneNumberId ya configurados en 'vapi' (una sola cuenta de
     * VAPI/Twilio), pero un Assistant ID distinto, dedicado a recolectar
     * nombre/correo/teléfono en vez de confirmar una cita.
     */
    public function isVoiceRegistrationConfigured(): bool
    {
        $credentials = $this->credentials();
        $voiceRegistration = $this->voiceRegistrationCredentials();

        return filled($credentials['api_key']) && filled($voiceRegistration['assistant_id']) && filled($credentials['phone_number_id']);
    }

    public function isVoiceRegistrationUsable(): bool
    {
        return (bool) $this->voiceRegistrationCredentials()['enabled'] && $this->isVoiceRegistrationConfigured();
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
     * (mismo formato que usa callForAppointment()). $assistantId permite
     * usar un asistente distinto al de confirmación de citas (ver
     * callForVoiceRegistration()) sin duplicar esta lógica HTTP.
     */
    public function callRaw(string $phoneNumber, string $patientName, string $appointmentTime, array $metadata = [], ?string $assistantId = null): string
    {
        $credentials = $this->credentials();

        if (! $this->isConfigured()) {
            throw new RuntimeException('VAPI no tiene configuradas todas las credenciales necesarias.');
        }

        $response = Http::withToken($credentials['api_key'])->post(self::API_URL, [
            'assistantId' => $assistantId ?? $credentials['assistant_id'],
            'phoneNumberId' => $credentials['phone_number_id'],
            'customer' => [
                'number' => PhoneNumber::normalize($phoneNumber),
            ],
            'assistantOverrides' => [
                'variableValues' => [
                    'nombrePaciente' => $patientName,
                    'horarioCita' => $appointmentTime,
                ],
            ],
            // VAPI exige que 'metadata' sea un objeto JSON, aunque esté
            // vacío ({}) — un array PHP vacío ([]) se codifica como array
            // JSON ([]), que la API rechaza con "metadata must be an
            // object" (rechazaba TODA llamada de prueba sin appointment_id,
            // como el botón de prueba con nombre/teléfono libres).
            'metadata' => empty($metadata) ? new \stdClass() : $metadata,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('VAPI rechazó la solicitud de llamada: '.$response->body());
        }

        return $response->json('id');
    }

    /**
     * Dispara la llamada saliente de registro de paciente por voz — el
     * visitante solo deja su teléfono en el sitio (ver
     * VoiceRegistrationLeadController), sin nombre ni horario todavía (eso
     * lo recolecta el propio asistente por voz durante la llamada, e invoca
     * la tool 'create_patient_account' contra VapiToolCallController). No
     * hay nombre/horario que mandar de antemano, así que se pasan cadenas
     * vacías — el guion de este asistente no las usa.
     */
    public function callForVoiceRegistration(string $phoneNumber): string
    {
        $voiceRegistration = $this->voiceRegistrationCredentials();

        if (! $this->isVoiceRegistrationConfigured()) {
            throw new RuntimeException('El registro de pacientes por voz no tiene configuradas todas las credenciales necesarias.');
        }

        return $this->callRaw(
            phoneNumber: $phoneNumber,
            patientName: '',
            appointmentTime: '',
            assistantId: $voiceRegistration['assistant_id'],
        );
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

    /**
     * Configuración propia del registro de paciente por voz — separada de
     * 'vapi' porque es un asistente distinto (con su propio Assistant ID e
     * interruptor enabled/disabled) y un secreto de webhook propio para la
     * tool-call entrante (ver VapiToolCallController), aunque comparta la
     * misma api_key/phone_number_id de la cuenta de VAPI.
     */
    private function voiceRegistrationCredentials(): array
    {
        $voiceRegistration = $this->settings->get('voice_registration', []);

        return array_replace([
            'enabled' => false,
            'assistant_id' => '',
            'webhook_secret' => '',
        ], $voiceRegistration);
    }

    /**
     * Compara el header 'X-Vapi-Secret' de la tool-call entrante de registro
     * por voz contra el secreto configurado para esta sección — secreto
     * propio, distinto al de 'vapi' (verifyWebhookSecret()), porque es un
     * endpoint y un asistente completamente separados.
     */
    public function verifyVoiceRegistrationWebhookSecret(?string $received): bool
    {
        $secret = $this->voiceRegistrationCredentials()['webhook_secret'];

        if (! $received || ! filled($secret)) {
            return false;
        }

        return hash_equals($secret, $received);
    }
}
