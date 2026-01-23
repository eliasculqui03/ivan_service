<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicos extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medicos';

    protected $fillable = [
        'user_id',
        'especialidad_id',
        'numero_colegiatura',
        'rne',
        'documento_identidad',
        'telefono',
        'direccion',
        'fecha_nacimiento',
        'genero',
        'firma_digital',
        'sello_digital',
        'status',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'status' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Un médico pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Un médico pertenece a una especialidad
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidades::class);
    }

    /**
     * Un médico tiene muchas atenciones
     */
    public function atenciones()
    {
        return $this->hasMany(Atenciones::class, 'medico_id');
    }

    /**
     * Un médico puede realizar muchas cirugías
     */
    public function cirugias()
    {
        return $this->hasMany(Cirugias::class, 'medico_cirujano_id');
    }

    /**
     * Un médico puede solicitar muchos exámenes de laboratorio
     */
    public function examenesLaboratorio()
    {
        return $this->hasMany(ExamenesLaboratorio::class, 'medico_solicitante_id');
    }

    /**
     * Un médico cardiólogo puede realizar muchas evaluaciones
     */
    public function evaluacionesCardiologicas()
    {
        return $this->hasMany(EvaluacionesCardiologicas::class, 'cardiologo_id');
    }

    /**
     * Un médico puede solicitar muchos estudios médicos
     */
    public function estudiosMedicos()
    {
        return $this->hasMany(EstudiosMedicos::class, 'medico_solicitante_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para médicos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope por especialidad
     */
    public function scopePorEspecialidad($query, $especialidadId)
    {
        return $query->where('especialidad_id', $especialidadId);
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener nombre completo del médico
     */
    public function getNombreCompletoAttribute()
    {
        return $this->user->name;
    }

    /**
     * Obtener edad del médico
     */
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return $this->fecha_nacimiento->age;
    }

    /**
     * Título profesional
     */
    public function getTituloProfesionalAttribute()
    {
        return "Dr(a). {$this->nombre_completo}";
    }
    

}
