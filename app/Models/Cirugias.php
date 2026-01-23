<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cirugias extends Model
{
   
    use HasFactory, SoftDeletes;

    protected $table = 'cirugias';

    protected $fillable = [
        'atencion_id',
        'medico_cirujano_id',
        'codigo_cirugia',
        'nombre_cirugia',
        'descripcion_procedimiento',
        'tipo_cirugia',
        'clasificacion',
        'fecha_programada',
        'hora_programada',
        'fecha_inicio_real',
        'fecha_fin_real',
        'duracion_minutos',
        'sala_operaciones',
        'equipo_quirurgico',
        'tipo_anestesia',
        'medicamentos_anestesia',
        'diagnostico_preoperatorio',
        'cie10_preoperatorio',
        'diagnostico_postoperatorio',
        'cie10_postoperatorio',
        'descripcion_tecnica_quirurgica',
        'hallazgos_operatorios',
        'complicaciones',
        'muestras_enviadas_patologia',
        'requiere_estudio_patologico',
        'indicaciones_postoperatorias',
        'pronostico',
        'estado_cirugia',
        'consentimiento_firmado',
        'fecha_consentimiento',
        'costo_estimado',
        'costo_real',
        'observaciones',
    ];

    protected $casts = [
        'fecha_programada' => 'date',
        'fecha_inicio_real' => 'datetime',
        'fecha_fin_real' => 'datetime',
        'fecha_consentimiento' => 'datetime',
        'requiere_estudio_patologico' => 'boolean',
        'consentimiento_firmado' => 'boolean',
        'costo_estimado' => 'decimal:2',
        'costo_real' => 'decimal:2',
        'equipo_quirurgico' => 'array', // JSON
    ];

    // ==================== RELACIONES ====================

    /**
     * Una cirugía pertenece a una atención
     */
    public function atencion()
    {
        return $this->belongsTo(Atenciones::class);
    }

    /**
     * Una cirugía es realizada por un médico cirujano
     */
    public function cirujano()
    {
        return $this->belongsTo(Medicos::class, 'medico_cirujano_id');
    }

    /**
     * Archivos adjuntos de la cirugía (videos, fotos, consentimiento)
     */
    public function archivos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }

    /**
     * Videos de la cirugía
     */
    public function videos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable')
                    ->where('categoria', 'Video Cirugía');
    }

    /**
     * Fotos del procedimiento
     */
    public function fotos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable')
                    ->where('categoria', 'Foto Procedimiento');
    }

    /**
     * Consentimiento informado
     */
    public function consentimiento()
    {
        return $this->morphOne(ArchivosAdjuntos::class, 'adjuntable')
                    ->where('categoria', 'Consentimiento Informado');
    }

    // ==================== SCOPES ====================

    /**
     * Scope por estado
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado_cirugia', $estado);
    }

    /**
     * Scope para cirugías programadas
     */
    public function scopeProgramadas($query)
    {
        return $query->where('estado_cirugia', 'Programada');
    }

    /**
     * Scope para cirugías completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('estado_cirugia', 'Completada');
    }

    /**
     * Scope por fecha programada
     */
    public function scopePorFechaProgramada($query, $fecha)
    {
        return $query->whereDate('fecha_programada', $fecha);
    }

    /**
     * Scope por cirujano
     */
    public function scopePorCirujano($query, $cirujanoId)
    {
        return $query->where('medico_cirujano_id', $cirujanoId);
    }

    // ==================== ACCESSORS ====================

    /**
     * Verificar si tiene consentimiento firmado
     */
    public function getTieneConsentimientoAttribute()
    {
        return $this->consentimiento_firmado && $this->consentimiento()->exists();
    }

    /**
     * Obtener duración real en formato legible
     */
    public function getDuracionFormateadaAttribute()
    {
        if (!$this->duracion_minutos) {
            return null;
        }

        $horas = floor($this->duracion_minutos / 60);
        $minutos = $this->duracion_minutos % 60;

        return "{$horas}h {$minutos}m";
    }

    /**
     * Verificar si está en proceso
     */
    public function getEnProcesoAttribute()
    {
        return $this->estado_cirugia === 'En Proceso';
    }

    /**
     * Verificar si está completada
     */
    public function getCompletadaAttribute()
    {
        return $this->estado_cirugia === 'Completada';
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Generar código de cirugía automático
     */
    public static function generarCodigoCirugia()
    {
        $ultimo = self::latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        $fecha = now()->format('Ymd');
        return "CIR{$fecha}" . str_pad($numero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Iniciar cirugía
     */
    public function iniciar()
    {
        $this->estado_cirugia = 'En Proceso';
        $this->fecha_inicio_real = now();
        $this->save();
        return $this;
    }

    /**
     * Finalizar cirugía
     */
    public function finalizar()
    {
        $this->estado_cirugia = 'Completada';
        $this->fecha_fin_real = now();
        
        // Calcular duración
        if ($this->fecha_inicio_real) {
            $this->duracion_minutos = $this->fecha_inicio_real->diffInMinutes($this->fecha_fin_real);
        }
        
        $this->save();
        return $this;
    }

    /**
     * Cancelar cirugía
     */
    public function cancelar($motivo = null)
    {
        $this->estado_cirugia = 'Cancelada';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '') . "Cancelada: {$motivo}";
        }
        $this->save();
        return $this;
    }

    /**
     * Registrar equipo quirúrgico
     */
    public function registrarEquipo(array $equipo)
    {
        $this->equipo_quirurgico = $equipo;
        $this->save();
        return $this;
    }
}
