<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\IconCollections\CollectionInfo;
use AbeTwoThree\LaravelIconifyApi\IconCollections\CollectionsInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconifyCollectionsController
{
    public function index(): JsonResponse
    {
        /** @var CollectionsInfo $collections */
        $collections = resolve(CollectionsInfo::class);

        return response()->json($collections->get());
    }

    public function show(Request $request): JsonResponse
    {
        if (! $request->has('prefix')) {
            return response()->json(['error' => 'No icon set prefix specified in query string'], 404);
        }

        $prefix = $request->input('prefix');

        // `$request->string()` would stringify an array parameter instead, which is an
        // array-to-string conversion: a warning and a 500.
        if (! is_string($prefix)) {
            return response()->json(['error' => 'The prefix parameter must be a string'], 400);
        }

        /** @var CollectionInfo $collection */
        $collection = resolve(CollectionInfo::class);

        return response()->json($collection->get($prefix));
    }
}
