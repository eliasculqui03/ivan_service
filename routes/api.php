<?php

use App\Http\Controllers\AtencionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CirugiaController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\ExamenLaboratorioController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/validate-token', [AuthController::class, 'validateToken']);
    });

    Route::post('/users/create', [UsersController::class, 'createUser']);

    Route::middleware('jwt')->group(function () {

        Route::prefix('resource')->group(function () {
            Route::post('get-roles', [ResourceController::class, 'getRoles']);
        });


        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // Users
        Route::prefix('users')->group(function () {
            Route::post('/', [UsersController::class, 'getUsers']);
            Route::post('/detail', [UsersController::class, 'detailUser']);
            Route::post('/update', [UsersController::class, 'updateUser']);
            Route::post('/change-status', [UsersController::class, 'changeStatus']);
            Route::post('/change-password', [UsersController::class, 'changePassword']);
        });

        Route::prefix('especialidades')->group(function () {

            Route::post('/', [EspecialidadController::class, 'index']);
            Route::post('/store', [EspecialidadController::class, 'store']);
            Route::post('/show', [EspecialidadController::class, 'show']);
            Route::post('/update', [EspecialidadController::class, 'update']);
            Route::post('/destroy', [EspecialidadController::class, 'destroy']);
            Route::post('/activas', [EspecialidadController::class, 'activas']);
            Route::post('/search', [EspecialidadController::class, 'search']);
            Route::post('/toggle-status', [EspecialidadController::class, 'toggleStatus']);
            Route::post('/stats', [EspecialidadController::class, 'estadisticas']);
            Route::post('/top', [EspecialidadController::class, 'top']);
            Route::post('/trashed', [EspecialidadController::class, 'trashed']);
            Route::post('/restore', [EspecialidadController::class, 'restore']);
        });

        // Pacientes
        Route::prefix('pacientes')->group(function () {
            Route::post('/', [PacienteController::class, 'index']);
            Route::post('/store', [PacienteController::class, 'store']);
            Route::post('/show', [PacienteController::class, 'show']);
            Route::post('/update', [PacienteController::class, 'update']);
            Route::post('/destroy', [PacienteController::class, 'destroy']);
            Route::post('/activos', [PacienteController::class, 'activos']);
            Route::post('/search', [PacienteController::class, 'search']);
            Route::post('/toggle-status', [PacienteController::class, 'toggleStatus']);
            Route::post('/stats', [PacienteController::class, 'estadisticas']);
            Route::post('/por-documento', [PacienteController::class, 'porDocumento']);
            Route::post('/por-historia', [PacienteController::class, 'porHistoria']);
            Route::post('/historial', [PacienteController::class, 'historial']);
            Route::post('/trashed', [PacienteController::class, 'trashed']);
            Route::post('/restore', [PacienteController::class, 'restore']);
        });

        // Atenciones (Visitas)
        Route::prefix('atenciones')->group(function () {
            Route::post('/', [AtencionController::class, 'index']);
            Route::post('/store', [AtencionController::class, 'store']);
            Route::post('/show', [AtencionController::class, 'show']);
            Route::post('/update', [AtencionController::class, 'update']);
            Route::post('/destroy', [AtencionController::class, 'destroy']);
            Route::post('/hoy', [AtencionController::class, 'hoy']);
            Route::post('/search', [AtencionController::class, 'search']);
            Route::post('/cambiar-estado', [AtencionController::class, 'cambiarEstado']);
            Route::post('/registrar-salida', [AtencionController::class, 'registrarSalida']);
            Route::post('/stats', [AtencionController::class, 'estadisticas']);
            Route::post('/por-paciente', [AtencionController::class, 'porPaciente']);
            Route::post('/por-medico', [AtencionController::class, 'porMedico']);
            Route::post('/agenda', [AtencionController::class, 'agenda']);
            Route::post('/trashed', [AtencionController::class, 'trashed']);
            Route::post('/restore', [AtencionController::class, 'restore']);
        });

        // Cirugías
        Route::prefix('cirugias')->group(function () {
            Route::post('/', [CirugiaController::class, 'index']);
            Route::post('/store', [CirugiaController::class, 'store']);
            Route::post('/show', [CirugiaController::class, 'show']);
            Route::post('/update', [CirugiaController::class, 'update']);
            Route::post('/destroy', [CirugiaController::class, 'destroy']);
            Route::post('/hoy', [CirugiaController::class, 'hoy']);
            Route::post('/search', [CirugiaController::class, 'search']);
            Route::post('/cambiar-estado', [CirugiaController::class, 'cambiarEstado']);
            Route::post('/stats', [CirugiaController::class, 'estadisticas']);
            Route::post('/por-paciente', [CirugiaController::class, 'porPaciente']);
            Route::post('/trashed', [CirugiaController::class, 'trashed']);
            Route::post('/restore', [CirugiaController::class, 'restore']);
        });

        // Exámenes de Laboratorio
        Route::prefix('examenes')->group(function () {
            Route::post('/', [ExamenLaboratorioController::class, 'index']);
            Route::post('/store', [ExamenLaboratorioController::class, 'store']);
            Route::post('/show', [ExamenLaboratorioController::class, 'show']);
            Route::post('/update', [ExamenLaboratorioController::class, 'update']);
            Route::post('/destroy', [ExamenLaboratorioController::class, 'destroy']);
            Route::post('/pendientes', [ExamenLaboratorioController::class, 'pendientes']);
            Route::post('/urgentes', [ExamenLaboratorioController::class, 'urgentes']);
            Route::post('/search', [ExamenLaboratorioController::class, 'search']);
            Route::post('/cambiar-estado', [ExamenLaboratorioController::class, 'cambiarEstado']);
            Route::post('/registrar-muestra', [ExamenLaboratorioController::class, 'registrarMuestra']);
            Route::post('/registrar-resultados', [ExamenLaboratorioController::class, 'registrarResultados']);
            Route::post('/validar', [ExamenLaboratorioController::class, 'validar']);
            Route::post('/stats', [ExamenLaboratorioController::class, 'estadisticas']);
            Route::post('/por-paciente', [ExamenLaboratorioController::class, 'porPaciente']);
            Route::post('/trashed', [ExamenLaboratorioController::class, 'trashed']);
            Route::post('/restore', [ExamenLaboratorioController::class, 'restore']);
        });
    });
});
