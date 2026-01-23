<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsultaExternaRequest;
use App\Http\Requests\UpdateConsultaExternaRequest;
use App\Http\Resources\ConsultaExternaResource;
use App\Services\ConsultaExternaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function store(StoreConsultaExternaRequest $request): JsonResponse
    {
        try {
            $consulta = $this->consultaService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Consulta externa creada exitosamente',
                'data' => new ConsultaExternaResource($consulta),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la consulta externa',
                'error' => $e->getMessage(),
            ], 400);
        }
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
    public function update(UpdateConsultaExternaRequest $request, int $id): JsonResponse
    {
        try {
            $consulta = $this->consultaService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Consulta externa actualizada exitosamente',
                'data' => new ConsultaExternaResource($consulta),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Consulta externa no encontrada',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

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
    public function historial(int $pacienteId): AnonymousResourceCollection
    {
        $consultas = $this->consultaService->getHistorialPaciente($pacienteId);

        return ConsultaExternaResource::collection($consultas);
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
}