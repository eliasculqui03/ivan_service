<?php

namespace App\Http\Controllers;

use App\Models\Medicos;
use App\Models\HorarioMedico;
use App\Services\HorarioMedicoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UtilsController extends Controller
{
    protected HorarioMedicoService $horarioService;

    public function __construct(HorarioMedicoService $horarioService)
    {
        $this->horarioService = $horarioService;
    }

    // ==================== MÉDICOS POR ESPECIALIDAD ====================

    /**
     * Listar médicos por especialidad
     * POST /api/utils/medicos-por-especialidad
     */
    public function medicosPorEspecialidad(Request $request): JsonResponse
    {
        $request->validate([
            'especialidad_id' => 'required|integer|exists:especialidades,id',
            'solo_activos' => 'nullable|boolean',
        ]);

        $query = Medicos::with(['user', 'especialidad'])
            ->where('especialidad_id', $request->especialidad_id);

        if ($request->input('solo_activos', true)) {
            $query->where('status', true);
        }

        $medicos = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $medicos->map(function($medico) {
                return [
                    'id' => $medico->id,
                    'user_id' => $medico->user_id,
                    'nombre_completo' => $medico->nombre_completo,
                    'numero_colegiatura' => $medico->numero_colegiatura,
                    'telefono' => $medico->telefono,
                    'especialidad' => [
                        'id' => $medico->especialidad->id,
                        'nombre' => $medico->especialidad->nombre,
                    ],
                    'status' => $medico->status,
                ];
            }),
        ]);
    }

    // ==================== HORARIOS DE MÉDICOS ====================

    /**
     * Obtener horarios de un médico
     * POST /api/utils/horarios-medico
     */
    public function horariosMedico(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'dia_semana' => 'nullable|integer|min:1|max:7',
        ]);

        if ($request->has('dia_semana')) {
            $horarios = $this->horarioService->getHorariosPorMedicoYDia(
                $request->medico_id,
                $request->dia_semana
            );
        } else {
            $horarios = $this->horarioService->getHorariosPorMedico($request->medico_id);
        }

        return response()->json([
            'success' => true,
            'data' => $horarios->map(function($horario) {
                return [
                    'id' => $horario->id,
                    'dia_semana' => $horario->dia_semana,
                    'dia_nombre' => $horario->dia_nombre,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'horario_formateado' => $horario->horario_formateado,
                    'duracion_cita' => $horario->duracion_cita,
                    'cupo_maximo' => $horario->cupo_maximo,
                    'cupos_disponibles' => $horario->calcularCuposDisponibles(),
                    'activo' => $horario->activo,
                ];
            }),
        ]);
    }

    /**
     * Crear horario de médico
     * POST /api/utils/crear-horario
     */
    public function crearHorario(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita' => 'nullable|integer|min:5|max:120',
            'cupo_maximo' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $horario = $this->horarioService->create([
                'medico_id' => $request->medico_id,
                'dia_semana' => $request->dia_semana,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_cita' => $request->input('duracion_cita', 30),
                'cupo_maximo' => $request->cupo_maximo,
                'activo' => true,
                'observaciones' => $request->observaciones,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => $horario,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener citas disponibles para un médico en una fecha
     * POST /api/utils/citas-disponibles
     */
    public function citasDisponibles(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'fecha' => 'required|date',
        ]);

        $citas = $this->horarioService->getCitasDisponibles(
            $request->medico_id,
            $request->fecha
        );

        return response()->json([
            'success' => true,
            'data' => $citas,
        ]);
    }

    // ==================== TIPOS DE ATENCIÓN ====================

    /**
     * Obtener tipos de atención disponibles
     * POST /api/utils/tipos-atencion
     */
    public function tiposAtencion(): JsonResponse
    {
        $tipos = [
            'Consulta Externa',
            'Emergencia',
            'Hospitalización',
            'Cirugía',
            'Procedimiento',
            'Control',
        ];

        return response()->json([
            'success' => true,
            'data' => $tipos,
        ]);
    }

    // ==================== TIPOS DE COBERTURA ====================

    /**
     * Obtener tipos de cobertura disponibles
     * POST /api/utils/tipos-cobertura
     */
    public function tiposCobertura(): JsonResponse
    {
        $tipos = [
            ['value' => 'SIS', 'label' => 'SIS (Seguro Integral de Salud)'],
            ['value' => 'EsSalud', 'label' => 'EsSalud'],
            ['value' => 'Privado', 'label' => 'Seguro Privado'],
            ['value' => 'Particular', 'label' => 'Particular (Sin seguro)'],
        ];

        return response()->json([
            'success' => true,
            'data' => $tipos,
        ]);
    }

    // ==================== ESTADOS DE ATENCIÓN ====================

    /**
     * Obtener estados de atención disponibles
     * POST /api/utils/estados-atencion
     */
    public function estadosAtencion(): JsonResponse
    {
        $estados = [
            'Programada',
            'En Espera',
            'En Atención',
            'Atendida',
            'Cancelada',
            'No Asistió',
        ];

        return response()->json([
            'success' => true,
            'data' => $estados,
        ]);
    }

    // ==================== GENERADORES AUTOMÁTICOS ====================

    /**
     * Generar número de historia clínica
     * POST /api/utils/generar-numero-historia
     */
    public function generarNumeroHistoria(): JsonResponse
    {
        $ultimo = DB::table('pacientes')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $numeroHistoria = 'HC' . str_pad($numero, 8, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => [
                'numero_historia' => $numeroHistoria,
            ],
        ]);
    }

    /**
     * Generar número de atención
     * POST /api/utils/generar-numero-atencion
     */
    public function generarNumeroAtencion(): JsonResponse
    {
        $ultimo = DB::table('atenciones')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $fecha = now()->format('Ymd');
        $numeroAtencion = "AT{$fecha}" . str_pad($numero, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => [
                'numero_atencion' => $numeroAtencion,
            ],
        ]);
    }

    /**
     * Verificar disponibilidad de número de historia
     * POST /api/utils/verificar-numero-historia
     */
    public function verificarNumeroHistoria(Request $request): JsonResponse
    {
        $request->validate([
            'numero_historia' => 'required|string',
        ]);

        $existe = DB::table('pacientes')
            ->where('numero_historia', $request->numero_historia)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'existe' => $existe,
                'disponible' => !$existe,
            ],
        ]);
    }

    // ==================== CATÁLOGOS ADICIONALES ====================

    /**
     * Obtener lista de especialidades activas
     * POST /api/utils/especialidades
     */
    public function especialidades(): JsonResponse
    {
        $especialidades = DB::table('especialidades')
            ->where('status', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        return response()->json([
            'success' => true,
            'data' => $especialidades,
        ]);
    }

    /**
     * Obtener tipos de sangre
     * POST /api/utils/tipos-sangre
     */
    public function tiposSangre(): JsonResponse
    {
        $tipos = [
            'A+', 'A-',
            'B+', 'B-',
            'AB+', 'AB-',
            'O+', 'O-',
        ];

        return response()->json([
            'success' => true,
            'data' => $tipos,
        ]);
    }

    /**
     * Obtener tipos de documento
     * POST /api/utils/tipos-documento
     */
    public function tiposDocumento(): JsonResponse
    {
        $tipos = [
            ['value' => 'DNI', 'label' => 'DNI'],
            ['value' => 'CE', 'label' => 'Carné de Extranjería'],
            ['value' => 'Pasaporte', 'label' => 'Pasaporte'],
            ['value' => 'Otro', 'label' => 'Otro'],
        ];

        return response()->json([
            'success' => true,
            'data' => $tipos,
        ]);
    }
}