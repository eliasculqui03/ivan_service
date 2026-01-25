<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atenciones extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'atenciones';

    // Definimos qué campos se pueden guardar en la base de datos
    protected $fillable = [
        'numero_atencion',
        'numero_historia',  // Opcional, por si guardas copia del nro historia aquí
        'paciente_id',      // LLAVE FORÁNEA (Crucial)
        'medico_id',        // LLAVE FORÁNEA (Crucial)
        'especialidad_id',  // Opcional, si filtras por especialidad
        'tipo_atencion',    // Ej: 'Consulta', 'Emergencia'
        'tipo_cobertura',   // Ej: 'SIS', 'Particular'
        'fecha_atencion',
        'hora_ingreso',
        'motivo_consulta',
        'observaciones',
        'estado',           // Ej: 'Pendiente', 'Atendida'
        'status'            // Activo/Inactivo (boolean)
    ];

    // Convertimos datos automáticamente
    protected $casts = [
        'fecha_atencion' => 'date',
        'status' => 'boolean',
    ];

    // ==================== RELACIONES ====================
    // Aquí es donde solucionamos tu error "Undefined relationship [paciente]"

    /**
     * Relación: Una atención pertenece a un Paciente.
     */
    public function paciente()
    {
        // belongsTo(Modelo, 'llave_foranea_local', 'id_del_otro_modelo')
        return $this->belongsTo(Pacientes::class, 'paciente_id');
    }

    /**
     * Relación: Una atención pertenece a un Médico.
     */
    public function medico()
    {
        return $this->belongsTo(Medicos::class, 'medico_id');
    }

    /**
     * (Opcional) Relación: Una atención pertenece a una Especialidad.
     */
    public function especialidad()
    {
        return $this->belongsTo(Especialidades::class, 'especialidad_id');
    }

    // ==================== SCOPES (Filtros rápidos) ====================

    /**
     * Scope para filtrar solo atenciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('status', true);
    }
}