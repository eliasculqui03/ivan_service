<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEspecialidadRequest;
use App\Http\Requests\UpdateEspecialidadRequest;
use App\Http\Resources\EspecialidadResource;
use App\Services\EspecialidadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class EspecialidadController extends Controller
{
    protected EspecialidadService $especialidadService;

    public function __construct(EspecialidadService $especialidadService)
    {
        $this->especialidadService = $especialidadService;
    }

    /**
     * Display a listing of the resource.
     * GET /api/especialidades
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'nombre'),
            'sort_order' => $request->input('sort_order', 'asc'),
        ];

        $perPage = $request->input('per_page', 15);

        $especialidades = $this->especialidadService->getAllPaginated($filters, $perPage);

        return EspecialidadResource::collection($especialidades);
    }

    /**
     * Obtener todas las especialidades activas (sin paginación)
     * GET /api/especialidades/activas
     *
     * @return AnonymousResourceCollection
     */
    public function activas(): AnonymousResourceCollection
    {
        $especialidades = $this->especialidadService->getAllActive();

        return EspecialidadResource::collection($especialidades);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/especialidades
     *
     * @param StoreEspecialidadRequest $request
     * @return JsonResponse
     */
    public function store(StoreEspecialidadRequest $request): JsonResponse
    {
        try {
            $especialidad = $this->especialidadService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Especialidad creada exitosamente',
                'data' => new EspecialidadResource($especialidad),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la especialidad',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/especialidades/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $especialidad = $this->especialidadService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new EspecialidadResource($especialidad),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/especialidades/{id}
     *
     * @param UpdateEspecialidadRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateEspecialidadRequest $request, int $id): JsonResponse
    {
        try {
            $especialidad = $this->especialidadService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Especialidad actualizada exitosamente',
                'data' => new EspecialidadResource($especialidad),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la especialidad',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/especialidades/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->especialidadService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Especialidad eliminada exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activar/Desactivar especialidad
     * PATCH /api/especialidades/{id}/toggle-status
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
            $especialidad = $this->especialidadService->toggleStatus(
                $id,
                $request->input('status')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => new EspecialidadResource($especialidad),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada',
            ], 404);
        }
    }

    /**
     * Obtener estadísticas de especialidades
     * GET /api/especialidades/estadisticas
     *
     * @return JsonResponse
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->especialidadService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Buscar especialidades
     * GET /api/especialidades/search?q=termino
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function search(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $especialidades = $this->especialidadService->search($term, $limit);

        return EspecialidadResource::collection($especialidades);
    }

    /**
     * Restaurar especialidad eliminada
     * POST /api/especialidades/{id}/restore
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $especialidad = $this->especialidadService->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Especialidad restaurada exitosamente',
                'data' => new EspecialidadResource($especialidad),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Especialidad no encontrada',
            ], 404);
        }
    }

    /**
     * Obtener especialidades eliminadas
     * GET /api/especialidades/trashed
     *
     * @return AnonymousResourceCollection
     */
    public function trashed(): AnonymousResourceCollection
    {
        $especialidades = $this->especialidadService->getTrashed();

        return EspecialidadResource::collection($especialidades);
    }

    /**
     * Obtener las especialidades más populares (con más médicos)
     * GET /api/especialidades/top
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function top(Request $request): AnonymousResourceCollection
    {
        $limit = $request->input('limit', 10);
        $especialidades = $this->especialidadService->getTopEspecialidades($limit);

        return EspecialidadResource::collection($especialidades);
    }
}
