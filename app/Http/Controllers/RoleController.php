<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * GET /api/roles
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_order' => $request->input('sort_order', 'asc'),
        ];

        $perPage = $request->input('per_page', 15);
        $roles = $this->roleService->getAllPaginated($filters, $perPage);

        return RoleResource::collection($roles);
    }

    /**
     * GET /api/roles/activos
     */
    public function activos(): AnonymousResourceCollection
    {
        $roles = $this->roleService->getAllActive();
        return RoleResource::collection($roles);
    }

    /**
     * POST /api/roles
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $role = $this->roleService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Rol creado exitosamente',
                'data' => new RoleResource($role),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $role = $this->roleService->getById($id);

            return response()->json([
                'success' => true,
                'data' => new RoleResource($role),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }
    }

    /**
     * PUT/PATCH /api/roles/{id}
     */
    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {
            $role = $this->roleService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado exitosamente',
                'data' => new RoleResource($role),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/roles/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->roleService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * PATCH /api/roles/{id}/toggle-status
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|boolean']);

        try {
            $role = $this->roleService->toggleStatus($id, $request->status);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado',
                'data' => new RoleResource($role),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * GET /api/roles/stats/general
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->roleService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * GET /api/roles/{id}/users
     */
    public function getUsersByRole(int $id): JsonResponse
    {
        try {
            $users = $this->roleService->getUsersByRole($id);

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}