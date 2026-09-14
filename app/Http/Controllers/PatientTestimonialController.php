<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Support\ProfanityFilter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PatientTestimonialController extends Controller
{
    /**
     * Un testimonio activo por paciente (0 o 1). Siempre opera sobre
     * Auth::user() — sin route-model-binding de otro usuario.
     */
    public function edit(): View
    {
        return view('profile.testimonial.edit', [
            'testimonial' => Auth::user()->testimonial,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:1000'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        if (ProfanityFilter::containsProhibitedWord($data['text'])) {
            $banned = $this->registerProfanityStrike();

            if ($banned) {
                // La cuenta ya quedó suspendida (ver registerProfanityStrike);
                // se cierra la sesión actual para que el middleware de auth
                // no la deje seguir navegando con una sesión ya inválida.
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('status', 'Tu cuenta fue suspendida por publicar contenido que infringe las normas de la comunidad en repetidas ocasiones.');
            }

            return back()->withErrors([
                'text' => 'Tu testimonio no puede publicarse porque infringe las normas de la comunidad.',
            ])->withInput();
        }

        // Editar un testimonio ya aprobado lo vuelve a pendiente: evita que
        // el paciente cuele contenido nuevo sin revisión reusando la
        // aprobación de uno anterior. is_approved/approved_at/approved_by no
        // son mass-assignable, así que se setean explícitamente con forceFill.
        $testimonial = Testimonial::firstOrNew(['user_id' => Auth::id()]);
        $testimonial->text = $data['text'];
        $testimonial->rating = $data['rating'];
        $testimonial->forceFill(['is_approved' => false, 'approved_at' => null, 'approved_by' => null]);
        $testimonial->save();

        return back()->with('status', 'Testimonio guardado. Quedará visible en el sitio una vez que lo revisemos.');
    }

    /**
     * Incrementa el contador de intentos con contenido inapropiado y, al
     * llegar al umbral configurado, banea la cuenta automáticamente (mismo
     * mecanismo que Admin\UsersController::ban(): banned_at/banned_reason +
     * borrar sus sesiones activas). Devuelve true si la cuenta quedó
     * suspendida en esta misma llamada.
     */
    private function registerProfanityStrike(): bool
    {
        $user = Auth::user();
        $user->increment('profanity_strikes');
        $user->refresh();

        activity('moderation')->causedBy($user)->log('testimonio_rechazado_por_contenido_inapropiado');

        if ($user->profanity_strikes >= ProfanityFilter::banThreshold() && ! $user->isBanned()) {
            $user->forceFill([
                'banned_at' => now(),
                'banned_reason' => 'Cuenta suspendida automáticamente por publicar contenido que infringe las normas de la comunidad en repetidas ocasiones.',
            ])->save();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            activity('moderation')->causedBy($user)->log('cuenta_suspendida_automaticamente_por_contenido_inapropiado');

            return true;
        }

        return false;
    }
}
