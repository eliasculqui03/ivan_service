<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HorarioMedicos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'horario_medicos';

    protected $fillable = [
        'medico_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
        'duracion_cita',
        'cupo_maximo',
        'activo',
        'observaciones',
    ];

    protected $casts = [
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

    public function scopePorDia($query, $dia)
    {
        return $query->where('dia_semana', $dia);
    }

    public function scopePorMedico($query, $medicoId)
    {
        return $query->where('medico_id', $medicoId);
    }

    // ==================== ACCESSORS ====================

    public function getDiaNombreAttribute()
    {
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

    // ==================== MÉTODOS ====================

    /**
     * Calcular cuántas citas caben en este horario
     */
    public function calcularCuposDisponibles()
    {
        $inicio = \Carbon\Carbon::parse($this->hora_inicio);
        $fin = \Carbon\Carbon::parse($this->hora_fin);

        $minutosTotales = $fin->diffInMinutes($inicio);
        $cuposCalculados = floor($minutosTotales / $this->duracion_cita);

        // Si hay cupo máximo definido, usar el menor
        if ($this->cupo_maximo) {
            return min($cuposCalculados, $this->cupo_maximo);
        }

        return $cuposCalculados;
    }

    /**
     * Generar horarios de citas disponibles
     */
    public function generarHorariosCitas()
    {
        $horarios = [];
        $inicio = \Carbon\Carbon::parse($this->hora_inicio);
        $fin = \Carbon\Carbon::parse($this->hora_fin);

        $actual = $inicio->copy();

        while ($actual->lt($fin)) {
            $horarios[] = $actual->format('H:i');
            $actual->addMinutes($this->duracion_cita);
        }

        return $horarios;
    }
}
