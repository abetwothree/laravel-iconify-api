<?php

it('renders heroicons with the icon set root viewBox', function () {
    $helper = 'icon';
    $svg = $helper('heroicons:clock');

    expect($svg)->toContain('viewBox="0 0 24 24"');
});

it('renders mdi with the icon set root viewBox', function () {
    $helper = 'icon';
    $svg = $helper('mdi:home');

    expect($svg)->toContain('viewBox="0 0 24 24"');
});
