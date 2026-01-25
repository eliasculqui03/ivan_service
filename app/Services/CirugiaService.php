<?php

namespace App\Services;

use App\Models\Cirugias;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CirugiaService
{
    /**
     * Obtener todas las cirugías con paginación
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Cirugias::with(['atencion.paciente', 'cirujano']);

        if (isset($filters['estado']) && !empty($filters['estado'])) {
            $query->where('estado_cirugia', $filters['estado']);
        }

        if (isset($filters['tipo_cirugia']) && !empty($filters['tipo_cirugia'])) {
            $query->where('tipo_cirugia', $filters['tipo_cirugia']);
        }

        if (isset($filters['clasificacion']) && !empty($filters['clasificacion'])) {
            $query->where('clasificacion', $filters['clasificacion']);
        }

        if (isset($filters['cirujano_id']) && !empty($filters['cirujano_id'])) {
            $query->where('medico_cirujano_id', $filters['cirujano_id']);
        }

        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_programada', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_programada', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('codigo_cirugia', 'like', "%{$search}%")
                    ->orWhere('nombre_cirugia', 'like', "%{$search}%")
                    ->orWhereHas('atencion.paciente', function ($qp) use ($search) {
                        $qp->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('documento_identidad', 'like', "%{$search}%");
                    });
            });
        }

        $sortField = $filters['sort_by'] ?? 'fecha_programada';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obtener cirugía por ID
     */
    public function getById(int $id): Cirugias
    {
        return Cirugias::with(['atencion.paciente', 'cirujano'])->findOrFail($id);
    }

    /**
     * Crear nueva cirugía
     */
    public function create(array $data): Cirugias
    {
        DB::beginTransaction();

        try {
            if (empty($data['codigo_cirugia'])) {
                $data['codigo_cirugia'] = Cirugias::generarCodigoCirugia();
            }

            $cirugia = Cirugias::create($data);

            DB::commit();

            Log::info('Cirugía creada', [
                'id' => $cirugia->id,
                'codigo' => $cirugia->codigo_cirugia,
            ]);

            return $cirugia->load(['atencion.paciente', 'cirujano']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cirugía', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar cirugía
     */
    public function update(int $id, array $data): Cirugias
    {
        DB::beginTransaction();

        try {
            $cirugia = $this->getById($id);
            $cirugia->update($data);

            DB::commit();

            Log::info('Cirugía actualizada', ['id' => $cirugia->id]);

            return $cirugia->fresh(['atencion.paciente', 'cirujano']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar cirugía', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Eliminar cirugía
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {
            $cirugia = $this->getById($id);

            if ($cirugia->estado_cirugia === 'En Proceso') {
                throw new \Exception("No se puede eliminar una cirugía en proceso.");
            }

            $cirugia->delete();

            DB::commit();

            Log::info('Cirugía eliminada', ['id' => $id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambiar estado de cirugía
     */
    public function cambiarEstado(int $id, string $estado): Cirugias
    {
        $cirugia = $this->getById($id);

        if ($estado === 'En Proceso') {
            $cirugia->iniciar();
        } elseif ($estado === 'Completada') {
            $cirugia->finalizar();
        } elseif ($estado === 'Cancelada') {
            $cirugia->cancelar();
        } else {
            $cirugia->estado_cirugia = $estado;
            $cirugia->save();
        }

        Log::info('Estado de cirugía actualizado', ['id' => $id, 'estado' => $estado]);

        return $cirugia->fresh(['atencion.paciente', 'cirujano']);
    }

    /**
     * Obtener cirugías programadas para hoy
     */
    public function getCirugiasHoy(?int $cirujanoId = null): Collection
    {
        $query = Cirugias::with(['atencion.paciente', 'cirujano'])
            ->whereDate('fecha_programada', Carbon::today())
            ->orderBy('hora_programada');

        if ($cirujanoId) {
            $query->where('medico_cirujano_id', $cirujanoId);
        }

        return $query->get();
    }

    /**
     * Obtener estadísticas
     */
    public function getEstadisticas(array $filters = []): array
    {
        $query = Cirugias::query();

        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_programada', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_programada', '<=', $filters['fecha_hasta']);
        }

        return [
            'total' => $query->count(),
            'por_estado' => Cirugias::select('estado_cirugia', DB::raw('count(*) as total'))
                ->groupBy('estado_cirugia')
                ->pluck('total', 'estado_cirugia'),
            'por_tipo' => Cirugias::select('tipo_cirugia', DB::raw('count(*) as total'))
                ->groupBy('tipo_cirugia')
                ->pluck('total', 'tipo_cirugia'),
            'por_clasificacion' => Cirugias::select('clasificacion', DB::raw('count(*) as total'))
                ->groupBy('clasificacion')
                ->pluck('total', 'clasificacion'),
            'hoy' => Cirugias::whereDate('fecha_programada', Carbon::today())->count(),
            'esta_semana' => Cirugias::whereBetween('fecha_programada', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'completadas_mes' => Cirugias::where('estado_cirugia', 'Completada')
                ->whereMonth('fecha_programada', Carbon::now()->month)
                ->count(),
        ];
    }

    /**
     * Buscar cirugías
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Cirugias::with(['atencion.paciente', 'cirujano'])
            ->where('codigo_cirugia', 'like', "%{$term}%")
            ->orWhere('nombre_cirugia', 'like', "%{$term}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener cirugías por paciente
     */
    public function getByPaciente(int $pacienteId): Collection
    {
        return Cirugias::with(['cirujano'])
            ->whereHas('atencion', function ($q) use ($pacienteId) {
                $q->where('paciente_id', $pacienteId);
            })
            ->orderBy('fecha_programada', 'desc')
            ->get();
    }

    /**
     * Restaurar cirugía
     */
    public function restore(int $id): Cirugias
    {
        $cirugia = Cirugias::withTrashed()->findOrFail($id);
        $cirugia->restore();

        return $cirugia->load(['atencion.paciente', 'cirujano']);
    }

    /**
     * Obtener eliminadas
     */
    public function getTrashed(): Collection
    {
        return Cirugias::onlyTrashed()->with(['atencion.paciente', 'cirujano'])->get();
    }
}
