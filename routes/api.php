<?php

use App\Http\Controllers\ArchivoAdjuntoController;
use App\Http\Controllers\AtencionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CirugiaController;
use App\Http\Controllers\ConsultaExternaController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\ExamenLaboratorioController;
use App\Http\Controllers\HorarioMedicosController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\RoleController;

use App\Http\Controllers\UsersController;
use App\Http\Controllers\UtilsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/validate-token', [AuthController::class, 'validateToken']);
    });

    Route::post('/users/create', [UsersController::class, 'createUser']);

    Route::middleware('auth:api')->group(function () {
        // Auth
        Route::prefix('auth')->group(function () {
            Route::post('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
        });

        // Users
        Route::prefix('users')->group(function () {
            Route::get('/', [UsersController::class, 'index']);
            Route::post('/', [UsersController::class, 'store']);
            Route::get('/stats/general', [UsersController::class, 'estadisticas']);
            Route::get('/{id}', [UsersController::class, 'show']);
            Route::put('/{id}', [UsersController::class, 'update']);
            Route::patch('/{id}', [UsersController::class, 'update']);
            Route::delete('/{id}', [UsersController::class, 'destroy']);
            Route::post('/{id}/toggle-status', [UsersController::class, 'toggleStatus']);
            Route::post('/{id}/change-password', [UsersController::class, 'changePassword']);
        });
        // ==================== ROLES ====================
        Route::prefix('roles')->group(function () {
            Route::get('/', [RoleController::class, 'index']);
            Route::post('/', [RoleController::class, 'store']);
            Route::get('/activos', [RoleController::class, 'activos']);
            Route::get('/stats/general', [RoleController::class, 'estadisticas']);
            Route::get('/{id}', [RoleController::class, 'show']);
            Route::put('/{id}', [RoleController::class, 'update']);
            Route::patch('/{id}', [RoleController::class, 'update']);
            Route::delete('/{id}', [RoleController::class, 'destroy']);
            Route::patch('/{id}/toggle-status', [RoleController::class, 'toggleStatus']);
            Route::get('/{id}/users', [RoleController::class, 'getUsersByRole']);
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
            Route::post('/por-fecha', [AtencionController::class, 'porFecha']);
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
        // ... dentro del grupo medicos ...

        Route::prefix('medicos')->group(function () {
            // 1. Rutas Generales y Específicas (PRIMERO)
            Route::get('/', [MedicoController::class, 'index']); // Listar
            Route::post('/', [MedicoController::class, 'store']); // Crear

            // Estas deben ir ANTES de /{id} para que no se confundan con un ID
            Route::get('/activos/list', [MedicoController::class, 'activos']);
            Route::get('/search/query', [MedicoController::class, 'search']);
            Route::get('/stats/general', [MedicoController::class, 'estadisticas']);
            Route::get('/trashed/list', [MedicoController::class, 'trashed']);
            // Especialidad también es específica
            Route::get('/especialidad/{especialidadId}/list', [MedicoController::class, 'porEspecialidad']);

            // 2. Rutas Dinámicas con ID (AL FINAL)
            Route::get('/{id}', [MedicoController::class, 'show']); // Ver uno
            Route::put('/{id}', [MedicoController::class, 'update']); // Actualizar
            Route::patch('/{id}', [MedicoController::class, 'update']); // Actualizar parcial
            Route::delete('/{id}', [MedicoController::class, 'destroy']); // Eliminar

            // Acciones sobre un ID específico
            Route::patch('/{id}/toggle-status', [MedicoController::class, 'toggleStatus']);
            Route::post('/{id}/restore', [MedicoController::class, 'restore']);
            Route::post('/{id}/change-password', [MedicoController::class, 'changePassword']);
        });
        Route::prefix('horarios-medicos')->group(function () {
            // Listar horarios
            // GET /api/v1/horarios-medicos?medico_id=1&tipo=recurrente&fecha=2026-02-15
            Route::get('/', [HorarioMedicosController::class, 'index']);
            // Ver un horario específico
            // GET /api/v1/horarios-medicos/1
            Route::get('/{id}', [HorarioMedicosController::class, 'show']);
            // Crear horario para fecha específica
            // POST /api/v1/horarios-medicos/fecha-especifica
            Route::post('/fecha-especifica', [HorarioMedicosController::class, 'crearFechaEspecifica']);
            // Crear horario recurrente
            // POST /api/v1/horarios-medicos/recurrente
            Route::post('/recurrente', [HorarioMedicosController::class, 'crearRecurrente']);
            // Actualizar horario
            // PUT /api/v1/horarios-medicos/1
            Route::put('/{id}', [HorarioMedicosController::class, 'update']);
            // Eliminar horario
            // DELETE /api/v1/horarios-medicos/1
            Route::delete('/{id}', [HorarioMedicosController::class, 'destroy']);
            // Obtener horarios de un médico
            // POST /api/v1/horarios-medicos/por-medico
            Route::post('/por-medico', [HorarioMedicosController::class, 'porMedico']);
            // Obtener citas disponibles
            // POST /api/v1/horarios-medicos/citas-disponibles
            Route::post('/citas-disponibles', [HorarioMedicosController::class, 'citasDisponibles']);
            // Activar/Desactivar horario
            // PATCH /api/v1/horarios-medicos/1/toggle-activo
            Route::patch('/{id}/toggle-activo', [HorarioMedicosController::class, 'toggleActivo']);
        });


        Route::prefix('consultas-externas')->group(function () {
            // GET /api/v1/consultas-externas/stats/general
            Route::get('/stats/general', [ConsultaExternaController::class, 'estadisticas']);
            // GET /api/v1/consultas-externas/search/diagnostico?q=liposuccion
            Route::get('/search/diagnostico', [ConsultaExternaController::class, 'buscarDiagnostico']);
            // Consultas eliminadas
            // GET /api/v1/consultas-externas/trashed/list
            Route::get('/trashed/list', [ConsultaExternaController::class, 'trashed']);
            // Obtener consulta por atención (CRÍTICO - usado en frontend)
            // GET /api/v1/consultas-externas/atencion/107
            Route::get('/atencion/{atencionId}', [ConsultaExternaController::class, 'getByAtencion']);
            // Historial completo de un paciente
            // GET /api/v1/consultas-externas/paciente/63/historial
            Route::get('/paciente/{pacienteId}/historial', [ConsultaExternaController::class, 'historial']);
            // Listar todas (paginado con filtros)
            // GET /api/v1/consultas-externas?page=1&medico_id=21&ficha_completada=1
            Route::get('/', [ConsultaExternaController::class, 'index']);
            // Crear nueva consulta
            // POST /api/v1/consultas-externas
            Route::post('/', [ConsultaExternaController::class, 'store']);
            // Ver una consulta específica
            // GET /api/v1/consultas-externas/1
            Route::get('/{id}', [ConsultaExternaController::class, 'show']);
            // Actualizar consulta
            // PUT/PATCH /api/v1/consultas-externas/1
            Route::put('/{id}', [ConsultaExternaController::class, 'update']);
            Route::patch('/{id}', [ConsultaExternaController::class, 'update']);
            // Eliminar (soft delete)
            // DELETE /api/v1/consultas-externas/1
            Route::delete('/{id}', [ConsultaExternaController::class, 'destroy']);
            // Completar y firmar consulta
            // POST /api/v1/consultas-externas/1/completar
            Route::post('/{id}/completar', [ConsultaExternaController::class, 'completar']);
            // Guardar como borrador
            // POST /api/v1/consultas-externas/1/borrador
            Route::post('/{id}/borrador', [ConsultaExternaController::class, 'borrador']);
            // Registrar firma de consentimiento
            // POST /api/v1/consultas-externas/1/firmar-consentimiento
            Route::post('/{id}/firmar-consentimiento', [ConsultaExternaController::class, 'firmarConsentimiento']);
            // Calcular IMC automáticamente
            // POST /api/v1/consultas-externas/1/calcular-imc
            Route::post('/{id}/calcular-imc', [ConsultaExternaController::class, 'calcularIMC']);
            // Obtener resumen
            // GET /api/v1/consultas-externas/1/resumen
            Route::get('/{id}/resumen', [ConsultaExternaController::class, 'resumen']);
            // ULTIMA CONSUTLA   
            Route::get('/paciente/{pacienteId}/ultima', [ConsultaExternaController::class, 'ultimaConsulta']);
            // Restaurar eliminada
            // POST /api/v1/consultas-externas/1/restore
            Route::post('/{id}/restore', [ConsultaExternaController::class, 'restore']);
        });
        Route::prefix('utils')->middleware('auth:api')->group(function () {
            // ==================== MÉDICOS ====================
            Route::post('/medicos-por-especialidad', [UtilsController::class, 'medicosPorEspecialidad']);
            // ==================== HORARIOS ====================
            Route::post('/crear-horario-fecha', [UtilsController::class, 'crearHorarioFecha']);
            Route::post('/crear-horario-recurrente', [UtilsController::class, 'crearHorarioRecurrente']);
            Route::post('/horarios-medico', [UtilsController::class, 'horariosMedico']);
            Route::post('/citas-disponibles', [UtilsController::class, 'citasDisponibles']);
            // ==================== CATÁLOGOS ====================
            Route::post('/tipos-atencion', [UtilsController::class, 'tiposAtencion']);
            Route::post('/tipos-cobertura', [UtilsController::class, 'tiposCobertura']);
            Route::post('/estados-atencion', [UtilsController::class, 'estadosAtencion']);
            Route::post('/especialidades', [UtilsController::class, 'especialidades']);
            // ==================== GENERADORES ====================
            Route::post('/generar-numero-historia', [UtilsController::class, 'generarNumeroHistoria']);
            Route::post('/generar-numero-atencion', [UtilsController::class, 'generarNumeroAtencion']);
        });
        Route::prefix('archivos')->middleware('auth:api')->group(function () {
            Route::get('/', [ArchivoAdjuntoController::class, 'index']); // Listar específicos
            Route::post('/upload', [ArchivoAdjuntoController::class, 'store']); // Subir
            Route::delete('/{id}', [ArchivoAdjuntoController::class, 'destroy']); // Eliminar

        });
        Route::get('/pacientes/{id}/galeria', [ArchivoAdjuntoController::class, 'getGaleriaPaciente']);
        Route::post('/pacientes/consulta-dni-externo', [PacienteController::class, 'consultarDniExterno']);
    });
});
