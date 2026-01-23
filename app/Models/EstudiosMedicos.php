<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstudiosMedicos extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'estudios_medicos';

    protected $fillable = [
        'atencion_id',
        'medico_solicitante_id',
        'numero_estudio',
        'fecha_solicitud',
        'fecha_realizacion',
        'tipo_estudio',
        'nombre_estudio',
        'descripcion_estudio',
        'region_anatomica',
        'prioridad',
        'estado',
        'indicacion_clinica',
        'diagnostico_presuntivo',
        'centro_diagnostico',
        'medico_radiologo',
        'tecnico_responsable',
        'tecnica_utilizada',
        'contraste_utilizado',
        'tipo_contraste',
        'hallazgos',
        'impresion_diagnostica',
        'conclusiones',
        'recomendaciones',
        'comparacion_estudios_previos',
        'informado_por',
        'fecha_informe',
        'informe_enviado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_realizacion' => 'date',
        'fecha_informe' => 'datetime',
        'contraste_utilizado' => 'boolean',
        'informe_enviado' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    public function atencion()
    {
        return $this->belongsTo(Atenciones::class);
    }

    public function medicoSolicitante()
    {
        return $this->belongsTo(Medicos::class, 'medico_solicitante_id');
    }

    public function informador()
    {
        return $this->belongsTo(User::class, 'informado_por');
    }

    public function archivos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }

    // ==================== SCOPES ====================

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_estudio', $tipo);
    }

    // ==================== MÉTODOS ====================

    public static function generarNumeroEstudio()
    {
        $ultimo = self::latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'EST' . now()->format('Ymd') . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function informar($userId)
    {
        $this->informado_por = $userId;
        $this->fecha_informe = now();
        $this->estado = 'Informado';
        $this->save();
        return $this;
    }
}

