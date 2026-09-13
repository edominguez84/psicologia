<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    /**
     * Slots disponibles + todas las citas propias del paciente autenticado,
     * en cualquier estado (pendiente, aprobada, rechazada, cancelada) —
     * siempre sobre Auth::user(), nunca un {user} de la URL.
     */
    public function index(): View
    {
        return view('profile.appointments.index', [
            'availableSlots' => AppointmentSlot::available()->ordered()->get(),
            'appointments' => Auth::user()->appointments()
                ->with('appointmentSlot')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appointment_slot_id' => ['required', 'integer', 'exists:appointment_slots,id'],
            'patient_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = DB::transaction(function () use ($data) {
            // lockForUpdate evita la condición de carrera de dos pacientes
            // reservando el mismo slot casi simultáneamente — la validación
            // de scopeAvailable() en el listado no es suficiente por sí sola
            // (TOCTOU) sin este lock dentro de la transacción.
            $slot = AppointmentSlot::where('id', $data['appointment_slot_id'])->lockForUpdate()->first();

            $alreadyTaken = $slot === null || ! $slot->is_active || $slot->starts_at->isPast()
                || $slot->appointments()->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::Approved->value])->exists();

            if ($alreadyTaken) {
                return null;
            }

            return Appointment::create([
                'user_id' => Auth::id(),
                'appointment_slot_id' => $slot->id,
                'patient_note' => $data['patient_note'] ?? null,
            ]);
        });

        if (! $appointment) {
            return back()->withErrors(['appointment_slot_id' => 'Ese horario ya no está disponible, elige otro.']);
        }

        $this->notifySuperAdmins($appointment);

        return back()->with('status', 'Solicitud enviada. Te avisaremos por email cuando se confirme.');
    }

    /**
     * Solo el dueño de la cita puede cancelarla, y solo mientras esté
     * pendiente (una vez aprobada, cancelar requeriría avisar a la
     * psicóloga — fuera de alcance de esta versión).
     */
    public function cancel(Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->user_id === Auth::id(), 403);

        if ($appointment->status !== AppointmentStatus::Pending) {
            return back()->withErrors(['appointment' => 'Solo puedes cancelar una cita mientras esté pendiente.']);
        }

        $appointment->forceFill(['status' => AppointmentStatus::Cancelled])->save();

        return back()->with('status', 'Cita cancelada.');
    }

    /**
     * Avisa a TODAS las cuentas super_admin de la solicitud nueva — no
     * bloquea la respuesta si falla el envío.
     */
    private function notifySuperAdmins(Appointment $appointment): void
    {
        try {
            $superAdmins = User::where('role', UserRole::SuperAdmin)->pluck('email');
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
