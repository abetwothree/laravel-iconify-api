<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use Dom\HTMLDocument;
use Throwable;

/**
 * Checks an icon body for known ways to run script. It reports and never sanitizes.
 *
 * It parses with the HTML5 parser and walks the whole document, since a body can close its `<svg>`.
 */
class IconBody
{
    /**
     * Elements that run script or embed a document. `noscript` parses differently with scripting off,
     * `template` content escapes the element walk, and `meta` can change the parse encoding.
     */
    protected const FORBIDDEN_ELEMENTS = ['script', 'foreignobject', 'iframe', 'embed', 'object', 'noscript', 'meta', 'template'];

    /**
     * Checks whether a body can be inlined without running script.
     *
     * A body the parser cannot process counts as unsafe.
     */
    public static function isSafe(string $body): bool
    {
        try {
            // Pinned to UTF-8, or a `<meta charset>` in the body would re-decode everything after it.
            $document = HTMLDocument::createFromString('<!DOCTYPE html><body><svg>'.$body.'</svg>', LIBXML_NOERROR, 'UTF-8');
        } catch (Throwable) {
            return false;
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            if (in_array(strtolower($element->localName), self::FORBIDDEN_ELEMENTS, true)) {
                return false;
            }

            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);
                $isHref = $name === 'href' || str_ends_with($name, ':href');

                if (strlen($name) > 2 && str_starts_with($name, 'on')) {
                    return false;
                }

                if ($isHref && ! static::isSafeReference(static::normalize($attribute->value))) {
                    return false;
                }

                if ($name === 'attributename' && static::targetsHref(static::normalize($attribute->value))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether a normalized href value points inside the document or at an inline image.
     */
    protected static function isSafeReference(string $value): bool
    {
        return str_starts_with($value, '#') || str_starts_with($value, 'data:image/');
    }

    /**
     * Whether a normalized SMIL `attributeName` targets an href, which could rewrite a link at runtime.
     */
    protected static function targetsHref(string $value): bool
    {
        return $value === 'href' || str_ends_with($value, ':href');
    }

    /**
     * Reduces an attribute value to what a browser's URL parser reads: no tabs or newlines, trimmed, lowercase.
     */
    protected static function normalize(string $value): string
    {
        return strtolower(trim(str_replace(["\t", "\n", "\r"], '', $value), "\x00..\x20"));
    }
}
