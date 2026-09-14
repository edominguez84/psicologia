<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve el idioma del contenido de fábrica de la landing (config('site'))
 * según la cookie 'site_locale' que deja el switch de idioma del header
 * (ver routes/web.php, ruta /idioma/{locale}), con el header
 * Accept-Language del navegador como respaldo en la primera visita.
 *
 * Solo aplica al contenido de fábrica (config/site.php vs config/site_en.php)
 * — el contenido editado desde el panel de administración (site_settings)
 * sigue solo en español, fuera del alcance de esta primera versión del
 * switch de idioma.
 */
class ResolveSiteLocale
{
    public const SUPPORTED = ['es', 'en'];
    public const COOKIE_NAME = 'site_locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        if ($locale === 'en') {
            config(['site' => require config_path('site_en.php')]);
        }

        $request->attributes->set('site_locale', $locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $cookieLocale = $request->cookie(self::COOKIE_NAME);
        if (in_array($cookieLocale, self::SUPPORTED, true)) {
            return $cookieLocale;
        }

        // Primera visita sin cookie: se usa el idioma preferido del
        // navegador como mejor esfuerzo, cayendo a español por defecto
        // (el idioma nativo de todo el contenido editable desde el panel).
        $preferred = $request->getPreferredLanguage(self::SUPPORTED);

        return $preferred ?? 'es';
    }
}
