<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServerController;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Dynamic subdomain routes for mock API servers
 * Matches: {subdomain}.ilusion.one/any/path
 * No /api prefix since these are in web.php
 */
Route::group([
    'domain' => '{subdomain}.ilusion.one'
], function () {
    Route::any('/{path?}', [ServerController::class, 'handleSubdomainRequest'])
        ->where('path', '.*')
        ->name('mock-api');
});
