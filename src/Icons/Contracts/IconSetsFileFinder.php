<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Contracts;

interface IconSetsFileFinder
{
    public function find(string $set): string;
}
