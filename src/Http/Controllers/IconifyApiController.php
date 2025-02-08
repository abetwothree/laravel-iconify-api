<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\Icons\IconDataResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconifyApiController
{
    public function show(string $set, Request $request): JsonResponse
    {
        if (! $request->has('icons')) {
            return response()->json(['error' => 'No icons specified'], 404);
        }

        $icons = explode(',', $request->string('icons'));
        $data = new IconDataResponse($set, $icons)->response();

        return response()->json($data);
    }
}
