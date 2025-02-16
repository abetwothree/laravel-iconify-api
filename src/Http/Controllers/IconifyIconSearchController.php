<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\SearchManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IconifyIconSearchController
{
    public function __construct(
        protected SearchManager $searchManager
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var SearchDriver $driver */
        $driver = $this->searchManager->driver();

        if (! $request->has('query')) {
            return response()->json([
                'error' => 'No query provided',
            ], 400);
        }

        if (! $request->has('prefixes')
            && config()->boolean('iconify-api.search.require_prefixes')
        ) {
            return response()->json([
                'error' => 'Icon set prefixes are required',
            ], 400);
        }

        if ($request->has('prefixes')) {
            $driver->prefixes(explode(',', $request->string('prefixes')));
        }

        if ($request->has('category')) {
            $driver->category($request->string('category'));
        }

        $icons = $driver->search($request->string('query'));

        return response()->json($icons);
    }
}
