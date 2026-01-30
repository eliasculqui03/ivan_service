<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultaExterna extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consulta_externas';

    // ⚠️ ESTA ES LA LISTA DE SEGURIDAD. SI FALTA AQUÍ, NO SE GUARDA.
    protected $fillable = [
        // === IDENTIFICACIÓN ===
        'atencion_id',
        // 'medico_id', // Lo quitamos para evitar el error de columna duplicada si no la creaste

        // === DATOS SOCIALES (Snapshot) ===
        'cantidad_hijos',
        'ultimo_embarazo',
        'ocupacion_actual',
        'telefono_consulta',
        'direccion_consulta',
        'estado_civil',

        // === MOTIVOS ESTÉTICOS (TEXTOS - LOS NUEVOS) ===
        // Asegúrate que estos nombres sean IDÉNTICOS a los de tu Base de Datos
        'motivos_zonas',
        'motivos_tratamientos_previos',
        'expectativa_paciente',
        'motivo_facial',
        'motivo_corporal',
        'motivo_capilar', // Texto
        'otros_motivos',

        // === ANTECEDENTES (BOOLEANOS) ===
        'diabetes',
        'hipertension_arterial',
        'cancer',
        'artritis',
        'otros_antecedentes',
        'tratamiento_actual',
        'intervenciones_quirurgicas',

        // Infectocontagiosas
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

        // Fisiológicos / Hábitos
        'fecha_ultima_regla',
        'regular',
        'irregular',
        'tabaco',
        'alcohol',
        'farmacos',

        // === MARKETING (Si tu tabla los tiene, déjalos. Si no, no estorban) ===
        'instagram_dr_ivan_pareja',
        'facebook_dr_ivan_pareja',
        'radio',
        'tv',
        'internet',
        'referencia_otro',

        // === MOTIVOS LEGACY (BOOLEANOS) ===
        // Déjalos por si acaso tienes datos antiguos
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
        'capilar', // Boolean

        // === VITALES & EVALUACIÓN ===
        'presion_arterial',
        'frecuencia_cardiaca',
        'peso',
        'talla',
        'imc',
        'evaluacion_zona',

        // === PLAN DE TRATAMIENTO ===
        'procedimiento_propuesto',
        'tecnica_utilizar',
        'productos_usar',
        'numero_sesiones',
        'precio_estimado',
        'proxima_cita',

        // === INDICACIONES ===
        'indicaciones_pre',
        'indicaciones_post',

        // === CONTROL ===
        'ficha_completada',
        'consentimiento_informado',
        'consentimiento_fecha',
        'consentimiento_archivo_id'
    ];

    // ==================== RELACIONES ====================

    public function atencion()
    {
        return $this->belongsTo(Atenciones::class, 'atencion_id');
    }

    public function consentimientoArchivo()
    {
        return $this->belongsTo(ArchivosAdjuntos::class, 'consentimiento_archivo_id');
    }

    // ==================== MÉTODOS ====================

    public function calcularIMC()
    {
        if ($this->peso && $this->talla && $this->talla > 0) {
            $this->imc = round($this->peso / ($this->talla * $this->talla), 2);
            $this->save();
        }
        return $this;
    }
    public function archivos()
    {
        // 1er argumento: El modelo de los archivos
        // 2do argumento: El nombre del prefijo en la tabla archivos (adjuntable_type, adjuntable_id)
        return $this->morphMany(ArchivosAdjuntos::class, 'adjuntable');
    }
}
