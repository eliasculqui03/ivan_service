<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultaExternaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'atencion_id' => $this->atencion_id,
            
            // Información de la atención
            'atencion' => $this->when(
                $this->relationLoaded('atencion'),
                fn() => [
                    'id' => $this->atencion?->id,
                    'numero_atencion' => $this->atencion?->numero_atencion,
                    'fecha_atencion' => $this->atencion?->fecha_atencion?->toDateString(),
                    'tipo_atencion' => $this->atencion?->tipo_atencion,
                    'estado' => $this->atencion?->estado,
                    
                    'paciente' => $this->when(
                        $this->atencion?->relationLoaded('paciente'),
                        fn() => [
                            'id' => $this->atencion->paciente?->id,
                            'numero_historia' => $this->atencion->paciente?->numero_historia,
                            'nombre_completo' => $this->atencion->paciente?->nombre_completo,
                            'documento_identidad' => $this->atencion->paciente?->documento_identidad,
                            'edad' => $this->atencion->paciente?->edad,
                            'genero' => $this->atencion->paciente?->genero,
                        ]
                    ),
                    
                    'medico' => $this->when(
                        $this->atencion?->relationLoaded('medico'),
                        fn() => [
                            'id' => $this->atencion->medico?->id,
                            'nombre_completo' => $this->atencion->medico?->nombre_completo,
                            'numero_colegiatura' => $this->atencion->medico?->numero_colegiatura,
                            'especialidad' => $this->atencion->medico?->especialidad?->nombre,
                        ]
                    ),
                ]
            ),
            
            // Datos actuales de la consulta
            'cantidad_hijos' => $this->cantidad_hijos,
            'ultimo_embarazo' => $this->ultimo_embarazo,
            'telefono_consulta' => $this->telefono_consulta,
            'direccion_consulta' => $this->direccion_consulta,
            'ocupacion_actual' => $this->ocupacion_actual,
            
            // Resúmenes calculados
            'motivos_seleccionados' => $this->motivos_seleccionados,
            'antecedentes_seleccionados' => $this->antecedentes_seleccionados,
            'canal_referencia' => $this->canal_referencia,
            'tiene_alergias' => $this->tiene_alergias,
            'tiene_habitos_nocivos' => $this->tiene_habitos_nocivos,
            
            // Evaluación médica
            'examen_fisico' => $this->examen_fisico,
            'diagnostico' => $this->diagnostico,
            'cie10' => $this->cie10,
            'plan_tratamiento' => $this->plan_tratamiento,
            'indicaciones' => $this->indicaciones,
            'observaciones' => $this->observaciones,
            
            // Control
            'ficha_completada' => $this->ficha_completada,
            'fecha_firma' => $this->fecha_firma?->toISOString(),
            'fecha_firma_formatted' => $this->fecha_firma?->format('d/m/Y H:i'),
            
            // Archivos
            'archivos_count' => $this->when(
                $this->relationLoaded('archivos'),
                fn() => $this->archivos->count()
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at_formatted' => $this->updated_at?->format('d/m/Y H:i'),
            
            // Todos los campos detallados (opcional, solo si se solicita)
            'detalles' => $this->when(
                $request->has('include_details') && $request->include_details,
                fn() => [
                    // Antecedentes
                    'antecedentes' => [
                        'diabetes' => $this->diabetes,
                        'hipertension_arterial' => $this->hipertension_arterial,
                        'cancer' => $this->cancer,
                        'artritis' => $this->artritis,
                        'otros' => $this->otros_antecedentes,
                    ],
                    // Enfermedades infecciosas
                    'enfermedades_infecciosas' => [
                        'infecciones_urinarias' => $this->infecciones_urinarias,
                        'pulmones' => $this->pulmones,
                        'hepatitis' => $this->hepatitis,
                        'hepatitis_tipo' => $this->hepatitis_tipo,
                        'hiv' => $this->hiv,
                    ],
                    // Alergias
                    'alergias' => [
                        'medicamentos' => $this->medicamentos_alergia,
                        'medicamentos_detalle' => $this->medicamentos_alergia_detalle,
                        'alimentos' => $this->alimentos_alergia,
                        'alimentos_detalle' => $this->alimentos_alergia_detalle,
                    ],
                    // Hábitos
                    'habitos' => [
                        'tabaco' => $this->tabaco,
                        'alcohol' => $this->alcohol,
                        'farmacos' => $this->farmacos,
                    ],
                    // Motivos (todos los campos)
                    'motivos' => [
                        'aumento_senos' => $this->aumento_senos,
                        'liposuccion' => $this->liposuccion,
                        'lifting_facial' => $this->lifting_facial,
                        // ... resto de motivos
                    ],
                ]
            ),
        ];
    }
}