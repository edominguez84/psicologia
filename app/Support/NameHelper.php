<?php

namespace App\Support;

class NameHelper
{
    /**
     * Extrae el primer nombre de pila de un nombre completo, ignorando
     * títulos comunes (Lic., Licda., Dr., Dra., etc.). Se usa para textos
     * cercanos como "Hablar con Erika" sin tener que configurarlo aparte.
     */
    public static function firstName(string $fullName): string
    {
        $titles = ['lic.', 'lic', 'licda.', 'licda', 'dr.', 'dr', 'dra.', 'dra', 'psic.', 'psic'];

        $parts = preg_split('/\s+/', trim($fullName));
        foreach ($parts as $part) {
            $normalized = mb_strtolower(rtrim($part, '.'), 'UTF-8').'.';
            if (! in_array(mb_strtolower($part, 'UTF-8'), $titles, true)
                && ! in_array($normalized, $titles, true)) {
                return $part;
            }
        }

        return $fullName;
    }
}
