<?php

namespace AbeTwoThree\LaravelIconifyApi\Http\Controllers;

use AbeTwoThree\LaravelIconifyApi\Search\Contracts\SearchDriver;
use AbeTwoThree\LaravelIconifyApi\Search\SearchManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use AbeTwoThree\LaravelIconifyApi\Http\Requests\SearchRequest;

class IconifyIconSearchController
{
    public function __construct(
        protected SearchManager $searchManager
    ) {}

    public function index(SearchRequest $request): JsonResponse
    {
        /** @var SearchDriver $driver */
        $driver = $this->searchManager->driver();

        if ($request->has('prefixes')) {
            $driver->prefixes(explode(',', $request->string('prefixes')));
        }

        if ($request->has('category')) {
            $driver->category($request->string('category'));
        }

        if ($request->has('page')) {
            $driver->page($request->integer('page'));
        }

        if ($request->has('limit')) {
            $driver->limit($request->integer('limit'));
        }

        if ($request->has('palette')) {
            $driver->palette($request->boolean('palette'));
        }

        if ($request->has('style')) {
            $driver->style($request->string('style'));
        }

        if ($request->has('similar')) {
            $driver->similar($request->boolean('similar'));
        }

        if ($request->has('tags')) {
            $driver->tags(explode(',', $request->string('tags')));
        }

        $icons = $driver->search($request->string('query'));

        return response()->json($icons);
    }
}
