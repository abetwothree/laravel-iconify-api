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

        /** @var CollectionInfo $collection */
        $collection = resolve(CollectionInfo::class);
        $set = $request->string('prefix');

        return response()->json($collection->get($set));
    }
}
