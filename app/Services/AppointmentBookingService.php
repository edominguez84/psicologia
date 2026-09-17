<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Lógica de reservar un slot para un usuario ya identificado — extraída de
 * Admin\AppointmentBookingController::store() para que también la use la
 * tool book_appointment de ChatbotAiService, sin duplicar el manejo de
 * concurrencia (lockForUpdate) ni las reglas de qué hace disponible a un
 * slot. Devuelve null si el slot ya no está disponible (mismo criterio en
 * ambos llamadores).
 */
class AppointmentBookingService
{
    public function book(User $user, int $appointmentSlotId, string $paymentMethod, ?int $promotionId = null, ?string $patientNote = null): ?Appointment
    {
        $promotion = $promotionId ? Promotion::active()->find($promotionId) : null;

        $appointment = DB::transaction(function () use ($user, $appointmentSlotId, $paymentMethod, $promotion, $patientNote) {
            // lockForUpdate evita la condición de carrera de dos pacientes
            // reservando el mismo slot casi simultáneamente.
            $slot = AppointmentSlot::where('id', $appointmentSlotId)->lockForUpdate()->first();

            $alreadyTaken = $slot === null || ! $slot->is_active || $slot->starts_at->isPast()
                || $slot->appointments()->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Approved->value])->exists();

            if ($alreadyTaken) {
                return null;
            }

            $appointment = Appointment::create([
                'user_id' => $user->id,
                'appointment_slot_id' => $slot->id,
                'patient_note' => $patientNote,
                'payment_method' => $paymentMethod,
                'amount' => $promotion?->price,
                'promotion_id' => $promotion?->id,
            ]);

            // payment_status no es mass-assignable (ver Appointment::$fillable):
            // con transferencia se asume "avisada" — el paciente confirma el
            // pago por WhatsApp y el super_admin lo valida manualmente.
            if ($paymentMethod === 'bank_transfer') {
                $appointment->forceFill(['payment_status' => 'reported'])->save();
            }

            return $appointment;
        });

        if ($appointment) {
            $this->notifySuperAdmins($appointment);
        }

        return $appointment;
    }

    /**
     * Avisa a TODAS las cuentas super_admin de la solicitud nueva — no
     * bloquea si falla el envío.
     */
    private function notifySuperAdmins(Appointment $appointment): void
    {
        try {
            $superAdmins = User::where('role', 'super_admin')->pluck('email');
            if ($superAdmins->isNotEmpty()) {
                Mail::to($superAdmins->first())
                    ->cc($superAdmins->slice(1))
                    ->send(new AppointmentRequested($appointment));
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo notificar la nueva solicitud de cita: '.$e->getMessage());
        }
    }
}
