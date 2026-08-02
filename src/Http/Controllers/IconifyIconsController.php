<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\Icons\IconDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconifyIconsController
{
    /**
     * Upstream's `matchIconName`, node_modules/@iconify/utils/lib/icon/name.js:7.
     *
     * The Iconify API only ever serves names in this shape, and all 344,625 names in
     * the 235 sets bundled with `@iconify/json` match it. Rejecting anything else
     * keeps caller-controlled text out of the cache key builder entirely.
     */
    protected const ICON_NAME_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/D';

    public function show(string $prefix, Request $request): JsonResponse
    {
        if (! $request->has('icons')) {
            return response()->json(['error' => 'No icons specified'], 404);
        }

        $requested = explode(',', $request->string('icons'));

        // Every name in the list mints a cache entry, so the list is bounded. A zero
        // limit disables the check. Iconify's own browser client splits its requests by
        // URL length and never approaches the default.
        $limit = max(0, config()->integer('iconify-api.max_icons_per_request', 200));

        if ($limit > 0 && count($requested) > $limit) {
            return response()->json([
                'error' => "Too many icons requested; the limit is {$limit}",
            ], 400);
        }

        $icons = [];
        $rejected = [];

        foreach ($requested as $icon) {
            if (preg_match(self::ICON_NAME_PATTERN, $icon) === 1) {
                $icons[] = $icon;

                continue;
            }

            $rejected[] = $icon;
        }

        /** @var IconDataResponse $dataResponse */
        $dataResponse = resolve(IconDataResponse::class);
        $data = $dataResponse->get($prefix, $icons);

        if ($rejected !== []) {
            $data['not_found'] = array_merge($data['not_found'] ?? [], $rejected);
        }

        return response()->json($data);
    }
}
