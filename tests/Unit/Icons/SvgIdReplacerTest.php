<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;

it('returns original body when no ids exist', function () {
    $replacer = new SvgIdReplacer;

    expect($replacer->replace('<path d="M0 0"/>'))->toBe('<path d="M0 0"/>');
});

it('replaces ids and linked references across subsequent renders', function () {
    $replacer = new SvgIdReplacer;

    $svg = '<defs><path id="grad1"/></defs><use xlink:href="#grad1"/><path fill="url(#grad1)"/><animate begin="grad1.end"/>';

    $first = $replacer->replace($svg);
    $second = $replacer->replace($svg);

    expect($first)->toContain('id="grad"');
    expect($first)->toContain('xlink:href="#grad"');
    expect($first)->toContain('url(#grad)');
    expect($first)->toContain('begin="grad.end"');

    expect($second)->toContain('id="grad1"');
    expect($second)->toContain('xlink:href="#grad1"');
    expect($second)->toContain('url(#grad1)');
    expect($second)->toContain('begin="grad1.end"');
});

it('resets counters and handles all-digit ids', function () {
    $replacer = new SvgIdReplacer;

    $numericIdSvg = '<defs><path id="123"/></defs><use xlink:href="#123"/>';

    $first = $replacer->replace($numericIdSvg);
    expect($first)->toContain('id="a"');
    expect($first)->toContain('xlink:href="#a"');

    $replacer->clear();

    $afterClear = $replacer->replace($numericIdSvg);
    expect($afterClear)->toContain('id="a"');
    expect($afterClear)->toContain('xlink:href="#a"');
});
