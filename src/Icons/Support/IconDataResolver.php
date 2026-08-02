<?php

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
     * Fold an alias chain the way internalGetIconData() does.
     *
     * Upstream seeds the accumulator with `mergeIconData(self, {})` and then folds each
     * ancestor in nearest-first, always as the *parent* operand — see
     * packages/utils/src/icon-set/get-icon.ts:6-14 and the `[parent].concat(value)`
     * ordering of getIconsTree(). That first merge against an empty object is not a
     * no-op: it is what turns a string `rotate` of `'1'` into `'1' + 0`, i.e. `'10'`,
     * i.e. a half turn. Nesting the merges the other way round would make it `0 + '1'`,
     * i.e. `'01'`, i.e. a quarter turn.
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

        // The rotations are added and reduced by `% 4` exactly as upstream does, with
        // JavaScript's `+` — which concatenates when either side is a string. A
        // fraction is carried through rather than collapsed here: `rotate: 0.5` on an
        // icon plus `rotate: 0.5` on its alias is a whole 1 and must rotate 90 degrees.
        // Only the SVG builder collapses, at the `switch`. Null means the merged
        // rotation is falsy, so `if (rotate)` skips it and mergeIconData() falls back
        // to the transformation default.
        $rotate = IconRotation::mergeIconData($parent['rotate'] ?? null, $child['rotate'] ?? null);

        if ($rotate !== null) {
            $result['rotate'] = $rotate;
        }

        return $result;
    }

    protected function truthy(mixed $value): bool
    {
        return $value !== null && $value !== false && $value !== 0 && $value !== '';
    }
}
