<?php

use Illuminate\Support\Facades\Route;
use AbeTwoThree\LaravelIconifyApi\Facades\LaravelIconifyApi;

Route::group([
    'domain' => LaravelIconifyApi::domain(),
    'prefix' => LaravelIconifyApi::path(),
    'namespace' => 'AbeTwoThree\LaravelIconifyApi\Http\Controllers',
    'middleware' => config()->array('iconify-api.api_middleware', []),
], function (): void {
    Route::get('/{set}/icons.json', 'IconifyApiController@show')->name('iconify-api.set-icons-json.show');
    Route::get('/{set}.json', 'IconifyApiController@show')->name('iconify-api.set-json.show');
});
