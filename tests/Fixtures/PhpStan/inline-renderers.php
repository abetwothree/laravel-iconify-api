<?php

declare(strict_types=1);

// Analysed by PHPStan, never executed. Analysis fails if an entry point is renamed or
// removed. Larastan forwards facade calls, so IconFacadeTest pins the facade's @method
// tag separately.

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

echo LaravelIconifyApi::icon('mdi:home', ['class' => 'w-6']);
echo icon('mdi:home', ['class' => 'w-6']);
echo AbeTwoThree\LaravelIconifyApi\icon('mdi:home');
echo iconify_svg('mdi:home');
