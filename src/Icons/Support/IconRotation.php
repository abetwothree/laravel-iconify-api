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
     * Coerce a rotation that came from icon data — an icon, an alias or an icon set
     * root — into the number mergeIconTransformations() would add.
     *
     * Deliberately not parse(): upstream never runs rotateFromString() on icon data, it
     * just adds the raw value. A `'90deg'` there is `'90deg' + 0`, the string
     * `'90deg0'`, whose `% 4` is NaN, whose `if (rotate)` is false — no rotation. See
     * packages/utils/src/icon/transformations.ts:8 and icon-set/get-icon.ts:12-20.
     * Reading it as a quarter turn instead would rotate icons upstream leaves alone.
     *
     * A numeric string is truncated rather than dropped. Upstream would make NaN of
     * that too, but this package's icon types have always allowed `rotate` to be a
     * string and have always read a numeric one, so it is left as it was.
     */
    public static function fromIconData(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return 0;
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
