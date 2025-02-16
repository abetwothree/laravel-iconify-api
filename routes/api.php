<?php

use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain' => LaravelIconifyApi::domain(),
    'prefix' => LaravelIconifyApi::path(),
    'namespace' => 'AbeTwoThree\LaravelIconifyApi\Http\Controllers',
    'middleware' => config()->array('iconify-api.api_middleware', []),
], function (): void {
    Route::get('/{set}/icons.json', 'IconifyIconsController@show')->name('iconify-api.set-icons-json.show');
    Route::get('/{set}.json', 'IconifyIconsController@show')->name('iconify-api.set-json.show');
    Route::get('/collections', 'IconifyCollectionsController@index')->name('iconify-api.collections.index');
    Route::get('/collection', 'IconifyCollectionsController@show')->name('iconify-api.collections.show');
    Route::get('/search', 'IconifyIconSearchController@index')->name('iconify-api.search.index');
});
