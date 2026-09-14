<?php

namespace App\Support;

/**
 * Catálogo curado de temas de color predefinidos que el admin puede aplicar
 * con un clic desde el panel de Apariencia, sin tener que elegir cada color
 * a mano. Cada tema define los mismos 4 valores que ya acepta
 * ThemeController::update() ('primary', 'background', 'accent', 'text'), así
 * que aplicar un preset reutiliza exactamente el mismo mecanismo de guardado
 * que la personalización manual — un tema es solo un punto de partida, nunca
 * un modo exclusivo: los colores siguen siendo editables después.
 *
 * Los 4 colores de cada tema se eligieron con contraste suficiente para
 * texto legible (el color 'text' sobre 'background', y blanco sobre
 * 'primary' para los botones), replicando el mismo criterio de la paleta
 * "Océano" original del sitio.
 */
class ColorThemes
{
    public static function all(): array
    {
        return [
            'ocean' => [
                'label' => 'Océano (predeterminado)',
                'colors' => [
                    'primary' => '#386a97',
                    'background' => '#ffffff',
                    'accent' => '#b9744c',
                    'text' => '#1f2a37',
                ],
            ],
            'mint' => [
                'label' => 'Menta',
                'colors' => [
                    'primary' => '#2f8f7c',
                    'background' => '#ffffff',
                    'accent' => '#e0684f',
                    'text' => '#1f2e2a',
                ],
            ],
            'lavender' => [
                'label' => 'Lavanda',
                'colors' => [
                    'primary' => '#6d5b9e',
                    'background' => '#ffffff',
                    'accent' => '#c99a3e',
                    'text' => '#2a2438',
                ],
            ],
            'terracotta' => [
                'label' => 'Terracota',
                'colors' => [
                    'primary' => '#b5603c',
                    'background' => '#fffaf5',
                    'accent' => '#5f8f6a',
                    'text' => '#3a2a20',
                ],
            ],
            'forest' => [
                'label' => 'Bosque',
                'colors' => [
                    'primary' => '#2f6b4f',
                    'background' => '#ffffff',
                    'accent' => '#c99a3e',
                    'text' => '#1f2e24',
                ],
            ],
            'sunset' => [
                'label' => 'Atardecer',
                'colors' => [
                    'primary' => '#c15d6c',
                    'background' => '#fff8f6',
                    'accent' => '#2f6f80',
                    'text' => '#332426',
                ],
            ],
            'slate' => [
                'label' => 'Pizarra',
                'colors' => [
                    'primary' => '#48607a',
                    'background' => '#f7f8fa',
                    'accent' => '#a8623d',
                    'text' => '#242c34',
                ],
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
