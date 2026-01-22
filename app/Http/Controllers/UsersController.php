<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    /**
     * Listar usuarios con paginación y búsqueda
     */
    public function getUsers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'page' => 'required|integer',
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

        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search', '');

        $query = User::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        if ($users->isEmpty()) {
            return response()->json([
                'status' => 204,
                'message' => 'No content'
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Users retrieved successfully',
            'data' => $users->items(),
            'data_external' => [
                'total_result' => $users->total(),
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem()
            ]
        ], 200);
    }

    /**
     * Crear usuario
     */
    public function createUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:50|unique:users,username',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string', // base64 o URL
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'password' => 'required|string|min:8',
            'notifications_enabled' => 'nullable|boolean',
            'marketing_consent' => 'nullable|boolean',
            'status' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        // Subir avatar si es base64
        $avatarPath = null;
        /* if ($request->avatar_url && $this->isBase64Image($request->avatar_url)) {
            $avatarPath = $this->uploadBase64ToFtp($request->avatar_url, 'avatars');

            if (!$avatarPath) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error uploading avatar',
                ], 200);
            }
        } elseif ($request->avatar_url) {
            $avatarPath = $request->avatar_url;
        } */

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone' => $request->phone,
            'avatar_url' => $avatarPath,
            'language' => $request->language ?? 'es',
            'timezone' => $request->timezone ?? 'America/Lima',
            'password' => Hash::make($request->password),
            'notifications_enabled' => $request->notifications_enabled ?? true,
            'marketing_consent' => $request->marketing_consent ?? false,
            'status' => $request->status ?? 1,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'User created successfully',
            'data' => $user
        ], 200);
    }

    /**
     * Detalle de usuario
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

        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'User retrieved successfully',
            'data' => $user,
        ], 200);
    }

    /**
     * Actualizar usuario
     */
    public function updateUser(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'username' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string',
            'language' => 'nullable|string|max:5',
            'timezone' => 'nullable|string|max:50',
            'notifications_enabled' => 'nullable|boolean',
            'marketing_consent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        // Validar email único (excepto el actual)
        $existingEmail = User::where('id', '!=', $request->id)
            ->where('email', $request->email)
            ->exists();

        if ($existingEmail) {
            return response()->json([
                'status' => 422,
                'message' => 'Duplicate data',
                'errors' => ['email' => ['El email ya está registrado.']]
            ], 200);
        }

        // Validar username único (excepto el actual)
        if ($request->username) {
            $existingUsername = User::where('id', '!=', $request->id)
                ->where('username', $request->username)
                ->exists();

            if ($existingUsername) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Duplicate data',
                    'errors' => ['username' => ['El username ya está registrado.']]
                ], 200);
            }
        }

        // Manejar avatar
        $avatarPath = $user->avatar_url;

        /*  if ($request->avatar_url) {
            if ($this->isBase64Image($request->avatar_url)) {
                // Eliminar avatar anterior si existe
                if ($user->avatar_url) {
                    $this->deleteFromFtp($user->avatar_url);
                }

                $avatarPath = $this->uploadBase64ToFtp($request->avatar_url, 'avatars');

                if (!$avatarPath) {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Error uploading avatar',
                    ], 200);
                }
            } else {
                $avatarPath = $request->avatar_url;
            }
        } */

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $request->username,
            'phone' => $request->phone,
            'avatar_url' => $avatarPath,
            'language' => $request->language ?? $user->language,
            'timezone' => $request->timezone ?? $user->timezone,
            'notifications_enabled' => $request->notifications_enabled ?? $user->notifications_enabled,
            'marketing_consent' => $request->marketing_consent ?? $user->marketing_consent,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'User updated successfully',
            'data' => $user->fresh()
        ], 200);
    }

    /**
     * Cambiar status
     */
    public function changeStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        // Toggle status (0 -> 1, 1 -> 0)
        $user->update([
            'status' => $user->status == 1 ? 0 : 1
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Status changed successfully',
            'data' => $user->fresh()
        ], 200);
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found'
            ], 200);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Password changed successfully'
        ], 200);
    }

    /**
     * Verificar si es base64
     */
    private function isBase64Image(string $string): bool
    {
        return (bool) preg_match('/^data:image\/(\w+);base64,/', $string);
    }

    /**
     * Subir imagen base64 a FTP
     */
    private function uploadBase64ToFtp(string $base64Image, string $folder = 'uploads'): ?string
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $matches)) {
                $extension = $matches[1];
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
            } else {
                $extension = 'png';
            }

            $imageData = base64_decode($base64Image);

            if ($imageData === false) {
                return null;
            }

            $fileName = uniqid() . '_' . time() . '.' . $extension;

            $uploaded = Storage::disk('ftp')->put($fileName, $imageData);

            if ($uploaded) {
                $url = config('filesystems.disks.ftp.url');
                return "{$url}/{$fileName}";
            }

            return null;
        } catch (\Exception $e) {
            Log::error('FTP Upload Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Eliminar archivo de FTP
     */
    /*  private function deleteFromFtp(string $path): bool
    {
        try {
            $fileName = basename($path);
            if (Storage::disk('ftp')->exists($fileName)) {
                return Storage::disk('ftp')->delete($fileName);
            }
            return true;
        } catch (\Exception $e) {
            Log::error('FTP Delete Error: ' . $e->getMessage());
            return false;
        }
    } */
}
