<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicoRequest;
use App\Http\Requests\UpdateMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Services\MedicoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class MedicoController extends Controller
{
    protected MedicoService $medicoService;

    public function __construct(MedicoService $medicoService)
    {
        $this->medicoService = $medicoService;
    }

    /**
     * Display a listing of the resource.
     * GET /api/medicos
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status'),
            'especialidad_id' => $request->input('especialidad_id'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $medicos = $this->medicoService->getAllPaginated($filters, $perPage);

        return MedicoResource::collection($medicos);
    }

    /**
     * Obtener todos los médicos activos (sin paginación)
     * GET /api/medicos/activos
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     */
    public function activos(Request $request): AnonymousResourceCollection
    {
        $especialidadId = $request->input('especialidad_id');
        $medicos = $this->medicoService->getAllActive($especialidadId);
        
        return MedicoResource::collection($medicos);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/medicos
     *
     * @param StoreMedicoRequest $request
     * @return JsonResponse
     */
    public function store(StoreMedicoRequest $request): JsonResponse
    {
        try {
            $medico = $this->medicoService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Médico creado exitosamente',
                'data' => new MedicoResource($medico),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el médico',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/medicos/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $medico = $this->medicoService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new MedicoResource($medico),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/medicos/{id}
     *
     * @param UpdateMedicoRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateMedicoRequest $request, int $id): JsonResponse
    {
        try {
            $medico = $this->medicoService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Médico actualizado exitosamente',
                'data' => new MedicoResource($medico),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el médico',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/medicos/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->medicoService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Médico eliminado exitosamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activar/Desactivar médico
     * PATCH /api/medicos/{id}/toggle-status
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
            $medico = $this->medicoService->toggleStatus(
                $id,
                $request->input('status')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => new MedicoResource($medico),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);
        }
    }

    /**
     * Obtener estadísticas de médicos
     * GET /api/medicos/estadisticas
     *
     * @return JsonResponse
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->medicoService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Buscar médicos
     * GET /api/medicos/search?q=termino
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

        $medicos = $this->medicoService->search($term, $limit);

        return MedicoResource::collection($medicos);
    }

    /**
     * Restaurar médico eliminado
     * POST /api/medicos/{id}/restore
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $medico = $this->medicoService->restore($id);

            return response()->json([
                'success' => true,
                'message' => 'Médico restaurado exitosamente',
                'data' => new MedicoResource($medico),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);
        }
    }

    /**
     * Obtener médicos eliminados
     * GET /api/medicos/trashed
     *
     * @return AnonymousResourceCollection
     */
    public function trashed(): AnonymousResourceCollection
    {
        $medicos = $this->medicoService->getTrashed();

        return MedicoResource::collection($medicos);
    }

    /**
     * Obtener médicos por especialidad
     * GET /api/medicos/por-especialidad/{especialidadId}
     *
     * @param int $especialidadId
     * @return AnonymousResourceCollection
     */
    public function porEspecialidad(int $especialidadId): AnonymousResourceCollection
    {
        $medicos = $this->medicoService->getAllActive($especialidadId);

        return MedicoResource::collection($medicos);
    }

    /**
     * Cambiar contraseña de médico
     * POST /api/medicos/{id}/change-password
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function changePassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->medicoService->changePassword($id, $request->input('new_password'));

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Médico no encontrado',
            ], 404);
        }
    }

}
