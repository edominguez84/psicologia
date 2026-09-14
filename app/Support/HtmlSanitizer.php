<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Sanitiza el HTML que produce el editor de texto enriquecido (Trix) de las
 * páginas legales antes de guardarlo, y otra vez antes de imprimirlo con
 * {!! !!} (defensa en profundidad barata, en caso de que algún dato antiguo
 * en base de datos no haya pasado por el filtro de guardado).
 *
 * Solo se permite una allowlist fija de tags y atributos — cualquier otra
 * cosa (scripts, estilos, iframes, atributos on*, hrefs "javascript:") se
 * elimina, quedándose solo con el texto/hijos permitidos dentro.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u',
        'ul', 'ol', 'li', 'h2', 'h3', 'h4', 'a', 'blockquote',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel'],
    ];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument();
        // Se envuelve en un contenedor propio para poder extraer solo su
        // contenido después; LIBXML_NOERROR evita que HTML5 "moderno" (que
        // DOMDocument no entiende del todo) llene el log de warnings.
        @$document->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root__">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        $root = $document->getElementById('__root__');
        if (! $root) {
            return '';
        }

        self::sanitizeNode($document, $root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeNode(DOMDocument $document, DOMNode $node): void
    {
        // Se copia la lista de hijos porque se va a mutar el árbol mientras
        // se recorre (reemplazar nodos no permitidos por sus propios hijos).
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    // Tag no permitido: se descarta el tag pero se conserva
                    // su contenido (p. ej. un <div> se aplana a su texto),
                    // salvo que sea un tag inherentemente peligroso, cuyo
                    // contenido tampoco interesa.
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                        $node->removeChild($child);
                        continue;
                    }

                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }

                self::sanitizeAttributes($child, $tag);
                self::sanitizeNode($document, $child);
            }
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);
                continue;
            }

            if ($name === 'href' && ! self::isSafeUrl($attribute->value)) {
                $element->removeAttribute('href');
            }
        }

        // Cualquier enlace permitido que abra en nueva pestaña debe llevar
        // rel="noopener" para no exponer window.opener al sitio de destino.
        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return true;
        }

        return (bool) preg_match('/^(https?|mailto):/i', $url);
    }
}
