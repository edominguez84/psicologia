<?php

namespace App\Support;

/**
 * Catálogo de plantillas de diseño de la landing pública — cada una es una
 * vista Blade completa en resources/views/landing/{key}.blade.php con su
 * propia estructura/orden de secciones, pero consumiendo exactamente los
 * mismos datos (ver SiteController::home()) y las mismas 11 keys de
 * App\Support\LandingSections para mostrar/ocultar secciones — cambiar de
 * plantilla nunca duplica ni pierde contenido ya cargado por el admin.
 *
 * Elegida desde /admin/landing-template (Admin\LandingTemplateController),
 * guardada como site_settings.landing_template = ['key' => '<template>'] —
 * envuelta en un array porque SiteSettingsService::get()/set() solo
 * aceptan array (mismo motivo por el que 'colors'/'fonts' guardan varios
 * campos en vez de un valor suelto).
 */
class LandingTemplates
{
    public const DEFAULT = 'classic';

    /**
     * 'preview' apunta a una captura de pantalla real de cada plantilla
     * (public/images/templates/*.png, generadas con Playwright contra el
     * entorno local) — si alguna plantilla cambia visualmente en el
     * futuro, basta con regenerar esa captura sin tocar código.
     */
    public static function all(): array
    {
        return [
            'classic' => [
                'label' => 'Clásica',
                'description' => 'La plantilla original del sitio: hero con foto lateral y secciones en columna, una debajo de otra.',
                'preview' => 'images/templates/classic.png',
            ],
            'minimal' => [
                'label' => 'Minimalista',
                'description' => 'Hero centrado sin foto, navegación por pasos y una sección final combinada de preguntas y contacto — más ligera y directa.',
                'preview' => 'images/templates/minimal.png',
            ],
            'cards' => [
                'label' => 'Tarjetas',
                'description' => 'Hero con imagen de fondo a pantalla completa y todo el contenido presentado como tarjetas grandes con sombra — más visual.',
                'preview' => 'images/templates/cards.png',
            ],
        ];
    }

    /**
     * La key guardada siempre se valida contra este catálogo antes de
     * usarse — si el valor en site_settings quedara corrupto (o ni
     * siquiera fuera un string — SiteSettingsService::get() devuelve [] si
     * la sección nunca se guardó, según el $default que se le pase) o
     * apuntara a una plantilla eliminada, la home cae a la plantilla por
     * defecto en vez de fallar. Acepta mixed a propósito: es la última
     * línea de defensa contra un valor con forma inesperada.
     */
    public static function resolve(mixed $key): string
    {
        return is_string($key) && array_key_exists($key, self::all()) ? $key : self::DEFAULT;
    }
}
