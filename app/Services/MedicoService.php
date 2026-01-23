<?php

namespace App\Services;

use App\Models\Medicos;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class MedicoService
{
    /**
     * Obtener todos los médicos con paginación
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Medicos::with(['user', 'especialidad']);

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por especialidad
        if (isset($filters['especialidad_id']) && !empty($filters['especialidad_id'])) {
            $query->where('especialidad_id', $filters['especialidad_id']);
        }

        // Filtro por búsqueda (nombre, número de colegiatura, DNI)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('numero_colegiatura', 'like', "%{$search}%")
                  ->orWhere('documento_identidad', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        if ($sortField === 'nombre') {
            // Ordenar por nombre del usuario
            $query->join('users', 'medicos.user_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('medicos.*');
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener todos los médicos activos (sin paginación)
     *
     * @param int|null $especialidadId
     * @return Collection
     */
    public function getAllActive(?int $especialidadId = null): Collection
    {
        $query = Medicos::activos()->with(['user', 'especialidad']);

        if ($especialidadId) {
            $query->porEspecialidad($especialidadId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtener médico por ID
     *
     * @param int $id
     * @return Medicos
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Medicos
    {
        return Medicos::with(['user', 'especialidad'])->findOrFail($id);
    }

    /**
     * Obtener médico por número de colegiatura
     *
     * @param string $numeroColegiatura
     * @return Medicos|null
     */
    public function getByNumeroColegiatura(string $numeroColegiatura): ?Medicos
    {
        return Medicos::where('numero_colegiatura', $numeroColegiatura)
            ->with(['user', 'especialidad'])
            ->first();
    }

    /**
     * Obtener médico por documento de identidad
     *
     * @param string $documento
     * @return Medicos|null
     */
    public function getByDocumento(string $documento): ?Medicos
    {
        return Medicos::where('documento_identidad', $documento)
            ->with(['user', 'especialidad'])
            ->first();
    }

    /**
     * Crear nuevo médico
     *
     * @param array $data
     * @return Medicos
     * @throws \Exception
     */
    public function create(array $data): Medicos
    {
        DB::beginTransaction();
        
        try {
            // Validaciones de negocio
            if ($this->colegiaturaExists($data['numero_colegiatura'])) {
                throw new \Exception("El número de colegiatura {$data['numero_colegiatura']} ya está en uso.");
            }

            if ($this->documentoExists($data['documento_identidad'])) {
                throw new \Exception("El documento de identidad {$data['documento_identidad']} ya está en uso.");
            }

            // Crear usuario
            $userData = [
                'name' => $data['nombre_completo'],
                'email' => $data['email'],
                'username' => $data['username'] ?? strtolower(str_replace(' ', '.', $data['nombre_completo'])),
                'password' => Hash::make($data['password'] ?? 'password123'),
                'phone' => $data['telefono'] ?? null,
                'status' => 1,
            ];

            $user = User::create($userData);

            // Asignar rol de médico (ID 2 según el seeder)
            $user->roles()->attach(2); // Rol Médico

            // Crear médico
            $medicoData = [
                'user_id' => $user->id,
                'especialidad_id' => $data['especialidad_id'],
                'numero_colegiatura' => $data['numero_colegiatura'],
                'rne' => $data['rne'] ?? null,
                'documento_identidad' => $data['documento_identidad'],
                'telefono' => $data['telefono'] ?? null,
                'direccion' => $data['direccion'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'genero' => $data['genero'] ?? null,
                'firma_digital' => $data['firma_digital'] ?? null,
                'sello_digital' => $data['sello_digital'] ?? null,
                'status' => $data['status'] ?? true,
            ];

            $medico = Medicos::create($medicoData);

            DB::commit();
            
            Log::info('Médico creado', [
                'id' => $medico->id,
                'nombre' => $medico->nombre_completo,
                'colegiatura' => $medico->numero_colegiatura,
            ]);

            return $medico->fresh(['user', 'especialidad']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear médico', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar médico
     *
     * @param int $id
     * @param array $data
     * @return Medicos
     * @throws \Exception
     */
    public function update(int $id, array $data): Medicos
    {
        DB::beginTransaction();
        
        try {
            $medico = $this->getById($id);

            // Validar número de colegiatura único
            if (isset($data['numero_colegiatura']) && 
                $data['numero_colegiatura'] !== $medico->numero_colegiatura && 
                $this->colegiaturaExists($data['numero_colegiatura'])) {
                throw new \Exception("El número de colegiatura {$data['numero_colegiatura']} ya está en uso.");
            }

            // Validar documento único
            if (isset($data['documento_identidad']) && 
                $data['documento_identidad'] !== $medico->documento_identidad && 
                $this->documentoExists($data['documento_identidad'])) {
                throw new \Exception("El documento de identidad {$data['documento_identidad']} ya está en uso.");
            }

            // Actualizar usuario si hay datos
            if (isset($data['nombre_completo']) || isset($data['email']) || isset($data['telefono'])) {
                $userUpdateData = [];
                
                if (isset($data['nombre_completo'])) {
                    $userUpdateData['name'] = $data['nombre_completo'];
                }
                if (isset($data['email'])) {
                    $userUpdateData['email'] = $data['email'];
                }
                if (isset($data['telefono'])) {
                    $userUpdateData['phone'] = $data['telefono'];
                }
                
                $medico->user->update($userUpdateData);
            }

            // Actualizar médico
            $medicoUpdateData = array_intersect_key($data, array_flip([
                'especialidad_id',
                'numero_colegiatura',
                'rne',
                'documento_identidad',
                'telefono',
                'direccion',
                'fecha_nacimiento',
                'genero',
                'firma_digital',
                'sello_digital',
                'status',
            ]));

            $medico->update($medicoUpdateData);

            DB::commit();
            
            Log::info('Médico actualizado', [
                'id' => $medico->id,
                'nombre' => $medico->nombre_completo,
            ]);

            return $medico->fresh(['user', 'especialidad']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar médico', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar médico (soft delete)
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $medico = $this->getById($id);

            // Verificar si tiene atenciones
            if ($medico->atenciones()->count() > 0) {
                throw new \Exception(
                    "No se puede eliminar el médico porque tiene {$medico->atenciones()->count()} atención(es) registrada(s)."
                );
            }

            $medico->delete();
            
            // Opcionalmente desactivar el usuario
            $medico->user->update(['status' => 0]);

            DB::commit();
            
            Log::info('Médico eliminado', [
                'id' => $medico->id,
                'nombre' => $medico->nombre_completo,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar médico', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activar/Desactivar médico
     *
     * @param int $id
     * @param bool $status
     * @return Medicos
     */
    public function toggleStatus(int $id, bool $status): Medicos
    {
        DB::beginTransaction();

        try {
            $medico = $this->getById($id);
            $medico->update(['status' => $status]);
            
            // Sincronizar estado con usuario
            $medico->user->update(['status' => $status ? 1 : 0]);

            DB::commit();

            Log::info('Estado de médico actualizado', [
                'id' => $medico->id,
                'status' => $status,
            ]);

            return $medico->fresh(['user', 'especialidad']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de médicos
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => Medicos::count(),
            'activos' => Medicos::where('status', true)->count(),
            'inactivos' => Medicos::where('status', false)->count(),
            'por_especialidad' => $this->getMedicosPorEspecialidad(),
            'con_atenciones' => Medicos::has('atenciones')->count(),
            'sin_atenciones' => Medicos::doesntHave('atenciones')->count(),
        ];
    }

    /**
     * Obtener médicos agrupados por especialidad
     *
     * @return array
     */
    public function getMedicosPorEspecialidad(): array
    {
        return Medicos::select('especialidad_id', DB::raw('count(*) as total'))
            ->with('especialidad:id,nombre')
            ->groupBy('especialidad_id')
            ->get()
            ->map(function($item) {
                return [
                    'especialidad' => $item->especialidad->nombre ?? 'Sin especialidad',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Buscar médicos por término
     *
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Medicos::with(['user', 'especialidad'])
            ->where('numero_colegiatura', 'like', "%{$term}%")
            ->orWhere('documento_identidad', 'like', "%{$term}%")
            ->orWhereHas('user', function($q) use ($term) {
                $q->where('name', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si un número de colegiatura ya existe
     *
     * @param string $numeroColegiatura
     * @param int|null $exceptId
     * @return bool
     */
    private function colegiaturaExists(string $numeroColegiatura, ?int $exceptId = null): bool
    {
        $query = Medicos::where('numero_colegiatura', $numeroColegiatura);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Verificar si un documento ya existe
     *
     * @param string $documento
     * @param int|null $exceptId
     * @return bool
     */
    private function documentoExists(string $documento, ?int $exceptId = null): bool
    {
        $query = Medicos::where('documento_identidad', $documento);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Restaurar médico eliminado
     *
     * @param int $id
     * @return Medicos
     */
    public function restore(int $id): Medicos
    {
        $medico = Medicos::withTrashed()->findOrFail($id);
        $medico->restore();
        
        // Reactivar usuario
        $medico->user->update(['status' => 1]);

        Log::info('Médico restaurado', [
            'id' => $medico->id,
            'nombre' => $medico->nombre_completo,
        ]);

        return $medico->fresh(['user', 'especialidad']);
    }

    /**
     * Obtener médicos eliminados
     *
     * @return Collection
     */
    public function getTrashed(): Collection
    {
        return Medicos::onlyTrashed()->with(['user', 'especialidad'])->get();
    }

    /**
     * Cambiar contraseña de médico
     *
     * @param int $id
     * @param string $newPassword
     * @return bool
     */
    public function changePassword(int $id, string $newPassword): bool
    {
        $medico = $this->getById($id);
        $medico->user->update([
            'password' => Hash::make($newPassword),
        ]);

        Log::info('Contraseña de médico actualizada', [
            'medico_id' => $medico->id,
        ]);

        return true;
    }
}