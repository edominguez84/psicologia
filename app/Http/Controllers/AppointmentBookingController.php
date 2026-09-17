<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Promotion;
use App\Services\AppointmentBookingService;
use App\Services\SiteSettingsService;
use App\Services\WompiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    public function __construct(
        private SiteSettingsService $settings,
        private WompiPaymentService $wompi,
        private AppointmentBookingService $booking,
    ) {
    }

    /**
     * Slots disponibles + todas las citas propias del paciente autenticado,
     * en cualquier estado (pendiente, aprobada, rechazada, cancelada) —
     * siempre sobre Auth::user(), nunca un {user} de la URL. Si llega
     * ?promotion=ID (desde una tarjeta de promoción en la landing), se
     * precarga esa promoción para reflejar su precio en el formulario.
     */
    public function index(Request $request): View
    {
        $payment = array_replace_recursive(
            ['method' => 'bank_transfer', 'wompi' => [], 'bank_transfer' => []],
            $this->settings->get('payment', [])
        );

        $selectedPromotion = null;
        if ($request->filled('promotion')) {
            $selectedPromotion = Promotion::active()->find($request->integer('promotion'));
        }

        return view('profile.appointments.index', [
            'availableSlots' => AppointmentSlot::available()->ordered()->get(),
            'appointments' => Auth::user()->appointments()
                ->with(['appointmentSlot', 'promotion'])
                ->latest()
                ->get(),
            'payment' => $payment,
            'selectedPromotion' => $selectedPromotion,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'appointment_slot_id' => ['required', 'integer', 'exists:appointment_slots,id'],
            'patient_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', Rule::in(['bank_transfer', 'wompi'])],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
        ]);

        $appointment = $this->booking->book(
            user: Auth::user(),
            appointmentSlotId: $data['appointment_slot_id'],
            paymentMethod: $data['payment_method'],
            promotionId: $data['promotion_id'] ?? null,
            patientNote: $data['patient_note'] ?? null,
        );

        if (! $appointment) {
            return back()->withErrors(['appointment_slot_id' => 'Ese horario ya no está disponible, elige otro.']);
        }

        if ($data['payment_method'] === 'wompi' && $appointment->amount > 0) {
            return $this->redirectToWompiPaymentLink($appointment);
        }

        return back()->with('status', 'Solicitud enviada. Te avisaremos por email cuando se confirme.');
    }

    /**
     * Genera el enlace de pago de Wompi para esta cita y redirige ahí a la
     * paciente. Si Wompi no está configurado o la llamada falla, la cita
     * queda igual creada (payment_status 'unpaid') y se avisa para que
     * intente de nuevo o elija transferencia — nunca se pierde la solicitud
     * ya guardada por un error de la pasarela.
     */
    private function redirectToWompiPaymentLink(Appointment $appointment): RedirectResponse
    {
        if (! $this->wompi->isConfigured()) {
            return back()->with('status', 'Solicitud guardada, pero Wompi no está disponible ahora mismo. Contáctanos por WhatsApp para coordinar el pago.');
        }

        try {
            $link = $this->wompi->createPaymentLink(
                amount: (float) $appointment->amount,
                reference: "cita-{$appointment->id}",
                productName: 'Cita psicológica - '.($appointment->promotion?->title ?? 'Consulta'),
                redirectUrl: route('patient.appointments.index'),
                webhookUrl: route('webhooks.wompi'),
            );

            $appointment->forceFill([
                'payment_reference' => $link['idEnlace'] ?? null,
            ])->save();

            return redirect()->away($link['urlEnlace']);
        } catch (\Throwable $e) {
            Log::error('No se pudo generar el enlace de pago de Wompi: '.$e->getMessage());

            return back()->with('status', 'Solicitud guardada, pero no se pudo generar el enlace de pago. Intenta de nuevo o elige transferencia bancaria.');
        }
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
}
