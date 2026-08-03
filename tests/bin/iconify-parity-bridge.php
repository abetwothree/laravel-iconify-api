<?php

declare(strict_types=1);

use AbeTwoThree\LaravelIconifyApi\Icons\Contracts\IconSetsFileFinder as IconSetsFileFinderContract;
use AbeTwoThree\LaravelIconifyApi\Icons\IconFinder;
use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconDataResolver;
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
    $customisations = is_array($payload['customisations'] ?? null) ? $payload['customisations'] : [];

    $result = $builder->build($icon, $customisations);
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

if ($mode === 'icon-data') {
    $iconSet = is_array($payload['iconSet'] ?? null) ? $payload['iconSet'] : [];
    $name = is_string($payload['name'] ?? null) ? $payload['name'] : '';
    $customisations = is_array($payload['customisations'] ?? null) ? $payload['customisations'] : [];

    $tempFile = tempnam(sys_get_temp_dir(), 'iconify-parity-');

    if ($tempFile === false) {
        fwrite(STDERR, "Unable to create temp file\n");
        exit(1);
    }

    file_put_contents($tempFile, json_encode($iconSet, JSON_THROW_ON_ERROR));

    $fileFinder = new class($tempFile) implements IconSetsFileFinderContract
    {
        public function __construct(private string $path) {}

        public function find(string $prefix, string $type = 'icons'): string
        {
            return $this->path;
        }
    };

    $prefix = is_string($iconSet['prefix'] ?? null) ? $iconSet['prefix'] : 'test';
    $found = (new IconFinder($fileFinder))->find($prefix, [$name]);

    @unlink($tempFile);

    $iconData = $found[$name] ?? null;

    if ($iconData === null || ! empty($iconData['not_found'])) {
        echo json_encode(null, JSON_THROW_ON_ERROR);
        exit(0);
    }

    $icon = (new IconDataResolver)->resolve($iconData, $name, $iconData['defaults'] ?? []);

    if ($icon === null) {
        echo json_encode(null, JSON_THROW_ON_ERROR);
        exit(0);
    }

    echo json_encode((new IconifySvgBuilder)->build($icon, $customisations), JSON_THROW_ON_ERROR);
    exit(0);
}

fwrite(STDERR, "Unsupported mode\n");
exit(1);
