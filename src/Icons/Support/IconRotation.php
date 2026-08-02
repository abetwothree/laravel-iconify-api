<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Support;

/**
 * The single home for Iconify's rotation rules.
 *
 * Mirrors:
 * - rotateFromString()    packages/utils/src/customisations/rotate.ts:4-38
 * - the rotation block of iconToSVG(), packages/utils/src/svg/build.ts:52-77
 * - the `% 4` of mergeIconTransformations(), packages/utils/src/icon/transformations.ts:8
 *
 * JavaScript gets these for free from its single number type; PHP does not, so every
 * path that touches a rotation goes through this class rather than casting.
 */
final class IconRotation
{
    /**
     * Coerce a rotation prop into the number JavaScript would carry around.
     *
     * Numbers pass through untouched, fractions included. A fraction has to survive
     * the merge arithmetic: `rotate: 0.5` on an icon plus `rotate: 0.5` on its alias
     * sums to a whole `1` and does rotate 90 degrees, because upstream adds during
     * the merge and only reaches its `switch` at build time. Collapsing is therefore
     * normalise()'s job alone, at the one point where upstream decides.
     *
     * Strings go through rotateFromString(), which is what the framework components do
     * to a `rotate` prop before it ever reaches iconToSVG(). This is the customisation
     * grammar only — icon data uses fromIconData() instead, where upstream applies no
     * such parsing.
     */
    public static function parse(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return 0;
        }

