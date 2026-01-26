<?php

namespace App\Services;

use App\Models\HorarioMedico;
use App\Models\Atenciones;
use App\Models\HorarioMedicos;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HorarioMedicoService
{
    /**
     * OPCIÓN 1: Crear horario para fecha específica
     * 
     * Ejemplo: 24-01-2026, 8:00 AM - 12:00 PM, 20 min por paciente
     */
    public function crearHorarioFechaEspecifica(array $data): HorarioMedicos
    {
        DB::beginTransaction();

        try {
            // Verificar conflicto
            $conflicto = $this->verificarConflictoFecha(
                $data['medico_id'],
                $data['fecha'],
                $data['hora_inicio'],
                $data['hora_fin']
            );

            if ($conflicto) {
                throw new \Exception('Ya existe un horario que se superpone en esta fecha.');
            }

            $horario = HorarioMedicos::create([
                'medico_id' => $data['medico_id'],
                'fecha' => $data['fecha'],
                'dia_semana' => null,  // No aplica
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'duracion_cita' => $data['duracion_cita'] ?? 30,
                'cupo_maximo' => $data['cupo_maximo'] ?? null,
                'tipo' => 'fecha_especifica',
                'activo' => true,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            DB::commit();

            Log::info('Horario fecha específica creado', [
                'id' => $horario->id,
                'medico_id' => $horario->medico_id,
                'fecha' => $horario->fecha->format('Y-m-d'),
                'horarios_generados' => count($horario->generarHorariosCitas()),
            ]);

            return $horario;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear horario fecha específica', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * OPCIÓN 2: Crear horario recurrente (cada semana)
     * 
     * Ejemplo: Todos los Lunes, 8:00 AM - 12:00 PM, 30 min por paciente
     */
    public function crearHorarioRecurrente(array $data): HorarioMedicos
    {
        DB::beginTransaction();

        try {
            // Verificar conflicto
            $conflicto = $this->verificarConflictoDia(
                $data['medico_id'],
                $data['dia_semana'],
                $data['hora_inicio'],
                $data['hora_fin']
            );

            if ($conflicto) {
                throw new \Exception('Ya existe un horario que se superpone en este día de la semana.');
            }

            $horario = HorarioMedicos::create([
                'medico_id' => $data['medico_id'],
                'fecha' => null,  // No aplica
                'dia_semana' => $data['dia_semana'],
                'hora_inicio' => $data['hora_inicio'],
                'hora_fin' => $data['hora_fin'],
                'duracion_cita' => $data['duracion_cita'] ?? 30,
                'cupo_maximo' => $data['cupo_maximo'] ?? null,
                'tipo' => 'recurrente',
                'activo' => true,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            DB::commit();

            Log::info('Horario recurrente creado', [
                'id' => $horario->id,
                'medico_id' => $horario->medico_id,
                'dia_semana' => $horario->dia_nombre,
            ]);

            return $horario;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear horario recurrente', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Obtener citas disponibles para un médico en una fecha
     * 
     * PRIORIDAD:
     * 1. Horarios de fecha específica
     * 2. Horarios recurrentes (si no hay específicos)
     */
    public function getCitasDisponibles($medicoId, $fecha)
    {
        // 1. Buscar el horario de trabajo del médico para esa fecha
        // Primero buscamos horario específico, si no, recurrente
        $horario = HorarioMedicos::where('medico_id', $medicoId)
            ->where('activo', true)
            ->where(function ($q) use ($fecha) {
                $q->where(function ($sub) use ($fecha) {
                    $sub->where('tipo', 'fecha_especifica')
                        ->whereDate('fecha', $fecha);
                })->orWhere(function ($sub) use ($fecha) {
                    $sub->where('tipo', 'recurrente')
                        ->where('dia_semana', Carbon::parse($fecha)->dayOfWeekIso);
                });
            })
            // Damos prioridad al horario específico ordenando
            ->orderByRaw("FIELD(tipo, 'fecha_especifica', 'recurrente')")
            ->first();

        if (!$horario) {
            return []; // El médico no trabaja ese día
        }

        // 2. Generar TODOS los cupos teóricos (Ej: 12 cupos de 08:00 a 12:00)
        // Esto devuelve: [['hora' => '08:00'], ['hora' => '08:20']...]
        $todosLosCupos = $horario->generarHorariosCitas();

        // 3. Buscar qué horas YA ESTÁN OCUPADAS en la tabla 'atenciones'
        // ¡AQUÍ CORREGIMOS EL ERROR DE LA COLUMNA! Usamos 'hora_ingreso'
        $horasOcupadas = Atenciones::where('medico_id', $medicoId)
            ->whereDate('fecha_atencion', $fecha)
            ->whereNotIn('estado', ['Cancelada', 'No Asistió']) // Ignoramos las canceladas (liberan cupo)
            ->where('status', true)
            ->pluck('hora_ingreso') // Obtenemos solo la columna de hora
            ->map(function ($hora) {
                // Aseguramos formato H:i (08:00) quitando segundos si los hay
                return substr($hora, 0, 5);
            })
            ->toArray();

        // 4. RESTAR: Filtrar los cupos disponibles
        $cuposLibres = [];

        foreach ($todosLosCupos as $cupo) {
            // El método generarHorariosCitas devuelve array con 'hora' y 'timestamp'
            // Verificamos si la hora del cupo (Ej: "08:20") NO está en la lista de ocupadas
            if (!in_array($cupo['hora'], $horasOcupadas)) {
                $cuposLibres[] = $cupo; // Si no está ocupada, la agregamos a disponibles
            }
        }

        return $cuposLibres;
    }

    /**
     * Generar lista de citas desde horarios (con verificación de ocupación)
     */
    private function generarCitasDesdeHorarios($horarios, $fecha): array
    {
        $citasDisponibles = [];

        foreach ($horarios as $horario) {
            $horariosGenerados = $horario->generarHorariosCitas();

            foreach ($horariosGenerados as $horaCita) {
                // Verificar si ya está ocupada
                $ocupada = $this->verificarHoraOcupada(
                    $horario->medico_id,
                    $fecha,
                    $horaCita['hora']
                );

                $citasDisponibles[] = [
                    'hora' => $horaCita['hora'],
                    'horario_id' => $horario->id,
                    'duracion' => $horario->duracion_cita,
                    'ocupada' => $ocupada,
                    'disponible' => !$ocupada,
                    'tipo_horario' => $horario->tipo,
                ];
            }
        }

        return $citasDisponibles;
    }

    /**
     * Verificar si una hora ya está ocupada
     */
    private function verificarHoraOcupada(int $medicoId, $fecha, string $hora): bool
    {
        return Atenciones::where('medico_id', $medicoId)
            ->whereDate('fecha_atencion', $fecha)
            ->where('hora_atencion', $hora)
            ->whereNotIn('estado', ['Cancelada', 'No Asistió'])
            ->exists();
    }

    /**
     * Verificar conflicto de horarios para fecha específica
     */
    private function verificarConflictoFecha(
        int $medicoId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $exceptId = null
    ): bool {
        $query = HorarioMedicos::porMedico($medicoId)
            ->where('fecha', $fecha)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                    ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                    ->orWhere(function ($q2) use ($horaInicio, $horaFin) {
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
     * Verificar conflicto de horarios para día recurrente
     */
    private function verificarConflictoDia(
        int $medicoId,
        int $dia,
        string $horaInicio,
        string $horaFin,
        ?int $exceptId = null
    ): bool {
        $query = HorarioMedicos::porMedico($medicoId)
            ->recurrente()
            ->porDia($dia)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->whereBetween('hora_inicio', [$horaInicio, $horaFin])
                    ->orWhereBetween('hora_fin', [$horaInicio, $horaFin])
                    ->orWhere(function ($q2) use ($horaInicio, $horaFin) {
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
     * Obtener todos los horarios de un médico
     */
    public function getHorariosPorMedico(int $medicoId, ?string $tipo = null): Collection
    {
        $query = HorarioMedicos::porMedico($medicoId)->activos();

        if ($tipo === 'fecha_especifica') {
            $query->fechaEspecifica()->orderBy('fecha')->orderBy('hora_inicio');
        } elseif ($tipo === 'recurrente') {
            $query->recurrente()->orderBy('dia_semana')->orderBy('hora_inicio');
        } else {
            $query->orderBy('fecha')->orderBy('dia_semana')->orderBy('hora_inicio');
        }

        return $query->get();
    }

    /**
     * Actualizar horario
     */
    public function update(int $id, array $data): HorarioMedicos
    {
        DB::beginTransaction();

        try {
            $horario = HorarioMedicos::findOrFail($id);
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
}
