<?php

declare(strict_types=1);

require_once __DIR__.'/../Fixtures/Helpers/custom-wrapper.php';

it('renders through the README custom helper example', function () {
    expect(iconify_svg('heroicons:clock', ['class' => 'w-6']))
        ->toBe(icon('heroicons:clock', ['class' => 'w-6']));
});
