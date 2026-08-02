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

        $icons = [];
        $rejected = [];

        foreach (explode(',', $request->string('icons')) as $icon) {
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
