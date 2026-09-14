<?php

namespace App\Support;

/**
 * Registro de las secciones visuales de la landing (home.blade.php), para
 * poder mostrarlas/ocultarlas desde el panel sin borrar su contenido. Es un
 * concepto distinto de SiteContentSections (que describe campos de texto
 * editables): aquí solo importa "esta <section> se ve o no se ve".
 *
 * 'hideable' => false marca las secciones que nunca deben poder ocultarse
 * (sin portada no hay landing). El contacto SÍ es ocultable a petición
 * explícita del usuario — al ocultarlo, el visitante sigue teniendo el
 * botón de WhatsApp del header y del hero para escribir.
 */
class LandingSections
{
    public static function all(): array
    {
        return [
            'gallery' => ['label' => 'Galería de imágenes', 'hideable' => true],
            'about' => ['label' => 'Sobre mí', 'hideable' => true],
            'services' => ['label' => 'Cómo te ayudo', 'hideable' => true],
            'benefits' => ['label' => 'Beneficios', 'hideable' => true],
            'emdr' => ['label' => 'Terapia EMDR', 'hideable' => true],
            'testimonials' => ['label' => 'Testimonios', 'hideable' => true],
            'myths' => ['label' => 'Mitos sobre la terapia', 'hideable' => true],
            'checkup' => ['label' => 'Chequeo emocional', 'hideable' => true],
            'faq' => ['label' => 'Preguntas frecuentes', 'hideable' => true],
            'contact_section' => ['label' => 'Formulario de contacto', 'hideable' => true],
        ];
    }

    /**
     * Solo las que el admin puede ocultar (excluye hero y contacto, que ni
     * siquiera se registran aquí porque nunca deben poder desactivarse).
     */
    public static function hideable(): array
    {
        return array_filter(self::all(), fn ($section) => $section['hideable']);
    }
}
