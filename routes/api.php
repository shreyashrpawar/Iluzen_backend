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
Route::domain('app.localhost')->group(function () {
    Route::get('/', function () {
        return 'Welcome to the APP frontend!';
    });
});


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
