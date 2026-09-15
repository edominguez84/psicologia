<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Mail\AppointmentRequested;
use App\Models\Appointment;
use App\Models\AppointmentSlot;
use App\Models\Promotion;
use App\Models\User;
use App\Services\SiteSettingsService;
use App\Services\WompiPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentBookingController extends Controller
{
    public function __construct(private SiteSettingsService $settings, private WompiPaymentService $wompi)
    {
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

        $promotion = ! empty($data['promotion_id']) ? Promotion::active()->find($data['promotion_id']) : null;

        $appointment = DB::transaction(function () use ($data, $promotion) {
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

            $appointment = Appointment::create([
                'user_id' => Auth::id(),
                'appointment_slot_id' => $slot->id,
                'patient_note' => $data['patient_note'] ?? null,
                'payment_method' => $data['payment_method'],
                'amount' => $promotion?->price,
                'promotion_id' => $promotion?->id,
            ]);

            // payment_status no es mass-assignable (ver Appointment::$fillable):
            // con transferencia se asume "avisada" — el paciente confirma el
            // pago por WhatsApp y el super_admin lo valida manualmente desde
            // Admin\AppointmentController. Con Wompi queda "unpaid" hasta que
            // exista integración real de cobro.
            if ($data['payment_method'] === 'bank_transfer') {
                $appointment->forceFill(['payment_status' => 'reported'])->save();
            }

            return $appointment;
        });

        if (! $appointment) {
            return back()->withErrors(['appointment_slot_id' => 'Ese horario ya no está disponible, elige otro.']);
        }

        $this->notifySuperAdmins($appointment);

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
