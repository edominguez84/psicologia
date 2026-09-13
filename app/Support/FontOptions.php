<?php

namespace App\Support;

/**
 * Catálogo curado de tipografías que el admin puede elegir desde el panel
 * (títulos y texto general por separado). Se ofrece un dropdown cerrado en
 * vez de un campo libre para evitar que se elija una fuente sin buen soporte
 * de tildes/ñ o que rompa la carga de Google Fonts con un valor inesperado.
 *
 * Cada entrada trae:
 * - 'label' : nombre mostrado en el select.
 * - 'family': valor CSS para font-family (con fallback genérico).
 * - 'google': fragmento a usar en la URL de fonts.googleapis.com/css2?family=...
 */
class FontOptions
{
    public static function headings(): array
    {
        return [
            'fraunces' => [
                'label' => 'Fraunces (actual)',
                'family' => "'Fraunces', 'Georgia', ui-serif, serif",
                'google' => 'Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600',
            ],
            'playfair-display' => [
                'label' => 'Playfair Display',
                'family' => "'Playfair Display', 'Georgia', ui-serif, serif",
                'google' => 'Playfair+Display:wght@400;500;600;700',
            ],
            'lora' => [
                'label' => 'Lora',
                'family' => "'Lora', 'Georgia', ui-serif, serif",
                'google' => 'Lora:wght@400;500;600;700',
            ],
            'merriweather' => [
                'label' => 'Merriweather',
                'family' => "'Merriweather', 'Georgia', ui-serif, serif",
                'google' => 'Merriweather:wght@400;700',
            ],
        ];
    }

    public static function body(): array
    {
        return [
            'nunito-sans' => [
                'label' => 'Nunito Sans (actual)',
                'family' => "'Nunito Sans', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Nunito+Sans:wght@400;600;700;800',
            ],
            'inter' => [
                'label' => 'Inter',
                'family' => "'Inter', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Inter:wght@400;600;700;800',
            ],
            'source-sans-3' => [
                'label' => 'Source Sans 3',
                'family' => "'Source Sans 3', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Source+Sans+3:wght@400;600;700;800',
            ],
            'mulish' => [
                'label' => 'Mulish',
                'family' => "'Mulish', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Mulish:wght@400;600;700;800',
            ],
        ];
    }

    public static function findHeading(string $key): ?array
    {
        return self::headings()[$key] ?? null;
    }

    public static function findBody(string $key): ?array
    {
        return self::body()[$key] ?? null;
    }

    public static function defaults(): array
    {
        return ['heading' => 'fraunces', 'body' => 'nunito-sans'];
    }
}