        return self::fromString($value);
    }

    /**
     * Merge two rotations that came from icon data — an icon, an alias or an icon set
     * root — the way mergeIconTransformations() does.
     *
     * Port of `((obj1.rotate || 0) + (obj2.rotate || 0)) % 4` plus the `if (rotate)`
     * that follows it, packages/utils/src/icon/transformations.ts:6-11. Null is
     * returned for a merged rotation JavaScript would treat as falsy, i.e. one the
     * caller must leave out so the transformation default applies.
     *
     * Upstream never parses a rotation that came from icon data: no rotateFromString(),
     * no Number(), it just adds the two raw values. A string operand therefore makes
     * `+` *concatenation*, and only the trailing `% 4` converts. So `'2' + 0` is the
     * string `'20'`, whose `% 4` is 0 — an icon written `"rotate": "2"` is not rotated
     * at all — while `'1' + 0` is `'10'`, whose `% 4` is 2, making `"rotate": "1"` a
     * half turn rather than a quarter one. Only a unit-suffixed string such as
     * `'90deg'` genuinely makes NaN. Reading a numeric string as a number, as an
     * earlier version of this class did, both rotates icons upstream leaves alone and
     * under-rotates the ones it turns.
     *
     * The customisation grammar (parse()/rotateFromString()) deliberately does not
     * apply here — it belongs to the component prop layer, not to icon data.
     */
    public static function mergeIconData(mixed $parent, mixed $child): int|float|null
    {
        $sum = self::add(self::orZero($parent), self::orZero($child));

        if (is_float($sum) && is_nan($sum)) {
            return null;
        }

        $rotate = self::modulo($sum);

        if ($rotate === 0 || $rotate === 0.0) {
            return null;
        }

        return $rotate;
    }

    /**
     * JavaScript's `value || 0`, keeping the operand's type so that add() below knows
     * whether it is adding or concatenating.
     */
    private static function orZero(mixed $value): int|float|string|bool
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            // `NaN || 0` and `±0 || 0` are both the number 0. Folding negative zero in
            // here also keeps `-0` from stringifying as `'-0'`, which JavaScript never
            // does.
            return is_nan($value) || $value === 0.0 ? 0 : $value;
        }

        if (is_string($value)) {
            return $value === '' ? 0 : $value;
        }

        if (is_bool($value)) {
            return $value ? true : 0;
        }

        // Anything else contributes nothing. json_decode() maps both a JSON array and
        // a JSON object onto a PHP array, so the two cannot be told apart well enough
        // to reproduce `String(value)` exactly — and it does not matter: upstream's own
        // quicklyValidateIconSet() rejects an icon whose `rotate` is not a number, so
        // such a set never reaches a renderer at all.
        return 0;
    }

    /**
     * JavaScript's `+`: concatenation when either operand is a string, numeric
     * addition otherwise.
     */
    private static function add(int|float|string|bool $left, int|float|string|bool $right): int|float
    {
        if (is_string($left) || is_string($right)) {
            return self::toNumber(self::toJsString($left).self::toJsString($right));
        }

        return self::toJsNumber($left) + self::toJsNumber($right);
    }

    /**
     * JavaScript's `String(value)` for the operand types a decoded icon set can hold.
     */
    private static function toJsString(int|float|string|bool $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_float($value)) {
            if (is_nan($value)) {
                return 'NaN';
            }

            if (is_infinite($value)) {
                return $value > 0 ? 'Infinity' : '-Infinity';
            }

            // JavaScript prints a whole float without its fraction; PHP's default
            // precision would also round long fractions, so json_encode() (shortest
            // round-trip, like JavaScript) does the rest.
            if ($value === floor($value) && abs($value) < 1.0e15) {
                return (string) (int) $value;
            }

            $encoded = json_encode($value);

            return is_string($encoded) ? $encoded : (string) $value;
        }

        return (string) $value;
    }

    /**
     * JavaScript's `Number(value)` for a non-string operand.
     */
    private static function toJsNumber(int|float|bool $value): int|float
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }

    /**
     * JavaScript's `Number(string)`: whitespace is trimmed, an empty string is 0, and
     * anything else that is not a number is NaN.
     */
    private static function toNumber(string $value): int|float
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return 0;
        }

        if (! is_numeric($trimmed)) {
            return NAN;
        }

        return $trimmed + 0;
    }

    /**
     * Reduce a rotation to the `switch` case iconToSVG() would take, 0 through 3.
     *
     * `iconToSVG()` reduces the rotation with `%= 4` and then feeds it to a `switch`
     * with integer cases, so a non-integral value such as 1.5 stays 1.5, matches no
     * case and rotates nothing. Casting to int, as PHP would, turns that into a 90
     * degree rotation instead. Reducing in float space also keeps a huge value away
     * from an out-of-range `(int)` cast.
     *
     * Returning 0 for a value that matches no case is exact rather than a fallback:
     * case 0 is the one that emits no transform, and `0 % 2 === 1` is false, so the
     * box is left unswapped as well.
     */
    public static function normalise(int|float $value): int
    {
        if (is_float($value) && (! is_finite($value) || fmod($value, 1.0) !== 0.0)) {
            return 0;
        }

        $rotation = self::modulo($value);

        if ($rotation < 0) {
            $rotation += 4;
        }

        return (int) $rotation;
    }

    /**
     * JavaScript's `% 4` on a rotation: the sign of the dividend is kept, and so is
     * any fraction. Integers stay integers so that a merged `rotate` is not silently
     * widened to a float.
     */
    public static function modulo(int|float $value): int|float
    {
        return is_float($value) ? fmod($value, 4.0) : $value % 4;
    }

    /**
     * Port of rotateFromString(), packages/utils/src/customisations/rotate.ts.
     */
    private static function fromString(string $value): int
    {
        $units = preg_replace('/^-?[0-9.]*/', '', $value);

        if ($units === null) {
            return 0;
        }

        if ($units === '') {
            // Whatever is left matches `^-?[0-9.]*$`, where `(int)` truncates exactly
            // the way parseInt() does — including '', '-' and '.', which are NaN there
            // and 0 here.
            return self::normalise((int) $value);
        }

        if ($units === $value) {
            return 0;
        }

        $split = match ($units) {
            '%' => 25,
            'deg' => 90,
            default => 0,
        };

        if ($split === 0) {
            return 0;
        }

        $numericPart = substr($value, 0, strlen($value) - strlen($units));

        if (! is_numeric($numericPart)) {
            return 0;
        }

        return self::normalise((float) $numericPart / $split);
    }
}
