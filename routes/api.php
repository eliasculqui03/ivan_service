<?php

use App\Http\Controllers\AtencionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsultaExternaController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/validate-token', [AuthController::class, 'validateToken']);
    });

    Route::post('/users/create', [UsersController::class, 'createUser']);

    Route::middleware('jwt')->group(function () {
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
