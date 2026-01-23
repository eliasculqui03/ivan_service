<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidades extends Model
{
    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Una especialidad tiene muchos médicos
     */
    public function medicos()
    {
        return $this->hasMany(Medicos::class, 'especialidad_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para especialidades activas
     */
    public function scopeActivas($query)
    {
        return $query->where('status', true);
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener cantidad de médicos
     */
    public function getCantidadMedicosAttribute()
    {
        return $this->medicos()->count();
    }
}
