<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\DatabaseController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/create_server', [ServerController::class, 'createServer'])->middleware('auth:sanctum');
Route::post('/delete_server', [ServerController::class, 'deleteServer'])->middleware('auth:sanctum');
Route::get('/get_server', [ServerController::class, 'getServers'])->middleware('auth:sanctum');
Route::get('/get_server/{subdomain}', [ServerController::class, 'getRequests'])->middleware('auth:sanctum');
Route::post('/get_server/{subdomain}', [ServerController::class, 'createRequest'])->middleware('auth:sanctum');
Route::post('/delete_request/{subdomain}', [ServerController::class, 'deleteRequests'])->middleware('auth:sanctum');

Route::post('/create_database', [DatabaseController::class, 'createDatabase'])->middleware('auth:sanctum');
Route::get('/get_databases', [DatabaseController::class, 'getDatabases'])->middleware('auth:sanctum');
Route::get('/get_databases/{database}', [DatabaseController::class, 'getTable'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
