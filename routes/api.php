<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Requieren JWT)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/', [UsersController::class, 'getUsers']);
        Route::post('create', [UsersController::class, 'createUser']);
        Route::get('detail', [UsersController::class, 'detailUser']);
        Route::put('update', [UsersController::class, 'updateUser']);
        Route::patch('change-status', [UsersController::class, 'changeStatus']);
        Route::patch('change-password', [UsersController::class, 'changePassword']);
    });
});
