<?php

namespace App\Http\Controllers;

use App\Models\HorarioMedicos;
use App\Models\Medicos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class HorarioMedicosController extends Controller
{
    /**
     * GET /api/v1/horarios-medicos
     * Listar todos los horarios (con filtros opcionales)
     */
    public function index(Request $request)
    {
        try {
            $query = HorarioMedicos::with('medico.user');

            // Filtros
            if ($request->has('medico_id')) {
                $query->where('medico_id', $request->medico_id);
            }

            if ($request->has('tipo')) {
                $query->where('tipo', $request->tipo);
            }

            if ($request->has('fecha')) {
                $query->where('fecha', $request->fecha);
            }

            if ($request->has('dia_semana')) {
                $query->where('dia_semana', $request->dia_semana);
            }

            if ($request->has('activo')) {
                $query->where('activo', $request->activo);
            }

            $perPage = $request->input('per_page', 15);
            $horarios = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 200,
                'message' => 'Horarios obtenidos exitosamente',
                'data' => $horarios->items(),
                'total' => $horarios->total(),
                'current_page' => $horarios->currentPage(),
                'last_page' => $horarios->lastPage()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener horarios',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * GET /api/v1/horarios-medicos/{id}
     * Obtener un horario específico
     */
    public function show($id)
    {
        try {
            $horario = HorarioMedicos::with('medico.user')->find($id);

            if (!$horario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Horario no encontrado'
                ], 200);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Horario obtenido exitosamente',
                'data' => $horario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener horario',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * POST /api/v1/utils/crear-horario-fecha
     * Crear horario para fecha específica
     */
    // ✅ CORREGIDO: El nombre coincide con lo definido en api.php
    public function crearHorarioFecha(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medico_id' => 'required|exists:medicos,id', // ✅ Confirmado: requiere medico_id
            'fecha' => 'required|date|after_or_equal:today',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita' => 'required|integer|min:5|max:120',
            'consultorio' => 'nullable|string|max:50', // ✅ AGREGADO: Validación para consultorio
            'cupo_maximo' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 200); // Mantenemos 200 si así lo maneja tu frontend, aunque 422 es estándar
        }

        try {
            // Verificar duplicados
            $existente = HorarioMedicos::where('medico_id', $request->medico_id)
                ->where('fecha', $request->fecha)
                ->where('tipo', 'fecha_especifica')
                ->first();

            if ($existente) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Ya existe un horario para este médico en esta fecha'
                ], 200);
            }

            $horario = HorarioMedicos::create([
                'medico_id' => $request->medico_id,
                'fecha' => $request->fecha,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_cita' => $request->duracion_cita,
                'consultorio' => $request->consultorio, // ✅ AGREGADO: Guardar consultorio
                'cupo_maximo' => $request->cupo_maximo,
                'tipo' => 'fecha_especifica',
                'activo' => true,
                'observaciones' => $request->observaciones
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Horario creado exitosamente',
                'data' => $horario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al crear horario',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * POST /api/v1/utils/crear-horario-recurrente
     * Crear horario recurrente
     */
    // ✅ CORREGIDO: El nombre coincide con api.php
    public function crearHorarioRecurrente(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medico_id' => 'required|exists:medicos,id',
            'dia_semana' => 'required|integer|min:1|max:7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'duracion_cita' => 'required|integer|min:5|max:120',
            'consultorio' => 'nullable|string|max:50', // ✅ AGREGADO
            'cupo_maximo' => 'nullable|integer|min:1',
            'observaciones' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 200);
        }

        try {
            $existente = HorarioMedicos::where('medico_id', $request->medico_id)
                ->where('dia_semana', $request->dia_semana)
                ->where('tipo', 'recurrente')
                ->where('activo', true)
                ->first();

            if ($existente) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Ya existe un horario recurrente activo para este día'
                ], 200);
            }

            $horario = HorarioMedicos::create([
                'medico_id' => $request->medico_id,
                'dia_semana' => $request->dia_semana,
                'hora_inicio' => $request->hora_inicio,
                'hora_fin' => $request->hora_fin,
                'duracion_cita' => $request->duracion_cita,
                'consultorio' => $request->consultorio, // ✅ AGREGADO
                'cupo_maximo' => $request->cupo_maximo,
                'tipo' => 'recurrente',
                'activo' => true,
                'observaciones' => $request->observaciones
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'Horario recurrente creado exitosamente',
                'data' => $horario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al crear horario recurrente',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * PUT /api/v1/horarios-medicos/{id}
     * Actualizar horario
     */
    public function update(Request $request, $id)
    {
        try {
            $horario = HorarioMedicos::find($id);

            if (!$horario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Horario no encontrado'
                ], 200);
            }

            $validator = Validator::make($request->all(), [
                'hora_inicio' => 'sometimes|date_format:H:i',
                'hora_fin' => 'sometimes|date_format:H:i|after:hora_inicio',
                'duracion_cita' => 'sometimes|integer|min:5|max:120',
                'cupo_maximo' => 'nullable|integer|min:1',
                'activo' => 'sometimes|boolean',
                'observaciones' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 200);
            }

            $horario->update($request->only([
                'hora_inicio',
                'hora_fin',
                'duracion_cita',
                'cupo_maximo',
                'activo',
                'observaciones'
            ]));

            return response()->json([
                'status' => 200,
                'message' => 'Horario actualizado exitosamente',
                'data' => $horario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al actualizar horario',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * DELETE /api/v1/horarios-medicos/{id}
     * Eliminar horario
     */
    public function destroy($id)
    {
        try {
            $horario = HorarioMedicos::find($id);

            if (!$horario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Horario no encontrado'
                ], 200);
            }

            $horario->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Horario eliminado exitosamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar horario',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * POST /api/v1/horarios-medicos/por-medico
     * Obtener horarios de un médico
     */
    public function porMedico(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medico_id' => 'required|exists:medicos,id',
            'fecha' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 200);
        }

        try {
            $query = HorarioMedicos::where('medico_id', $request->medico_id)
                ->where('activo', true);

            if ($request->has('fecha')) {
                $fecha = Carbon::parse($request->fecha);
                $diaSemana = $fecha->dayOfWeekIso; // 1=Lunes, 7=Domingo

                // Buscar horarios específicos para esa fecha O recurrentes para ese día
                $query->where(function ($q) use ($request, $diaSemana) {
                    $q->where(function ($sq) use ($request) {
                        $sq->where('tipo', 'fecha_especifica')
                            ->where('fecha', $request->fecha);
                    })->orWhere(function ($sq) use ($diaSemana) {
                        $sq->where('tipo', 'recurrente')
                            ->where('dia_semana', $diaSemana);
                    });
                });
            }

            $horarios = $query->get();

            return response()->json([
                'status' => 200,
                'message' => 'Horarios obtenidos exitosamente',
                'data' => $horarios
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener horarios',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * POST /api/v1/horarios-medicos/citas-disponibles
     * Obtener horarios de citas disponibles para un médico en una fecha
     */
    public function citasDisponibles(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'medico_id' => 'required|exists:medicos,id',
            'fecha' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 200);
        }

        try {
            $fecha = Carbon::parse($request->fecha);
            $diaSemana = $fecha->dayOfWeekIso;

            // Buscar horario para esa fecha
            $horario = HorarioMedicos::where('medico_id', $request->medico_id)
                ->where('activo', true)
                ->where(function ($q) use ($request, $diaSemana) {
                    $q->where(function ($sq) use ($request) {
                        $sq->where('tipo', 'fecha_especifica')
                            ->where('fecha', $request->fecha);
                    })->orWhere(function ($sq) use ($diaSemana) {
                        $sq->where('tipo', 'recurrente')
                            ->where('dia_semana', $diaSemana);
                    });
                })
                ->first();

            if (!$horario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No hay horario disponible para esta fecha',
                    'data' => []
                ], 200);
            }

            $citasDisponibles = $horario->generarHorariosCitas();

            return response()->json([
                'status' => 200,
                'message' => 'Citas disponibles obtenidas exitosamente',
                'data' => [
                    'horario' => $horario,
                    'citas' => $citasDisponibles,
                    'total_cupos' => count($citasDisponibles)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener citas disponibles',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * PATCH /api/v1/horarios-medicos/{id}/toggle-activo
     * Activar/Desactivar horario
     */
    public function toggleActivo($id)
    {
        try {
            $horario = HorarioMedicos::find($id);

            if (!$horario) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Horario no encontrado'
                ], 200);
            }

            $horario->activo = !$horario->activo;
            $horario->save();

            return response()->json([
                'status' => 200,
                'message' => 'Estado actualizado exitosamente',
                'data' => $horario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al cambiar estado',
                'errors' => $e->getMessage()
            ], 200);
        }
    }
}
