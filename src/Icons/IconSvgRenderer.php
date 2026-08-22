<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons;

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconFinder as IconFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconDataResolver;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconRotation;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;
use Stringable;

/**
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
        'children',
        'fallback',
        'customise',
        '_ref',
    ];

    /**
     * Both spellings of the aria-hidden opt-out.
     *
     * @var array<int, string>
     */
    protected const ARIA_HIDDEN_KEYS = ['ariaHidden', 'aria-hidden'];

    /**
     * Conservative XML attribute name, with a leading `@` allowed for Alpine's `@click`
     * shorthand. Names that do not match are skipped.
     *
     * A well-formedness check on the *name*, not a sanitiser for attribute *semantics*.
     * It stops a key like `x onload=alert(1)` — which carries no HTML special character
     * and so survives htmlspecialchars() — from opening a second, live attribute. Option
     * keys are trusted developer input, as in any Blade attribute bag: a literal
     * `onclick` is well-formed and renders, by design.
     *
     * The `D` modifier is load-bearing. Without it PCRE's `$` also matches before a
     * final newline, so `"onLoad\n"` slipped past the exact-match control-key filters
     * above and was emitted verbatim.
     */
    protected const ATTRIBUTE_NAME_PATTERN = '/^[A-Za-z_:@][-A-Za-z0-9_:.]*$/D';

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

        $setDefaults = $iconData['defaults'] ?? [];

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

        $buildResult = $this->svgBuilder->build($icon, $this->extractCustomisations($options));
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

        $removeAriaHidden = $this->shouldRemoveAriaHidden($options);

        foreach (self::ARIA_HIDDEN_KEYS as $key) {
            unset($options[$key]);
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

        if ($removeAriaHidden) {
            unset($attributes['aria-hidden']);
        }

        $attributes = array_merge($attributes, $options);

        $attributeString = $this->stringifyAttributes($attributes);

        return '<svg '.$attributeString.'>'.$renderBody.'</svg>';
    }

    /**
     * Decide whether the default `aria-hidden="true"` should be dropped.
     *
     * Upstream handles both spellings inside one `switch` in a single pass over the
     * props: either key holding a value that is not `true`/`'true'` deletes the
     * attribute, and nothing ever puts it back, so the two keys compose rather than
     * clobber each other. See components/react/src/render.ts:175-181.
     *
     * @param  array<string, mixed>  $options
     */
    protected function shouldRemoveAriaHidden(array $options): bool
    {
        foreach (self::ARIA_HIDDEN_KEYS as $key) {
            if (! array_key_exists($key, $options)) {
                continue;
            }

            $value = $options[$key];

            if ($value !== true && $value !== 'true') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function extractCustomisations(array $options): array
    {
        $customisations = [];

        // Only keys the SVG builder reads are copied. `inline` and `flip` are handled
        // separately below and never reach the builder, which ignores both.
        foreach (['hFlip', 'vFlip', 'rotate'] as $key) {
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

        if (isset($options['flip']) && is_string($options['flip'])) {
            foreach (preg_split('/[\s,]+/', $options['flip']) ?: [] as $flipValue) {
                $value = trim($flipValue);

                if ($value === 'horizontal') {
                    $customisations['hFlip'] = true;
                }

                if ($value === 'vertical') {
                    $customisations['vFlip'] = true;
                }
            }
        }

        // The component layer only turns a string prop into a number; a numeric prop is
        // handed to iconToSVG() untouched, fraction and all. See
        // components/react/src/render.ts:170-177.
        if (isset($customisations['rotate'])) {
            $customisations['rotate'] = IconRotation::parse($customisations['rotate']);
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

            if (preg_match(self::ATTRIBUTE_NAME_PATTERN, (string) $key) !== 1) {
                continue;
            }

            // A value safeString() cannot represent — an array, a closure, a plain object
            // — coerces to the empty default. Emitting it would produce a present but
            // empty attribute, which is not the same thing as no attribute at all.
            $stringValue = $this->safeString($value, '');

            if ($stringValue === '') {
                continue;
            }

            $escapedKey = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedValue = htmlspecialchars($stringValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $parts[] = $escapedKey.'="'.$escapedValue.'"';
        }

        return implode(' ', $parts);
    }

    /**
     * Render a value as an attribute string, or the default when it has no representation.
     *
     * `Stringable` is included because a Blade component hands over whatever was bound to
     * it — `<x-icon :data-x="$stringable" />` reaches here as the object — and Laravel's
     * own ComponentAttributeBag would have rendered its string.
     */
    protected function safeString(mixed $value, string $default = ''): string
    {
        if (is_string($value) || is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
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
     * Upstream assigns `style.color = value` through the CSSOM, which silently drops a
     * value carrying a declaration separator. This renderer concatenates into a raw CSS
     * string instead, so it has to reject the same class of value itself — dropping it
     * entirely rather than partially sanitising it.
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
