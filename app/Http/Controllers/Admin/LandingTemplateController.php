<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\LandingTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Elección de la plantilla de diseño de la landing pública (ver
 * App\Support\LandingTemplates y SiteController::home()) — exclusivamente
 * cambia la ESTRUCTURA visual, nunca el contenido: las 3 plantillas
 * consumen los mismos datos (config/site.php, testimonios, promociones,
 * etc.) y respetan section_visibility por igual.
 */
class LandingTemplateController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $active = LandingTemplates::resolve($this->settings->get('landing_template')['key'] ?? null);

        return view('admin.landing-template.edit', [
            'templates' => LandingTemplates::all(),
            'active' => $active,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', 'string', 'in:'.implode(',', array_keys(LandingTemplates::all()))],
        ]);

        $this->settings->set('landing_template', ['key' => $data['template']]);

        return back()->with('status', 'Plantilla de la landing actualizada.');
    }
}
