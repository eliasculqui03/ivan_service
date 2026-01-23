<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCirugiaRequest;
use App\Http\Requests\UpdateCirugiaRequest;
use App\Services\CirugiaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class CirugiaController extends Controller
{
    protected CirugiaService $cirugiaService;

    public function __construct(CirugiaService $cirugiaService)
    {
        $this->cirugiaService = $cirugiaService;
    }

    /**
     * Listar cirugías con paginación
     * POST /api/v1/cirugias
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'estado' => $request->input('estado'),
            'tipo_cirugia' => $request->input('tipo_cirugia'),
            'clasificacion' => $request->input('clasificacion'),
            'cirujano_id' => $request->input('cirujano_id'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'fecha_programada'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $cirugias = $this->cirugiaService->getAllPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $cirugias,
        ]);
    }

    /**
     * Crear cirugía
     * POST /api/v1/cirugias/store
     */
    public function store(StoreCirugiaRequest $request): JsonResponse
    {
        try {
            $cirugia = $this->cirugiaService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cirugía creada exitosamente',
                'data' => $cirugia,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la cirugía',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mostrar cirugía
     * POST /api/v1/cirugias/show
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $cirugia = $this->cirugiaService->getById($request->input('id'));

            return response()->json([
                'success' => true,
                'data' => $cirugia,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cirugía no encontrada',
            ], 404);
        }
    }

    /**
     * Actualizar cirugía
     * POST /api/v1/cirugias/update
     */
    public function update(UpdateCirugiaRequest $request): JsonResponse
    {
        try {
            $cirugia = $this->cirugiaService->update(
                $request->input('id'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Cirugía actualizada exitosamente',
                'data' => $cirugia,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cirugía no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cirugía',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Eliminar cirugía
     * POST /api/v1/cirugias/destroy
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $this->cirugiaService->delete($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Cirugía eliminada exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cirugía no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cirugías de hoy
     * POST /api/v1/cirugias/hoy
     */
    public function hoy(Request $request): JsonResponse
    {
        $cirujanoId = $request->input('cirujano_id');
        $cirugias = $this->cirugiaService->getCirugiasHoy($cirujanoId);

        return response()->json([
            'success' => true,
            'data' => $cirugias,
        ]);
    }

    /**
     * Cambiar estado
     * POST /api/v1/cirugias/cambiar-estado
     */
    public function cambiarEstado(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'estado' => 'required|in:Programada,En Proceso,Completada,Suspendida,Cancelada',
        ]);

        try {
            $cirugia = $this->cirugiaService->cambiarEstado(
                $request->input('id'),
                $request->input('estado')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $cirugia,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cirugía no encontrada',
            ], 404);
        }
    }

    /**
     * Buscar cirugías
     * POST /api/v1/cirugias/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $cirugias = $this->cirugiaService->search($term, $limit);

        return response()->json([
            'success' => true,
            'data' => $cirugias,
        ]);
    }

    /**
     * Estadísticas
     * POST /api/v1/cirugias/stats
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $filters = [
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ];

        $estadisticas = $this->cirugiaService->getEstadisticas($filters);

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Cirugías por paciente
     * POST /api/v1/cirugias/por-paciente
     */
    public function porPaciente(Request $request): JsonResponse
    {
        $request->validate(['paciente_id' => 'required|integer']);

        $cirugias = $this->cirugiaService->getByPaciente($request->input('paciente_id'));

        return response()->json([
            'success' => true,
            'data' => $cirugias,
        ]);
    }

    /**
     * Restaurar cirugía
     * POST /api/v1/cirugias/restore
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $cirugia = $this->cirugiaService->restore($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Cirugía restaurada exitosamente',
                'data' => $cirugia,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cirugía no encontrada',
            ], 404);
        }
    }

    /**
     * Cirugías eliminadas
     * POST /api/v1/cirugias/trashed
     */
    public function trashed(): JsonResponse
    {
        $cirugias = $this->cirugiaService->getTrashed();

        return response()->json([
            'success' => true,
            'data' => $cirugias,
        ]);
    }
}
