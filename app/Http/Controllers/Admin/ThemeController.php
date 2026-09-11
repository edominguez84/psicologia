<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        return view('admin.theme.edit', [
            'colors' => $this->settings->get('colors', [
                'primary' => '#386a97',
                'background' => '#ffffff',
                'accent' => '#b9744c',
                'text' => '#1f2a37',
            ]),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'regex' => 'Debe ser un color hexadecimal válido, por ejemplo #386a97.',
        ]);

        $this->settings->set('colors', $data);

        return back()->with('status', 'Colores actualizados. Ya se ven reflejados en el sitio.');
    }
}
