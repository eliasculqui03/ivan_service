<?php

namespace App\Services;

use App\Models\HorarioMedico;
use App\Models\HorarioMedicos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HorarioMedicoService
{
    /**
     * Obtener horarios de un médico
     */
    public function getHorariosPorMedico(int $medicoId): Collection
    {
        return HorarioMedicos::porMedico($medicoId)
            ->activos()
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Obtener horarios de un médico para un día específico
     */
    public function getHorariosPorMedicoYDia(int $medicoId, int $dia): Collection
    {
        return HorarioMedicos::porMedico($medicoId)
            ->porDia($dia)
            ->activos()
            ->orderBy('hora_inicio')
            ->get();
    }

    /**
     * Crear horario
     */
    public function create(array $data): HorarioMedicos
    {
        DB::beginTransaction();
        
        try {
            // Verificar que no haya conflicto de horarios
            $conflicto = $this->verificarConflicto(
                $data['medico_id'],
                $data['dia_semana'],
                $data['hora_inicio'],
                $data['hora_fin']
            );

            if ($conflicto) {
                throw new \Exception('Ya existe un horario que se superpone con este rango.');
            }

            $horario = HorarioMedicos::create($data);

            DB::commit();
            
            Log::info('Horario de médico creado', [
                'id' => $horario->id,
                'medico_id' => $horario->medico_id,
                'dia' => $horario->dia_nombre,
            ]);

            return $horario;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear horario', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar horario
     */
    public function update(int $id, array $data): HorarioMedicos
    {
        DB::beginTransaction();
        
        try {
            $horario = HorarioMedicos::findOrFail($id);

            // Verificar conflictos (excluyendo el horario actual)
            if (isset($data['hora_inicio']) && isset($data['hora_fin'])) {
                $conflicto = $this->verificarConflicto(
                    $data['medico_id'] ?? $horario->medico_id,
                    $data['dia_semana'] ?? $horario->dia_semana,
                    $data['hora_inicio'],
                    $data['hora_fin'],
                    $id
                );

                if ($conflicto) {
                    throw new \Exception('Ya existe un horario que se superpone con este rango.');
                }
            }

            $horario->update($data);

            DB::commit();
            
            Log::info('Horario actualizado', ['id' => $horario->id]);

            return $horario->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar horario
     */
    public function delete(int $id): bool
    {
        $horario = HorarioMedicos::findOrFail($id);
        $horario->delete();

        Log::info('Horario eliminado', ['id' => $id]);

        return true;
    }

    /**
     * Verificar si hay conflicto de horarios
     */
    private function verificarConflicto(
        int $medicoId, 
        int $dia, 
        string $horaInicio, 
        string $horaFin, 
        ?int $exceptId = null
    ): bool {
        $query = HorarioMedicos::porMedico($medicoId)
            ->porDia($dia)
            ->where(function($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                  ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                  ->orWhere(function($q2) use ($horaInicio, $horaFin) {
                      $q2->where('hora_inicio', '<=', $horaInicio)
                         ->where('hora_fin', '>=', $horaFin);
                  });
            });

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }

    /**
     * Obtener citas disponibles para un médico en una fecha
     */
    public function getCitasDisponibles(int $medicoId, string $fecha): array
    {
        $diaSemana = \Carbon\Carbon::parse($fecha)->dayOfWeekIso; // 1=Lunes, 7=Domingo
        
        $horarios = $this->getHorariosPorMedicoYDia($medicoId, $diaSemana);
        
        $citasDisponibles = [];
        
        foreach ($horarios as $horario) {
            $horariosGenerados = $horario->generarHorariosCitas();
            
            foreach ($horariosGenerados as $hora) {
                // Aquí podrías verificar si ya hay cita ocupada
                // Por ahora retornamos todos los horarios posibles
                $citasDisponibles[] = [
                    'hora' => $hora,
                    'horario_id' => $horario->id,
                    'duracion' => $horario->duracion_cita,
                ];
            }
        }
        
        return $citasDisponibles;
    }
}