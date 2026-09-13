<?php

namespace App\Http\Controllers;

use App\Models\CustomSection;

class SiteController extends Controller
{
    public function home()
    {
        // Modo demo (Netlify sin backend): sin BD, sin secciones personalizadas.
        $customSections = config('site.demo_mode')
            ? collect()
            : CustomSection::active()->ordered()->get();

        return view('home', [
            'site' => config('site'),
            'customSections' => $customSections,
        ]);
    }

    public function privacy()
    {
        return view('privacy', [
            'site' => config('site'),
        ]);
    }
}
