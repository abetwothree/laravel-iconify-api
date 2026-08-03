<?php

declare(strict_types=1);

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

interface IconSetsFileFinder
{
    public function find(string $prefix, string $type = 'icons'): string;
}
