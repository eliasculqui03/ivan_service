<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultaExternaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // === IDENTIFICADORES ===
            'atencion_id' => 'required|integer|exists:atenciones,id',
            
            // === DATOS DEL PACIENTE (Se usan para actualizar la tabla 'pacientes') ===
            'cantidad_hijos' => 'nullable|integer',
            'ultimo_embarazo' => 'nullable|string|max:100',
            'estado_civil' => 'nullable|string|max:50',
            'ocupacion_actual' => 'nullable|string|max:150',
            'telefono_consulta' => 'nullable|string|max:20',
            'direccion_consulta' => 'nullable|string|max:255',

            // === 1. MOTIVOS ESTÉTICOS (TEXTOS - Lo que usas ahora) ===
            'motivos_zonas' => 'nullable|string',
            'motivos_tratamientos_previos' => 'nullable|string',
            'expectativa_paciente' => 'nullable|string',
            'motivo_facial' => 'nullable|string',
            'motivo_corporal' => 'nullable|string',
            'motivo_capilar' => 'nullable|string',
            'otros_motivos' => 'nullable|string',

            // === 2. ANTECEDENTES (BOOLEANOS - Estos SÍ se mantienen) ===
            'diabetes' => 'boolean',
            'hipertension_arterial' => 'boolean',
            'cancer' => 'boolean',
            'artritis' => 'boolean',
            'otros_antecedentes' => 'nullable|string',
            'tratamiento_actual' => 'nullable|string',
            'intervenciones_quirurgicas' => 'nullable|string',

            // Infectocontagiosas
            'enfermedades_infectocontagiosas' => 'boolean',
            'infecciones_urinarias' => 'boolean',
            'infecciones_urinarias_detalle' => 'nullable|string',
            'pulmones' => 'boolean',
            'infec_gastrointestinal' => 'boolean',
            'enf_transmision_sexual' => 'boolean',
            'hepatitis' => 'boolean',
            'hepatitis_tipo' => 'nullable|string',
            'hiv' => 'boolean',
            'otros_enfermedades' => 'nullable|string',

            // Alergias
            'medicamentos_alergia' => 'boolean',
            'medicamentos_alergia_detalle' => 'nullable|string',
            'alimentos_alergia' => 'boolean',
            'alimentos_alergia_detalle' => 'nullable|string',
            'otros_alergias' => 'nullable|string',

            // Hábitos y Fisiológicos
            'fecha_ultima_regla' => 'nullable|date',
            'regular' => 'boolean',
            'irregular' => 'boolean',
            'tabaco' => 'boolean',
            'alcohol' => 'boolean',
            'farmacos' => 'boolean',

            // === 3. VITALES Y EVALUACIÓN ===
            'presion_arterial' => 'nullable|string|max:20',
            'frecuencia_cardiaca' => 'nullable|string|max:20',
            'peso' => 'nullable|numeric',
            'talla' => 'nullable|numeric',
            'imc' => 'nullable|numeric', // O string, depende de tu migración
            'evaluacion_zona' => 'nullable|string',

            // === 4. PLAN DE TRATAMIENTO ===
            'procedimiento_propuesto' => 'nullable|string',
            'tecnica_utilizar' => 'nullable|string',
            'productos_usar' => 'nullable|string',
            'numero_sesiones' => 'nullable|integer',
            'precio_estimado' => 'nullable|numeric',
            'proxima_cita' => 'nullable|date',

            // === 5. INDICACIONES ===
            'indicaciones_pre' => 'nullable|string',
            'indicaciones_post' => 'nullable|string',

            // === CONTROL ===
            'ficha_completada' => 'boolean',
        
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convertir strings vacíos a null
        $textFields = [
            'ultimo_embarazo', 'telefono_consulta', 'direccion_consulta', 'ocupacion_actual',
            'otros_antecedentes', 'tratamiento_actual', 'intervenciones_quirurgicas',
            'infecciones_urinarias_detalle', 'hepatitis_tipo', 'otros_enfermedades',
            'medicamentos_alergia_detalle', 'alimentos_alergia_detalle', 'otros_alergias',
            'referencia_otro', 'otros_motivos',
            'presion_arterial', 'frecuencia_cardiaca',
            'evaluacion_zona', 'procedimiento_propuesto', 'tecnica_utilizar', 'productos_usar',
            'indicaciones_pre', 'indicaciones_post',
            'examen_fisico', 'diagnostico', 'cie10', 'plan_tratamiento', 'indicaciones', 'observaciones'
        ];

        $cleanedData = [];
        foreach ($textFields as $field) {
            if ($this->has($field) && trim($this->input($field)) === '') {
                $cleanedData[$field] = null;
            }
        }

        if (!empty($cleanedData)) {
            $this->merge($cleanedData);
        }

        // Convertir valores a boolean
        $booleanFields = [
            'diabetes', 'hipertension_arterial', 'cancer', 'artritis',
            'enfermedades_infectocontagiosas', 'infecciones_urinarias', 'pulmones',
            'infec_gastrointestinal', 'enf_transmision_sexual', 'hepatitis', 'hiv',
            'medicamentos_alergia', 'alimentos_alergia', 'regular', 'irregular',
            'tabaco', 'alcohol', 'farmacos',
            'instagram_dr_ivan_pareja', 'facebook_dr_ivan_pareja', 'radio', 'tv', 'internet',
            'marcas_manchas_4k', 'flacidez', 'rellenos_faciales_corporales', 'aumento_labios',
            'aumento_senos', 'ojeras', 'ptosis_facial', 'gluteos', 'levantamiento_mama',
            'modelado_corporal', 'proptoplastia', 'lifting_facial', 'liposuccion',
            'arrugas_alisox', 'rejuvenecimiento_facial', 'capilar',
            'consentimiento_informado', 'ficha_completada'
        ];

        $booleanData = [];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                $booleanData[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
            }
        }

        if (!empty($booleanData)) {
            $this->merge($booleanData);
        }
    }
}