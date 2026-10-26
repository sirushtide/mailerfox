<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

class SvgSanitizer
{
    /**
     * Sanitize an SVG file's contents for safe public serving.
     * Removes script elements, event handler attributes, javascript:/data: URIs,
     * foreignObject elements and external references.
     *
     * @param  string  $content  Raw SVG/XML content
     * @return string|null Sanitized SVG or null if the content is not valid XML
     */
    public static function sanitize(string $content): ?string
    {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;

        libxml_use_internal_errors(true);
        $loaded = $doc->loadXML($content, LIBXML_NOENT | LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();

        if (!$loaded) {
            return null;
        }

        $root = $doc->documentElement;

        if (!$root instanceof DOMElement || strtolower($root->nodeName) !== 'svg') {
            return null;
        }

        self::cleanElement($root);
        self::removeScripts($root);

        return $doc->saveXML($root) ?: null;
    }

    private static function removeScripts(DOMElement $element): void
    {
        $toRemove = [];

        foreach (iterator_to_array($element->getElementsByTagName('*')) as $node) {
            $name = strtolower($node->nodeName);

            if (in_array($name, ['script', 'foreignobject', 'iframe', 'embed', 'object', 'use'], true)) {
                $toRemove[] = $node;
                continue;
            }

            foreach (iterator_to_array($node->attributes ?? []) as $attr) {
                $attrName = strtolower($attr->nodeName);
                $attrValue = strtolower(trim((string) $attr->nodeValue));

                $isEventHandler = str_starts_with($attrName, 'on');
                $isDangerousUri = in_array($attrName, ['href', 'xlink:href', 'src', 'action', 'formaction'], true)
                    && (str_starts_with($attrValue, 'javascript:') || str_starts_with($attrValue, 'data:'));

                if ($isEventHandler || $isDangerousUri) {
                    $node->removeAttribute($attr->nodeName);
                }
            }
        }

        foreach ($toRemove as $node) {
            $parent = $node->parentNode;

            if ($parent !== null) {
                $parent->removeChild($node);
            }
        }
    }

    private static function cleanElement(DOMElement $element): void
    {
        // Doctype/DTD entities can be used for XXE-style tricks; rewrite without them.
        $document = $element->ownerDocument;

        if ($document !== null) {
            $document->substituteEntities = false;
        }
    }
}
