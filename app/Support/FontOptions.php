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
            'cormorant-garamond' => [
                'label' => 'Cormorant Garamond',
                'family' => "'Cormorant Garamond', 'Georgia', ui-serif, serif",
                'google' => 'Cormorant+Garamond:wght@400;500;600;700',
            ],
            'libre-baskerville' => [
                'label' => 'Libre Baskerville',
                'family' => "'Libre Baskerville', 'Georgia', ui-serif, serif",
                'google' => 'Libre+Baskerville:wght@400;700',
            ],
            'eb-garamond' => [
                'label' => 'EB Garamond',
                'family' => "'EB Garamond', 'Georgia', ui-serif, serif",
                'google' => 'EB+Garamond:wght@400;500;600;700',
            ],
            'crimson-pro' => [
                'label' => 'Crimson Pro',
                'family' => "'Crimson Pro', 'Georgia', ui-serif, serif",
                'google' => 'Crimson+Pro:wght@400;500;600;700',
            ],
            'dm-serif-display' => [
                'label' => 'DM Serif Display',
                'family' => "'DM Serif Display', 'Georgia', ui-serif, serif",
                'google' => 'DM+Serif+Display:wght@400',
            ],
            'spectral' => [
                'label' => 'Spectral',
                'family' => "'Spectral', 'Georgia', ui-serif, serif",
                'google' => 'Spectral:wght@400;500;600;700',
            ],
            'poppins' => [
                'label' => 'Poppins (sans)',
                'family' => "'Poppins', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Poppins:wght@400;500;600;700',
            ],
            'montserrat' => [
                'label' => 'Montserrat (sans)',
                'family' => "'Montserrat', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Montserrat:wght@400;500;600;700',
            ],
            'raleway' => [
                'label' => 'Raleway (sans)',
                'family' => "'Raleway', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Raleway:wght@400;500;600;700',
            ],
            'quicksand' => [
                'label' => 'Quicksand (sans, redondeada)',
                'family' => "'Quicksand', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Quicksand:wght@400;500;600;700',
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
            'open-sans' => [
                'label' => 'Open Sans',
                'family' => "'Open Sans', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Open+Sans:wght@400;600;700;800',
            ],
            'lato' => [
                'label' => 'Lato',
                'family' => "'Lato', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Lato:wght@400;700',
            ],
            'work-sans' => [
                'label' => 'Work Sans',
                'family' => "'Work Sans', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Work+Sans:wght@400;500;600;700',
            ],
            'karla' => [
                'label' => 'Karla',
                'family' => "'Karla', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Karla:wght@400;500;600;700',
            ],
            'jost' => [
                'label' => 'Jost',
                'family' => "'Jost', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Jost:wght@400;500;600;700',
            ],
            'figtree' => [
                'label' => 'Figtree',
                'family' => "'Figtree', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Figtree:wght@400;500;600;700',
            ],
            'plus-jakarta-sans' => [
                'label' => 'Plus Jakarta Sans',
                'family' => "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Plus+Jakarta+Sans:wght@400;500;600;700',
            ],
            'manrope' => [
                'label' => 'Manrope',
                'family' => "'Manrope', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Manrope:wght@400;500;600;700',
            ],
            'nunito' => [
                'label' => 'Nunito (redondeada)',
                'family' => "'Nunito', ui-sans-serif, system-ui, sans-serif",
                'google' => 'Nunito:wght@400;600;700;800',
            ],
            'lora-body' => [
                'label' => 'Lora (con serifa)',
                'family' => "'Lora', 'Georgia', ui-serif, serif",
                'google' => 'Lora:wght@400;500;600;700',
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
        return ['heading' => 'fraunces', 'body' => 'nunito-sans', 'button' => 'nunito-sans'];
    }
}
