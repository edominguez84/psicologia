<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Modo mantenimiento — un simple check que reemplaza toda la landing
 * pública por resources/views/maintenance.blade.php (ver
 * App\Http\Middleware\EnsureSiteIsNotInMaintenance). El panel /admin sigue
 * accesible normalmente mientras está activo.
 */
class MaintenanceController extends Controller
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $enabled = (bool) ($this->settings->get('maintenance', [])['enabled'] ?? false);

        return view('admin.maintenance.edit', ['enabled' => $enabled]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->settings->set('maintenance', ['enabled' => $request->boolean('enabled')]);

        return back()->with('status', $request->boolean('enabled')
            ? 'Modo mantenimiento activado: el sitio público ya no es visible para los visitantes.'
            : 'Modo mantenimiento desactivado: el sitio ya es visible con normalidad.');
    }
}
