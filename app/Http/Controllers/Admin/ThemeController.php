<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\ColorThemes;
use App\Support\FontOptions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemeController extends Controller
{
    private const DEFAULT_COLORS = [
        'primary' => '#386a97',
        'background' => '#ffffff',
        'accent' => '#b9744c',
        'text' => '#1f2a37',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        $colors = $this->settings->get('colors', self::DEFAULT_COLORS);

        return view('admin.theme.edit', [
            'colors' => $colors,
            'presets' => ColorThemes::all(),
            // Si los colores actuales coinciden exactamente con un preset, se
            // resalta esa tarjeta como "activa". Si la usuaria personalizó a
            // mano, ningún preset queda seleccionado. Se compara por valor
            // (no con ===) porque el orden de las claves de un array JSON no
            // se preserva de forma garantizada al volver de la base de datos.
            'activePreset' => collect(ColorThemes::all())->search(
                fn ($preset) => $this->sameColors($preset['colors'], $colors)
            ),
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

        $this->saveColors([
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

    public function applyPreset(Request $request)
    {
        $data = $request->validate([
            'preset' => ['required', Rule::in(array_keys(ColorThemes::all()))],
        ]);

        $preset = ColorThemes::find($data['preset']);

        $this->saveColors($preset['colors']);

        return back()->with('status', 'Tema "'.$preset['label'].'" aplicado. Puedes seguir personalizando los colores debajo.');
    }

    private function saveColors(array $colors): void
    {
        $this->settings->set('colors', $colors);
    }

    private function sameColors(array $a, array $b): bool
    {
        $keys = ['primary', 'background', 'accent', 'text'];

        foreach ($keys as $key) {
            if (($a[$key] ?? null) !== ($b[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }
}
