<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons;

/**
 * Deny-list check for known script vectors in an icon body.
 *
 * Meant for bodies from icon sets the app does not fully trust. It only reports; it never
 * modifies a body. It is deliberately conservative and is not an HTML sanitizer: a body
 * it accepts has none of the vectors listed below, not a guarantee that no other exists.
 */
class IconBody
{
    /**
     * A <script> element. Matched anywhere and without a word boundary so that `<script`
     * is rejected however the tag is finished.
     */
    protected const SCRIPT_ELEMENT = '/<script/i';

    /**
     * Elements that embed HTML or another document, which can carry script that none of
     * the other rules see (an iframe `srcdoc`, an `<object data>` URL).
     */
    protected const EMBEDDING_ELEMENT = '/<(?:foreignObject|iframe|embed|object)\b/i';

    /**
     * An `on*` event attribute. The lookbehind is where an HTML tokenizer starts an
     * attribute name: after whitespace, `/`, `"` or `'`. It catches `d="M0"onload=`,
     * where no whitespace separates the attributes, without matching `stroke-dashoffset=`.
     */
    protected const EVENT_ATTRIBUTE = '/(?<=[\s\/"\'])on[a-z]+\s*=/i';

    /**
     * An `href` attribute with any optional namespace prefix (`xlink:href`, `x:href`), at
     * the same boundary. Captures the value in group 1 (double-quoted), 2 (single-quoted)
     * or 3 (unquoted, up to whitespace or `>`), the three forms a browser accepts.
     */
    protected const HREF_ATTRIBUTE = '/(?<=[\s\/"\'])(?:[^\s"\'=<>\/]+:)?href\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]*))/i';

    /**
     * An `attributeName` attribute, same boundary and value forms as the href pattern.
     * SMIL `<set>`/`<animate>` can rewrite an href at runtime, which the static href rule
     * cannot see, so the target of the animation is checked instead.
     */
    protected const ATTRIBUTE_NAME_ATTRIBUTE = '/(?<=[\s\/"\'])attributeName\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]*))/i';

    /**
     * Numeric character references, with or without the trailing semicolon. Browsers
     * decode `&#106avascript:` as `javascript:`, so the semicolon cannot be required.
     */
    protected const DECIMAL_REFERENCE = '/&#(\d+);?/';

    /** Hexadecimal form of the numeric character reference. */
    protected const HEX_REFERENCE = '/&#[xX]([0-9a-fA-F]+);?/';

    /**
     * Whether the body is free of the known script vectors.
     *
     * A body the regex engine cannot process counts as unsafe, never as safe.
     */
    public static function isSafe(string $body): bool
    {
        foreach ([self::SCRIPT_ELEMENT, self::EMBEDDING_ELEMENT, self::EVENT_ATTRIBUTE] as $pattern) {
            if (preg_match($pattern, $body) !== 0) {
                return false;
            }
        }

        $hrefs = static::attributeValues(self::HREF_ATTRIBUTE, $body);

        if ($hrefs === null) {
            return false;
        }

        foreach ($hrefs as $href) {
            $href = static::normalize($href);

            if ($href === null || (! str_starts_with($href, '#') && ! str_starts_with($href, 'data:image/'))) {
                return false;
            }
        }

        $targets = static::attributeValues(self::ATTRIBUTE_NAME_ATTRIBUTE, $body);

        if ($targets === null) {
            return false;
        }

        foreach ($targets as $target) {
            $target = static::normalize($target);

            if ($target === null || $target === 'href' || str_ends_with($target, ':href')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Collect the raw value of every attribute the pattern matches.
     *
     * The pattern captures the double-quoted, single-quoted and unquoted forms in groups
     * 1 to 3. Returns null when the regex engine fails, which the caller treats as unsafe.
     *
     * @return list<string>|null
     */
    protected static function attributeValues(string $pattern, string $body): ?array
    {
        $count = preg_match_all($pattern, $body, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);

        if ($count === false) {
            return null;
        }

        $values = [];

        foreach ($matches as $match) {
            $values[] = $match[1] ?? $match[2] ?? $match[3] ?? '';
        }

        return $values;
    }

    /**
     * Reduce an attribute value to what a browser would read, so it can be compared.
     *
     * Decodes numeric then named references, drops the tab and newlines the URL parser
     * ignores, trims control characters and spaces, and lowercases. Returns null when the
     * regex engine fails, which the caller treats as unsafe.
     */
    protected static function normalize(string $value): ?string
    {
        $decode = static fn (int $codePoint): string => $codePoint === 0 ? "\u{FFFD}" : (mb_chr($codePoint, 'UTF-8') ?: "\u{FFFD}");

        // Capped at 8 digits: anything longer is past U+10FFFF however many digits follow,
        // and it keeps the integer conversion from overflowing.
        $value = preg_replace_callback(
            self::DECIMAL_REFERENCE,
            static fn (array $m): string => $decode(strlen($m[1]) > 8 ? 0 : (int) $m[1]),
            $value,
        );

        if ($value === null) {
            return null;
        }

        $value = preg_replace_callback(
            self::HEX_REFERENCE,
            static fn (array $m): string => $decode(strlen(ltrim($m[1], '0')) > 6 ? 0 : (int) hexdec($m[1])),
            $value,
        );

        if ($value === null) {
            return null;
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return strtolower(trim(str_replace(["\t", "\n", "\r"], '', $value), "\x00..\x20"));
    }
}
