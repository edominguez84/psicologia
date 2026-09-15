<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Mail\AppointmentDecided;
use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Lista todas las citas (pendientes, aprobadas y rechazadas) — tanto
     * admin como super_admin pueden gestionar solicitudes.
     */
    public function index(): View
    {
        return view('admin.appointments.index', [
            'appointments' => Appointment::with(['user', 'appointmentSlot', 'decidedBy'])
                ->latest()
                ->get(),
        ]);
    }

    public function approve(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        $appointment->forceFill([
            'status' => AppointmentStatus::Approved,
            'admin_note' => $data['admin_note'] ?? null,
            'decided_at' => now(),
            'decided_by' => Auth::id(),
        ])->save();

        $this->notifyPatient($appointment);

        return back()->with('status', 'Cita aprobada. Se avisó al paciente por email.');
    }

    public function reject(Request $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:1000']]);

        $appointment->forceFill([
            'status' => AppointmentStatus::Rejected,
            'admin_note' => $data['admin_note'] ?? null,
            'decided_at' => now(),
            'decided_by' => Auth::id(),
        ])->save();

        $this->notifyPatient($appointment);

        return back()->with('status', 'Cita rechazada. Se avisó al paciente por email.');
    }

    /**
     * Confirma manualmente que el pago por transferencia bancaria (avisado
     * por WhatsApp) ya se verificó. Los pagos con Wompi normalmente se
     * confirman solos vía Webhooks\WompiWebhookController cuando Wompi
     * notifica la transacción exitosa; este botón queda disponible también
     * para Wompi como respaldo manual si el webhook no llegara.
     * payment_status no es mass-assignable — se setea explícitamente aquí.
     */
    public function confirmPayment(Appointment $appointment): RedirectResponse
    {
        $appointment->forceFill(['payment_status' => 'confirmed'])->save();

        return back()->with('status', 'Pago confirmado.');
    }

    /**
     * Avisa al paciente de la decisión — no bloquea la respuesta si falla el
     * envío (mismo patrón que ContactController).
     */
    private function notifyPatient(Appointment $appointment): void
    {
        try {
            Mail::to($appointment->user->email)->send(new AppointmentDecided($appointment));
        } catch (\Throwable $e) {
            Log::warning('No se pudo notificar al paciente sobre su cita: '.$e->getMessage());
        }
    }
}
