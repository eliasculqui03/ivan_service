<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Obtener todos los usuarios con paginación
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por rol
        if (isset($filters['role_id']) && !empty($filters['role_id'])) {
            $query->whereHas('roles', function($q) use ($filters) {
                $q->where('roles.id', $filters['role_id']);
            });
        }

        // Filtro por búsqueda
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obtener usuario por ID con roles
     *
     * @param int $id
     * @return User
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Obtener usuario con sus roles
     *
     * @param int $id
     * @return array
     */
    public function getByIdWithRoles(int $id): array
    {
        $user = $this->getById($id);
        
        $roles = DB::table('roles_users')
            ->join('roles', 'roles.id', '=', 'roles_users.id_role')
            ->where('roles_users.id_user', $id)
            ->select('roles.id', 'roles.name')
            ->get();

        return [
            'user' => $user,
            'roles' => $roles,
        ];
    }

    /**
     * Crear nuevo usuario
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function create(array $data): User
    {
        DB::beginTransaction();
        
        try {
            // Verificar email único
            if ($this->emailExists($data['email'])) {
                throw new \Exception('El correo electrónico ya está registrado.');
            }

            // Verificar username único (si se proporciona)
            if (isset($data['username']) && $this->usernameExists($data['username'])) {
                throw new \Exception('El nombre de usuario ya está registrado.');
            }

            // Crear usuario
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'] ?? null,
                'phone' => $data['phone'] ?? null,
                'avatar_url' => $data['avatar_url'] ?? null,
                'language' => $data['language'] ?? 'es',
                'timezone' => $data['timezone'] ?? 'America/Lima',
                'password' => Hash::make($data['password']),
                'notifications_enabled' => $data['notifications_enabled'] ?? true,
                'marketing_consent' => $data['marketing_consent'] ?? false,
                'status' => $data['status'] ?? 1,
            ]);

            // Asignar roles
            if (isset($data['roles']) && count($data['roles']) > 0) {
                foreach ($data['roles'] as $roleId) {
                    DB::table('roles_users')->insert([
                        'id_user' => $user->id,
                        'id_role' => $roleId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Usuario creado', [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear usuario', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar usuario
     *
     * @param int $id
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function update(int $id, array $data): User
    {
        DB::beginTransaction();
        
        try {
            $user = $this->getById($id);

            // Verificar email único (excepto el usuario actual)
            if (isset($data['email']) && 
                $data['email'] !== $user->email && 
                $this->emailExists($data['email'])) {
                throw new \Exception('El correo electrónico ya está registrado.');
            }

            // Verificar username único (excepto el usuario actual)
            if (isset($data['username']) && 
                $data['username'] !== $user->username && 
                $this->usernameExists($data['username'])) {
                throw new \Exception('El nombre de usuario ya está registrado.');
            }

            // Actualizar datos del usuario
            $updateData = array_intersect_key($data, array_flip([
                'name', 'email', 'username', 'phone', 'avatar_url',
                'language', 'timezone', 'notifications_enabled', 'marketing_consent'
            ]));

            $user->update($updateData);

            // Actualizar roles si se proporcionaron
            if (isset($data['roles'])) {
                // Eliminar roles existentes
                DB::table('roles_users')->where('id_user', $id)->delete();

                // Asignar nuevos roles
                if (count($data['roles']) > 0) {
                    foreach ($data['roles'] as $roleId) {
                        DB::table('roles_users')->insert([
                            'id_user' => $id,
                            'id_role' => $roleId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();
            
            Log::info('Usuario actualizado', [
                'id' => $user->id,
                'name' => $user->name,
            ]);

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar usuario', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cambiar estado del usuario
     *
     * @param int $id
     * @return User
     */
    public function toggleStatus(int $id): User
    {
        $user = $this->getById($id);
        $newStatus = $user->status == 1 ? 0 : 1;
        
        $user->update(['status' => $newStatus]);

        Log::info('Estado de usuario actualizado', [
            'id' => $user->id,
            'status' => $newStatus,
        ]);

        return $user->fresh();
    }

    /**
     * Cambiar contraseña
     *
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        $user = $this->getById($id);
        
        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        Log::info('Contraseña de usuario actualizada', [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Eliminar usuario
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $user = $this->getById($id);

            // Verificar si es médico y tiene atenciones
            if ($user->medico()->exists()) {
                $atencionesCount = $user->medico->atenciones()->count();
                if ($atencionesCount > 0) {
                    throw new \Exception(
                        "No se puede eliminar el usuario porque es médico y tiene {$atencionesCount} atención(es) registrada(s)."
                    );
                }
            }

            // Eliminar roles
            DB::table('roles_users')->where('id_user', $id)->delete();

            // Eliminar usuario
            $user->delete();

            DB::commit();
            
            Log::info('Usuario eliminado', [
                'id' => $id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar usuario', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de usuarios
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => User::count(),
            'activos' => User::where('status', 1)->count(),
            'inactivos' => User::where('status', 0)->count(),
            'medicos' => User::whereHas('medico')->count(),
            'con_roles' => DB::table('roles_users')
                ->distinct('id_user')
                ->count('id_user'),
            'sin_roles' => User::doesntHave('roles')->count(),
        ];
    }

    /**
     * Verificar si un email ya existe
     *
     * @param string $email
     * @param int|null $exceptId
     * @return bool
     */
    private function emailExists(string $email, ?int $exceptId = null): bool
    {
        $query = User::where('email', $email);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Verificar si un username ya existe
     *
     * @param string $username
     * @param int|null $exceptId
     * @return bool
     */
    private function usernameExists(string $username, ?int $exceptId = null): bool
    {
        $query = User::where('username', $username);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}