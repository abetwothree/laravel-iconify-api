<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconDataResolver;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;

/**
 * @phpstan-import-type TIcon from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TAlias from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 * @phpstan-import-type TIconData from \AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder
 */
class IconSvgRenderer
{
    /**
     * Customisation keys consumed by the SVG builder, never emitted as attributes.
     *
     * @var array<int, string>
     */
    protected const CUSTOMISATION_KEYS = ['width', 'height', 'inline', 'hFlip', 'vFlip', 'flip', 'rotate'];

    /**
     * Framework control props Iconify's components swallow.
     *
     * Mirrors components/react/src/render.ts:129-143,
     * components/vue/src/render.ts:126-133 and
     * components/svelte/src/render.ts:110-116.
     *
     * @var array<int, string>
     */
    protected const IGNORED_OPTIONS = [
        'icon',
        'mode',
        'ssr',
        'onLoad',
        'onload',
        'children',
        'fallback',
        'customise',
        '_ref',
    ];

    /**
     * Alternate spellings for the boolean flip customisations.
     *
     * Mirrors components/vue/src/render.ts:66-76 and :172-178.
     *
     * @var array<string, string>
     */
    protected const FLIP_ALIASES = [
        'horizontal-flip' => 'hFlip',
        'h-flip' => 'hFlip',
        'horizontalFlip' => 'hFlip',
        'vertical-flip' => 'vFlip',
        'v-flip' => 'vFlip',
        'verticalFlip' => 'vFlip',
    ];

    protected IconifySvgBuilder $svgBuilder;

    protected SvgIdReplacer $svgIdReplacer;

    protected IconDataResolver $iconDataResolver;

    public function __construct(
        protected IconFinderContract $iconFinder,
        ?IconifySvgBuilder $svgBuilder = null,
        ?SvgIdReplacer $svgIdReplacer = null,
        ?IconDataResolver $iconDataResolver = null,
    ) {
        $this->svgBuilder = $svgBuilder ?? new IconifySvgBuilder;
        $this->svgIdReplacer = $svgIdReplacer ?? new SvgIdReplacer;
        $this->iconDataResolver = $iconDataResolver ?? new IconDataResolver;
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

        /** @var array<string, mixed> $setDefaults */
        $setDefaults = $iconData['defaults'];

        $resolvedIcon = $this->iconDataResolver->resolve($iconData, $iconName, $setDefaults);

        if ($resolvedIcon === null) {
            return '';
        }

        return $this->buildSvg($resolvedIcon, $options, $parsedName);
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
     * @param  array<string, mixed>  $icon
     * @param  array<string, mixed>  $options
     * @param  array{provider: string, prefix: string, name: string}|null  $parsedName
     */
    protected function buildSvg(array $icon, array $options, ?array $parsedName = null): string
    {
        // Configured defaults are merged before anything is read out of the options so
        // that `inline.defaults` drives customisations (width, rotate, flip, inline) as
        // uniformly as it drives plain SVG attributes. Per-call options still win.
        $options = $this->mergeDefaultAttributes($options, $this->buildAutomaticClasses($parsedName));

        $buildResult = $this->svgBuilder->build($icon, [], $this->extractCustomisations($options));
        $renderAttributes = $buildResult['attributes'];
        $renderBody = $this->svgIdReplacer->replace($buildResult['body']);

        $inline = $this->narrowTruthy($options['inline'] ?? false);

        foreach (self::CUSTOMISATION_KEYS as $key) {
            unset($options[$key]);
        }

        foreach (self::IGNORED_OPTIONS as $key) {
            unset($options[$key]);
        }

        foreach (array_keys(self::FLIP_ALIASES) as $alias) {
            unset($options[$alias]);
        }

        unset($options['viewBox']);

        $options = $this->buildStyleAttribute($options, $inline);

        if (array_key_exists('ariaHidden', $options)) {
            $options['aria-hidden'] = $options['ariaHidden'];
            unset($options['ariaHidden']);
        }

        $attributes = [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'aria-hidden' => 'true',
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

        if (array_key_exists('aria-hidden', $options)) {
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

        foreach (['inline', 'hFlip', 'vFlip', 'flip', 'rotate'] as $key) {
            if (array_key_exists($key, $options)) {
                $customisations[$key] = $options[$key];
            }
        }

        // Dimensions follow mergeCustomisations(), packages/utils/src/customisations/merge.ts:25-32:
        // null is copied, falsy values are dropped, and only strings/numbers are accepted.
        foreach (['width', 'height'] as $key) {
            if (! array_key_exists($key, $options)) {
                continue;
            }

            $value = $options[$key];

            if ($value === null) {
                $customisations[$key] = null;

                continue;
            }

            if ($this->jsTruthy($value) && (is_string($value) || is_int($value) || is_float($value))) {
                $customisations[$key] = $value;
            }
        }

        foreach (self::FLIP_ALIASES as $alias => $target) {
            if (array_key_exists($alias, $options) && $this->narrowTruthy($options[$alias])) {
                $customisations[$target] = true;
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

    protected function safeString(mixed $value, string $default = ''): string
    {
        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Fold `color` and `inline` into the style attribute.
     *
     * Precedence follows React/Vue: the caller's own `style` is emitted last and
     * therefore wins. See components/react/src/render.ts:166-168 and :200-209.
     *
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function buildStyleAttribute(array $options, bool $inline): array
    {
        $parts = [];

        if (array_key_exists('color', $options)) {
            $color = trim($this->safeString($options['color'], ''));

            if ($color !== '' && $this->isSafeCssDeclarationValue($color)) {
                $parts[] = 'color: '.$color.';';
            }

            unset($options['color']);
        }

        if ($inline) {
            $parts[] = 'vertical-align: -0.125em;';
        }

        $userStyle = trim($this->safeString($options['style'] ?? '', ''));

        if ($userStyle !== '') {
            $parts[] = $userStyle;
        }

        if ($parts === []) {
            unset($options['style']);

            return $options;
        }

        $options['style'] = implode(' ', $parts);

        return $options;
    }

    /**
     * Boolean coercion used by every Iconify component for boolean customisations.
     *
     * Mirrors components/react/src/render.ts:155, components/vue/src/render.ts:139
     * and components/svelte/src/render.ts:122.
     */
    protected function narrowTruthy(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1;
    }

    /**
     * Reject values that could terminate a CSS declaration or open a new block.
     *
     * Upstream sets `color` via a CSSOM property assignment (`style.color = value`),
     * which the browser parses as a single property value: anything containing a
     * declaration separator is invalid and the assignment is silently dropped, so
     * nothing is set. Our renderer instead concatenates `color` into a raw CSS
     * string, so we must reject the same class of value ourselves rather than
     * emitting it — a value is dropped entirely, never partially sanitised.
     */
    protected function isSafeCssDeclarationValue(string $value): bool
    {
        if (str_contains($value, ';') || str_contains($value, '{') || str_contains($value, '}')) {
            return false;
        }

        if (str_contains($value, '/*') || str_contains($value, '*/')) {
            return false;
        }

        return true;
    }

    /**
     * JavaScript truthiness, which differs from PHP's: the string "0" is truthy in JS.
     *
     * Needed so that a dimension of "0" behaves the way mergeCustomisations() does.
     */
    protected function jsTruthy(mixed $value): bool
    {
        if ($value === null || $value === false || $value === '') {
            return false;
        }

        if (is_int($value) && $value === 0) {
            return false;
        }

        if (is_float($value) && ($value === 0.0 || is_nan($value))) {
            return false;
        }

        return true;
    }
}
