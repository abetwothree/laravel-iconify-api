<?php

declare(strict_types=1);

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

        $iconsParameter = $request->input('icons');

        // `$request->string()` would stringify an array parameter instead, which is an
        // array-to-string conversion — a warning, a 500, and a lookup for an icon
        // literally named "Array".
        if (! is_string($iconsParameter)) {
            return response()->json(['error' => 'The icons parameter must be a comma separated string'], 400);
        }

        // Names are deliberately unfiltered: a hand-authored set may use names upstream's
        // `matchIconName` would reject, an unresolvable name already comes back in
        // `not_found`, and the key builder guards itself (`CachesIcons::iconKeySegment()`).
        $icons = explode(',', $iconsParameter);

        // Every name in the list mints a cache entry, so the list is bounded. Zero
        // disables the check.
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
