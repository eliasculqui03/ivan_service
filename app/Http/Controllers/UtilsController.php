<?php

namespace App\Http\Controllers;

use App\Models\Medicos;
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
            'data' => $medicos->map(function ($medico) {
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

    // ==================== HORARIOS ====================

    /**
     * OPCIÓN 1: Crear horario para FECHA ESPECÍFICA
     * POST /api/utils/crear-horario-fecha
     * 
     * Ejemplo: 24-01-2026, 8:00 AM - 12:00 PM, 20 min por paciente
     */
    public function crearHorarioFecha(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita' => 'required|integer|min:5|max:120',
            'cupo_maximo' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $horario = $this->horarioService->crearHorarioFechaEspecifica([
                'medico_id' => $request->medico_id,
                'fecha' => $request->fecha,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_cita' => $request->duracion_cita,
                'cupo_maximo' => $request->cupo_maximo,
                'observaciones' => $request->observaciones,
            ]);

            // Generar lista de horarios disponibles
            $horariosGenerados = $horario->generarHorariosCitas();

            return response()->json([
                'success' => true,
                'message' => 'Horario creado exitosamente',
                'data' => [
                    'horario' => $horario,
                    'total_citas' => count($horariosGenerados),
                    'horarios_disponibles' => $horariosGenerados,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * OPCIÓN 2: Crear horario RECURRENTE (semanal)
     * POST /api/utils/crear-horario-recurrente
     * 
     * Ejemplo: Todos los Lunes, 8:00 AM - 12:00 PM, 30 min
     */
    public function crearHorarioRecurrente(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita' => 'required|integer|min:5|max:120',
            'cupo_maximo' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string',
        ]);

        try {
            $horario = $this->horarioService->crearHorarioRecurrente([
                'medico_id' => $request->medico_id,
                'dia_semana' => $request->dia_semana,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_cita' => $request->duracion_cita,
                'cupo_maximo' => $request->cupo_maximo,
                'observaciones' => $request->observaciones,
            ]);

            $horariosGenerados = $horario->generarHorariosCitas();

            return response()->json([
                'success' => true,
                'message' => 'Horario recurrente creado exitosamente',
                'data' => [
                    'horario' => $horario,
                    'total_citas' => count($horariosGenerados),
                    'horarios_disponibles' => $horariosGenerados,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Listar horarios de un médico
     * POST /api/utils/horarios-medico
     */
    public function horariosMedico(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer|exists:medicos,id',
            'tipo' => 'nullable|in:fecha_especifica,recurrente',
        ]);

        $horarios = $this->horarioService->getHorariosPorMedico(
            $request->medico_id,
            $request->tipo
        );

        return response()->json([
            'success' => true,
            'data' => $horarios->map(function ($horario) {
                return [
                    'id' => $horario->id,
                    'tipo' => $horario->tipo,
                    'fecha' => $horario->fecha_formateada,
                    'dia_semana' => $horario->dia_semana,
                    'dia_nombre' => $horario->dia_nombre,
                    'hora_inicio' => $horario->hora_inicio,
                    'hora_fin' => $horario->hora_fin,
                    'horario_formateado' => $horario->horario_formateado,
                    'duracion_cita' => $horario->duracion_cita,
                    'cupo_maximo' => $horario->cupo_maximo,
                    'cupos_calculados' => $horario->calcularCuposDisponibles(),
                    'activo' => $horario->activo,
                    'observaciones' => $horario->observaciones,
                ];
            }),
        ]);
    }

    /**
     * Obtener citas disponibles para un médico en una fecha
     * POST /api/utils/citas-disponibles
     * 
     * Prioridad:
     * 1. Horarios de fecha específica
     * 2. Horarios recurrentes
     */
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

        // El servicio ya devuelve SOLO las citas disponibles (array limpio)
        $citasDisponibles = $this->horarioService->getCitasDisponibles(
            $request->medico_id,
            $request->fecha
        );

        return response()->json([
            'success' => true,
            'data' => [
                'fecha' => $request->fecha,
                // Como ya están filtradas, el total es igual a las disponibles
                'total_horarios' => count($citasDisponibles),
                'disponibles' => count($citasDisponibles),
                'ocupados' => 0, // El servicio ya eliminó las ocupadas
                'horarios' => $citasDisponibles, // Esta es la lista que usa tu frontend
            ],
        ]);
    }

    // ==================== CATÁLOGOS ====================

    /**
     * Tipos de atención
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

        return response()->json(['success' => true, 'data' => $tipos]);
    }

    /**
     * Tipos de cobertura
     */
    public function tiposCobertura(): JsonResponse
    {
        $tipos = [
            ['value' => 'SIS', 'label' => 'SIS (Seguro Integral de Salud)'],
            ['value' => 'EsSalud', 'label' => 'EsSalud'],
            ['value' => 'Privado', 'label' => 'Seguro Privado'],
            ['value' => 'Particular', 'label' => 'Particular (Sin seguro)'],
        ];

        return response()->json(['success' => true, 'data' => $tipos]);
    }

    /**
     * Estados de atención
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

        return response()->json(['success' => true, 'data' => $estados]);
    }

    // ==================== GENERADORES ====================

    /**
     * Generar número de historia clínica
     */
    public function generarNumeroHistoria(): JsonResponse
    {
        $ultimo = DB::table('pacientes')->orderBy('id', 'desc')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $numeroHistoria = 'HC' . str_pad($numero, 8, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => ['numero_historia' => $numeroHistoria],
        ]);
    }

    /**
     * Generar número de atención
     */
    public function generarNumeroAtencion(): JsonResponse
    {
        $ultimo = DB::table('atenciones')->orderBy('id', 'desc')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $fecha = now()->format('Ymd');
        $numeroAtencion = "AT{$fecha}" . str_pad($numero, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'success' => true,
            'data' => ['numero_atencion' => $numeroAtencion],
        ]);
    }

    /**
     * Especialidades activas
     */
    public function especialidades(): JsonResponse
    {
        $especialidades = DB::table('especialidades')
            ->where('status', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        return response()->json(['success' => true, 'data' => $especialidades]);
    }
}
