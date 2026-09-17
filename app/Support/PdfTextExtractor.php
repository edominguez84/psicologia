<?php

namespace App\Support;

use Smalot\PdfParser\Parser;

/**
 * Extrae texto plano de un PDF subido como fuente adicional de información
 * para la IA del chatbot (ver Admin\ChatbotChannelsController). 100% PHP
 * puro (smalot/pdfparser, sin binarios de sistema ni proc_open) — necesario
 * porque el hosting de producción no permite instalar dependencias externas
 * como poppler/pdftotext.
 *
 * El texto extraído se guarda ya procesado en site_settings (no el PDF en
 * cada request) y se recorta a un tope de caracteres para no disparar el
 * costo/tamaño del system prompt de cada mensaje — pensado para un
 * documento moderado (tarifario, folleto de servicios), no para manuales
 * extensos.
 */
class PdfTextExtractor
{
    /**
     * ~20 páginas de texto normal. Es un tope defensivo sobre el tamaño del
     * contexto que se manda a la IA, no un límite editorial exacto de
     * páginas — un PDF con poco texto por página puede superar esa cifra de
     * páginas sin problema.
     */
    public const MAX_CHARS = 40000;

    public static function extract(string $absolutePath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($absolutePath);
        $text = trim($pdf->getText());

        if (mb_strlen($text) > self::MAX_CHARS) {
            $text = mb_substr($text, 0, self::MAX_CHARS)."\n\n[Documento recortado por longitud.]";
        }

        return $text;
    }
}
