<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Services\VapiCallService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Configuración del registro de paciente por llamada de voz — exclusiva de
 * super_admin, mismo patrón que Admin\VapiSettingsController. Usa la misma
 * api_key/phone_number_id ya guardadas en site_settings.vapi (una sola
 * cuenta de VAPI/Twilio), pero un Assistant ID propio dedicado a recolectar
 * nombre/correo/teléfono por voz en vez de confirmar una cita.
 */
class VoiceRegistrationSettingsController extends Controller
{
    private const DEFAULTS = [
        // Interruptor manual — controla tanto si el formulario público
        // dispara la llamada como si se muestra el botón en el sitio.
        'enabled' => false,
        'assistant_id' => '',
        // Secreto compartido para validar la tool-call entrante (header
        // X-Vapi-Secret) — propio de esta sección, distinto al de 'vapi'.
        'webhook_secret' => '',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $voiceRegistration = array_replace(self::DEFAULTS, $this->settings->get('voice_registration', []));

        if (blank($voiceRegistration['webhook_secret'])) {
            $voiceRegistration['webhook_secret'] = Str::random(40);
            $this->settings->set('voice_registration', $voiceRegistration);
        }

        $vapi = $this->settings->get('vapi', []);

        return view('admin.voice-registration-settings.edit', [
            'voiceRegistration' => $voiceRegistration,
            'hasVapiAccount' => filled($vapi['api_key'] ?? null) && filled($vapi['phone_number_id'] ?? null),
            'toolCallWebhookUrl' => route('webhooks.vapi-tools'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'assistant_id' => ['nullable', 'string', 'max:255'],
        ]);

        $current = array_replace(self::DEFAULTS, $this->settings->get('voice_registration', []));

        $this->settings->set('voice_registration', [
            'enabled' => $request->boolean('enabled'),
            'assistant_id' => $data['assistant_id'] ?? '',
            'webhook_secret' => $current['webhook_secret'],
        ]);

        return back()->with('status', 'Configuración de registro por voz actualizada.');
    }

    /**
     * Regenera el secreto del webhook — por si se sospecha que se filtró.
     */
    public function regenerateWebhookSecret(): RedirectResponse
    {
        $voiceRegistration = array_replace(self::DEFAULTS, $this->settings->get('voice_registration', []));
        $voiceRegistration['webhook_secret'] = Str::random(40);
        $this->settings->set('voice_registration', $voiceRegistration);

        return back()->with('status', 'Se generó un nuevo secreto de webhook. Actualiza el header X-Vapi-Secret en la tool del asistente en VAPI.');
    }

    /**
     * Demo rápida: dispara la llamada de registro a un teléfono escrito a
     * mano, igual criterio que VapiSettingsController::sendTestCall().
     */
    public function sendTestCall(Request $request, VapiCallService $vapi): RedirectResponse
    {
        $data = $request->validate([
            'test_phone' => ['required', 'string', 'max:30'],
        ]);

        try {
            $vapi->callForVoiceRegistration($data['test_phone']);

            return back()->with('voice_registration_test_result', [
                'ok' => true,
                'message' => "Llamada de prueba enviada a {$data['test_phone']}.",
            ]);
        } catch (\Throwable $e) {
            return back()->with('voice_registration_test_result', [
                'ok' => false,
                'message' => 'No se pudo disparar la llamada de prueba: '.$e->getMessage(),
            ]);
        }
    }
}
