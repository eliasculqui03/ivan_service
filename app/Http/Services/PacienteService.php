<?php

namespace App\Services;

use App\Models\Pacientes;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PacienteService
{
    /**
     * Obtener todos los pacientes con paginación
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Pacientes::query();

        // Filtro por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por tipo de documento
        if (isset($filters['tipo_documento']) && !empty($filters['tipo_documento'])) {
            $query->where('tipo_documento', $filters['tipo_documento']);
        }

        // Filtro por género
        if (isset($filters['genero']) && !empty($filters['genero'])) {
            $query->where('genero', $filters['genero']);
        }

        // Filtro por tipo de seguro
        if (isset($filters['tipo_seguro']) && !empty($filters['tipo_seguro'])) {
            $query->where('tipo_seguro', $filters['tipo_seguro']);
        }

        // Filtro por búsqueda
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('documento_identidad', 'like', "%{$search}%")
                    ->orWhere('numero_historia', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Contar atenciones
        $query->withCount('atenciones');

        return $query->paginate($perPage);
    }

    /**
     * Obtener todos los pacientes activos
     */
    public function getAllActive(): Collection
    {
        return Pacientes::activos()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->get();
    }

    /**
     * Obtener paciente por ID
     */
    public function getById(int $id): Pacientes
    {
        return Pacientes::with(['atenciones' => function ($query) {
            $query->orderBy('fecha_atencion', 'desc')->limit(10);
        }])->findOrFail($id);
    }

    /**
     * Obtener paciente por documento de identidad
     */
    public function getByDocumento(string $documento): Pacientes
    {
        return Pacientes::where('documento_identidad', $documento)->firstOrFail();
    }

    /**
     * Obtener paciente por número de historia
     */
    public function getByNumeroHistoria(string $numeroHistoria): Pacientes
    {
        return Pacientes::where('numero_historia', $numeroHistoria)->firstOrFail();
    }

    /**
     * Crear nuevo paciente
     */
    public function create(array $data): Pacientes
    {
        DB::beginTransaction();

        try {
            // Verificar si el documento ya existe
            if (isset($data['documento_identidad']) && $this->documentoExists($data['documento_identidad'])) {
                throw new \Exception("El documento {$data['documento_identidad']} ya está registrado.");
            }

            // Generar número de historia automático si no se proporciona
            if (empty($data['numero_historia'])) {
                $data['numero_historia'] = Pacientes::generarNumeroHistoria();
            }

            $paciente = Pacientes::create($data);

            DB::commit();

            Log::info('Paciente creado', [
                'id' => $paciente->id,
                'numero_historia' => $paciente->numero_historia,
                'documento' => $paciente->documento_identidad,
            ]);

            return $paciente;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear paciente', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar paciente
     */
    public function update(int $id, array $data): Pacientes
    {
        DB::beginTransaction();

        try {
            $paciente = $this->getById($id);

            // Verificar si el documento ya existe (excepto para este paciente)
            if (
                isset($data['documento_identidad']) &&
                $data['documento_identidad'] !== $paciente->documento_identidad &&
                $this->documentoExists($data['documento_identidad'])
            ) {
                throw new \Exception("El documento {$data['documento_identidad']} ya está registrado.");
            }

            $paciente->update($data);

            DB::commit();

            Log::info('Paciente actualizado', [
                'id' => $paciente->id,
                'numero_historia' => $paciente->numero_historia,
            ]);

            return $paciente->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar paciente', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar paciente (soft delete)
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {
            $paciente = $this->getById($id);

            // Verificar si tiene atenciones activas
            $atencionesActivas = $paciente->atenciones()
                ->whereIn('estado', ['Programada', 'En Espera', 'En Atención'])
                ->count();

            if ($atencionesActivas > 0) {
                throw new \Exception(
                    "No se puede eliminar el paciente porque tiene {$atencionesActivas} atención(es) pendiente(s)."
                );
            }

            $paciente->delete();

            DB::commit();

            Log::info('Paciente eliminado', [
                'id' => $paciente->id,
                'numero_historia' => $paciente->numero_historia,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar paciente', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Activar/Desactivar paciente
     */
    public function toggleStatus(int $id, bool $status): Pacientes
    {
        $paciente = $this->getById($id);
        $paciente->update(['status' => $status]);

        Log::info('Estado de paciente actualizado', [
            'id' => $paciente->id,
            'status' => $status,
        ]);

        return $paciente->fresh();
    }

    /**
     * Obtener estadísticas de pacientes
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => Pacientes::count(),
            'activos' => Pacientes::where('status', true)->count(),
            'inactivos' => Pacientes::where('status', false)->count(),
            'por_genero' => [
                'masculino' => Pacientes::where('genero', 'M')->count(),
                'femenino' => Pacientes::where('genero', 'F')->count(),
                'otro' => Pacientes::where('genero', 'Otro')->count(),
            ],
            'por_tipo_seguro' => Pacientes::select('tipo_seguro', DB::raw('count(*) as total'))
                ->whereNotNull('tipo_seguro')
                ->groupBy('tipo_seguro')
                ->pluck('total', 'tipo_seguro'),
            'nuevos_este_mes' => Pacientes::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    /**
     * Buscar pacientes por término
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Pacientes::where('nombres', 'like', "%{$term}%")
            ->orWhere('apellido_paterno', 'like', "%{$term}%")
            ->orWhere('apellido_materno', 'like', "%{$term}%")
            ->orWhere('documento_identidad', 'like', "%{$term}%")
            ->orWhere('numero_historia', 'like', "%{$term}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Verificar si un documento ya existe
     */
    private function documentoExists(string $documento, ?int $exceptId = null): bool
    {
        $query = Pacientes::where('documento_identidad', $documento);

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Restaurar paciente eliminado
     */
    public function restore(int $id): Pacientes
    {
        $paciente = Pacientes::withTrashed()->findOrFail($id);
        $paciente->restore();

        Log::info('Paciente restaurado', [
            'id' => $paciente->id,
            'numero_historia' => $paciente->numero_historia,
        ]);

        return $paciente;
    }

    /**
     * Obtener pacientes eliminados
     */
    public function getTrashed(): Collection
    {
        return Pacientes::onlyTrashed()->get();
    }

    /**
     * Obtener historial de atenciones del paciente
     */
    public function getHistorialAtenciones(int $id): array
    {
        $paciente = Pacientes::with(['atenciones' => function ($query) {
            $query->orderBy('fecha_atencion', 'desc');
        }, 'atenciones.medico'])->findOrFail($id);

        return [
            'paciente' => [
                'id' => $paciente->id,
                'numero_historia' => $paciente->numero_historia,
                'nombre_completo' => $paciente->nombre_completo,
            ],
            'total_atenciones' => $paciente->atenciones->count(),
            'atenciones' => $paciente->atenciones,
        ];
    }
}
