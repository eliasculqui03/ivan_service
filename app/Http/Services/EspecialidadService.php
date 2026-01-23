<?php

namespace App\Services;

use App\Models\Especialidad;
use App\Models\Especialidades;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EspecialidadService
{
    /**
     * Obtener todas las especialidades con paginación
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Especialidades::query();

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por búsqueda (nombre o código)
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'nombre';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortField, $sortOrder);

        // Contar médicos por especialidad
        $query->withCount('medicos');

        return $query->paginate($perPage);
    }

    /**
     * Obtener todas las especialidades activas (sin paginación)
     *
     * @return Collection
     */
    public function getAllActive(): Collection
    {
        return Especialidades::activas()
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtener especialidad por ID
     *
     * @param int $id
     * @return Especialidad
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Especialidades
    {
        return Especialidades::with(['medicos' => function($query) {
            $query->where('status', true);
        }])->findOrFail($id);
    }

    /**
     * Obtener especialidad por código
     *
     * @param string $codigo
     * @return Especialidad|null
     */
    public function getByCodigo(string $codigo): ?Especialidades
    {
        return Especialidades::where('codigo', $codigo)->first();
    }

    /**
     * Crear nueva especialidad
     *
     * @param array $data
     * @return Especialidad
     * @throws \Exception
     */
    public function create(array $data): Especialidades
    {
        DB::beginTransaction();
        
        try {
            // Verificar si el código ya existe
            if (isset($data['codigo']) && $this->codigoExists($data['codigo'])) {
                throw new \Exception("El código {$data['codigo']} ya está en uso.");
            }

            // Generar código automático si no se proporciona
            if (empty($data['codigo'])) {
                $data['codigo'] = $this->generateCodigo($data['nombre']);
            }

            $especialidad = Especialidades::create($data);

            DB::commit();
            
            Log::info('Especialidad creada', [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
                'codigo' => $especialidad->codigo,
            ]);

            return $especialidad;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear especialidad', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar especialidad
     *
     * @param int $id
     * @param array $data
     * @return Especialidad
     * @throws \Exception
     */
    public function update(int $id, array $data): Especialidades
    {
        DB::beginTransaction();
        
        try {
            $especialidad = $this->getById($id);

            // Verificar si el código ya existe (excepto para esta especialidad)
            if (isset($data['codigo']) && 
                $data['codigo'] !== $especialidad->codigo && 
                $this->codigoExists($data['codigo'])) {
                throw new \Exception("El código {$data['codigo']} ya está en uso.");
            }

            $especialidad->update($data);

            DB::commit();
            
            Log::info('Especialidad actualizada', [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
            ]);

            return $especialidad->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar especialidad', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar especialidad (soft delete)
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $especialidad = $this->getById($id);

            // Verificar si tiene médicos asociados
            if ($especialidad->medicos()->count() > 0) {
                throw new \Exception(
                    "No se puede eliminar la especialidad porque tiene {$especialidad->medicos()->count()} médico(s) asociado(s)."
                );
            }

            $especialidad->delete();

            DB::commit();
            
            Log::info('Especialidad eliminada', [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar especialidad', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activar/Desactivar especialidad
     *
     * @param int $id
     * @param bool $status
     * @return Especialidad
     */
    public function toggleStatus(int $id, bool $status): Especialidades
    {
        $especialidad = $this->getById($id);
        $especialidad->update(['status' => $status]);

        Log::info('Estado de especialidad actualizado', [
            'id' => $especialidad->id,
            'status' => $status,
        ]);

        return $especialidad->fresh();
    }

    /**
     * Obtener estadísticas de especialidades
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => Especialidades::count(),
            'activas' => Especialidades::where('status', true)->count(),
            'inactivas' => Especialidades::where('status', false)->count(),
            'con_medicos' => Especialidades::has('medicos')->count(),
            'sin_medicos' => Especialidades::doesntHave('medicos')->count(),
            'top_especialidades' => $this->getTopEspecialidades(5),
        ];
    }

    /**
     * Obtener las especialidades con más médicos
     *
     * @param int $limit
     * @return Collection
     */
    public function getTopEspecialidades(int $limit = 10): Collection
    {
        return Especialidades::withCount('medicos')
            ->orderBy('medicos_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar especialidades por término
     *
     * @param string $term
     * @param int $limit
     * @return Collection
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Especialidades::where('nombre', 'like', "%{$term}%")
            ->orWhere('codigo', 'like', "%{$term}%")
            ->orWhere('descripcion', 'like', "%{$term}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si un código ya existe
     *
     * @param string $codigo
     * @param int|null $exceptId
     * @return bool
     */
    private function codigoExists(string $codigo, ?int $exceptId = null): bool
    {
        $query = Especialidades::where('codigo', $codigo);
        
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Generar código automático basado en el nombre
     *
     * @param string $nombre
     * @return string
     */
    private function generateCodigo(string $nombre): string
    {
        // Tomar las primeras letras del nombre
        $palabras = explode(' ', $nombre);
        $iniciales = '';
        
        foreach ($palabras as $palabra) {
            if (strlen($palabra) > 0) {
                $iniciales .= strtoupper(substr($palabra, 0, 1));
            }
        }

        // Agregar número consecutivo
        $contador = 1;
        $codigo = $iniciales . str_pad($contador, 3, '0', STR_PAD_LEFT);

        while ($this->codigoExists($codigo)) {
            $contador++;
            $codigo = $iniciales . str_pad($contador, 3, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }

    /**
     * Restaurar especialidad eliminada
     *
     * @param int $id
     * @return Especialidad
     */
    public function restore(int $id): Especialidades
    {
        $especialidad = Especialidades::withTrashed()->findOrFail($id);
        $especialidad->restore();

        Log::info('Especialidad restaurada', [
            'id' => $especialidad->id,
            'nombre' => $especialidad->nombre,
        ]);

        return $especialidad;
    }

    /**
     * Obtener especialidades eliminadas
     *
     * @return Collection
     */
    public function getTrashed(): Collection
    {
        return Especialidades::onlyTrashed()->get();
    }
}