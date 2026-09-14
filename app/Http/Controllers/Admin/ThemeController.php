<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\FontOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'fonts' => $this->settings->get('fonts', FontOptions::defaults()),
            'headingFonts' => FontOptions::headings(),
            'bodyFonts' => FontOptions::body(),
            // Botones reutiliza el mismo catálogo de fuentes sans que el
            // texto general — no se justifica un tercer catálogo separado.
            'buttonFonts' => FontOptions::body(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'background' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'text' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_heading' => ['required', Rule::in(array_keys(FontOptions::headings()))],
            'font_body' => ['required', Rule::in(array_keys(FontOptions::body()))],
            'font_button' => ['required', Rule::in(array_keys(FontOptions::body()))],
        ], [
            'regex' => 'Debe ser un color hexadecimal válido, por ejemplo #386a97.',
        ]);

        $this->settings->set('colors', [
            'primary' => $data['primary'],
            'background' => $data['background'],
            'accent' => $data['accent'],
            'text' => $data['text'],
        ]);

        $this->settings->set('fonts', [
            'heading' => $data['font_heading'],
            'body' => $data['font_body'],
            'button' => $data['font_button'],
        ]);

        return back()->with('status', 'Apariencia actualizada. Ya se ve reflejada en el sitio.');
    }
}
