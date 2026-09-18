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
use Illuminate\Support\Facades\Storage;

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
    public function __construct(
        private SiteSettingsService $settings,
        private WompiPaymentService $wompi,
        private NotificationService $notifications,
    ) {
    }

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
     * Información de pago para una cita ya creada: si el método es Wompi,
     * intenta generar el enlace de pago real (y lo guarda como
     * payment_reference); si es transferencia, devuelve los datos bancarios
     * e instrucciones ya configuradas por el super_admin. Usado tanto por
     * Admin\AppointmentBookingController (redirige al enlace) como por la
     * tool book_appointment de ChatbotAiService (le pasa el texto/link al
     * paciente dentro de la conversación, sin poder redirigir).
     *
     * Nunca lanza si Wompi falla — la cita ya está creada y no debe
     * perderse por un error de la pasarela; en ese caso devuelve
     * 'wompi_unavailable' para que el llamador informe con honestidad.
     */
    public function paymentInfoFor(Appointment $appointment): array
    {
        if ($appointment->payment_method === 'wompi') {
            if (! $this->wompi->isConfigured()) {
                return ['method' => 'wompi', 'status' => 'wompi_unavailable'];
            }

            try {
                $link = $this->wompi->createPaymentLink(
                    amount: (float) $appointment->amount,
                    reference: "cita-{$appointment->id}",
                    productName: 'Cita psicológica - '.($appointment->promotion?->title ?? 'Consulta'),
                    redirectUrl: route('patient.appointments.index'),
                    webhookUrl: route('webhooks.wompi'),
                );

                $appointment->forceFill(['payment_reference' => $link['idEnlace'] ?? null])->save();

                return ['method' => 'wompi', 'status' => 'ok', 'payment_url' => $link['urlEnlace'] ?? null];
            } catch (\Throwable $e) {
                Log::error('No se pudo generar el enlace de pago de Wompi: '.$e->getMessage());

                return ['method' => 'wompi', 'status' => 'wompi_unavailable'];
            }
        }

        $bankTransfer = array_replace(
            ['bank_name' => '', 'account_number' => '', 'account_holder' => '', 'instructions' => '', 'account_image_path' => null],
            $this->settings->get('payment', [])['bank_transfer'] ?? []
        );

        return [
            'method' => 'bank_transfer',
            'status' => 'ok',
            'bank_name' => $bankTransfer['bank_name'],
            'account_number' => $bankTransfer['account_number'],
            'account_holder' => $bankTransfer['account_holder'],
            'instructions' => $bankTransfer['instructions'],
            'account_image_url' => $bankTransfer['account_image_path'] ? Storage::url($bankTransfer['account_image_path']) : null,
        ];
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

        $this->notifications->notify(
            type: 'appointment_pending',
            title: 'Nueva cita pendiente de aprobar',
            body: $appointment->user->name,
            link: route('admin.appointments.index'),
            feature: 'appointments',
        );

        if ($appointment->payment_status === 'reported') {
            $this->notifications->notify(
                type: 'payment_reported',
                title: 'Pago por transferencia reportado',
                body: $appointment->user->name,
                link: route('admin.appointments.index'),
                feature: 'appointments',
            );
        }
    }
}
