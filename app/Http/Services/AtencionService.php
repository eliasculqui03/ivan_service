<?php

namespace App\Services;

use App\Models\Atenciones;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AtencionService
{
    /**
     * Obtener todas las atenciones con paginación
     */
    public function getAllPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Atenciones::with(['paciente', 'medico']);

        // Filtro por estado activo
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por paciente
        if (isset($filters['paciente_id']) && !empty($filters['paciente_id'])) {
            $query->where('paciente_id', $filters['paciente_id']);
        }

        // Filtro por médico
        if (isset($filters['medico_id']) && !empty($filters['medico_id'])) {
            $query->where('medico_id', $filters['medico_id']);
        }

        // Filtro por tipo de atención
        if (isset($filters['tipo_atencion']) && !empty($filters['tipo_atencion'])) {
            $query->where('tipo_atencion', $filters['tipo_atencion']);
        }

        // Filtro por tipo de cobertura
        if (isset($filters['tipo_cobertura']) && !empty($filters['tipo_cobertura'])) {
            $query->where('tipo_cobertura', $filters['tipo_cobertura']);
        }

        // Filtro por estado de atención
        if (isset($filters['estado']) && !empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por rango de fechas
        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_atencion', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_atencion', '<=', $filters['fecha_hasta']);
        }

        // Filtro por búsqueda
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero_atencion', 'like', "%{$search}%")
                    ->orWhere('motivo_consulta', 'like', "%{$search}%")
                    ->orWhereHas('paciente', function ($qp) use ($search) {
                        $qp->where('nombres', 'like', "%{$search}%")
                            ->orWhere('apellido_paterno', 'like', "%{$search}%")
                            ->orWhere('documento_identidad', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenamiento
        $sortField = $filters['sort_by'] ?? 'fecha_atencion';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Obtener atenciones del día
     */
    public function getAtencionesHoy(?int $medicoId = null): Collection
    {
        $query = Atenciones::with(['paciente', 'medico'])
            ->whereDate('fecha_atencion', Carbon::today())
            ->orderBy('hora_ingreso');

        if ($medicoId) {
            $query->where('medico_id', $medicoId);
        }

        return $query->get();
    }

    /**
     * Obtener atención por ID
     */
    public function getById(int $id): Atenciones
    {
        return Atenciones::with(['paciente', 'medico', 'medico.especialidad'])->findOrFail($id);
    }

    /**
     * Crear nueva atención
     */
    public function create(array $data): Atenciones
    {
        DB::beginTransaction();

        try {
            // Generar número de atención automático
            if (empty($data['numero_atencion'])) {
                $data['numero_atencion'] = $this->generarNumeroAtencion();
            }

            $atencion = Atenciones::create($data);

            DB::commit();

            Log::info('Atención creada', [
                'id' => $atencion->id,
                'numero_atencion' => $atencion->numero_atencion,
                'paciente_id' => $atencion->paciente_id,
            ]);

            return $atencion->load(['paciente', 'medico']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear atención', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar atención
     */
    public function update(int $id, array $data): Atenciones
    {
        DB::beginTransaction();

        try {
            $atencion = $this->getById($id);
            $atencion->update($data);

            DB::commit();

            Log::info('Atención actualizada', [
                'id' => $atencion->id,
                'numero_atencion' => $atencion->numero_atencion,
            ]);

            return $atencion->fresh(['paciente', 'medico']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar atención', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar atención (soft delete)
     */
    public function delete(int $id): bool
    {
        DB::beginTransaction();

        try {
            $atencion = $this->getById($id);

            // No permitir eliminar atenciones en proceso
            if (in_array($atencion->estado, ['En Espera', 'En Atención'])) {
                throw new \Exception(
                    "No se puede eliminar una atención en estado '{$atencion->estado}'."
                );
            }

            $atencion->delete();

            DB::commit();

            Log::info('Atención eliminada', [
                'id' => $atencion->id,
                'numero_atencion' => $atencion->numero_atencion,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar atención', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Cambiar estado de la atención
     */
    public function cambiarEstado(int $id, string $estado): Atenciones
    {
        $atencion = $this->getById($id);

        $updateData = ['estado' => $estado];

        // Si se marca como atendida, registrar hora de salida
        if ($estado === 'Atendida' && empty($atencion->hora_salida)) {
            $updateData['hora_salida'] = Carbon::now()->format('H:i:s');
        }

        $atencion->update($updateData);

        Log::info('Estado de atención actualizado', [
            'id' => $atencion->id,
            'estado' => $estado,
        ]);

        return $atencion->fresh(['paciente', 'medico']);
    }

    /**
     * Registrar hora de salida
     */
    public function registrarSalida(int $id, ?string $horaSalida = null): Atenciones
    {
        $atencion = $this->getById($id);

        $atencion->update([
            'hora_salida' => $horaSalida ?? Carbon::now()->format('H:i:s'),
            'estado' => 'Atendida',
        ]);

        Log::info('Salida registrada', [
            'id' => $atencion->id,
            'hora_salida' => $atencion->hora_salida,
        ]);

        return $atencion->fresh(['paciente', 'medico']);
    }

    /**
     * Obtener estadísticas de atenciones
     */
    public function getEstadisticas(array $filters = []): array
    {
        $query = Atenciones::query();

        // Aplicar filtros de fecha
        if (isset($filters['fecha_desde']) && !empty($filters['fecha_desde'])) {
            $query->whereDate('fecha_atencion', '>=', $filters['fecha_desde']);
        }

        if (isset($filters['fecha_hasta']) && !empty($filters['fecha_hasta'])) {
            $query->whereDate('fecha_atencion', '<=', $filters['fecha_hasta']);
        }

        if (isset($filters['medico_id']) && !empty($filters['medico_id'])) {
            $query->where('medico_id', $filters['medico_id']);
        }

        $total = $query->count();

        return [
            'total' => $total,
            'por_estado' => Atenciones::select('estado', DB::raw('count(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado'),
            'por_tipo_atencion' => Atenciones::select('tipo_atencion', DB::raw('count(*) as total'))
                ->groupBy('tipo_atencion')
                ->pluck('total', 'tipo_atencion'),
            'por_tipo_cobertura' => Atenciones::select('tipo_cobertura', DB::raw('count(*) as total'))
                ->groupBy('tipo_cobertura')
                ->pluck('total', 'tipo_cobertura'),
            'hoy' => Atenciones::whereDate('fecha_atencion', Carbon::today())->count(),
            'esta_semana' => Atenciones::whereBetween('fecha_atencion', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ])->count(),
            'este_mes' => Atenciones::whereMonth('fecha_atencion', Carbon::now()->month)
                ->whereYear('fecha_atencion', Carbon::now()->year)
                ->count(),
            'ingresos_total' => Atenciones::sum('monto_total'),
            'ingresos_mes' => Atenciones::whereMonth('fecha_atencion', Carbon::now()->month)
                ->whereYear('fecha_atencion', Carbon::now()->year)
                ->sum('monto_total'),
        ];
    }

    /**
     * Buscar atenciones por término
     */
    public function search(string $term, int $limit = 10): Collection
    {
        return Atenciones::with(['paciente', 'medico'])
            ->where('numero_atencion', 'like', "%{$term}%")
            ->orWhere('motivo_consulta', 'like', "%{$term}%")
            ->orWhereHas('paciente', function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellido_paterno', 'like', "%{$term}%")
                    ->orWhere('documento_identidad', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener atenciones por paciente
     */
    public function getByPaciente(int $pacienteId): Collection
    {
        return Atenciones::with(['medico'])
            ->where('paciente_id', $pacienteId)
            ->orderBy('fecha_atencion', 'desc')
            ->get();
    }

    /**
     * Obtener atenciones por médico
     */
    public function getByMedico(int $medicoId, ?string $fecha = null): Collection
    {
        $query = Atenciones::with(['paciente'])
            ->where('medico_id', $medicoId);

        if ($fecha) {
            $query->whereDate('fecha_atencion', $fecha);
        }

        return $query->orderBy('fecha_atencion', 'desc')
            ->orderBy('hora_ingreso')
            ->get();
    }

    /**
     * Generar número de atención automático
     */
    private function generarNumeroAtencion(): string
    {
        $fecha = Carbon::now()->format('Ymd');
        $ultimo = Atenciones::where('numero_atencion', 'like', "AT{$fecha}%")
            ->orderBy('numero_atencion', 'desc')
            ->first();

        if ($ultimo) {
            $ultimoNumero = (int) substr($ultimo->numero_atencion, -4);
            $nuevoNumero = $ultimoNumero + 1;
        } else {
            $nuevoNumero = 1;
        }

        return 'AT' . $fecha . str_pad($nuevoNumero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Restaurar atención eliminada
     */
    public function restore(int $id): Atenciones
    {
        $atencion = Atenciones::withTrashed()->findOrFail($id);
        $atencion->restore();

        Log::info('Atención restaurada', [
            'id' => $atencion->id,
            'numero_atencion' => $atencion->numero_atencion,
        ]);

        return $atencion->load(['paciente', 'medico']);
    }

    /**
     * Obtener atenciones eliminadas
     */
    public function getTrashed(): Collection
    {
        return Atenciones::onlyTrashed()->with(['paciente', 'medico'])->get();
    }

    /**
     * Obtener agenda del día por médico
     */
    public function getAgenda(int $medicoId, ?string $fecha = null): array
    {
        $fecha = $fecha ? Carbon::parse($fecha) : Carbon::today();

        $atenciones = Atenciones::with(['paciente'])
            ->where('medico_id', $medicoId)
            ->whereDate('fecha_atencion', $fecha)
            ->orderBy('hora_ingreso')
            ->get();

        return [
            'fecha' => $fecha->format('Y-m-d'),
            'total_citas' => $atenciones->count(),
            'atendidas' => $atenciones->where('estado', 'Atendida')->count(),
            'pendientes' => $atenciones->whereIn('estado', ['Programada', 'En Espera'])->count(),
            'en_atencion' => $atenciones->where('estado', 'En Atención')->count(),
            'canceladas' => $atenciones->where('estado', 'Cancelada')->count(),
            'atenciones' => $atenciones,
        ];
    }
}
