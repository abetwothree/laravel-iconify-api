<?php

use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;

it('covers transform passes and defs-safe wrapping', function () {
    $builder = new IconifySvgBuilder;

    $applyTransformPass = new ReflectionMethod($builder, 'applyTransformPass');
    $applyTransformPass->setAccessible(true);

    $hFlipResult = $applyTransformPass->invoke($builder, '<path d="M0 0"/>', [
        'left' => 1,
        'top' => 2,
        'width' => 10,
        'height' => 20,
    ], [
        'rotate' => 1,
        'hFlip' => true,
        'vFlip' => false,
    ]);

    expect($hFlipResult['body'])->toContain('rotate(90');
    expect($hFlipResult['body'])->toContain('scale(-1 1)');
    expect($hFlipResult['box']['left'])->toBe(0);
    expect($hFlipResult['box']['top'])->toBe(0);

    $vFlipResult = $applyTransformPass->invoke($builder, '<path d="M0 0"/>', [
        'left' => 3,
        'top' => 4,
        'width' => 8,
        'height' => 12,
    ], [
        'rotate' => 2,
        'hFlip' => false,
        'vFlip' => true,
    ]);

    expect($vFlipResult['body'])->toContain('rotate(180');
    expect($vFlipResult['body'])->toContain('scale(1 -1)');

    $negativeRotationResult = $applyTransformPass->invoke($builder, '<defs><path id="a"/></defs><path d="M1 1"/>', [
        'left' => 1,
        'top' => 1,
        'width' => 6,
        'height' => 10,
    ], [
        'rotate' => -1,
        'hFlip' => true,
        'vFlip' => true,
    ]);

    expect($negativeRotationResult['body'])->toContain('<defs><path id="a"/></defs><g transform="rotate(90');
    expect($negativeRotationResult['box']['width'])->toBe(10);
    expect($negativeRotationResult['box']['height'])->toBe(6);

    $normalisedNegativeRotation = $applyTransformPass->invoke($builder, '<path d="M2 2"/>', [
        'left' => 0,
        'top' => 0,
        'width' => 8,
        'height' => 8,
    ], [
        'rotate' => -1,
        'hFlip' => false,
        'vFlip' => false,
    ]);

    expect($normalisedNegativeRotation['body'])->toContain('rotate(-90');
});

it('covers dimensions, size math, rotate parsing, and scalar guards', function () {
    $builder = new IconifySvgBuilder;

    $normaliseCustomisations = new ReflectionMethod($builder, 'normaliseCustomisations');
    $normaliseCustomisations->setAccessible(true);
    $custom = $normaliseCustomisations->invoke($builder, [
        'width' => ['bad-width'],
        'height' => new stdClass,
        'rotate' => -1.6,
        'hFlip' => 'true',
        'vFlip' => 1,
    ]);

    expect($custom['width'])->toBeNull();
    expect($custom['height'])->toBeNull();
    expect($custom['rotate'])->toBe(3);
    expect($custom['hFlip'])->toBeTrue();
    expect($custom['vFlip'])->toBeTrue();

    $calculateDimensions = new ReflectionMethod($builder, 'calculateDimensions');
    $calculateDimensions->setAccessible(true);

    $dimsAutoHeight = $calculateDimensions->invoke($builder, [
        'width' => null,
        'height' => 'auto',
        'rotate' => 0,
        'hFlip' => false,
        'vFlip' => false,
    ], 24, 12);
    expect($dimsAutoHeight[0])->toBe(24.0);
    expect($dimsAutoHeight[1])->toBe(12);

    $dimsDerivedHeight = $calculateDimensions->invoke($builder, [
        'width' => 30,
        'height' => null,
        'rotate' => 0,
        'hFlip' => false,
        'vFlip' => false,
    ], 20, 10);
    expect($dimsDerivedHeight[0])->toBe(30);
    expect($dimsDerivedHeight[1])->toBe(15.0);

    $calculateSize = new ReflectionMethod($builder, 'calculateSize');
    $calculateSize->setAccessible(true);
    expect($calculateSize->invoke($builder, 10, 1.25))->toBe(12.5);
    expect($calculateSize->invoke($builder, '1.5em', 2.0))->toBe('3em');
    expect($calculateSize->invoke($builder, '3em', 1.0))->toBe('3em');

    $parseRotateValue = new ReflectionMethod($builder, 'parseRotateValue');
    $parseRotateValue->setAccessible(true);
    expect($parseRotateValue->invoke($builder, 3))->toBe(3);
    expect($parseRotateValue->invoke($builder, 2.9))->toBe(2);
    expect($parseRotateValue->invoke($builder, '2'))->toBe(2);
    expect($parseRotateValue->invoke($builder, '.'))->toBe(0);
    expect($parseRotateValue->invoke($builder, '180deg'))->toBe(2);
    expect($parseRotateValue->invoke($builder, '75%'))->toBe(3);
    expect($parseRotateValue->invoke($builder, '45deg'))->toBe(0);
    expect($parseRotateValue->invoke($builder, '12rad'))->toBe(0);
    expect($parseRotateValue->invoke($builder, '.deg'))->toBe(0);
    expect($parseRotateValue->invoke($builder, []))->toBe(0);
    expect($parseRotateValue->invoke($builder, 'deg'))->toBe(0);

    $normaliseRotate = new ReflectionMethod($builder, 'normaliseRotate');
    $normaliseRotate->setAccessible(true);
    expect($normaliseRotate->invoke($builder, -5))->toBe(3);

    $splitSvgDefs = new ReflectionMethod($builder, 'splitSvgDefs');
    $splitSvgDefs->setAccessible(true);
    [$defs, $content] = $splitSvgDefs->invoke($builder, '<defs><path id="a"/></defs><defs><path id="b"/></defs><path d="M0 0"/>');
    expect($defs)->toBe('<path id="a"/><path id="b"/>');
    expect($content)->toBe('<path d="M0 0"/>');

    [$brokenDefs, $brokenContent] = $splitSvgDefs->invoke($builder, '<defs malformed');
    expect($brokenDefs)->toBe('');
    expect($brokenContent)->toBe('<defs malformed');

    [$missingEndBracketDefs, $missingEndBracketContent] = $splitSvgDefs->invoke($builder, '<defs><path/></defs');
    expect($missingEndBracketDefs)->toBe('');
    expect($missingEndBracketContent)->toBe('<defs><path/></defs');

    $safeString = new ReflectionMethod($builder, 'safeString');
    $safeString->setAccessible(true);
    expect($safeString->invoke($builder, ['invalid'], 'fallback'))->toBe('fallback');
});

it('covers build output with unset keywords', function () {
    $builder = new IconifySvgBuilder;

    $result = $builder->build([
        'body' => '<path d="M0 0"/>',
        'width' => 20,
        'height' => 10,
    ], [
        'width' => 16,
        'height' => 16,
    ], [
        'width' => 'unset',
        'height' => 'none',
    ]);

    expect($result['attributes']['viewBox'])->toBe('0 0 20 10');
    expect($result['attributes'])->not->toHaveKey('width');
    expect($result['attributes'])->not->toHaveKey('height');
});
