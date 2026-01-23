<?php

namespace App\Services;

use App\Models\ConsultaExterna;
use App\Models\Atenciones;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConsultaExternaService
{
    /**
     * Obtener todas las consultas externas con paginación
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ConsultaExterna::with(['atencion.paciente', 'atencion.medico.user', 'atencion.medico.especialidad']);

        // Filtro por estado de ficha
        if (isset($filters['ficha_completada'])) {
            $query->where('ficha_completada', $filters['ficha_completada']);
        }

        // Filtro por médico
        if (isset($filters['medico_id']) && !empty($filters['medico_id'])) {
            $query->whereHas('atencion', function($q) use ($filters) {
                $q->where('medico_id', $filters['medico_id']);
            });
        }

        // Filtro por paciente
        if (isset($filters['paciente_id']) && !empty($filters['paciente_id'])) {
            $query->whereHas('atencion', function($q) use ($filters) {
                $q->where('paciente_id', $filters['paciente_id']);
            });
        }

        // Filtro por rango de fechas
        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereHas('atencion', function($q) use ($filters) {
                $q->whereDate('fecha_atencion', '>=', $filters['fecha_desde']);
            });
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereHas('atencion', function($q) use ($filters) {
                $q->whereDate('fecha_atencion', '<=', $filters['fecha_hasta']);
            });
        }

        // Búsqueda por diagnóstico
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('diagnostico', 'like', "%{$search}%")
                  ->orWhere('cie10', 'like', "%{$search}%")
                  ->orWhereHas('atencion.paciente', function($q) use ($search) {
                      $q->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellido_paterno', 'like', "%{$search}%");
                  });
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obtener consulta externa por ID
     *
     * @param int $id
     * @return ConsultaExterna
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): ConsultaExterna
    {
        return ConsultaExterna::with([
            'atencion.paciente',
            'atencion.medico.user',
            'atencion.medico.especialidad',
            'archivos'
        ])->findOrFail($id);
    }

    /**
     * Obtener consulta externa por atención
     *
     * @param int $atencionId
     * @return ConsultaExterna|null
     */
    public function getByAtencion(int $atencionId): ?ConsultaExterna
    {
        return ConsultaExterna::with([
            'atencion.paciente',
            'atencion.medico.user',
            'archivos'
        ])->where('atencion_id', $atencionId)->first();
    }

    /**
     * Crear nueva consulta externa
     *
     * @param array $data
     * @return ConsultaExterna
     * @throws \Exception
     */
    public function create(array $data): ConsultaExterna
    {
        DB::beginTransaction();
        
        try {
            // Verificar que la atención existe y no tiene consulta
            $atencion = Atenciones::findOrFail($data['atencion_id']);
            
            if ($atencion->consultaExterna()->exists()) {
                throw new \Exception("Esta atención ya tiene una consulta externa registrada.");
            }

            // Verificar que la atención sea del tipo correcto
            if ($atencion->tipo_atencion !== 'Consulta Externa') {
                throw new \Exception("Esta atención no es del tipo 'Consulta Externa'.");
            }

            // Crear consulta externa
            $consulta = ConsultaExterna::create($data);

            // Actualizar estado de la atención si está completada
            if (isset($data['ficha_completada']) && $data['ficha_completada']) {
                $atencion->update(['estado' => 'Atendida']);
            }

            DB::commit();
            
            Log::info('Consulta externa creada', [
                'id' => $consulta->id,
                'atencion_id' => $consulta->atencion_id,
                'paciente' => $atencion->paciente->nombre_completo,
            ]);

            return $consulta->fresh([
                'atencion.paciente',
                'atencion.medico.user',
                'archivos'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear consulta externa', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar consulta externa
     *
     * @param int $id
     * @param array $data
     * @return ConsultaExterna
     * @throws \Exception
     */
    public function update(int $id, array $data): ConsultaExterna
    {
        DB::beginTransaction();
        
        try {
            $consulta = $this->getById($id);

            // No permitir editar si está completada y firmada
            if ($consulta->ficha_completada && !isset($data['force_update'])) {
                throw new \Exception("No se puede editar una consulta completada y firmada.");
            }

            $consulta->update($data);

            // Actualizar estado de la atención
            if (isset($data['ficha_completada']) && $data['ficha_completada']) {
                $consulta->atencion->update(['estado' => 'Atendida']);
            }

            DB::commit();
            
            Log::info('Consulta externa actualizada', [
                'id' => $consulta->id,
                'atencion_id' => $consulta->atencion_id,
            ]);

            return $consulta->fresh([
                'atencion.paciente',
                'atencion.medico.user',
                'archivos'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar consulta externa', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar consulta externa (soft delete)
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();
        
        try {
            $consulta = $this->getById($id);

            // Verificar si está firmada
            if ($consulta->ficha_completada && $consulta->fecha_firma) {
                throw new \Exception("No se puede eliminar una consulta completada y firmada.");
            }

            $consulta->delete();

            DB::commit();
            
            Log::info('Consulta externa eliminada', [
                'id' => $consulta->id,
                'atencion_id' => $consulta->atencion_id,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar consulta externa', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Completar y firmar consulta
     *
     * @param int $id
     * @return ConsultaExterna
     */
    public function completarYFirmar(int $id): ConsultaExterna
    {
        DB::beginTransaction();

        try {
            $consulta = $this->getById($id);
            $consulta->completarYFirmar();
            
            // Marcar atención como atendida
            $consulta->atencion->update(['estado' => 'Atendida']);

            DB::commit();

            Log::info('Consulta externa completada y firmada', [
                'id' => $consulta->id,
                'fecha_firma' => $consulta->fecha_firma,
            ]);

            return $consulta->fresh([
                'atencion.paciente',
                'atencion.medico.user'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Guardar como borrador
     *
     * @param int $id
     * @return ConsultaExterna
     */
    public function guardarBorrador(int $id): ConsultaExterna
    {
        $consulta = $this->getById($id);
        $consulta->guardarBorrador();

        Log::info('Consulta externa guardada como borrador', [
            'id' => $consulta->id,
        ]);

        return $consulta->fresh();
    }

    /**
     * Obtener estadísticas de consultas
     *
     * @return array
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => ConsultaExterna::count(),
            'completadas' => ConsultaExterna::where('ficha_completada', true)->count(),
            'borradores' => ConsultaExterna::where('ficha_completada', false)->count(),
            'con_archivos' => ConsultaExterna::has('archivos')->count(),
            'por_medico' => $this->getConsultasPorMedico(),
            'motivos_frecuentes' => $this->getMotivosFrecuentes(),
        ];
    }

    /**
     * Obtener consultas agrupadas por médico
     *
     * @return array
     */
    private function getConsultasPorMedico(): array
    {
        return ConsultaExterna::select('atenciones.medico_id', DB::raw('count(*) as total'))
            ->join('atenciones', 'consultas_externas.atencion_id', '=', 'atenciones.id')
            ->with('atencion.medico.user')
            ->groupBy('atenciones.medico_id')
            ->get()
            ->map(function($item) {
                return [
                    'medico' => $item->atencion->medico->nombre_completo ?? 'Sin médico',
                    'total' => $item->total,
                ];
            })
            ->toArray();
    }

    /**
     * Obtener los motivos de consulta más frecuentes
     *
     * @return array
     */
    private function getMotivosFrecuentes(): array
    {
        $motivos = [
            'aumento_senos' => 'Aumento de senos',
            'liposuccion' => 'Liposucción',
            'lifting_facial' => 'Lifting Facial',
            'rinoplastia' => 'Rinoplastia',
            'gluteos' => 'Glúteos',
        ];

        $resultados = [];
        foreach ($motivos as $campo => $nombre) {
            $count = ConsultaExterna::where($campo, true)->count();
            if ($count > 0) {
                $resultados[] = [
                    'motivo' => $nombre,
                    'total' => $count,
                ];
            }
        }

        // Ordenar por total descendente
        usort($resultados, function($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        return array_slice($resultados, 0, 10);
    }

    /**
     * Obtener historial de consultas de un paciente
     *
     * @param int $pacienteId
     * @return Collection
     */
    public function getHistorialPaciente(int $pacienteId): Collection
    {
        return ConsultaExterna::whereHas('atencion', function($q) use ($pacienteId) {
            $q->where('paciente_id', $pacienteId);
        })
        ->with(['atencion.medico.user', 'atencion.medico.especialidad'])
        ->orderBy('created_at', 'desc')
        ->get();
    }

    /**
     * Buscar consultas por diagnóstico
     *
     * @param string $diagnostico
     * @param int $limit
     * @return Collection
     */
    public function buscarPorDiagnostico(string $diagnostico, int $limit = 10): Collection
    {
        return ConsultaExterna::with(['atencion.paciente', 'atencion.medico.user'])
            ->where('diagnostico', 'like', "%{$diagnostico}%")
            ->orWhere('cie10', 'like', "%{$diagnostico}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Restaurar consulta eliminada
     *
     * @param int $id
     * @return ConsultaExterna
     */
    public function restore(int $id): ConsultaExterna
    {
        $consulta = ConsultaExterna::withTrashed()->findOrFail($id);
        $consulta->restore();

        Log::info('Consulta externa restaurada', [
            'id' => $consulta->id,
        ]);

        return $consulta->fresh([
            'atencion.paciente',
            'atencion.medico.user'
        ]);
    }

    /**
     * Obtener consultas eliminadas
     *
     * @return Collection
     */
    public function getTrashed(): Collection
    {
        return ConsultaExterna::onlyTrashed()
            ->with(['atencion.paciente', 'atencion.medico.user'])
            ->get();
    }

    /**
     * Obtener resumen de consulta
     *
     * @param int $id
     * @return array
     */
    public function getResumen(int $id): array
    {
        $consulta = $this->getById($id);
        return $consulta->obtenerResumen();
    }
}