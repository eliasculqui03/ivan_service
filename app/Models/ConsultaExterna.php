<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultaExterna extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultas_externas';

    protected $fillable = [
        'atencion_id',
        // Datos de la consulta actual
        'cantidad_hijos',
        'ultimo_embarazo',
        'telefono_consulta',
        'direccion_consulta',
        'ocupacion_actual',
        // Antecedentes Clínicos
        'diabetes',
        'hipertension_arterial',
        'cancer',
        'artritis',
        'otros_antecedentes',
        'tratamiento_actual',
        'intervenciones_quirurgicas',
        // Enfermedades Infecciosas
        'enfermedades_infectocontagiosas',
        'infecciones_urinarias',
        'infecciones_urinarias_detalle',
        'pulmones',
        'infec_gastrointestinal',
        'enf_transmision_sexual',
        'hepatitis',
        'hepatitis_tipo',
        'hiv',
        'otros_enfermedades',
        // Alergias
        'medicamentos_alergia',
        'medicamentos_alergia_detalle',
        'alimentos_alergia',
        'alimentos_alergia_detalle',
        'otros_alergias',
        // Fisiológicos
        'fecha_ultima_regla',
        'regular',
        'irregular',
        // Hábitos Nocivos
        'tabaco',
        'alcohol',
        'farmacos',
        // Recomendado Por
        'instagram_dr_ivan_pareja',
        'facebook_dr_ivan_pareja',
        'radio',
        'tv',
        'internet',
        'referencia_otro',
        // Motivo de Consulta
        'marcas_manchas_4k',
        'flacidez',
        'rellenos_faciales_corporales',
        'aumento_labios',
        'aumento_senos',
        'ojeras',
        'ptosis_facial',
        'gluteos',
        'levantamiento_mama',
        'modelado_corporal',
        'proptoplastia',
        'lifting_facial',
        'liposuccion',
        'arrugas_alisox',
        'rejuvenecimiento_facial',
        'capilar',
        'otros_motivos',
        // Campos adicionales
        'examen_fisico',
        'diagnostico',
        'cie10',
        'plan_tratamiento',
        'indicaciones',
        'observaciones',
        'fecha_firma',
        'ficha_completada',
    ];

    protected $casts = [
        'fecha_ultima_regla' => 'date',
        'fecha_firma' => 'datetime',
        'diabetes' => 'boolean',
        'hipertension_arterial' => 'boolean',
        'cancer' => 'boolean',
        'artritis' => 'boolean',
        'enfermedades_infectocontagiosas' => 'boolean',
        'infecciones_urinarias' => 'boolean',
        'pulmones' => 'boolean',
        'infec_gastrointestinal' => 'boolean',
        'enf_transmision_sexual' => 'boolean',
        'hepatitis' => 'boolean',
        'hiv' => 'boolean',
        'medicamentos_alergia' => 'boolean',
        'alimentos_alergia' => 'boolean',
        'regular' => 'boolean',
        'irregular' => 'boolean',
        'tabaco' => 'boolean',
        'alcohol' => 'boolean',
        'farmacos' => 'boolean',
        'instagram_dr_ivan_pareja' => 'boolean',
        'facebook_dr_ivan_pareja' => 'boolean',
        'radio' => 'boolean',
        'tv' => 'boolean',
        'internet' => 'boolean',
        'marcas_manchas_4k' => 'boolean',
        'flacidez' => 'boolean',
        'rellenos_faciales_corporales' => 'boolean',
        'aumento_labios' => 'boolean',
        'aumento_senos' => 'boolean',
        'ojeras' => 'boolean',
        'ptosis_facial' => 'boolean',
        'gluteos' => 'boolean',
        'levantamiento_mama' => 'boolean',
        'modelado_corporal' => 'boolean',
        'proptoplastia' => 'boolean',
        'lifting_facial' => 'boolean',
        'liposuccion' => 'boolean',
        'arrugas_alisox' => 'boolean',
        'rejuvenecimiento_facial' => 'boolean',
        'capilar' => 'boolean',
        'ficha_completada' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Una consulta externa pertenece a una atención
     */
    public function atencion()
    {
        return $this->belongsTo(Atenciones::class);
    }

    /**
     * Archivos adjuntos de la consulta (relación polimórfica)
     */
    public function archivos()
    {
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }

    // ==================== SCOPES ====================

    /**
     * Scope para consultas completadas
     */
    public function scopeCompletadas($query)
    {
        return $query->where('ficha_completada', true);
    }

    /**
     * Scope para borradores
     */
    public function scopeBorradores($query)
    {
        return $query->where('ficha_completada', false);
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener lista de motivos de consulta seleccionados
     */
    public function getMotivosSeleccionadosAttribute()
    {
        $motivos = [];
        
        $camposMotivos = [
            'marcas_manchas_4k' => 'Marcas/Manchas 4K',
            'flacidez' => 'Flacidez',
            'rellenos_faciales_corporales' => 'Rellenos faciales o corporales',
            'aumento_labios' => 'Aumento de labios',
            'aumento_senos' => 'Aumento de senos',
            'ojeras' => 'Ojeras',
            'ptosis_facial' => 'Ptosis Facial',
            'gluteos' => 'Glúteos',
            'levantamiento_mama' => 'Levantamiento de Mama',
            'modelado_corporal' => 'Modelado Corporal',
            'proptoplastia' => 'Proptoplastia',
            'lifting_facial' => 'Lifting Facial',
            'liposuccion' => 'Liposucción',
            'arrugas_alisox' => 'Arrugas alisox',
            'rejuvenecimiento_facial' => 'Rejuvenecimiento Facial',
            'capilar' => 'Capilar',
        ];

        foreach ($camposMotivos as $campo => $nombre) {
            if ($this->$campo) {
                $motivos[] = $nombre;
            }
        }

        if ($this->otros_motivos) {
            $motivos[] = $this->otros_motivos;
        }

        return $motivos;
    }

    /**
     * Obtener lista de antecedentes seleccionados
     */
    public function getAntecedentesSeleccionadosAttribute()
    {
        $antecedentes = [];
        
        if ($this->diabetes) $antecedentes[] = 'Diabetes';
        if ($this->hipertension_arterial) $antecedentes[] = 'Hipertensión Arterial';
        if ($this->cancer) $antecedentes[] = 'Cáncer';
        if ($this->artritis) $antecedentes[] = 'Artritis';
        
        return $antecedentes;
    }

    /**
     * Obtener canal de referencia
     */
    public function getCanalReferenciaAttribute()
    {
        if ($this->instagram_dr_ivan_pareja) return 'Instagram Dr Ivan Pareja';
        if ($this->facebook_dr_ivan_pareja) return 'Facebook Dr Ivan Pareja';
        if ($this->radio) return 'Radio';
        if ($this->tv) return 'TV';
        if ($this->internet) return 'Internet';
        if ($this->referencia_otro) return $this->referencia_otro;
        
        return 'No especificado';
    }

    /**
     * Verificar si tiene alergias
     */
    public function getTieneAlergiasAttribute()
    {
        return $this->medicamentos_alergia || $this->alimentos_alergia;
    }

    /**
     * Verificar si tiene hábitos nocivos
     */
    public function getTieneHabitosNocivosAttribute()
    {
        return $this->tabaco || $this->alcohol || $this->farmacos;
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Marcar como completada y firmar
     */
    public function completarYFirmar()
    {
        $this->ficha_completada = true;
        $this->fecha_firma = now();
        $this->save();
        return $this;
    }

    /**
     * Guardar como borrador
     */
    public function guardarBorrador()
    {
        $this->ficha_completada = false;
        $this->save();
        return $this;
    }

    /**
     * Obtener resumen de la consulta
     */
    public function obtenerResumen()
    {
        return [
            'motivos' => $this->motivos_seleccionados,
            'antecedentes' => $this->antecedentes_seleccionados,
            'alergias' => $this->tiene_alergias,
            'habitos_nocivos' => $this->tiene_habitos_nocivos,
            'diagnostico' => $this->diagnostico,
            'completada' => $this->ficha_completada,
        ];
    }
}
