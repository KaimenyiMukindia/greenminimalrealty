<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'a', 'blockquote'];
    private const DROP_CONTENT_TAGS = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form'];

    public static function sanitize(string $html): string
    {
        if ($html === '') return '';

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML('<?xml encoding="utf-8"?><div id="safe-html-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('safe-html-root');
        if (! $root) return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $output = '';
        foreach ($root->childNodes as $child) $output .= self::render($child);

        return $output;
    }

    private static function render(DOMNode $node): string
    {
        if ($node instanceof DOMText) return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (! $node instanceof DOMElement) return '';

        $tag = strtolower($node->tagName);
        if (in_array($tag, self::DROP_CONTENT_TAGS, true)) return '';

        $children = '';
        foreach ($node->childNodes as $child) $children .= self::render($child);
        if (! in_array($tag, self::ALLOWED_TAGS, true)) return $children;
        if ($tag === 'br') return '<br>';

        $attributes = '';
        if ($tag === 'a' && $node->hasAttribute('href')) {
            $href = trim($node->getAttribute('href'));
            $normalized = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $href));
            $hasUnsafeScheme = preg_match('/^[a-z][a-z0-9+.-]*:/i', $normalized) && ! preg_match('/^(https?:|mailto:|tel:)/i', $normalized);
            if ($href !== '' && ! $hasUnsafeScheme) $attributes = ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return "<{$tag}{$attributes}>{$children}</{$tag}>";
    }
}
