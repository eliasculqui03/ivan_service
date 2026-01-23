<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultaExternaController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\MedicoController;
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


    Route::prefix('medicos')->group(function () {
        // Listar todos los médicos (paginado)
        // GET /api/medicos?page=1&per_page=15&search=juan&status=1&especialidad_id=1
        Route::get('/', [MedicoController::class, 'index']);
        // Crear nuevo médico
        // POST /api/medicos
        Route::post('/', [MedicoController::class, 'store']);
        // Ver un médico específico
        // GET /api/medicos/1
        Route::get('/{id}', [MedicoController::class, 'show']);
        // Actualizar médico
        // PUT/PATCH /api/medicos/1
        Route::put('/{id}', [MedicoController::class, 'update']);
        Route::patch('/{id}', [MedicoController::class, 'update']);
        // Eliminar médico (soft delete)
        // DELETE /api/medicos/1
        Route::delete('/{id}', [MedicoController::class, 'destroy']);
        // ==================== RUTAS ADICIONALES ====================
        // Obtener solo médicos activos (sin paginación)
        // GET /api/medicos/activos/list?especialidad_id=1
        Route::get('/activos/list', [MedicoController::class, 'activos']);
        // Buscar médicos
        // GET /api/medicos/search/query?q=juan
        Route::get('/search/query', [MedicoController::class, 'search']);
        // Cambiar estado (activar/desactivar)
        // PATCH /api/medicos/1/toggle-status
        Route::patch('/{id}/toggle-status', [MedicoController::class, 'toggleStatus']);
        // Obtener estadísticas
        // GET /api/medicos/stats/general
        Route::get('/stats/general', [MedicoController::class, 'estadisticas']);
        // Obtener médicos por especialidad
        // GET /api/medicos/especialidad/1/list
        Route::get('/especialidad/{especialidadId}/list', [MedicoController::class, 'porEspecialidad']);
        // Obtener eliminados
        // GET /api/medicos/trashed/list
        Route::get('/trashed/list', [MedicoController::class, 'trashed']);
        // Restaurar eliminado
        // POST /api/medicos/1/restore
        Route::post('/{id}/restore', [MedicoController::class, 'restore']);
        // Cambiar contraseña
        // POST /api/medicos/1/change-password
        Route::post('/{id}/change-password', [MedicoController::class, 'changePassword']);
    });

    Route::prefix('consultas-externas')->group(function () {
        // Listar todas las consultas externas (paginado)
        // GET /api/consultas-externas?medico_id=1&paciente_id=1&ficha_completada=1
        Route::get('/', [ConsultaExternaController::class, 'index']);
        // Crear nueva consulta externa
        // POST /api/consultas-externas
        Route::post('/', [ConsultaExternaController::class, 'store']);
        // Ver una consulta externa específica
        // GET /api/consultas-externas/1
        Route::get('/{id}', [ConsultaExternaController::class, 'show']);
        // Actualizar consulta externa
        // PUT/PATCH /api/consultas-externas/1
        Route::put('/{id}', [ConsultaExternaController::class, 'update']);
        Route::patch('/{id}', [ConsultaExternaController::class, 'update']);
        // Eliminar consulta externa (soft delete)
        // DELETE /api/consultas-externas/1
        Route::delete('/{id}', [ConsultaExternaController::class, 'destroy']);
        // Obtener consulta por atención
        // GET /api/consultas-externas/atencion/1
        Route::get('/atencion/{atencionId}', [ConsultaExternaController::class, 'getByAtencion']);
        // Completar y firmar consulta
        // POST /api/consultas-externas/1/completar
        Route::post('/{id}/completar', [ConsultaExternaController::class, 'completar']);
        // Guardar como borrador
        // POST /api/consultas-externas/1/borrador
        Route::post('/{id}/borrador', [ConsultaExternaController::class, 'borrador']);
        // Obtener resumen
        // GET /api/consultas-externas/1/resumen
        Route::get('/{id}/resumen', [ConsultaExternaController::class, 'resumen']);
        // Obtener estadísticas
        // GET /api/consultas-externas/stats/general
        Route::get('/stats/general', [ConsultaExternaController::class, 'estadisticas']);
        // Historial de paciente
        // GET /api/consultas-externas/paciente/1/historial
        Route::get('/paciente/{pacienteId}/historial', [ConsultaExternaController::class, 'historial']);
        // Buscar por diagnóstico
        // GET /api/consultas-externas/search/diagnostico?q=diabetes
        Route::get('/search/diagnostico', [ConsultaExternaController::class, 'buscarDiagnostico']);
        // Obtener eliminadas
        // GET /api/consultas-externas/trashed/list
        Route::get('/trashed/list', [ConsultaExternaController::class, 'trashed']);
        // Restaurar eliminada
        // POST /api/consultas-externas/1/restore
        Route::post('/{id}/restore', [ConsultaExternaController::class, 'restore']);
    });
});
