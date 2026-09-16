<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteSettingsService;
use App\Support\AdminPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Permite al super_admin elegir, para los roles 'admin' y 'editor', qué
 * secciones del panel puede ver cada uno — sin crear roles nuevos, los 5
 * roles del sistema (super_admin, admin, editor, patient, user) siguen
 * siendo fijos. Guardado en site_settings.admin_role_permissions, aplicado
 * por App\Http\Middleware\EnsureAdminHasFeaturePermission y reflejado en el
 * nav de admin-layout.blade.php.
 */
class RolePermissionsController extends Controller
{
    private const CONFIGURABLE_ROLES = ['admin', 'editor'];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function edit(): View
    {
        $saved = $this->settings->get('admin_role_permissions', []);

        $permissions = collect(self::CONFIGURABLE_ROLES)->mapWithKeys(
            fn ($role) => [$role => array_replace(AdminPermissions::defaultsFor($role), $saved[$role] ?? [])]
        );

        return view('admin.role-permissions.edit', [
            'features' => AdminPermissions::all(),
            'roles' => self::CONFIGURABLE_ROLES,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $featureKeys = array_keys(AdminPermissions::all());

        $permissions = [];
        foreach (self::CONFIGURABLE_ROLES as $role) {
            $submitted = (array) $request->input($role, []);
            $permissions[$role] = collect($featureKeys)
                ->mapWithKeys(fn ($key) => [$key => in_array($key, $submitted, true)])
                ->all();
        }

        $this->settings->set('admin_role_permissions', $permissions);

        return back()->with('status', 'Permisos actualizados.');
    }
}
