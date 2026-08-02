<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Support;

class IconifySvgBuilder
{
    /**
     * @param  array<string, mixed>  $icon
     * @param  array<string, mixed>  $iconSetInfo
     * @param  array<string, mixed>  $customisations
     * @return array{
     *     attributes: array{viewBox:string, width?:string, height?:string},
     *     viewBox: array{0:int|float,1:int|float,2:int|float,3:int|float},
     *     body:string,
     * }
     */
    public function build(array $icon, array $iconSetInfo, array $customisations = []): array
    {
        $fullIcon = $this->normaliseIcon($icon, $iconSetInfo);
        $fullCustomisations = $this->normaliseCustomisations($customisations);

        $box = [
            'left' => $fullIcon['left'],
            'top' => $fullIcon['top'],
            'width' => $fullIcon['width'],
            'height' => $fullIcon['height'],
        ];

        $body = $fullIcon['body'];

        $iconPass = $this->applyTransformPass($body, $box, [
            'hFlip' => $fullIcon['hFlip'],
            'vFlip' => $fullIcon['vFlip'],
            'rotate' => $fullIcon['rotate'],
        ]);
        $body = $iconPass['body'];
        $box = $iconPass['box'];

        $customPass = $this->applyTransformPass($body, $box, [
            'hFlip' => $fullCustomisations['hFlip'],
            'vFlip' => $fullCustomisations['vFlip'],
            'rotate' => $fullCustomisations['rotate'],
        ]);
        $body = $customPass['body'];
        $box = $customPass['box'];

        $boxWidth = $box['width'];
        $boxHeight = $box['height'];

        [$width, $height] = $this->calculateDimensions($fullCustomisations, $boxWidth, $boxHeight);

        $attributes = [
            'viewBox' => $box['left'].' '.$box['top'].' '.$boxWidth.' '.$boxHeight,
        ];

        if (! $this->isUnsetKeyword($width)) {
            $attributes['width'] = (string) $width;
        }

        if (! $this->isUnsetKeyword($height)) {
            $attributes['height'] = (string) $height;
        }

        return [
            'attributes' => $attributes,
            'viewBox' => [$box['left'], $box['top'], $boxWidth, $boxHeight],
            'body' => $body,
        ];
    }

