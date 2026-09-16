<?php

namespace App\Http\Middleware;

use App\Models\Role;
use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea con 403 el acceso a una feature del panel que el super_admin haya
 * desactivado para el rol de quien la pide. Se registra en el grupo general
 * de rutas admin — super_admin nunca pasa por aquí, siempre ve todo. Los
 * permisos viven en roles.permissions (columna JSON), no en
 * site_settings — cualquier rol de staff, incluidos los que el super_admin
 * cree desde /admin/roles, queda cubierto automáticamente sin tocar este
 * middleware.
 */
class EnsureAdminHasFeaturePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $feature = $this->featureForRoute($routeName);

        if ($feature && ! $this->isEnabled($feature, $user->role)) {
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

    private function isEnabled(string $feature, string $roleSlug): bool
    {
        $role = Role::where('slug', $roleSlug)->first();

        // Un rol sin fila (dato inconsistente) o sin permisos guardados aún
        // se comporta como "todo habilitado" — mismo criterio que ya regía
        // antes de que este sistema existiera.
        return $role ? $role->hasFeature($feature) : true;
    }
}
