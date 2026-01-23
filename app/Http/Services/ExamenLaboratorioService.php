<?php

namespace App\Services;

use App\Models\ExamenesLaboratorio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExamenLaboratorioService
{
    /**
     * Obtener todos los exámenes con paginación
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ExamenesLaboratorio::with(['atencion.paciente', 'medicoSolicitante']);

        if (isset($filters['estado']) && !empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (isset($filters['tipo_examen']) && !empty($filters['tipo_examen'])) {
            $query->where('tipo_examen', $filters['tipo_examen']);
        }

        if (isset($filters['prioridad']) && !empty($filters['prioridad'])) {
            $query->where('prioridad', $filters['prioridad']);
        }

        if (isset($filters['medico_id']) && !empty($filters['medico_id'])) {
            $query->where('medico_solicitante_id', $filters['medico_id']);
        }

        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_orden', 'like', "%{$search}%")
                    ->orWhere('nombre_examen', 'like', "%{$search}%")
                    ->orWhereHas('atencion.paciente', function ($qp) use ($search) {
                        $qp->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('documento_identidad', 'like', "%{$search}%");
                    });
            });
        }

        $sortField = $filters['sort_by'] ?? 'fecha_solicitud';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obtener examen por ID
     */
    public function getById(int $id): ExamenesLaboratorio
    {
        return ExamenesLaboratorio::with(['atencion.paciente', 'medicoSolicitante', 'validador'])->findOrFail($id);
    }

    /**
     * Crear nuevo examen
     */
    public function create(array $data): ExamenesLaboratorio
    {
        DB::beginTransaction();

        try {
            if (empty($data['numero_orden'])) {
                $data['numero_orden'] = ExamenesLaboratorio::generarNumeroOrden();
            }

            if (empty($data['fecha_solicitud'])) {
                $data['fecha_solicitud'] = now();
            }

            $examen = ExamenesLaboratorio::create($data);

            DB::commit();

            Log::info('Examen de laboratorio creado', [
                'id' => $examen->id,
                'numero_orden' => $examen->numero_orden,
            ]);

            return $examen->load(['atencion.paciente', 'medicoSolicitante']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear examen', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar examen
     */
    public function update(int $id, array $data): ExamenesLaboratorio
    {
        DB::beginTransaction();

        try {
            $examen = $this->getById($id);
            $examen->update($data);

            DB::commit();

            Log::info('Examen actualizado', ['id' => $examen->id]);

            return $examen->fresh(['atencion.paciente', 'medicoSolicitante']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar examen', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Eliminar examen
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {
            $examen = $this->getById($id);

            if (in_array($examen->estado, ['En Proceso', 'Completado'])) {
                throw new \Exception("No se puede eliminar un examen en estado '{$examen->estado}'.");
            }

            $examen->delete();

            DB::commit();

            Log::info('Examen eliminado', ['id' => $id]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cambiar estado
     */
    public function cambiarEstado(int $id, string $estado): ExamenesLaboratorio
    {
        $examen = $this->getById($id);
        $examen->estado = $estado;
        $examen->save();

        Log::info('Estado de examen actualizado', ['id' => $id, 'estado' => $estado]);

        return $examen->fresh(['atencion.paciente', 'medicoSolicitante']);
    }

    /**
     * Registrar toma de muestra
     */
    public function registrarMuestra(int $id, ?string $fecha = null): ExamenesLaboratorio
    {
        $examen = $this->getById($id);
        $examen->registrarTomaMuestra($fecha);

        return $examen->fresh(['atencion.paciente', 'medicoSolicitante']);
    }

    /**
     * Registrar resultados
     */
    public function registrarResultados(int $id, array $resultados, ?string $interpretacion = null, ?string $valoresCriticos = null): ExamenesLaboratorio
    {
        $examen = $this->getById($id);
        $examen->resultados = $resultados;
        $examen->interpretacion = $interpretacion;
        $examen->valores_criticos = $valoresCriticos;
        $examen->fecha_resultado = now();
        $examen->estado = 'Completado';
        $examen->save();

        Log::info('Resultados registrados', ['id' => $id]);

        return $examen->fresh(['atencion.paciente', 'medicoSolicitante']);
    }

    /**
     * Validar resultados
     */
    public function validar(int $id, int $userId): ExamenesLaboratorio
    {
        $examen = $this->getById($id);
        $examen->validar($userId);

        return $examen->fresh(['atencion.paciente', 'medicoSolicitante', 'validador']);
    }

    /**
     * Obtener exámenes pendientes
     */
    public function getPendientes(): Collection
    {
        return ExamenesLaboratorio::with(['atencion.paciente', 'medicoSolicitante'])
            ->pendientes()
            ->orderBy('prioridad', 'desc')
            ->orderBy('fecha_solicitud')
            ->get();
    }

    /**
     * Obtener exámenes urgentes
     */
    public function getUrgentes(): Collection
    {
        return ExamenesLaboratorio::with(['atencion.paciente', 'medicoSolicitante'])
            ->urgentes()
            ->pendientes()
            ->orderBy('fecha_solicitud')
            ->get();
    }

    /**
     * Obtener estadísticas
     */
    public function getEstadisticas(array $filters = []): array
    {
        $query = ExamenesLaboratorio::query();

        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_solicitud', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_solicitud', '<=', $filters['fecha_hasta']);
        }

        return [
            'total' => $query->count(),
            'por_estado' => ExamenesLaboratorio::select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'por_tipo' => ExamenesLaboratorio::select('tipo_examen', DB::raw('count(*) as total'))
                ->groupBy('tipo_examen')
                ->pluck('total', 'tipo_examen'),
            'por_prioridad' => ExamenesLaboratorio::select('prioridad', DB::raw('count(*) as total'))
                ->groupBy('prioridad')
                ->pluck('total', 'prioridad'),
            'pendientes' => ExamenesLaboratorio::pendientes()->count(),
            'urgentes_pendientes' => ExamenesLaboratorio::urgentes()->pendientes()->count(),
            'hoy' => ExamenesLaboratorio::whereDate('fecha_solicitud', Carbon::today())->count(),
            'completados_mes' => ExamenesLaboratorio::where('estado', 'Completado')
                ->whereMonth('fecha_solicitud', Carbon::now()->month)
                ->count(),
        ];
    }

    /**
     * Buscar exámenes
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return ExamenesLaboratorio::with(['atencion.paciente', 'medicoSolicitante'])
            ->where('numero_orden', 'like', "%{$term}%")
            ->orWhere('nombre_examen', 'like', "%{$term}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener exámenes por paciente
     */
    public function getByPaciente(int $pacienteId): Collection
    {
        return ExamenesLaboratorio::with(['medicoSolicitante'])
            ->whereHas('atencion', function ($q) use ($pacienteId) {
                $q->where('paciente_id', $pacienteId);
            })
            ->orderBy('fecha_solicitud', 'desc')
            ->get();
    }

    /**
     * Restaurar examen
     */
    public function restore(int $id): ExamenesLaboratorio
    {
        $examen = ExamenesLaboratorio::withTrashed()->findOrFail($id);
        $examen->restore();

        return $examen->load(['atencion.paciente', 'medicoSolicitante']);
    }

    /**
     * Obtener eliminados
     */
    public function getTrashed(): Collection
    {
        return ExamenesLaboratorio::onlyTrashed()->with(['atencion.paciente', 'medicoSolicitante'])->get();
    }
}
