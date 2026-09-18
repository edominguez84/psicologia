<?php

namespace App\Support;

/**
 * Catálogo de plantillas de diseño de la página de login — mismo espíritu
 * que App\Support\LandingTemplates, pero para /login en vez de la home.
 * 'classic' es el diseño original (auth/login.blade.php con
 * <x-guest-layout>, sin cambios) y no requiere vista propia; el resto son
 * vistas completas en resources/views/auth/login-{key}.blade.php.
 *
 * Ninguna plantilla toca la lógica de seguridad: mismo formulario
 * (name="email"/"password"/"remember", action route('login'), @csrf),
 * mismos componentes de Breeze (x-input-label, x-text-input, etc.) — solo
 * cambia la disposición visual (ver auth/login.blade.php, que decide cuál
 * incluir).
 *
 * Elegida desde /admin/login-template (Admin\LoginTemplateController),
 * guardada como site_settings.login_template = ['key' => '<template>']
 * (envuelto en array por el mismo motivo que landing_template).
 */
class LoginTemplates
{
    public const DEFAULT = 'classic';

    public static function all(): array
    {
        return [
            'classic' => [
                'label' => 'Clásica',
                'description' => 'El diseño original: tarjeta centrada con el formulario, sin imágenes.',
                'preview' => 'images/templates/login-classic.png',
            ],
            'carousel' => [
                'label' => 'Carrusel',
                'description' => 'Carrusel de imágenes a la izquierda y el formulario a la derecha — un look más moderno, a pantalla completa.',
                'preview' => 'images/templates/login-carousel.png',
            ],
        ];
    }

    public static function resolve(mixed $key): string
    {
        return is_string($key) && array_key_exists($key, self::all()) ? $key : self::DEFAULT;
    }
}
