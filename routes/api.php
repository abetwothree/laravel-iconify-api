<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::group([
    'domain' => config()->string('iconify-api.route_domain', ''),
    'prefix' => Str::finish(config()->string('iconify-api.route_path'), '/').'api',
    'namespace' => 'AbeTwoThree\LaravelIconifyApi\Http\Controllers',
    'middleware' => config()->array('iconify-api.api_middleware', []),
], function (): void {
    Route::get('/{set}/icons.json', 'IconifyApiController@show')->name('iconify-api.set-icons-json.show');
    Route::get('/{set}.json', 'IconifyApiController@show')->name('iconify-api.set-json.show');
});
