<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultaExternaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'atencion_id' => $this->atencion_id,
            'medico_id' => $this->medico_id, // Si lo agregaste

            // ==================== INFORMACIÓN DE LA ATENCIÓN ====================
            'atencion' => $this->when(
                $this->relationLoaded('atencion'),
                fn() => [
                    'id' => $this->atencion->id,
                    'numero_atencion' => $this->atencion->numero_atencion,
                    'fecha_atencion' => $this->atencion->fecha_atencion?->toDateString(),
                    'tipo_atencion' => $this->atencion->tipo_atencion,
                    'estado' => $this->atencion->estado,
                    'medio_captacion' => $this->atencion->medio_captacion, // ✅ Marketing

                    'paciente' => $this->when(
                        $this->atencion->relationLoaded('paciente'),
                        fn() => [
                            'id' => $this->atencion->paciente->id,
                            'numero_historia' => $this->atencion->paciente->numero_historia,
                            'nombres' => $this->atencion->paciente->nombres,
                            'apellido_paterno' => $this->atencion->paciente->apellido_paterno,
                            'apellido_materno' => $this->atencion->paciente->apellido_materno,
                            'nombre_completo' => $this->atencion->paciente->nombre_completo,
                            'documento_identidad' => $this->atencion->paciente->documento_identidad,
                            'edad' => $this->atencion->paciente->edad,
                            'genero' => $this->atencion->paciente->genero,
                            // Datos sociales del paciente (centralizados)
                            'cantidad_hijos' => $this->atencion->paciente->cantidad_hijos,
                            'ultimo_embarazo' => $this->atencion->paciente->ultimo_embarazo,
                            'estado_civil' => $this->atencion->paciente->estado_civil,
                            'ocupacion' => $this->atencion->paciente->ocupacion,
                        ]
                    ),
                ]
            ),

            // ==================== DATOS SOCIALES (Snapshot en consulta) ====================
            // Estos campos existen en la tabla consulta por si queremos guardar una foto del momento,
            // aunque el frontend prioriza los del paciente.
            'cantidad_hijos' => $this->cantidad_hijos,
            'ultimo_embarazo' => $this->ultimo_embarazo,
            'ocupacion_actual' => $this->ocupacion_actual,
            'telefono_consulta' => $this->telefono_consulta,
            'direccion_consulta' => $this->direccion_consulta,

            // ==================== MOTIVOS ESTÉTICOS (LOS NUEVOS) ====================
            'motivos_zonas' => $this->motivos_zonas,
            'motivos_tratamientos_previos' => $this->motivos_tratamientos_previos,
            'expectativa_paciente' => $this->expectativa_paciente,
            'motivo_facial' => $this->motivo_facial,
            'motivo_corporal' => $this->motivo_corporal,
            'motivo_capilar' => $this->motivo_capilar,
            'otros_motivos' => $this->otros_motivos,

            // ==================== ANTECEDENTES (BOOLEANOS) ====================
            'diabetes' => (bool) $this->diabetes,
            'hipertension_arterial' => (bool) $this->hipertension_arterial,
            'cancer' => (bool) $this->cancer,
            'artritis' => (bool) $this->artritis,
            'otros_antecedentes' => $this->otros_antecedentes,
            'tratamiento_actual' => $this->tratamiento_actual,
            'intervenciones_quirurgicas' => $this->intervenciones_quirurgicas,

            // Infectocontagiosas
            'enfermedades_infectocontagiosas' => (bool) $this->enfermedades_infectocontagiosas,
            'infecciones_urinarias' => (bool) $this->infecciones_urinarias,
            'infecciones_urinarias_detalle' => $this->infecciones_urinarias_detalle,
            'pulmones' => (bool) $this->pulmones,
            'infec_gastrointestinal' => (bool) $this->infec_gastrointestinal,
            'enf_transmision_sexual' => (bool) $this->enf_transmision_sexual,
            'hepatitis' => (bool) $this->hepatitis,
            'hepatitis_tipo' => $this->hepatitis_tipo,
            'hiv' => (bool) $this->hiv,
            'otros_enfermedades' => $this->otros_enfermedades,

            // Alergias
            'medicamentos_alergia' => (bool) $this->medicamentos_alergia,
            'medicamentos_alergia_detalle' => $this->medicamentos_alergia_detalle,
            'alimentos_alergia' => (bool) $this->alimentos_alergia,
            'alimentos_alergia_detalle' => $this->alimentos_alergia_detalle,
            'otros_alergias' => $this->otros_alergias,

            // Hábitos
            'fecha_ultima_regla' => $this->fecha_ultima_regla,
            'regular' => (bool) $this->regular,
            'irregular' => (bool) $this->irregular,
            'tabaco' => (bool) $this->tabaco,
            'alcohol' => (bool) $this->alcohol,
            'farmacos' => (bool) $this->farmacos,

            // ==================== VITALES & EVALUACIÓN ====================
            'presion_arterial' => $this->presion_arterial,
            'frecuencia_cardiaca' => $this->frecuencia_cardiaca,
            'peso' => $this->peso,
            'talla' => $this->talla,
            'imc' => $this->imc,
            'evaluacion_zona' => $this->evaluacion_zona,

            // ==================== PLAN DE TRATAMIENTO ====================
            'procedimiento_propuesto' => $this->procedimiento_propuesto,
            'tecnica_utilizar' => $this->tecnica_utilizar,
            'productos_usar' => $this->productos_usar,
            'numero_sesiones' => $this->numero_sesiones,
            'precio_estimado' => $this->precio_estimado,
            'proxima_cita' => $this->proxima_cita,

            // ==================== INDICACIONES ====================
            'indicaciones_pre' => $this->indicaciones_pre,
            'indicaciones_post' => $this->indicaciones_post,

            // ==================== CONTROL ====================
            'ficha_completada' => (bool) $this->ficha_completada,
            'consentimiento_informado' => (bool) $this->consentimiento_informado,
            'consentimiento_fecha' => $this->consentimiento_fecha,
            'consentimiento_archivo_id' => $this->consentimiento_archivo_id,

            // Archivos adjuntos (si los cargas con with('archivos'))
            'archivos' => $this->when(
                $this->relationLoaded('archivos'),
                fn() => $this->archivos
            ),

            // Timestamps
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
