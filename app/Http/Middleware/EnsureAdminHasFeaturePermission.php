<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea con 403 el acceso a una feature del panel que el super_admin haya
 * desactivado para el rol 'admin' o 'editor'. Se registra en el grupo
 * general de rutas admin (después de 'admin', antes del subgrupo
 * 'super_admin') — super_admin nunca pasa por aquí, siempre ve todo.
 */
class EnsureAdminHasFeaturePermission
{
    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $feature = $this->featureForRoute($routeName);

        if ($feature && ! $this->isEnabled($feature, $user->role->value)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }

    private function featureForRoute(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        foreach (AdminPermissions::all() as $key => $feature) {
            foreach ($feature['routes'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function isEnabled(string $feature, string $role): bool
    {
        $permissions = $this->settings->get('admin_role_permissions', []);
        $rolePermissions = $permissions[$role] ?? AdminPermissions::defaultsFor($role);

        return $rolePermissions[$feature] ?? true;
    }
}
