<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EspecialidadController;
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
    Route::prefix('especialidades')->group(function () {
        // Listar todas las especialidades (paginado)
        // GET /api/especialidades?page=1&per_page=15&search=cirugía&status=1
        Route::get('/', [EspecialidadController::class, 'index']);
        // Crear nueva especialidad
        // POST /api/especialidades
        Route::post('/', [EspecialidadController::class, 'store']);
        // Ver una especialidad específica
        // GET /api/especialidades/1
        Route::get('/{id}', [EspecialidadController::class, 'show']);
        // Actualizar especialidad
        // PUT/PATCH /api/especialidades/1
        Route::put('/{id}', [EspecialidadController::class, 'update']);
        Route::patch('/{id}', [EspecialidadController::class, 'update']);
        // Eliminar especialidad (soft delete)
        // DELETE /api/especialidades/1
        Route::delete('/{id}', [EspecialidadController::class, 'destroy']);
        // Obtener solo especialidades activas (sin paginación)
        // GET /api/especialidades/activas
        Route::get('/activas/list', [EspecialidadController::class, 'activas']);
        // Buscar especialidades
        // GET /api/especialidades/search?q=cardio
        Route::get('/search/query', [EspecialidadController::class, 'search']);
        // Cambiar estado (activar/desactivar)
        // PATCH /api/especialidades/1/toggle-status
        Route::patch('/{id}/toggle-status', [EspecialidadController::class, 'toggleStatus']);
        // Obtener estadísticas
        // GET /api/especialidades/stats/general
        Route::get('/stats/general', [EspecialidadController::class, 'estadisticas']);
        // Top especialidades (más médicos)
        // GET /api/especialidades/top/list?limit=10
        Route::get('/top/list', [EspecialidadController::class, 'top']);
        // Obtener eliminadas
        // GET /api/especialidades/trashed/list
        Route::get('/trashed/list', [EspecialidadController::class, 'trashed']);
        // Restaurar eliminada
        // POST /api/especialidades/1/restore
        Route::post('/{id}/restore', [EspecialidadController::class, 'restore']);
    });
});
