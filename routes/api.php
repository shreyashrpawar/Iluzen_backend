<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\NoauthDatabase;

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
Route::post('/connect_remote_database', [DatabaseController::class, 'connectRemoteDatabase'])->middleware('auth:sanctum');
Route::get('/get_database/{database}', [DatabaseController::class, 'getTable'])->middleware('auth:sanctum');
Route::get('/get_databases', [DatabaseController::class, 'getDatabases'])->middleware('auth:sanctum');
Route::post('/create_table/{subdomain}', [DatabaseController::class, 'createTable'])->middleware('auth:sanctum');

//For non-auth page
Route::post('/connect-remote-db', [NoauthDatabase::class, 'connectRemoteDatabaseNoAuth']);
Route::post('get-remote-table-data',[NoauthDatabase::class, 'getRemoteTableDataNoAuth']);


Route::post('/delete_table/{database}', [DatabaseController::class, 'deleteTable'])->middleware('auth:sanctum');
Route::get('/get_table_columns/{database}/{table}', [DatabaseController::class, 'getTableColumns'])->middleware('auth:sanctum');

    // Data management
    Route::post('/insert_data/{database}/{table}', [DatabaseController::class, 'insertData'])->middleware('auth:sanctum');
    Route::get('/get_table_data/{database}/{table}', [DatabaseController::class, 'getTableData'])->middleware('auth:sanctum');
    Route::post('/delete_data/{database}/{table}', [DatabaseController::class, 'deleteData'])->middleware('auth:sanctum');


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
