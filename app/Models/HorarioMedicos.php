<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class HorarioMedicos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'horario_medicos';

   protected $fillable = [
        'medico_id',
        'fecha',                // Para fecha específica
        'dia_semana',          // Para recurrente
        'hora_inicio',
        'hora_fin',
        'duracion_cita',
        'cupo_maximo',
        'tipo',                // 'fecha_especifica' o 'recurrente'
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
        'dia_semana' => 'integer',
        'duracion_cita' => 'integer',
        'cupo_maximo' => 'integer',
    ];

    // ==================== RELACIONES ====================

    public function medico()
    {
        return $this->belongsTo(Medicos::class, 'medico_id');
    }

    // ==================== SCOPES ====================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    public function scopePorDia($query, $dia)
    {
        return $query->where('dia_semana', $dia);
    }

    public function scopePorMedico($query, $medicoId)
    {
        return $query->where('medico_id', $medicoId);
    }

    public function scopeFechaEspecifica($query)
    {
        return $query->where('tipo', 'fecha_especifica');
    }

    public function scopeRecurrente($query)
    {
        return $query->where('tipo', 'recurrente');
    }

    // ==================== ACCESSORS ====================

    public function getDiaNombreAttribute()
    {
        if (!$this->dia_semana) return null;
        
        $dias = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        return $dias[$this->dia_semana] ?? '';
    }

    public function getHorarioFormateadoAttribute()
    {
        return "{$this->hora_inicio} - {$this->hora_fin}";
    }

    public function getFechaFormateadaAttribute()
    {
        return $this->fecha ? $this->fecha->format('d/m/Y') : null;
    }

    // ==================== MÉTODOS ====================

    /**
     * Calcular cuántas citas caben en este horario
     */
    public function calcularCuposDisponibles()
    {
        $inicio = Carbon::parse($this->hora_inicio);
        $fin = Carbon::parse($this->hora_fin);
        
        $minutosTotales = $fin->diffInMinutes($inicio);
        $cuposCalculados = floor($minutosTotales / $this->duracion_cita);
        
        // Si hay cupo máximo definido, usar el menor
        if ($this->cupo_maximo) {
            return min($cuposCalculados, $this->cupo_maximo);
        }
        
        return $cuposCalculados;
    }

    /**
     * Generar lista de horarios de citas disponibles
     * 
     * Ejemplo:
     * - Hora inicio: 08:00
     * - Hora fin: 12:00
     * - Duración: 20 minutos
     * 
     * Retorna: ["08:00", "08:20", "08:40", "09:00", ...]
     */
    public function generarHorariosCitas()
    {
        $horarios = [];
        $inicio = Carbon::parse($this->hora_inicio);
        $fin = Carbon::parse($this->hora_fin);
        
        $actual = $inicio->copy();
        
        while ($actual->lt($fin)) {
            $horarios[] = [
                'hora' => $actual->format('H:i'),
                'timestamp' => $actual->format('H:i:s'),
            ];
            $actual->addMinutes($this->duracion_cita);
        }
        
        return $horarios;
    }

    /**
     * Verificar si este horario aplica para una fecha dada
     */
    public function aplicaParaFecha($fecha): bool
    {
        $fecha = Carbon::parse($fecha);
        
        if ($this->tipo === 'fecha_especifica') {
            // Solo aplica si es exactamente esa fecha
            return $this->fecha && $this->fecha->isSameDay($fecha);
        }
        
        if ($this->tipo === 'recurrente') {
            // Aplica si el día de la semana coincide
            return $this->dia_semana === $fecha->dayOfWeekIso;
        }
        
        return false;
    }
}