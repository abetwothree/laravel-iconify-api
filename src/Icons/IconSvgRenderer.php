<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetInfoFinder as IconSetInfoFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;

/**
 * @phpstan-import-type TIcon from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TAlias from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TIconData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 */
class IconSvgRenderer
{
    protected IconifySvgBuilder $svgBuilder;

    protected SvgIdReplacer $svgIdReplacer;

    public function __construct(
        protected IconFinderContract $iconFinder,
        protected IconSetInfoFinderContract $iconSetInfoFinder,
        ?IconifySvgBuilder $svgBuilder = null,
        ?SvgIdReplacer $svgIdReplacer = null,
    ) {
        $this->svgBuilder = $svgBuilder ?? new IconifySvgBuilder;
        $this->svgIdReplacer = $svgIdReplacer ?? new SvgIdReplacer;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function render(string $name, array $options = []): string
    {
        $parsedName = $this->parseIconName($name);

        if ($parsedName === null) {
            return '';
        }

        $prefix = $parsedName['prefix'];
        $iconName = $parsedName['name'];

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

        return $this->buildSvg($resolvedIcon, $iconSetInfo, $options, $parsedName);
    }

    /**
     * @return array{provider: string, prefix: string, name: string}|null
     */
    protected function parseIconName(string $name): ?array
    {
        if (blank($name)) {
            return null;
        }

        $provider = '';
        $value = $name;

        if (str_starts_with($value, '@')) {
            $parts = explode(':', $value);

            if (count($parts) < 2 || count($parts) > 3) {
                return null;
            }

            $provider = ltrim((string) array_shift($parts), '@');
            $value = implode(':', $parts);
        }

        $colonSeparated = explode(':', $value);

        if (count($colonSeparated) > 3) {
            return null;
        }

        if (count($colonSeparated) > 1) {
            $iconName = (string) array_pop($colonSeparated);
            $prefix = (string) array_pop($colonSeparated);

            if (blank($iconName) || blank($prefix)) {
                return null;
            }

            $resolvedProvider = '';

            if (count($colonSeparated) > 0) {
                $resolvedProvider = (string) $colonSeparated[0];
            } else {
                $resolvedProvider = $provider;
            }

            return [
                'provider' => $resolvedProvider,
                'prefix' => $prefix,
                'name' => $iconName,
            ];
        }

        $dashSeparated = explode('-', $value);

        if (count($dashSeparated) <= 1) {
            return null;
        }

        $prefix = (string) array_shift($dashSeparated);
        $iconName = implode('-', $dashSeparated);

        if (blank($prefix) || blank($iconName)) {
            return null;
        }

        return [
            'provider' => $provider,
            'prefix' => $prefix,
            'name' => $iconName,
        ];
    }

    /**
     * @param  array{provider: string, prefix: string, name: string}|null  $parsedName
     * @return array<int, string>
     */
    protected function buildAutomaticClasses(?array $parsedName): array
    {
        if ($parsedName === null) {
            return [];
        }

        $classes = ['iconify'];

        if ($parsedName['provider'] !== '') {
            $classes[] = 'iconify--'.$parsedName['provider'];
        }

        if ($parsedName['prefix'] !== '') {
            $classes[] = 'iconify--'.$parsedName['prefix'];
        }

        return $classes;
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
     * @param  array{provider: string, prefix: string, name: string}|null  $parsedName
     */
    protected function buildSvg(array $icon, array $iconSetInfo, array $options, ?array $parsedName = null): string
    {
        $buildResult = $this->svgBuilder->build($icon, $iconSetInfo, $this->extractCustomisations($options));
        $renderAttributes = $buildResult['attributes'];
        $renderBody = $this->svgIdReplacer->replace($buildResult['body']);

        $options = $this->mergeDefaultAttributes($options, $this->buildAutomaticClasses($parsedName));
        unset(
            $options['width'],
            $options['height'],
            $options['inline'],
            $options['hFlip'],
            $options['vFlip'],
            $options['flip'],
            $options['rotate'],
            $options['viewBox']
        );

        if (isset($options['ariaHidden'])) {
            $options['aria-hidden'] = $options['ariaHidden'];
            unset($options['ariaHidden']);
        }

        $attributes = [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'aria-hidden' => true,
            'role' => 'img',
            'viewBox' => $renderAttributes['viewBox'],
        ];

        if (str_contains($renderBody, 'xlink:')) {
            $attributes['xmlns:xlink'] = 'http://www.w3.org/1999/xlink';
        }

        if (isset($renderAttributes['width'])) {
            $attributes['width'] = $renderAttributes['width'];
        }

        if (isset($renderAttributes['height'])) {
            $attributes['height'] = $renderAttributes['height'];
        }

        if (isset($options['aria-hidden'])) {
            if ($options['aria-hidden'] !== true && $options['aria-hidden'] !== 'true') {
                unset($attributes['aria-hidden']);
            }

            unset($options['aria-hidden']);
        }

        $attributes = array_merge($attributes, $options);

        $attributeString = $this->stringifyAttributes($attributes);

        return '<svg '.$attributeString.'>'.$renderBody.'</svg>';
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function extractCustomisations(array $options): array
    {
        $customisations = [];

        foreach (['width', 'height', 'inline', 'hFlip', 'vFlip', 'flip', 'rotate'] as $key) {
            if (array_key_exists($key, $options)) {
                $customisations[$key] = $options[$key];
            }
        }

        if (isset($customisations['flip']) && is_string($customisations['flip'])) {
            foreach (preg_split('/[\s,]+/', $customisations['flip']) ?: [] as $flipValue) {
                $value = trim($flipValue);

                if ($value === 'horizontal') {
                    $customisations['hFlip'] = true;
                }

                if ($value === 'vertical') {
                    $customisations['vFlip'] = true;
                }
            }
        }

        if (isset($customisations['rotate']) && (is_string($customisations['rotate']) || is_int($customisations['rotate']))) {
            $customisations['rotate'] = $this->parseRotateValue($customisations['rotate']);
        }

        return $customisations;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<int, string>  $automaticClasses
     * @return array<string, mixed>
     */
    protected function mergeDefaultAttributes(array $options, array $automaticClasses = []): array
    {
        $defaults = config()->array('iconify-api.inline.defaults', []);

        $defaultClasses = $this->explodeClasses($this->safeString($defaults['class'] ?? '', ''));
        $optionClasses = $this->explodeClasses($this->safeString($options['class'] ?? '', ''));

        $classes = $this->implodeUniqueClasses(array_merge($automaticClasses, $defaultClasses, $optionClasses));

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
     * @return array<int, string>
     */
    protected function explodeClasses(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        /** @var array<int, string> $parts */
        $parts = preg_split('/\s+/', $value) ?: [];

        return array_values(array_filter($parts, static fn (string $class): bool => $class !== ''));
    }

    /**
     * @param  array<int, string>  $classes
     */
    protected function implodeUniqueClasses(array $classes): string
    {
        $result = [];

        foreach ($classes as $class) {
            if ($class === '' || in_array($class, $result, true)) {
                continue;
            }

            $result[] = $class;
        }

        return implode(' ', $result);
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

    protected function normalizeRotate(int|string $parentRotate, int|string $aliasRotate): int
    {
        $rotation = $this->parseRotateValue($parentRotate) + $this->parseRotateValue($aliasRotate);

        return $rotation % 4;
    }

    protected function parseRotateValue(int|string $value): int
    {
        $cleanup = static function (int $rotation): int {
            while ($rotation < 0) {
                $rotation += 4;
            }

            return $rotation % 4;
        };

        if (is_int($value)) {
            return $cleanup($value);
        }

        if (is_numeric($value)) {
            return $cleanup((int) $value);
        }

        $units = preg_replace('/^-?[0-9.]*/', '', $value);

        if ($units === null || $units === $value) {
            return 0;
        }

        $split = 0;

        if ($units === '%') {
            $split = 25;
        }

        if ($units === 'deg') {
            $split = 90;
        }

        if ($split === 0) {
            return 0;
        }

        $numericPart = substr($value, 0, strlen($value) - strlen($units));

        if (! is_numeric($numericPart)) {
            return 0;
        }

        $num = (float) $numericPart / $split;

        if (fmod($num, 1.0) !== 0.0) {
            return 0;
        }

        return $cleanup((int) $num);
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
