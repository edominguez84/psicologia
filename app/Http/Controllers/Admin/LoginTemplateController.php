<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\LoginTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Elección de la plantilla de diseño de /login (ver App\Support\LoginTemplates
 * y AuthenticatedSessionController::create()) — mismo patrón exacto que
 * Admin\LandingTemplateController, para la página de login en vez de la home.
 */
class LoginTemplateController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $active = LoginTemplates::resolve($this->settings->get('login_template')['key'] ?? null);

        $carouselOverride = $this->settings->get('login_carousel');
        $carouselImages = collect($carouselOverride['images'] ?? LoginCarouselController::DEFAULT_IMAGES)
            ->map(fn ($img) => [
                ...$img,
                'url' => str_starts_with($img['path'], 'login-carousel/')
                    ? Storage::url($img['path'])
                    : asset($img['path']),
            ])
            ->values()
            ->all();

        return view('admin.login-template.edit', [
            'templates' => LoginTemplates::all(),
            'active' => $active,
            'carouselImages' => $carouselImages,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'template' => ['required', 'string', 'in:'.implode(',', array_keys(LoginTemplates::all()))],
        ]);

        $this->settings->set('login_template', ['key' => $data['template']]);

        return back()->with('status', 'Plantilla de login actualizada.');
    }
}