    /**
     * @param  array<string, mixed>  $icon
     * @param  array<string, mixed>  $iconSetInfo
     * @return array{left:int|float, top:int|float, width:int|float, height:int|float, rotate:int|float, hFlip:bool, vFlip:bool, body:string}
     */
    protected function normaliseIcon(array $icon, array $iconSetInfo): array
    {
        return [
            'left' => $this->safeNumber($icon['left'] ?? 0, 0),
            'top' => $this->safeNumber($icon['top'] ?? 0, 0),
            'width' => $this->safeNumber($icon['width'] ?? $iconSetInfo['width'] ?? 16, 16),
            'height' => $this->safeNumber($icon['height'] ?? $iconSetInfo['height'] ?? 16, 16),
            'rotate' => IconRotation::parse($icon['rotate'] ?? 0),
            'hFlip' => $this->toBool($icon['hFlip'] ?? false),
            'vFlip' => $this->toBool($icon['vFlip'] ?? false),
            'body' => $this->safeString($icon['body'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $customisations
     * @return array{width:int|float|string|null, height:int|float|string|null, rotate:int|float, hFlip:bool, vFlip:bool}
     */
    protected function normaliseCustomisations(array $customisations): array
    {
        $width = $customisations['width'] ?? null;
        $height = $customisations['height'] ?? null;

        if (! is_int($width) && ! is_float($width) && ! is_string($width) && $width !== null) {
            $width = null;
        }

        if (! is_int($height) && ! is_float($height) && ! is_string($height) && $height !== null) {
            $height = null;
        }

        return [
            'width' => $width,
            'height' => $height,
            'rotate' => IconRotation::parse($customisations['rotate'] ?? 0),
            'hFlip' => $this->toBool($customisations['hFlip'] ?? false),
            'vFlip' => $this->toBool($customisations['vFlip'] ?? false),
        ];
    }

    /**
     * @param  array{left:int|float, top:int|float, width:int|float, height:int|float}  $box
     * @param  array{rotate:int|float, hFlip:bool, vFlip:bool}  $props
     * @return array{body:string, box:array{left:int|float, top:int|float, width:int|float, height:int|float}}
     */
    protected function applyTransformPass(string $body, array $box, array $props): array
    {
        $transformations = [];
        $hFlip = $props['hFlip'];
        $vFlip = $props['vFlip'];
        $rotation = $props['rotate'];

        if ($hFlip) {
            if ($vFlip) {
                $rotation += 2;
            } else {
                $transformations[] = 'translate('.($box['width'] + $box['left']).' '.(0 - $box['top']).')';
                $transformations[] = 'scale(-1 1)';
                $box['top'] = 0;
                $box['left'] = 0;
            }
        } elseif ($vFlip) {
            $transformations[] = 'translate('.(0 - $box['left']).' '.($box['height'] + $box['top']).')';
            $transformations[] = 'scale(1 -1)';
            $box['top'] = 0;
            $box['left'] = 0;
        }

        // The rotation is only collapsed here, where upstream's `switch` decides, and
        // never earlier: a fractional rotation has to survive the `+= 2` above, since
        // 1.5 + 2 is 3.5, which still matches no case.
        $rotation = IconRotation::normalise($rotation);

        switch ($rotation) {
            case 1:
                $tempValue = ($box['height'] / 2) + $box['top'];
                array_unshift($transformations, 'rotate(90 '.$tempValue.' '.$tempValue.')');
                break;

            case 2:
                array_unshift($transformations, 'rotate(180 '.(($box['width'] / 2) + $box['left']).' '.(($box['height'] / 2) + $box['top']).')');
                break;

            case 3:
                $tempValue = ($box['width'] / 2) + $box['left'];
                array_unshift($transformations, 'rotate(-90 '.$tempValue.' '.$tempValue.')');
                break;
        }

        if ($rotation % 2 === 1) {
            if ($box['left'] !== $box['top']) {
                $tempValue = $box['left'];
                $box['left'] = $box['top'];
                $box['top'] = $tempValue;
            }

            if ($box['width'] !== $box['height']) {
                $tempValue = $box['width'];
                $box['width'] = $box['height'];
                $box['height'] = $tempValue;
            }
        }

        if (count($transformations) > 0) {
            $body = $this->wrapSvgContent($body, '<g transform="'.implode(' ', $transformations).'">', '</g>');
        }

        return [
            'body' => $body,
            'box' => $box,
        ];
    }

    /**
     * @param  array{width:int|float|string|null, height:int|float|string|null, rotate:int|float, hFlip:bool, vFlip:bool}  $customisations
     * @return array{0:int|float|string, 1:int|float|string}
     */
    protected function calculateDimensions(array $customisations, int|float $boxWidth, int|float $boxHeight): array
    {
        $customWidth = $customisations['width'];
        $customHeight = $customisations['height'];

        if ($customWidth === null) {
            $height = $customHeight === null
                ? '1em'
                : ($customHeight === 'auto' ? $boxHeight : $customHeight);

            $width = $this->calculateSize($height, $this->aspectRatio($boxWidth, $boxHeight));

            return [$width, $height];
        }

        $width = $customWidth === 'auto' ? $boxWidth : $customWidth;
        $height = $customHeight === null
            ? $this->calculateSize($width, $this->aspectRatio($boxHeight, $boxWidth))
            : ($customHeight === 'auto' ? $boxHeight : $customHeight);

        return [$width, $height];
    }

    /**
     * A zero box dimension has no aspect ratio, so fall back to 1:1 and let the
     * requested size through unscaled: JavaScript divides to Infinity and emits
     * nonsense sizes, while PHP 8 throws an uncaught DivisionByZeroError.
     */
    protected function aspectRatio(int|float $numerator, int|float $denominator): float
    {
        if ($denominator === 0 || $denominator === 0.0) {
            return 1.0;
        }

        return $numerator / $denominator;
    }

    protected function calculateSize(int|float|string $size, float $ratio, int $precision = 100): int|float|string
    {
        if ($ratio === 1.0) {
            return $size;
        }

        if (is_int($size) || is_float($size)) {
            return ceil($size * $ratio * $precision) / $precision;
        }

        $unitsSplit = '/(-?[0-9.]*[0-9]+[0-9.]*)/';
        $unitsTest = '/^-?[0-9.]*[0-9]+[0-9.]*$/';

        $oldParts = preg_split($unitsSplit, $size, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$size];

        $newParts = [];
        $isNumber = preg_match($unitsTest, $oldParts[0]) === 1;

        foreach ($oldParts as $code) {
            if ($isNumber) {
                $num = (float) $code;
                $newParts[] = is_nan($num) ? $code : (string) (ceil($num * $ratio * $precision) / $precision);
            } else {
                $newParts[] = $code;
            }

            $isNumber = ! $isNumber;
        }

        return implode('', $newParts);
    }

    protected function isUnsetKeyword(mixed $value): bool
    {
        return $value === 'unset' || $value === 'undefined' || $value === 'none';
    }

    /**
     * Wrap SVG content without nesting existing defs inside transform groups.
     */
    protected function wrapSvgContent(string $body, string $start, string $end): string
    {
        [$defs, $content] = $this->splitSvgDefs($body);

        $wrapped = $start.$content.$end;

        return $defs === '' ? $wrapped : '<defs>'.$defs.'</defs>'.$wrapped;
    }

    /**
     * @return array{0:string,1:string}
     */
    protected function splitSvgDefs(string $content): array
    {
        $defs = '';

        while (true) {
            $index = strpos($content, '<defs');

            if ($index === false) {
                break;
            }

            $start = strpos($content, '>', $index);
            $end = strpos($content, '</defs');

            if ($start === false || $end === false) {
                break;
            }

            $endEnd = strpos($content, '>', $end);

            if ($endEnd === false) {
                break;
            }

            $defs .= trim(substr($content, $start + 1, $end - $start - 1));
            $content = trim(substr($content, 0, $index)).substr($content, $endEnd + 1);
        }

        return [$defs, $content];
    }

    protected function toBool(mixed $value): bool
    {
        return $value === true || $value === 'true' || $value === 1;
    }

    protected function safeNumber(mixed $value, int|float $default = 0): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value + 0;
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
