<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(): View
    {
        return view('admin.testimonials.index', [
            'testimonials' => Testimonial::with('user')->latest()->get(),
        ]);
    }

    public function approve(Testimonial $testimonial): RedirectResponse
    {
        // is_approved/approved_at/approved_by no son mass-assignable a
        // propósito (ver Testimonial::$fillable) — se setean explícitamente
        // aquí, el único lugar donde corresponde aprobar contenido.
        $testimonial->forceFill([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ])->save();

        return back()->with('status', 'Testimonio aprobado y visible en el sitio.');
    }

    public function unapprove(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->forceFill([
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('status', 'Testimonio oculto del sitio.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();

        return back()->with('status', 'Testimonio eliminado.');
    }
}
