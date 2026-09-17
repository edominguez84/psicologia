<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Services\SiteSettingsService;
use App\Services\VapiCallService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Configuración de la integración con VAPI (vapi.ai) — llamadas
 * telefónicas automáticas de confirmación de cita, exclusiva de
 * super_admin. Mismo patrón que Admin\PaymentSettingsController/
 * ChatbotChannelsController: todo se guarda en site_settings bajo una sola
 * clave, con un botón de "probar conexión".
 */
class VapiSettingsController extends Controller
{
    private const DEFAULTS = [
        // Interruptor manual, independiente de si las credenciales están
        // completas — permite desactivar las llamadas sin borrar la
        // configuración ya guardada.
        'enabled' => false,
        'api_key' => '',
        'assistant_id' => '',
        'phone_number_id' => '',
        // Secreto compartido para validar el webhook de resultado de la
        // llamada (header X-Vapi-Secret) — se autogenera al entrar por
        // primera vez, igual criterio que otros secretos del sitio.
        'webhook_secret' => '',
        // Cuántas horas antes del horario de la cita se dispara la llamada.
        'hours_before' => 24,
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $vapi = array_replace(self::DEFAULTS, $this->settings->get('vapi', []));

        if (blank($vapi['webhook_secret'])) {
            $vapi['webhook_secret'] = Str::random(40);
            $this->settings->set('vapi', $vapi);
        }

        $this->deleteExpiredDemoAppointments();

        return view('admin.vapi-settings.edit', [
            'vapi' => $vapi,
            'webhookUrl' => route('webhooks.vapi'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'assistant_id' => ['nullable', 'string', 'max:255'],
            'phone_number_id' => ['nullable', 'string', 'max:255'],
            'hours_before' => ['nullable', 'integer', 'min:1', 'max:168'],
        ]);

        $current = array_replace(self::DEFAULTS, $this->settings->get('vapi', []));

        $this->settings->set('vapi', [
            'enabled' => $request->boolean('enabled'),
            'api_key' => $data['api_key'] ?? '',
            'assistant_id' => $data['assistant_id'] ?? '',
            'phone_number_id' => $data['phone_number_id'] ?? '',
            'webhook_secret' => $current['webhook_secret'],
            'hours_before' => isset($data['hours_before']) ? (int) $data['hours_before'] : self::DEFAULTS['hours_before'],
        ]);

        return back()->with('status', 'Configuración de VAPI actualizada.');
    }

    /**
     * Regenera el secreto del webhook — por si se sospecha que se filtró.
     * Cualquier configuración ya guardada en el panel de VAPI para el
     * header X-Vapi-Secret quedaría desactualizada tras esto.
     */
    public function regenerateWebhookSecret(): RedirectResponse
    {
        $vapi = array_replace(self::DEFAULTS, $this->settings->get('vapi', []));
        $vapi['webhook_secret'] = Str::random(40);
        $this->settings->set('vapi', $vapi);

        return back()->with('status', 'Se generó un nuevo secreto de webhook. Actualiza el header X-Vapi-Secret en la configuración del servidor en VAPI.');
    }

    /**
     * Confirma que la API key es válida, sin generar ninguna llamada real —
     * feedback inmediato antes de que un paciente real reciba una llamada
     * de prueba fallida.
     */
    public function testConnection(VapiCallService $vapi): RedirectResponse
    {
        try {
            $vapi->testConnection();

            return back()->with('vapi_test_result', [
                'ok' => true,
                'message' => 'Conexión exitosa: VAPI aceptó las credenciales.',
            ]);
        } catch (\Throwable $e) {
            return back()->with('vapi_test_result', [
                'ok' => false,
                'message' => 'No se pudo conectar con VAPI: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Demo rápida: crea un horario y una cita aprobada de verdad, a nombre
     * del propio super_admin que hace clic (con su teléfono registrado), y
     * dispara la llamada de inmediato — sin esperar al cron ni tener que
     * armar una cita real a mano desde el flujo normal del sitio. El slot
     * queda 5 minutos en el futuro solo para que la cita nazca con un
     * horario válido (AppointmentSlot::scopeAvailable() exige starts_at en
     * el futuro); el llamado en sí es inmediato, no depende de esperar a
     * que llegue ese horario.
     */
    public function sendTestCall(VapiCallService $vapi): RedirectResponse
    {
        $superAdmin = Auth::user();

        if (blank($superAdmin->phone_number)) {
            return back()->with('vapi_test_result', [
                'ok' => false,
                'message' => 'Tu cuenta no tiene un teléfono registrado — agrégalo a tu perfil antes de probar la llamada.',
            ]);
        }

        $startsAt = now()->addMinutes(5);
        $slot = AppointmentSlot::create([
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'user_id' => $superAdmin->id,
            'appointment_slot_id' => $slot->id,
            'payment_method' => 'bank_transfer',
            'patient_note' => 'Cita de demostración generada desde /admin/vapi-settings — se elimina automáticamente.',
        ]);
        $appointment->forceFill(['status' => AppointmentStatus::Approved])->save();

        try {
            $callId = $vapi->callForAppointment($appointment->load(['user', 'appointmentSlot']));
            $appointment->forceFill(['vapi_call_status' => 'scheduled', 'vapi_call_id' => $callId, 'vapi_called_at' => now()])->save();

            return back()->with('vapi_test_result', [
                'ok' => true,
                'message' => "Llamada de prueba enviada a tu teléfono ({$superAdmin->phone_number}). La cita y el horario de demostración se eliminan solos la próxima vez que entres a esta pantalla.",
            ]);
        } catch (\Throwable $e) {
            $appointment->delete();
            $slot->delete();

            return back()->with('vapi_test_result', [
                'ok' => false,
                'message' => 'No se pudo disparar la llamada de prueba: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Borra las citas/horarios de demostración generados por sendTestCall()
     * en visitas anteriores — se identifican por su patient_note fijo, así
     * no se acumulan en el sistema real de citas. Se llama al entrar a
     * edit() en vez de intentar borrarlas "al cerrar la pantalla" (no existe
     * tal evento capturable desde el backend).
     */
    private function deleteExpiredDemoAppointments(): void
    {
        Appointment::where('patient_note', 'Cita de demostración generada desde /admin/vapi-settings — se elimina automáticamente.')
            ->get()
            ->each(function (Appointment $appointment) {
                $slot = $appointment->appointmentSlot;
                $appointment->delete();
                $slot?->delete();
            });
    }
}
