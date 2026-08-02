<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\SvgIdReplacer;

require __DIR__.'/../../vendor/autoload.php';

$input = file_get_contents('php://stdin');

if ($input === false) {
    fwrite(STDERR, "Unable to read stdin\n");
    exit(1);
}

$payload = json_decode($input, true);

if (! is_array($payload) || ! isset($payload['mode']) || ! is_string($payload['mode'])) {
    fwrite(STDERR, "Invalid payload\n");
    exit(1);
}

$mode = $payload['mode'];

if ($mode === 'build') {
    $builder = new IconifySvgBuilder;
    $icon = is_array($payload['icon'] ?? null) ? $payload['icon'] : [];
    $iconSetInfo = is_array($payload['iconSetInfo'] ?? null) ? $payload['iconSetInfo'] : [];
    $customisations = is_array($payload['customisations'] ?? null) ? $payload['customisations'] : [];

    $result = $builder->build($icon, $iconSetInfo, $customisations);
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit(0);
}

if ($mode === 'ids') {
    $replacer = new SvgIdReplacer;
    $body = is_string($payload['body'] ?? null) ? $payload['body'] : '';
    $times = is_int($payload['times'] ?? null) ? $payload['times'] : 1;
    $times = max(1, $times);

    $outputs = [];

    for ($i = 0; $i < $times; $i++) {
        $outputs[] = $replacer->replace($body);
    }

    echo json_encode(['outputs' => $outputs], JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unsupported mode\n");
exit(1);
