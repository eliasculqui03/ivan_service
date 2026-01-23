<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluacionesCardiologicas extends Model
{


    use HasFactory, SoftDeletes;

    protected $table = 'evaluaciones_cardiologicas';

    protected $fillable = [
        'atencion_id',
        'cardiologo_id',
        'numero_evaluacion',
        'fecha_evaluacion',
        'tipo_evaluacion',
        'motivo_evaluacion',
        'sintomas_cardiovasculares',
        'dolor_toracico',
        'disnea',
        'palpitaciones',
        'sincope',
        'edema',
        'antecedentes_cardiovasculares',
        'medicacion_cardiovascular',
        'presion_arterial',
        'frecuencia_cardiaca',
        'ritmo_cardiaco',
        'auscultacion_cardiaca',
        'pulsos_perifericos',
        'ecg_realizado',
        'ecg_hallazgos',
        'ecg_ritmo',
        'ecg_frecuencia',
        'ecg_eje',
        'ecg_interpretacion',
        'ecocardiograma_realizado',
        'fraccion_eyeccion',
        'ecocardiograma_hallazgos',
        'prueba_esfuerzo_realizada',
        'prueba_esfuerzo_resultados',
        'holter_realizado',
        'holter_resultados',
        'mapa_realizado',
        'mapa_resultados',
        'diagnostico_cardiologico',
        'cie10_principal',
        'diagnosticos_secundarios',
        'clase_funcional_nyha',
        'riesgo_cardiovascular',
        'recomendaciones',
        'tratamiento_sugerido',
        'aptitud_quirurgica',
        'observaciones_aptitud',
        'proxima_evaluacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_evaluacion' => 'date',
        'proxima_evaluacion' => 'date',
        'dolor_toracico' => 'boolean',
        'disnea' => 'boolean',
        'palpitaciones' => 'boolean',
        'sincope' => 'boolean',
        'edema' => 'boolean',
        'ecg_realizado' => 'boolean',
        'ecocardiograma_realizado' => 'boolean',
        'prueba_esfuerzo_realizada' => 'boolean',
        'holter_realizado' => 'boolean',
        'mapa_realizado' => 'boolean',
        'fraccion_eyeccion' => 'decimal:2',
    ];

    // ==================== RELACIONES ====================

    public function atencion()
    {
        return $this->belongsTo(Atenciones::class);
    }

    public function cardiologo()
    {
        return $this->belongsTo(Medicos::class, 'cardiologo_id');
    }

    public function archivos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }

    // ==================== MÉTODOS ====================

    public static function generarNumeroEvaluacion()
    {
        $ultimo = self::latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'CARD' . now()->format('Ymd') . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    public function getAptoParaCirugiaAttribute()
    {
        return in_array($this->aptitud_quirurgica, ['Apto', 'Apto con Riesgo']);
    }

}
