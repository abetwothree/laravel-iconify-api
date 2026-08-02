<?php

namespace AbeTwoThree\LaravelIconifyApi\Icons\Support {

    function preg_replace($pattern, $replacement, $subject, int $limit = -1, &$count = null)
    {
        $mock = $GLOBALS['iconify_mock_svg_builder_preg_replace'] ?? null;

        if ($mock !== null) {
            return $mock($pattern, $replacement, $subject, $limit, $count);
        }

        return \preg_replace($pattern, $replacement, $subject, $limit, $count);
    }
}

namespace {

    use AbeTwoThree\LaravelIconifyApi\Icons\Support\IconifySvgBuilder;

    beforeEach(function () {
        unset($GLOBALS['iconify_mock_svg_builder_preg_replace']);
    });

    afterEach(function () {
        unset($GLOBALS['iconify_mock_svg_builder_preg_replace']);
    });

    it('covers parseRotateValue branch when preg_replace returns null', function () {
        $builder = new IconifySvgBuilder;

        $GLOBALS['iconify_mock_svg_builder_preg_replace'] = static function ($pattern, $replacement, $subject, $limit, &$count) {
            if ($pattern === '/^-?[0-9.]*/') {
                return null;
            }

            return \preg_replace($pattern, $replacement, $subject, $limit, $count);
        };

        $parseRotateValue = new ReflectionMethod($builder, 'parseRotateValue');
        $parseRotateValue->setAccessible(true);

        expect($parseRotateValue->invoke($builder, '90deg'))->toBe(0);
    });
}
