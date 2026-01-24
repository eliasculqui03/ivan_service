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

class UserController extends Controller
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
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'username' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'notifications_enabled' => 'nullable|boolean',
            'marketing_consent' => 'nullable|boolean',
            'status' => 'nullable|integer|in:0,1',
            'roles' => 'nullable|array',
            'roles.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        // Validate dangerous characters
        $dangerousCheck = $this->checkDangerousFields([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone' => $request->phone,
            'password' => $request->password,
        ]);
        if ($dangerousCheck) return $dangerousCheck;

        // Check email exists
        $emailExists = DB::select("SELECT id FROM users WHERE email = ? LIMIT 1", [$request->email]);
        if (!empty($emailExists)) {
            return response()->json([
                'status' => 422,
                'message' => 'Duplicate data',
                'errors' => ['email' => ['Email already registered.']]
            ], 200);
        }

        // Check username exists
        if ($request->username) {
            $usernameExists = DB::select("SELECT id FROM users WHERE username = ? LIMIT 1", [$request->username]);
            if (!empty($usernameExists)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Duplicate data',
                    'errors' => ['username' => ['Username already registered.']]
                ], 200);
            }
        }

        // Insert user
        $id = DB::table('users')->insertGetId([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone' => $request->phone,
            'avatar_url' => null,
            'language' => $request->language ?? 'es',
            'timezone' => $request->timezone ?? 'America/Lima',
            'password' => Hash::make($request->password),
            'notifications_enabled' => $request->notifications_enabled ?? true,
            'marketing_consent' => $request->marketing_consent ?? false,
            'status' => $request->status ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Save roles
        if ($request->roles && count($request->roles) > 0) {
            foreach ($request->roles as $roleId) {
                DB::insert("INSERT INTO roles_users (id_user, id_role) VALUES (?, ?)", [$id, $roleId]);
            }
        }

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$id]);

        return response()->json([
            'status' => 200,
            'message' => 'User created successfully',
            'data' => $user[0] ?? null
        ], 200);
    }
}
