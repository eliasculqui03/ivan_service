<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pacientes extends Model
{
   use HasFactory, SoftDeletes;

    protected $table = 'pacientes';

    protected $fillable = [
        'numero_historia',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'documento_identidad',
        'tipo_documento',
        'fecha_nacimiento',
        'genero',
        'grupo_sanguineo',
        'telefono',
        'celular',
        'telefono_domicilio',
        'telefono_oficina',
        'email',
        'correo_electronico',
        'direccion',
        'distrito',
        'provincia',
        'departamento',
        'lugar_nacimiento',
        'ocupacion',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'contacto_emergencia_parentesco',
        'tipo_seguro',
        'numero_seguro',
        'alergias',
        'antecedentes_personales',
        'antecedentes_familiares',
        'foto_url',
        'status',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'status' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Un paciente tiene muchas atenciones
     */
    public function atenciones()
    {
        return $this->hasMany(Atenciones::class, 'paciente_id');
    }

    /**
     * Archivos adjuntos del paciente (relación polimórfica)
     */
    public function archivos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para pacientes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('status', true);
    }

    /**
     * Buscar por documento de identidad
     */
    public function scopePorDocumento($query, $documento)
    {
        return $query->where('documento_identidad', $documento);
    }

    /**
     * Buscar por número de historia
     */
    public function scopePorNumeroHistoria($query, $numeroHistoria)
    {
        return $query->where('numero_historia', $numeroHistoria);
    }

    /**
     * Buscar por nombre
     */
    public function scopeBuscarPorNombre($query, $nombre)
    {
        return $query->where(function($q) use ($nombre) {
            $q->where('nombres', 'like', "%{$nombre}%")
              ->orWhere('apellido_paterno', 'like', "%{$nombre}%")
              ->orWhere('apellido_materno', 'like', "%{$nombre}%");
        });
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener nombre completo del paciente
     */
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    /**
     * Obtener edad del paciente
     */
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }
        return $this->fecha_nacimiento->age;
    }

    /**
     * Obtener última atención
     */
    public function getUltimaAtencionAttribute()
    {
        return $this->atenciones()->latest('fecha_atencion')->first();
    }

    /**
     * Obtener total de atenciones
     */
    public function getTotalAtencionesAttribute()
    {
        return $this->atenciones()->count();
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Generar número de historia automático
     */
    public static function generarNumeroHistoria()
    {
        $ultimo = self::latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'HC' . str_pad($numero, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si tiene alergias
     */
    public function tieneAlergias()
    {
        return !empty($this->alergias);
    }

    /**
     * Verificar si tiene antecedentes
     */
    public function tieneAntecedentes()
    {
        return !empty($this->antecedentes_personales) || !empty($this->antecedentes_familiares);
    }
}
