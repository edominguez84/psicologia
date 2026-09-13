<?php

namespace App\Http\Controllers;

use App\Models\CustomSection;
use App\Models\Testimonial;

class SiteController extends Controller
{
    public function home()
    {
        // Modo demo (Netlify sin backend): sin BD, sin secciones
        // personalizadas ni testimonios de pacientes.
        $demoMode = (bool) config('site.demo_mode');

        $customSections = $demoMode ? collect() : CustomSection::active()->ordered()->get();

        // Testimonios: los de config/site.php (curados por la propia
        // psicóloga) más los aprobados de pacientes registrados. Nunca se
        // muestran los no aprobados.
        $patientTestimonials = $demoMode
            ? collect()
            : Testimonial::approved()->with('user')->latest()->get()->map(fn ($t) => [
                'name' => $t->user->name,
                'place' => null,
                'text' => $t->text,
            ]);

        return view('home', [
            'site' => config('site'),
            'customSections' => $customSections,
            'patientTestimonials' => $patientTestimonials,
        ]);
    }

    public function privacy()
    {
        return view('privacy', [
            'site' => config('site'),
        ]);
    }
}
