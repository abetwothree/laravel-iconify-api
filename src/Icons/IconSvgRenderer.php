<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;

/**
 * @phpstan-import-type TIcon from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TAlias from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TIconData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 */
class IconSvgRenderer
{
    public function __construct(
        protected IconFinderContract $iconFinder,
        protected IconSetInfoFinderContract $iconSetInfoFinder,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function render(string $name, array $options = []): string
    {
        [$prefix, $iconName] = $this->splitName($name);

        if ($prefix === null || $iconName === null) {
            return '';
        }

        $foundIcons = $this->iconFinder->find($prefix, [$iconName]);

        if (! isset($foundIcons[$iconName])) {
            return '';
        }

        /** @var TIconData $iconData */
        $iconData = $foundIcons[$iconName];

        if (! empty($iconData['not_found'])) {
            return '';
        }

        $resolvedIcon = $this->resolveIcon($iconName, $iconData);

        if ($resolvedIcon === null) {
            return '';
        }

        $iconSetInfo = $this->iconSetInfoFinder->find($prefix);

        return $this->buildSvg($resolvedIcon, $iconSetInfo, $options);
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    protected function splitName(string $name): array
    {
        $parts = explode(':', $name, 2);

        if (count($parts) !== 2 || blank($parts[0]) || blank($parts[1])) {
            return [null, null];
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * @param  TIconData  $iconData
     * @return TIcon|null
     */
    protected function resolveIcon(string $iconName, array $iconData): ?array
    {
        $resolved = $this->resolveIconRecursive($iconName, $iconData, []);

        if ($resolved === null) {
            return null;
        }

        return $resolved;
    }

    /**
     * @param  TIconData  $iconData
     * @param  array<int, string>  $visited
     * @return TIcon|null
     */
    protected function resolveIconRecursive(string $iconName, array $iconData, array $visited): ?array
    {
        if (in_array($iconName, $visited, true)) {
            return null;
        }

        if (isset($iconData['icons'][$iconName])) {
            return $iconData['icons'][$iconName];
        }

        if (! isset($iconData['aliases'][$iconName])) {
            return null;
        }

        $visited[] = $iconName;

        /** @var TAlias $alias */
        $alias = $iconData['aliases'][$iconName];

        $parent = $this->resolveIconRecursive($alias['parent'], $iconData, $visited);

        if ($parent === null) {
            return null;
        }

        return $this->mergeAliasIntoIcon($parent, $alias);
    }

    /**
     * @param  TIcon  $icon
     * @param  TAlias  $alias
     * @return TIcon
     */
    protected function mergeAliasIntoIcon(array $icon, array $alias): array
    {
        foreach (['left', 'top', 'width', 'height'] as $property) {
            if (isset($alias[$property])) {
                $icon[$property] = (int) $alias[$property];
            }
        }

        if (isset($alias['rotate'])) {
            $icon['rotate'] = $this->normalizeRotate($this->safeRotateValue($icon['rotate'] ?? 0), $alias['rotate']);
        }

        if (isset($alias['hFlip'])) {
            $icon['hFlip'] = (bool) ($icon['hFlip'] ?? false) !== (bool) $alias['hFlip'];
        }

        if (isset($alias['vFlip'])) {
            $icon['vFlip'] = (bool) ($icon['vFlip'] ?? false) !== (bool) $alias['vFlip'];
        }

        return $icon;
    }

    /**
     * @param  array<string, mixed>  $iconSetInfo
     * @param  array<string, mixed>  $icon
     * @param  array<string, mixed>  $options
     */
    protected function buildSvg(array $icon, array $iconSetInfo, array $options): string
    {
        $left = $this->safeInt($icon['left'] ?? 0, 0);
        $top = $this->safeInt($icon['top'] ?? 0, 0);
        $width = $this->safeInt($icon['width'] ?? $iconSetInfo['width'] ?? 16, 16);
        $height = $this->safeInt($icon['height'] ?? $iconSetInfo['height'] ?? 16, 16);

        $options = $this->mergeDefaultAttributes($options);

        $attributes = [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'viewBox' => "{$left} {$top} {$width} {$height}",
            'width' => $options['width'] ?? '1em',
            'height' => $options['height'] ?? '1em',
        ];

        unset($options['width'], $options['height']);
        $attributes = array_merge($attributes, $options);

        $attributeString = $this->stringifyAttributes($attributes);

        return '<svg '.$attributeString.'>'.$this->injectTransforms($icon, $left, $top, $width, $height).'</svg>';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function mergeDefaultAttributes(array $options): array
    {
        $defaults = config()->array('iconify-api.inline.defaults', []);

        $defaultClass = trim($this->safeString($defaults['class'] ?? '', ''));
        $optionClass = trim($this->safeString($options['class'] ?? '', ''));

        $classes = trim(implode(' ', array_filter([$defaultClass, $optionClass])));

        if ($classes === '') {
            unset($defaults['class']);
            unset($options['class']);
        } else {
            $options['class'] = $classes;
            unset($defaults['class']);
        }

        /** @var array<string, mixed> $merged */
        $merged = array_merge($defaults, $options);

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function stringifyAttributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $escapedKey = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedValue = htmlspecialchars($this->safeString($value, ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $parts[] = $escapedKey.'="'.$escapedValue.'"';
        }

        return implode(' ', $parts);
    }

    /**
     * @param  array<string, mixed>  $icon
     */
    protected function injectTransforms(array $icon, int $left, int $top, int $width, int $height): string
    {
        $transformations = [];

        if (($icon['hFlip'] ?? false) === true) {
            $transformations[] = 'translate('.($left + $width).' '.(-$top).') scale(-1 1)';
        }

        if (($icon['vFlip'] ?? false) === true) {
            $transformations[] = 'translate('.(-$left).' '.($top + $height).') scale(1 -1)';
        }

        $rotate = $this->normalizeRotate($this->safeRotateValue($icon['rotate'] ?? 0), 0);

        if ($rotate !== 0) {
            $degrees = $rotate * 90;
            $centerX = $left + ($width / 2);
            $centerY = $top + ($height / 2);

            $transformations[] = 'rotate('.$degrees.' '.$centerX.' '.$centerY.')';
        }

        if (count($transformations) === 0) {
            return $this->safeString($icon['body'] ?? '', '');
        }

        return '<g transform="'.implode(' ', $transformations).'">'.$this->safeString($icon['body'] ?? '', '').'</g>';
    }

    protected function normalizeRotate(int|string $parentRotate, int|string $aliasRotate): int
    {
        $rotation = $this->parseRotateValue($parentRotate) + $this->parseRotateValue($aliasRotate);
        $rotation %= 4;

        if ($rotation < 0) {
            $rotation += 4;
        }

        return $rotation;
    }

    protected function parseRotateValue(int|string $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (str_ends_with($value, 'deg') && is_numeric(substr($value, 0, -3))) {
            $degrees = (int) substr($value, 0, -3);

            return (int) floor($degrees / 90);
        }

        return 0;
    }

    protected function safeRotateValue(mixed $value): int|string
    {
        if (is_int($value) || is_string($value)) {
            return $value;
        }

        return 0;
    }

    protected function safeInt(mixed $value, int $default = 0): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    protected function safeString(mixed $value, string $default = ''): string
    {
        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }

        return $default;
    }
}
