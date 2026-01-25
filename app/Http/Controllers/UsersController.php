<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    protected UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * GET /api/users
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->input('status'),
            'role_id' => $request->input('role_id'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'id'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);
        $users = $this->userService->getAllPaginated($filters, $perPage);

        return UserResource::collection($users);
    }

    /**
     * POST /api/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado exitosamente',
                'data' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * GET /api/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $data = $this->userService->getByIdWithRoles($id);

            return response()->json([
                'success' => true,
                'data' => new UserResource($data['user']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    /**
     * PUT/PATCH /api/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente',
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * DELETE /api/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/users/{id}/toggle-status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleStatus($id);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * POST /api/users/{id}/change-password
     */
    public function changePassword(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'new_password' => 'required|string|min:8',
        ]);

        try {
            $this->userService->changePassword($id, $request->new_password);

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * GET /api/users/stats/general
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->userService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }
   
}
