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
     * Coerce a `rotate` *prop* into the number JavaScript would carry around.
     *
     * Numbers pass through untouched, fractions included — a fraction has to survive the
     * merge arithmetic, so collapsing is normalise()'s job alone. Strings go through
     * rotateFromString(), the grammar the framework components apply before iconToSVG().
     *
     * Props only. Icon data goes through mergeIconData(), where upstream parses nothing.
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
     * Merge two rotations from icon data — an icon, an alias or an icon set root.
     *
     * Port of `((obj1.rotate || 0) + (obj2.rotate || 0)) % 4` and the `if (rotate)` after
     * it, packages/utils/src/icon/transformations.ts:6-11. Null means the result is falsy
     * in JavaScript, so the caller must omit it and let the transformation default apply.
     *
     * Upstream parses nothing here — it adds the raw values, so a string operand makes
     * `+` *concatenation* and only the trailing `% 4` converts. `'2' + 0` is `'20'`,
     * whose `% 4` is 0, so `"rotate": "2"` does not rotate at all; `'1' + 0` is `'10'`,
     * whose `% 4` is 2, so `"rotate": "1"` is a half turn. Reading a numeric string as a
     * number instead both rotates icons upstream leaves alone and under-rotates the rest.
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
            // `NaN || 0` and `±0 || 0` are both 0. Folding negative zero here also keeps
            // it from stringifying as `'-0'`, which JavaScript never does.
            return is_nan($value) || $value === 0.0 ? 0 : $value;
        }

        if (is_string($value)) {
            return $value === '' ? 0 : $value;
        }

        if (is_bool($value)) {
            return $value ? true : 0;
        }

        // Anything else contributes nothing. json_decode() maps a JSON array and a JSON
        // object onto the same PHP type, so `String(value)` cannot be reproduced exactly
        // — and need not be: upstream's quicklyValidateIconSet() rejects a non-numeric
        // `rotate` outright, so such a set never reaches a renderer there either.
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

            // JavaScript prints a whole float without its fraction. json_encode() handles
            // the rest, being shortest-round-trip like JavaScript where PHP's default
            // precision would round.
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
     *
     * Deliberate deviation: `is_numeric()` is narrower than `Number()`, which also reads
     * the `0x`/`0o`/`0b` literals and `Infinity` — `Number('0x10')` is 16 where this is
     * NaN. Only a hand-authored set pairing a radix-prefixed `rotate` string with a
     * second rotation in the same chain can reach it, and upstream's
     * quicklyValidateIconSet() rejects such a set anyway. Recorded as deviation 19 in
     * docs/iconify-renderer-parity-audit.md.
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
     * That `switch` has integer cases, so a non-integral value like 1.5 matches none and
     * rotates nothing; casting to int would turn it into a 90 degree rotation instead.
     * Returning 0 there is exact rather than a fallback — case 0 emits no transform and
     * leaves the box unswapped, which is what no-match produces.
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
