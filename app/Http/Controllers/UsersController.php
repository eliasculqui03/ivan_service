<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    /**
     * Validate dangerous characters
     */
    private function hasDangerousChars($value): bool
    {
        if (is_null($value)) return false;
        return (bool) preg_match("/[<>'\"]/", $value);
    }

    /**
     * Check multiple fields for dangerous characters
     */
    private function checkDangerousFields(array $fields): ?JsonResponse
    {
        foreach ($fields as $field => $value) {
            if ($this->hasDangerousChars($value)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation error',
                    'errors' => [$field => ["The $field contains invalid characters."]]
                ], 200);
            }
        }
        return null;
    }

    /**
     * Get users with pagination and search
     */
    public function getUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer',
            'per_page' => 'integer',
            'search' => 'string|nullable'
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
            'search' => $request->search
        ]);
        if ($dangerousCheck) return $dangerousCheck;

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        // Count query
        $countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $countQuery .= " AND (name LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
            $searchParam = "%{$search}%";
            $params = [$searchParam, $searchParam, $searchParam, $searchParam];
        }

        $totalResult = DB::select($countQuery, $params);
        $total = $totalResult[0]->total;

        // Data query
        $offset = ($page - 1) * $perPage;
        $dataQuery = "SELECT * FROM users WHERE 1=1";

        if (!empty($search)) {
            $dataQuery .= " AND (name LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
        }

        $dataQuery .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $users = DB::select($dataQuery, $params);

        if (empty($users)) {
            return response()->json([
                'status' => 204,
                'message' => 'No content'
            ], 200);
        }

        $lastPage = ceil($total / $perPage);
        $from = ($page - 1) * $perPage + 1;
        $to = min($page * $perPage, $total);

        return response()->json([
            'status' => 200,
            'message' => 'Users retrieved successfully',
            'data' => $users,
            'data_external' => [
                'total_result' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => $lastPage,
                'from' => $from,
                'to' => $to
            ]
        ], 200);
    }

    /**
     * Create user
     */
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

    /**
     * User detail
     */
    public function detailUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$request->id]);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 200);
        }

        // Get user roles
        $roles = DB::select("
            SELECT r.id, r.name 
            FROM roles_users ru 
            INNER JOIN roles r ON r.id = ru.id_role 
            WHERE ru.id_user = ?
        ", [$request->id]);

        return response()->json([
            'status' => 200,
            'message' => 'User retrieved successfully',
            'data' => $user[0],
            'roles' => $roles,
        ], 200);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'username' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'notifications_enabled' => 'nullable|boolean',
            'marketing_consent' => 'nullable|boolean',
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
        ]);
        if ($dangerousCheck) return $dangerousCheck;

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$request->id]);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        $user = $user[0];

        // Check email duplicate
        $emailExists = DB::select("SELECT id FROM users WHERE id != ? AND email = ? LIMIT 1", [$request->id, $request->email]);
        if (!empty($emailExists)) {
            return response()->json([
                'status' => 422,
                'message' => 'Duplicate data',
                'errors' => ['email' => ['Email already registered.']]
            ], 200);
        }

        // Check username duplicate
        if ($request->username) {
            $usernameExists = DB::select("SELECT id FROM users WHERE id != ? AND username = ? LIMIT 1", [$request->id, $request->username]);
            if (!empty($usernameExists)) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Duplicate data',
                    'errors' => ['username' => ['Username already registered.']]
                ], 200);
            }
        }

        // Update user
        DB::update("
            UPDATE users SET 
                name = ?,
                email = ?,
                username = ?,
                phone = ?,
                language = ?,
                timezone = ?,
                notifications_enabled = ?,
                marketing_consent = ?,
                updated_at = ?
            WHERE id = ?
        ", [
            $request->name,
            $request->email,
            $request->username,
            $request->phone,
            $request->language ?? $user->language,
            $request->timezone ?? $user->timezone,
            $request->notifications_enabled ?? $user->notifications_enabled,
            $request->marketing_consent ?? $user->marketing_consent,
            now(),
            $request->id
        ]);

        // Update roles
        if ($request->has('roles')) {
            DB::delete("DELETE FROM roles_users WHERE id_user = ?", [$request->id]);

            if (count($request->roles) > 0) {
                foreach ($request->roles as $roleId) {
                    DB::insert("INSERT INTO roles_users (id_user, id_role) VALUES (?, ?)", [$request->id, $roleId]);
                }
            }
        }

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$request->id]);

        return response()->json([
            'status' => 200,
            'message' => 'User updated successfully',
            'data' => $user[0] ?? null
        ], 200);
    }

    /**
     * Change status
     */
    public function changeStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        $user = DB::select("SELECT * FROM users WHERE id = ?", [$request->id]);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        $newStatus = $user[0]->status == 1 ? 0 : 1;

        DB::update("UPDATE users SET status = ?, updated_at = ? WHERE id = ?", [
            $newStatus,
            now(),
            $request->id
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Status changed successfully'
        ], 200);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'new_password' => 'required|string|min:8',
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
            'new_password' => $request->new_password,
        ]);
        if ($dangerousCheck) return $dangerousCheck;

        $user = DB::select("SELECT id FROM users WHERE id = ?", [$request->id]);

        if (empty($user)) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        DB::update("UPDATE users SET password = ?, updated_at = ? WHERE id = ?", [
            Hash::make($request->new_password),
            now(),
            $request->id
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Password changed successfully'
        ], 200);
    }
}
