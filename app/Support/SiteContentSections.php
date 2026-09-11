<?php

namespace App\Support;

/**
 * Describe, sección por sección, los campos editables de config/site.php para
 * poder generar un formulario admin genérico (y validar/guardar su envío)
 * sin tener que escribir una vista a mano por cada sección.
 *
 * Tipos de campo soportados:
 * - 'text'      : input de una línea
 * - 'textarea'  : texto largo
 * - 'list'      : lista de strings simples (una por línea en el textarea)
 * - 'repeater'  : lista de objetos, cada uno con sus propios 'fields' (text/textarea)
 */
class SiteContentSections
{
    public static function all(): array
    {
        return [
            'hero' => [
                'label' => 'Portada (hero)',
                'fields' => [
                    'kicker' => ['type' => 'text', 'label' => 'Texto pequeño superior'],
                    'title' => ['type' => 'text', 'label' => 'Título principal'],
                    'subtitle' => ['type' => 'textarea', 'label' => 'Subtítulo'],
                    'cta_primary' => ['type' => 'text', 'label' => 'Botón principal'],
                    'cta_secondary' => ['type' => 'text', 'label' => 'Botón secundario'],
                    'points' => [
                        'type' => 'repeater',
                        'label' => 'Propuestas de valor',
                        'fields' => [
                            'title' => ['type' => 'text', 'label' => 'Título'],
                            'text' => ['type' => 'textarea', 'label' => 'Texto'],
                        ],
                    ],
                ],
            ],
            'about' => [
                'label' => 'Sobre mí',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'lead' => ['type' => 'textarea', 'label' => 'Frase destacada'],
                    'paragraphs' => ['type' => 'list', 'label' => 'Párrafos (uno por línea)'],
                    'credentials' => ['type' => 'list', 'label' => 'Credenciales (una por línea)'],
                ],
            ],
            'services' => [
                'label' => 'Cómo te ayudo',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'subtitle' => ['type' => 'textarea', 'label' => 'Subtítulo'],
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'Servicios',
                        'fields' => [
                            'title' => ['type' => 'text', 'label' => 'Título'],
                            'text' => ['type' => 'textarea', 'label' => 'Descripción'],
                        ],
                    ],
                ],
            ],
            'benefits' => [
                'label' => 'Beneficios',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'subtitle' => ['type' => 'textarea', 'label' => 'Subtítulo'],
                    'items' => ['type' => 'list', 'label' => 'Beneficios (uno por línea)'],
                ],
            ],
            'emdr' => [
                'label' => 'Terapia EMDR',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'lead' => ['type' => 'textarea', 'label' => 'Explicación'],
                    'advantages' => [
                        'type' => 'repeater',
                        'label' => 'Ventajas',
                        'fields' => [
                            'title' => ['type' => 'text', 'label' => 'Título'],
                            'text' => ['type' => 'textarea', 'label' => 'Texto'],
                        ],
                    ],
                ],
            ],
            'testimonials' => [
                'label' => 'Testimonios',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'note' => ['type' => 'textarea', 'label' => 'Nota aclaratoria'],
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'Testimonios',
                        'fields' => [
                            'name' => ['type' => 'text', 'label' => 'Nombre'],
                            'place' => ['type' => 'text', 'label' => 'Lugar'],
                            'text' => ['type' => 'textarea', 'label' => 'Testimonio'],
                        ],
                    ],
                ],
            ],
            'myths' => [
                'label' => 'Mitos sobre la terapia',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'subtitle' => ['type' => 'textarea', 'label' => 'Subtítulo'],
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'Mitos',
                        'fields' => [
                            'myth' => ['type' => 'textarea', 'label' => 'Mito'],
                            'truth' => ['type' => 'textarea', 'label' => 'Realidad'],
                        ],
                    ],
                ],
            ],
            'faq' => [
                'label' => 'Preguntas frecuentes',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'items' => [
                        'type' => 'repeater',
                        'label' => 'Preguntas',
                        'fields' => [
                            'q' => ['type' => 'text', 'label' => 'Pregunta'],
                            'a' => ['type' => 'textarea', 'label' => 'Respuesta'],
                        ],
                    ],
                ],
            ],
            'contact_section' => [
                'label' => 'Sección de contacto',
                'fields' => [
                    'title' => ['type' => 'text', 'label' => 'Título'],
                    'subtitle' => ['type' => 'textarea', 'label' => 'Subtítulo'],
                    'subjects' => ['type' => 'list', 'label' => 'Asuntos del formulario (uno por línea)'],
                ],
            ],
            'footer' => [
                'label' => 'Pie de página',
                'fields' => [
                    'disclaimer' => ['type' => 'textarea', 'label' => 'Aviso legal'],
                    'privacy_note' => ['type' => 'textarea', 'label' => 'Nota de privacidad'],
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
