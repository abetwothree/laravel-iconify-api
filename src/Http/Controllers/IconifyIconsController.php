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

        $icons = explode(',', $request->string('icons'));

        /** @var IconDataResponse $dataResponse */
        $dataResponse = resolve(IconDataResponse::class);
        $data = $dataResponse->get($prefix, $icons);

        return response()->json($data);
    }
}
