<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultaExternaRequest;
use App\Http\Requests\UpdateConsultaExternaRequest;
use App\Http\Resources\ConsultaExternaResource;
use App\Models\Atenciones;
use App\Models\ConsultaExterna;
use App\Services\ConsultaExternaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ConsultaExternaController extends Controller
{
    protected ConsultaExternaService $consultaService;

    public function __construct(ConsultaExternaService $consultaService)
    {
        $this->consultaService = $consultaService;
    }

    /**
     * Display a listing of the resource.
     * GET /api/consultas-externas
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'ficha_completada' => $request->input('ficha_completada'),
            'medico_id' => $request->input('medico_id'),
            'paciente_id' => $request->input('paciente_id'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);
        $consultas = $this->consultaService->getAllPaginated($filters, $perPage);

        return ConsultaExternaResource::collection($consultas);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/consultas-externas
     */
    public function store(StoreConsultaExternaRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try {
                // 1. Obtenemos TODOS los datos validados
                $allData = $request->validated();

                // 2. Buscamos la Atención y el Paciente
                $atencion = Atenciones::with('paciente')->findOrFail($allData['atencion_id']);

                // 3. ACTUALIZAR PACIENTE (Sacamos los datos sociales del request)
                if ($atencion->paciente) {
                    $atencion->paciente->update([
                        'cantidad_hijos'   => $allData['cantidad_hijos'] ?? $atencion->paciente->cantidad_hijos,
                        'ultimo_embarazo'  => $allData['ultimo_embarazo'] ?? $atencion->paciente->ultimo_embarazo,
                        'estado_civil'     => $allData['estado_civil'] ?? $atencion->paciente->estado_civil,
                        'ocupacion'        => $allData['ocupacion_actual'] ?? $atencion->paciente->ocupacion,
                        'direccion'        => $allData['direccion_consulta'] ?? $atencion->paciente->direccion,
                        'celular'          => $allData['telefono_consulta'] ?? $atencion->paciente->celular,
                    ]);
                }

                // 4. ACTUALIZAR MARKETING EN ATENCIÓN (Si viene el dato)
                if (!empty($allData['medio_captacion'])) {
                    $atencion->update([
                        'medio_captacion' => $allData['medio_captacion']
                    ]);
                }

                // 5. LIMPIAR DATOS PARA LA CONSULTA
                // Quitamos los campos que NO existen en la tabla 'consulta_externas'
                // para evitar el error "Column not found".
                $consultaData = Arr::except($allData, [
                    'cantidad_hijos',
                    'ultimo_embarazo',
                    'estado_civil',
                    'ocupacion_actual',
                    'direccion_consulta',
                    'telefono_consulta',
                    'medio_captacion' // Este va a Atenciones, no a Consultas
                ]);



                $consulta = ConsultaExterna::create($consultaData);

                // 7. ACTUALIZAR ESTADO DE LA ATENCIÓN
                if ($atencion->estado === 'Programada' || $atencion->estado === 'En Espera') {
                    $atencion->update(['estado' => 'En Atención']);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Consulta guardada correctamente',
                    'data' => $consulta
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar: ' . $e->getMessage(),
                    'trace' => $e->getTraceAsString() // Opcional para debug
                ], 500);
            }
        });
    }

    /**
     * Actualizar una consulta existente
     */
    public function update(StoreConsultaExternaRequest $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            try {
                $consulta = ConsultaExterna::with('atencion.paciente')->findOrFail($id);
                $allData = $request->validated();

                // 1. ACTUALIZAR PACIENTE
                if ($consulta->atencion && $consulta->atencion->paciente) {
                    $consulta->atencion->paciente->update([
                        'cantidad_hijos'   => $allData['cantidad_hijos'] ?? null,
                        'ultimo_embarazo'  => $allData['ultimo_embarazo'] ?? null,
                        'estado_civil'     => $allData['estado_civil'] ?? null,
                        'ocupacion'        => $allData['ocupacion_actual'] ?? null,
                        'direccion'        => $allData['direccion_consulta'] ?? null,
                        'celular'          => $allData['telefono_consulta'] ?? null,
                    ]);
                }

                // 2. FILTRAR DATOS PARA LA CONSULTA
                $consultaData = Arr::except($allData, [
                    'cantidad_hijos',
                    'ultimo_embarazo',
                    'estado_civil',
                    'ocupacion_actual',
                    'direccion_consulta',
                    'telefono_consulta',
                    'medio_captacion'
                ]);

                // 3. ACTUALIZAR CONSULTA
                $consulta->update($consultaData);

                return response()->json([
                    'success' => true,
                    'message' => 'Consulta actualizada exitosamente',
                    'data' => $consulta
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar: ' . $e->getMessage()
                ], 500);
            }
        });
    }

    /**
     * Display the specified resource.
     * GET /api/consultas-externas/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $consulta = $this->consultaService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new ConsultaExternaResource($consulta),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consulta externa no encontrada',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/consultas-externas/{id}
     */


    /**
     * Remove the specified resource from storage.
     * DELETE /api/consultas-externas/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->consultaService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Consulta externa eliminada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener consulta por atención
     * GET /api/consultas-externas/atencion/{atencionId}
     */
    public function getByAtencion(int $atencionId): JsonResponse
    {
        $consulta = $this->consultaService->getByAtencion($atencionId);

        if (!$consulta) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró consulta externa para esta atención',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ConsultaExternaResource($consulta),
        ]);
    }

    /**
     * Completar y firmar consulta
     * POST /api/consultas-externas/{id}/completar
     */
    public function completar(int $id): JsonResponse
    {
        try {
            $consulta = $this->consultaService->completarYFirmar($id);

            return response()->json([
                'success' => true,
                'message' => 'Consulta completada y firmada exitosamente',
                'data' => new ConsultaExternaResource($consulta),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Guardar como borrador
     * POST /api/consultas-externas/{id}/borrador
     */
    public function borrador(int $id): JsonResponse
    {
        try {
            $consulta = $this->consultaService->guardarBorrador($id);

            return response()->json([
                'success' => true,
                'message' => 'Consulta guardada como borrador',
                'data' => new ConsultaExternaResource($consulta),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtener estadísticas
     * GET /api/consultas-externas/stats/general
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->consultaService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Obtener historial de un paciente
     * GET /api/consultas-externas/paciente/{pacienteId}/historial
     */
    public function historial(int $pacienteId): JsonResponse
    {
        $consultas = $this->consultaService->getHistorialPaciente($pacienteId);

        return response()->json([
            'success' => true,
            'data' => ConsultaExternaResource::collection($consultas)
        ]);
    }

    /**
     * Buscar por diagnóstico
     * GET /api/consultas-externas/search-diagnostico?q=diabetes
     */
    public function buscarDiagnostico(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $consultas = $this->consultaService->buscarPorDiagnostico($term, $limit);

        return ConsultaExternaResource::collection($consultas);
    }

    /**
     * Obtener resumen de consulta
     * GET /api/consultas-externas/{id}/resumen
     */
    public function resumen(int $id): JsonResponse
    {
        try {
            $resumen = $this->consultaService->getResumen($id);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Restaurar consulta eliminada
     * POST /api/consultas-externas/{id}/restore
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $consulta = $this->consultaService->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Consulta restaurada exitosamente',
                'data' => new ConsultaExternaResource($consulta),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Obtener consultas eliminadas
     * GET /api/consultas-externas/trashed
     */
    public function trashed(): AnonymousResourceCollection
    {
        $consultas = $this->consultaService->getTrashed();

        return ConsultaExternaResource::collection($consultas);
    }
    // En ConsultaExternaController.php

    public function ultimaConsulta($pacienteId)
    {
        try {
            $consulta = ConsultaExterna::whereHas('atencion', function ($query) use ($pacienteId) {
                $query->where('paciente_id', $pacienteId);
            })
                ->latest() // Ordena por created_at descendente
                ->first();

            if (!$consulta) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron consultas previas para este paciente.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $consulta
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la última consulta: ' . $e->getMessage()
            ], 500);
        }
    }
}
