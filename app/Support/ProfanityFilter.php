<?php

namespace App\Support;

use App\Services\SiteSettingsService;

/**
 * Filtro de palabras soeces/insultos para los testimonios de pacientes. La
 * lista base cubre las groserías más comunes en español (moderada, no
 * exhaustiva); el super_admin puede añadir palabras extra desde el panel
 * sin tocar código (site_settings.profanity_words).
 *
 * La comparación es por palabra completa (no substring), insensible a
 * mayúsculas/acentos, para evitar falsos positivos con palabras que
 * simplemente contienen la cadena prohibida como parte de otra palabra.
 */
class ProfanityFilter
{
    private const BASE_WORDS = [
        'mierda', 'puta', 'puto', 'putas', 'putos', 'cabron', 'cabrona', 'cabrones',
        'pendejo', 'pendeja', 'pendejos', 'idiota', 'imbecil', 'estupido', 'estupida',
        'gilipollas', 'joder', 'maricon', 'marica', 'zorra', 'perra', 'verga',
        'chinga', 'chingar', 'chingada', 'chingado', 'culero', 'culera', 'malparido',
        'malparida', 'hijueputa', 'hp', 'coño', 'carajo', 'mamahuevo', 'huevon',
        'huevona', 'baboso', 'babosa', 'sonso', 'menso', 'mensa',
    ];

    public static function words(): array
    {
        $extra = app(SiteSettingsService::class)->get('profanity_filter', [])['extra_words'] ?? [];

        return array_values(array_unique(array_merge(self::BASE_WORDS, array_map('mb_strtolower', $extra))));
    }

    public static function banThreshold(): int
    {
        return (int) (app(SiteSettingsService::class)->get('profanity_filter', [])['ban_threshold'] ?? 5);
    }

    public static function containsProhibitedWord(string $text): bool
    {
        $normalized = self::normalize($text);

        foreach (self::words() as $word) {
            if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote(self::normalize($word), '/').'(?![\p{L}\p{N}])/u', $normalized)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Minúsculas + sin acentos, para que "café" y "cafe" (o "PUTA"/"Puta")
     * coincidan igual contra la lista.
     */
    private static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $withoutAccents !== false ? $withoutAccents : $text;
    }
}
