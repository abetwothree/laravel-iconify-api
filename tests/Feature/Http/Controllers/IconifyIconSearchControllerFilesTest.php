<?php

beforeEach(function () {
    config()->set('iconify-api.search.default', 'files');
});

it('can search for icons files', function (
    string $query,
    string $prefixes,
    ?string $category = null,
    ?bool $similar = null,
    ?string $tags = null,
    ?bool $palette = null,
    ?string $style = null,
) {

    $params = array_filter(
        get_defined_vars(),
        fn ($value) => ! empty($value)
    );

    $response = test()->get(route('iconify-api.search.index', $params));

    dump($response->json());
})->with([
    [
        'query' => 'home',
        'prefixes' => 'bx,mdi,heroicons,bx',
        'category' => 'Material',
    ],
    // [
    //     'query' => 'activity',
    //     'prefixes' => 'bytesize,cbi,cil',
    // ],
    // [
    //     'query' => 'alert',
    //     'prefixes' => 'bytesize,ei',
    // ],
    // [
    //     'query' => 'bad-icon',
    //     'prefixes' => 'bytesize,wpf,codex',
    //     'category' => "UI Other / Mixed Grid",
    //     // 'similar' => false,
    //     // 'tags' => '',
    //     // 'palette' => false,
    //     // 'style' => '',
    // ],
]);
