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
     * @param  TIconData  $iconData
     * @param  array<int, string>  $visited
     * @return array<string, mixed>|null
     */
    protected function resolveChain(array $iconData, string $name, array $visited): ?array
    {
        if (in_array($name, $visited, true)) {
            return null;
        }

        if (isset($iconData['icons'][$name])) {
            return $iconData['icons'][$name];
        }

        if (! isset($iconData['aliases'][$name])) {
            return null;
        }

        $visited[] = $name;

        /** @var TAlias $alias */
        $alias = $iconData['aliases'][$name];

        $parent = $this->resolveChain($iconData, $alias['parent'], $visited);

        if ($parent === null) {
            return null;
        }

        return $this->mergeIconData($parent, $alias);
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

        $rotate = ($this->rotateValue($parent['rotate'] ?? 0) + $this->rotateValue($child['rotate'] ?? 0)) % 4;

        if ($rotate !== 0) {
            $result['rotate'] = $rotate;
        }

        return $result;
    }

    protected function truthy(mixed $value): bool
    {
        return $value !== null && $value !== false && $value !== 0 && $value !== '';
    }

    protected function rotateValue(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }
}
