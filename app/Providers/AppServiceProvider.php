<?php

namespace App\Providers;

use App\Services\SiteSettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SiteSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->mergeSiteContentOverrides();
    }

    /**
     * Aplica, una vez por request, los overrides de contenido guardados desde
     * el panel de administración sobre el árbol config('site'), para que
     * SiteController, home.blade.php y los partials sigan leyendo
     * config('site.*') sin cambios.
     */
    private function mergeSiteContentOverrides(): void
    {
        try {
            $settings = $this->app->make(SiteSettingsService::class);

            $contentOverrides = $settings->get('content');
            if ($contentOverrides) {
                config(['site' => array_replace_recursive(config('site'), $contentOverrides)]);
            }

            $contactOverrides = $settings->get('contact');
            if ($contactOverrides) {
                config(['site.contact' => array_replace_recursive(config('site.contact'), $contactOverrides)]);
            }
        } catch (\Throwable $e) {
            // Base de datos no disponible todavía (instalación en frío, migración
            // pendiente, comando artisan sin conexión, etc.) — seguimos con los
            // valores por defecto de config/site.php sin romper la aplicación.
        }
    }
}
