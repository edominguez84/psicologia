<?php

namespace App\Support;

class ColorHelper
{
    /**
     * Oscurece un color hexadecimal (#rrggbb) un porcentaje dado (0-1).
     * Se usa para derivar el tono "hover" del color primario elegido por el
     * admin sin pedirle un segundo valor.
     */
    public static function darken(string $hex, float $amount = 0.15): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '#'.$hex;
        }

        [$r, $g, $b] = [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];

        $r = max(0, (int) round($r * (1 - $amount)));
        $g = max(0, (int) round($g * (1 - $amount)));
        $b = max(0, (int) round($b * (1 - $amount)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
