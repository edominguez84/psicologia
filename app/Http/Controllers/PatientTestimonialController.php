<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        ]);

        // Editar un testimonio ya aprobado lo vuelve a pendiente: evita que
        // el paciente cuele contenido nuevo sin revisión reusando la
        // aprobación de uno anterior. is_approved/approved_at/approved_by no
        // son mass-assignable, así que se setean explícitamente con forceFill.
        $testimonial = Testimonial::firstOrNew(['user_id' => Auth::id()]);
        $testimonial->text = $data['text'];
        $testimonial->forceFill(['is_approved' => false, 'approved_at' => null, 'approved_by' => null]);
        $testimonial->save();

        return back()->with('status', 'Testimonio guardado. Quedará visible en el sitio una vez que lo revisemos.');
    }
}
