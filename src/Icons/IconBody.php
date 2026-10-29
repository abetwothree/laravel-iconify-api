<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use Dom\HTMLDocument;
use Throwable;

/**
 * Deny-list check for known script vectors in an icon body.
 *
 * Meant for bodies from icon sets the app does not fully trust. It only reports; it never
 * modifies a body. It is deliberately conservative and is not an HTML sanitizer: a body
 * it accepts has none of the vectors listed below, not a guarantee that no other exists.
 *
 * The body is parsed with the HTML5 parser so markup and character references are read
 * exactly as a browser reads them. Patterns read them differently: a quoted decoy value
 * can swallow a real attribute, and `&#0000000104;` is `h`. Every element in the document
 * is checked, not only those inside the `<svg>`, because a body can close the `<svg>`
 * and land HTML elements after it.
 */
class IconBody
{
    /**
     * Elements that run script or embed another document. `noscript` is included because
     * this parser runs with scripting disabled and browsers with it enabled, so they
     * disagree on whether its content is markup or text. Content that is text here can be
     * a live element in a browser. `template` is included because its children sit in a
     * separate content fragment this walk never visits, and a declarative shadow root is
     * live in the page. `meta` is included because it can steer encoding sniffing.
     */
    protected const FORBIDDEN_ELEMENTS = ['script', 'foreignobject', 'iframe', 'embed', 'object', 'noscript', 'meta', 'template'];

    /**
     * Whether the body is free of the known script vectors.
     *
     * Unsafe when it has a forbidden element, an `on*` event attribute, an `href` that is
     * not `#id` or `data:image/`, or SMIL whose `attributeName` targets an `href`. A body
     * the parser cannot process counts as unsafe, never as safe.
     */
    public static function isSafe(string $body): bool
    {
        try {
            // The encoding is fixed because createFromString() otherwise sniffs it, and a
            // `<meta charset>` in the body would re-decode everything after it. A browser
            // gets this markup as UTF-8 or as a JS string, so it never sniffs.
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
     * Whether a normalized `attributeName` value names an href, with or without a prefix.
     *
     * SMIL `<set>` and `<animate>` can rewrite an href at runtime, which the static href
     * rule cannot see, so the target of the animation is checked instead.
     */
    protected static function targetsHref(string $value): bool
    {
        return $value === 'href' || str_ends_with($value, ':href');
    }

    /**
     * Reduce an attribute value to what a browser's URL parser would read.
     *
     * The HTML parser has already decoded character references. What remains is the tab
     * and newlines the URL parser drops anywhere, the control characters and spaces it
     * trims from both ends, and case.
     */
    protected static function normalize(string $value): string
    {
        return strtolower(trim(str_replace(["\t", "\n", "\r"], '', $value), "\x00..\x20"));
    }
}
