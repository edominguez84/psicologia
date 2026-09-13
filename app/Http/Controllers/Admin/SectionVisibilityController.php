<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\LandingSections;
use Illuminate\Http\Request;

class SectionVisibilityController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit()
    {
        $saved = $this->settings->get('section_visibility', []);
        $sections = LandingSections::hideable();

        // Ausencia de key = visible (comportamiento de fábrica, antes de que
        // el admin haya tocado nada en este panel).
        $visibility = [];
        foreach ($sections as $key => $section) {
            $visibility[$key] = $saved[$key] ?? true;
        }

        return view('admin.section-visibility.edit', [
            'sections' => $sections,
            'visibility' => $visibility,
        ]);
    }

    public function update(Request $request)
    {
        $hideableKeys = array_keys(LandingSections::hideable());

        $data = $request->validate([
            'visible' => ['nullable', 'array'],
            'visible.*' => ['string', 'in:'.implode(',', $hideableKeys)],
        ]);

        // Los checkboxes marcados llegan en 'visible'; los desmarcados
        // simplemente no vienen en el request. Se reconstruye el mapa
        // completo para las secciones ocultables conocidas.
        $checked = $data['visible'] ?? [];
        $visibility = [];
        foreach ($hideableKeys as $key) {
            $visibility[$key] = in_array($key, $checked, true);
        }

        $this->settings->set('section_visibility', $visibility);

        return back()->with('status', 'Visibilidad de secciones actualizada.');
    }
}
