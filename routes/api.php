<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/create_server', [ServerController::class, 'createServer'])->middleware('auth:sanctum');
Route::get('/get_server', [ServerController::class, 'getServers'])->middleware('auth:sanctum');
Route::get('/get_server/{subdomain}', [ServerController::class, 'getRequests'])
    ->middleware([\Illuminate\Http\Middleware\HandleCors::class, 'auth:sanctum']);

Route::post('/get_server/{subdomain}', [ServerController::class, 'createRequest'])
    ->middleware([\Illuminate\Http\Middleware\HandleCors::class, 'auth:sanctum']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
