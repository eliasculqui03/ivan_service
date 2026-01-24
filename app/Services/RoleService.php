<?php

namespace App\Services;

use App\Models\Roles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleService
{
    /**
     * Obtener todos los roles con paginación
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Roles::query();

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por búsqueda
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortField, $sortOrder);

        // Contar usuarios por rol
        $query->withCount('users');

        return $query->paginate($perPage);
    }

    /**
     * Obtener todos los roles activos (sin paginación)
     *
     * @return Collection
     */
    public function getAllActive(): Collection
    {
        return Roles::where('status', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener rol por ID
     *
     * @param int $id
     * @return Roles
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Roles
    {
        return Roles::findOrFail($id);
    }

    /**
     * Crear nuevo rol
     *
     * @param array $data
     * @return Roles
     * @throws \Exception
     */
    public function create(array $data): Roles
    {
        DB::beginTransaction();
        
        try {
            // Verificar si el nombre ya existe
            if ($this->nameExists($data['name'])) {
                throw new \Exception("Ya existe un rol con el nombre '{$data['name']}'.");
            }

            $role = Roles::create($data);

            DB::commit();
            
            Log::info('Rol creado', [
                'id' => $role->id,
                'name' => $role->name,
            ]);

            return $role;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear rol', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar rol
     *
     * @param int $id
     * @param array $data
     * @return Roles
     * @throws \Exception
     */
    public function update(int $id, array $data): Roles
    {
        DB::beginTransaction();
        
        try {
            $role = $this->getById($id);

            // Verificar si el nombre ya existe (excepto para este rol)
            if (isset($data['name']) && 
                $data['name'] !== $role->name && 
                $this->nameExists($data['name'])) {
                throw new \Exception("Ya existe un rol con el nombre '{$data['name']}'.");
            }

            $role->update($data);

            DB::commit();
            
            Log::info('Rol actualizado', [
                'id' => $role->id,
                'name' => $role->name,
            ]);

            return $role->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar rol', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar rol
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $role = $this->getById($id);

            // Verificar si tiene usuarios asociados
            $usersCount = DB::table('roles_users')
                ->where('id_role', $id)
                ->count();

            if ($usersCount > 0) {
                throw new \Exception(
                    "No se puede eliminar el rol porque tiene {$usersCount} usuario(s) asociado(s)."
                );
            }

            // Verificar que no sea un rol del sistema
            $systemRoles = ['Administrador', 'Médico'];
            if (in_array($role->name, $systemRoles)) {
                throw new \Exception("No se puede eliminar un rol del sistema.");
            }

            $role->delete();

            DB::commit();
            
            Log::info('Rol eliminado', [
                'id' => $role->id,
                'name' => $role->name,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar rol', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cambiar estado del rol
     *
     * @param int $id
     * @param bool $status
     * @return Roles
     */
    public function toggleStatus(int $id, bool $status): Roles
    {
        $role = $this->getById($id);
        $role->update(['status' => $status]);

        Log::info('Estado de rol actualizado', [
            'id' => $role->id,
            'status' => $status,
        ]);

        return $role->fresh();
    }

    /**
     * Obtener estadísticas de roles
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        $total = Roles::count();
        $activos = Roles::where('status', true)->count();
        $inactivos = Roles::where('status', false)->count();

        // Roles con más usuarios
        $rolesConUsuarios = DB::table('roles')
            ->leftJoin('roles_users', 'roles.id', '=', 'roles_users.id_role')
            ->select('roles.id', 'roles.name', DB::raw('COUNT(roles_users.id_user) as users_count'))
            ->groupBy('roles.id', 'roles.name')
            ->orderBy('users_count', 'desc')
            ->limit(10)
            ->get();

        return [
            'total' => $total,
            'activos' => $activos,
            'inactivos' => $inactivos,
            'roles_con_usuarios' => $rolesConUsuarios,
        ];
    }

    /**
     * Verificar si un nombre de rol ya existe
     *
     * @param string $name
     * @param int|null $exceptId
     * @return bool
     */
    private function nameExists(string $name, ?int $exceptId = null): bool
    {
        $query = Roles::where('name', $name);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Obtener usuarios de un rol
     *
     * @param int $roleId
     * @return Collection
     */
    public function getUsersByRole(int $roleId): Collection
    {
        return DB::table('users')
            ->join('roles_users', 'users.id', '=', 'roles_users.id_user')
            ->where('roles_users.id_role', $roleId)
            ->select('users.*')
            ->get();
    }
}