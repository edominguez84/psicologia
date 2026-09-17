<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\VapiCallService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Dispara la llamada automática de VAPI para cada cita aprobada que entra
 * en la ventana de aviso configurada (site_settings.vapi.hours_before) y
 * que todavía no fue llamada. Pensado para correr cada pocos minutos vía
 * el scheduler de Laravel (ver routes/console.php) — el rango de la
 * ventana se calcula sobre el momento exacto de cada ejecución, así que
 * una frecuencia más espaciada que el ancho de la ventana no se salta
 * ninguna cita mientras el cron real del servidor esté funcionando.
 */
class SendAppointmentCallReminders extends Command
{
    protected $signature = 'appointments:call-reminders';

    protected $description = 'Llama automáticamente (VAPI) a los pacientes con cita aprobada dentro de la ventana de aviso configurada';

    public function handle(VapiCallService $vapi): int
    {
        if (! $vapi->isUsable()) {
            return self::SUCCESS;
        }

        $hoursBefore = $vapi->hoursBefore();
        $now = now();
        // Ventana de 10 minutos alrededor del punto exacto "ahora +
        // hoursBefore" — ancha para tolerar variación en la frecuencia real
        // del cron del hosting, pero acotada para no re-evaluar citas ya
        // lejanas en cada ejecución (whereNull('vapi_call_status') ya evita
        // llamar dos veces, esto es solo para no barrer toda la tabla).
        $windowStart = $now->copy()->addHours($hoursBefore)->subMinutes(5);
        $windowEnd = $now->copy()->addHours($hoursBefore)->addMinutes(5);

        $appointments = Appointment::readyForVapiCall($windowStart, $windowEnd)
            ->with(['user', 'appointmentSlot'])
            ->get();

        foreach ($appointments as $appointment) {
            if (! $appointment->user || ! filled($appointment->user->phone_number)) {
                // Sin teléfono no hay a quién llamar — se marca así para no
                // reintentarlo en cada ejecución.
                $appointment->forceFill(['vapi_call_status' => 'failed', 'vapi_call_result' => ['error' => 'El paciente no tiene teléfono registrado.']])->save();

                continue;
            }

            try {
                $callId = $vapi->callForAppointment($appointment);
                $appointment->forceFill([
                    'vapi_call_status' => 'scheduled',
                    'vapi_call_id' => $callId,
                    'vapi_called_at' => now(),
                ])->save();

                $this->info("Llamada disparada para la cita #{$appointment->id} (call id: {$callId}).");
            } catch (\Throwable $e) {
                Log::error("Fallo disparando la llamada VAPI para la cita #{$appointment->id}: ".$e->getMessage());
                $appointment->forceFill([
                    'vapi_call_status' => 'failed',
                    'vapi_call_result' => ['error' => $e->getMessage()],
                ])->save();
            }
        }

        return self::SUCCESS;
    }
}
