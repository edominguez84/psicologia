<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Modo mantenimiento propio (no el "php artisan down" nativo de Laravel,
 * que bloquea TODO indiscriminadamente, incluido el panel admin, y no deja
 * mostrar HTML dinámico con los datos de contacto reales sin re-renderizar
 * a mano en cada "down"). Activable con un simple check en
 * /admin/maintenance (delegable, ver App\Support\AdminPermissions),
 * guardado en site_settings.maintenance — mismo patrón que
 * landing_template/section_visibility.
 *
 * Corre en TODA request web (registrado en bootstrap/app.php junto a
 * ResolveSiteLocale) porque routes/web.php no tiene un grupo único que
 * envuelva solo "lo público" — la exclusión de /admin, /login y los
 * webhooks server-to-server vive aquí dentro, no a nivel de registro de
 * middleware.
 */
class EnsureSiteIsNotInMaintenance
{
    /**
     * Rutas que deben seguir funcionando aunque el mantenimiento esté
     * activo: el panel completo (para que el staff pueda desactivarlo),
     * el login (para entrar a ese panel sin sesión previa), y los webhooks
     * de servicios externos (Wompi, Telegram, VAPI, el disparador del
     * scheduler) — bloquearlos rompería integraciones que no tienen nada
     * que ver con la landing pública.
     */
    private const EXCEPT_PATTERNS = [
        'admin',
        'admin/*',
        'login',
        'logout',
        'webhooks/*',
        'cron/run-scheduler',
        'up',
    ];

    public function __construct(private SiteSettingsService $settings)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) ($this->settings->get('maintenance', [])['enabled'] ?? false);

        if (! $enabled || $request->is(self::EXCEPT_PATTERNS)) {
            return $next($request);
        }

        return response()->view('maintenance', [], 503);
    }
}
