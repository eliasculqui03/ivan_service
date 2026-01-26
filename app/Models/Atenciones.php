<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atenciones extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'atenciones';

    protected $fillable = [
        'numero_atencion',
        'numero_historia',
        'paciente_id',
        'medico_id',
        'especialidad_id',
        'tipo_atencion',
        'tipo_cobertura',
        'fecha_atencion',
        'hora_ingreso',
        'hora_salida',
        'motivo_consulta',
        'observaciones',
        'estado',
        'status'
    ];

    protected $casts = [
        'fecha_atencion' => 'date',
        'status' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación: Una atención pertenece a un Paciente
     */
    public function paciente()
    {
        return $this->belongsTo(Pacientes::class, 'paciente_id');
    }

    /**
     * Relación: Una atención pertenece a un Médico
     */
    public function medico()
    {
        return $this->belongsTo(Medicos::class, 'medico_id');
    }

    /**
     * Relación: Una atención pertenece a una Especialidad (opcional)
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidades::class, 'especialidad_id');
    }

    /**
     * ✅ CRÍTICO: Relación con Consulta Externa
     * Una atención tiene UNA consulta externa (hasOne)
     */
    public function consultaExterna()
    {
        return $this->hasOne(ConsultaExterna::class, 'atencion_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para filtrar solo atenciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('status', true);
    }
}