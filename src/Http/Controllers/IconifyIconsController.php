<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\Icons\IconDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconifyIconsController
{
    public function show(string $prefix, Request $request): JsonResponse
    {
        if (! $request->has('icons')) {
            return response()->json(['error' => 'No icons specified'], 404);
        }

        // Names are not filtered here. A set reached through a custom `icons_location`
        // may be hand-authored and use names upstream's `matchIconName` would reject,
        // and `IconFinder::find()` already reports a name it cannot resolve in
        // `not_found`, so a name that does not exist costs a lookup and nothing more.
        // The cache key builder guards itself — see `CachesIcons::iconKeySegment()`.
        $icons = explode(',', $request->string('icons'));

        // Every name in the list mints a cache entry, so the list is bounded. A zero
        // limit disables the check. Iconify's own browser client splits its requests by
        // URL length and never approaches the default.
        $limit = max(0, config()->integer('iconify-api.max_icons_per_request', 200));

        if ($limit > 0 && count($icons) > $limit) {
            return response()->json([
                'error' => "Too many icons requested; the limit is {$limit}",
            ], 400);
        }

        /** @var IconDataResponse $dataResponse */
        $dataResponse = resolve(IconDataResponse::class);

        return response()->json($dataResponse->get($prefix, $icons));
    }
}
