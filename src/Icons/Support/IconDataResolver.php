<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons\Support;

/**
 * Port of Iconify's icon resolution pipeline.
 *
 * Mirrors:
 * - internalGetIconData()      packages/utils/src/icon-set/get-icon.ts:12-32
 * - mergeIconData()            packages/utils/src/icon/merge.ts:13-36
 * - mergeIconTransformations() packages/utils/src/icon/transformations.ts:6-22
 *
 * @phpstan-import-type TIcon from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TAlias from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TIconData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 */
class IconDataResolver
{
    /**
     * Transformation properties and their defaults.
     *
     * Mirrors defaultIconTransformations, packages/utils/src/icon/defaults.ts:31-36.
     *
     * @var array<string, int|bool>
     */
    protected const TRANSFORMATIONS = [
        'rotate' => 0,
        'vFlip' => false,
        'hFlip' => false,
    ];

    /**
     * Every property mergeIconData() copies, in upstream order.
     *
     * Mirrors defaultExtendedIconProps, packages/utils/src/icon/defaults.ts:45-50.
     *
     * @var array<int, string>
     */
    protected const EXTENDED_PROPS = [
        'left',
        'top',
        'width',
        'height',
        'rotate',
        'vFlip',
        'hFlip',
        'body',
        'hidden',
    ];

    /**
     * Resolve an icon name against icon/alias data, then merge icon set root defaults.
     *
     * @param  TIconData  $iconData
     * @param  array<string, mixed>  $setDefaults
     * @return TIcon|null
     */
    public function resolve(array $iconData, string $name, array $setDefaults = []): ?array
    {
        $resolved = $this->resolveChain($iconData, $name, []);

        if ($resolved === null) {
            return null;
        }

        /** @var TIcon $merged */
        $merged = $this->mergeIconData($setDefaults, $resolved);

        return $merged;
    }

    /**
     * Fold an alias chain the way internalGetIconData() does: seed with
     * `mergeIconData(self, {})`, then fold each ancestor in nearest-first, always as the
     * *parent* operand (packages/utils/src/icon-set/get-icon.ts:6-14).
     *
     * The seeding merge against an empty array is not a no-op. It is what makes a string
     * `rotate` of `'1'` into `'1' + 0` = `'10'`, a half turn; nesting the other way round
     * gives `0 + '1'` = `'01'`, a quarter turn.
     *
     * @param  TIconData  $iconData
     * @param  array<int, string>  $visited
     * @return array<string, mixed>|null
     */
    protected function resolveChain(array $iconData, string $name, array $visited): ?array
    {
        /** @var array<int, array<string, mixed>> $chain */
        $chain = [];
        $current = $name;

        while (true) {
            if (in_array($current, $visited, true)) {
                return null;
            }

            $visited[] = $current;

            if (isset($iconData['icons'][$current])) {
                $chain[] = $iconData['icons'][$current];

                break;
            }

            if (! isset($iconData['aliases'][$current])) {
                return null;
            }

            /** @var TAlias $alias */
            $alias = $iconData['aliases'][$current];
            $chain[] = $alias;
            $current = $alias['parent'];
        }

        $resolved = [];

        foreach ($chain as $entry) {
            $resolved = $this->mergeIconData($entry, $resolved);
        }

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $parent
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    public function mergeIconData(array $parent, array $child): array
    {
        $result = $this->mergeTransformations($parent, $child);

        foreach (self::EXTENDED_PROPS as $key) {
            if (array_key_exists($key, self::TRANSFORMATIONS)) {
                if (array_key_exists($key, $parent) && ! array_key_exists($key, $result)) {
                    $result[$key] = self::TRANSFORMATIONS[$key];
                }

                continue;
            }

            if (array_key_exists($key, $child)) {
                $result[$key] = $child[$key];

                continue;
            }

            if (array_key_exists($key, $parent)) {
                $result[$key] = $parent[$key];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $parent
     * @param  array<string, mixed>  $child
     * @return array<string, mixed>
     */
    protected function mergeTransformations(array $parent, array $child): array
    {
        $result = [];

        if ($this->truthy($parent['hFlip'] ?? null) !== $this->truthy($child['hFlip'] ?? null)) {
            $result['hFlip'] = true;
        }

        if ($this->truthy($parent['vFlip'] ?? null) !== $this->truthy($child['vFlip'] ?? null)) {
            $result['vFlip'] = true;
        }

        // A fraction is carried through rather than collapsed here: `rotate: 0.5` on an
        // icon plus `rotate: 0.5` on its alias is a whole 1 and must rotate 90 degrees.
        // Only the SVG builder collapses, at the `switch`. Null means the merged rotation
        // is falsy, so the transformation default applies instead.
        $rotate = IconRotation::mergeIconData($parent['rotate'] ?? null, $child['rotate'] ?? null);

        if ($rotate !== null) {
            $result['rotate'] = $rotate;
        }

        return $result;
    }

    /**
     * JavaScript's `!!value`, which the flip merge above depends on.
     *
     * The float branch is not redundant: the strict comparisons are type-sensitive, so
     * `0.0 !== 0` and a float zero would read as truthy — an icon set written
     * `"hFlip": 0.0` would mirror an icon upstream leaves alone. `NAN` needs the same
     * branch, being falsy in JavaScript but passing every strict comparison here.
     *
     * Loose comparison is not the fix: `'0' != 0` is false in PHP 8 but `'0'` is truthy
     * in JavaScript, which introduces the opposite bug. The parity harness cannot cover
     * this either — `JSON.stringify` emits a float zero as `0`, so only a hand-written
     * icon set can carry one.
     */
    protected function truthy(mixed $value): bool
    {
        if (is_float($value)) {
            return $value !== 0.0 && ! is_nan($value);
        }

        return $value !== null && $value !== false && $value !== 0 && $value !== '';
    }
}
