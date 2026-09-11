<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Overrides de contenido/tema editables desde el panel de administración,
 * guardados como filas JSON en site_settings (una fila por sección: content,
 * colors, contact, logo). config/site.php sigue siendo la fuente de valores
 * por defecto; esta capa solo aplica lo que el admin haya cambiado.
 */
class SiteSettingsService
{
    private const TTL_FOREVER_KEY_PREFIX = 'site_settings.';

    public function get(string $section, array $default = []): array
    {
        try {
            // Solo se cachea lo que hay en BD (o null); el $default se aplica
            // siempre fuera del caché para que cada llamada pueda pedir un
            // valor por defecto distinto sin que quede "pegado" el primero.
            $stored = Cache::rememberForever(
                self::TTL_FOREVER_KEY_PREFIX.$section,
                fn () => SiteSetting::query()->where('key', $section)->value('value'),
            );

            return $stored ?? $default;
        } catch (\Throwable $e) {
            // Tabla aún no migrada (instalación en frío, tests sin
            // RefreshDatabase, etc.) — se sigue con los valores por defecto.
            return $default;
        }
    }

    public function set(string $section, array $value): void
    {
        SiteSetting::query()->updateOrCreate(['key' => $section], ['value' => $value]);
        Cache::forget(self::TTL_FOREVER_KEY_PREFIX.$section);
    }

    public function forget(string $section): void
    {
        SiteSetting::query()->where('key', $section)->delete();
        Cache::forget(self::TTL_FOREVER_KEY_PREFIX.$section);
    }
}
