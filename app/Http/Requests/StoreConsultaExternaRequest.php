<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultaExternaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Atención (obligatoria)
            'atencion_id' => 'required|integer|exists:atenciones,id',
            
            // Datos de la consulta actual
            'cantidad_hijos' => 'nullable|integer|min:0|max:20',
            'ultimo_embarazo' => 'nullable|string|max:100',
            'telefono_consulta' => 'nullable|string|max:20',
            'direccion_consulta' => 'nullable|string|max:500',
            'ocupacion_actual' => 'nullable|string|max:100',
            
            // Antecedentes Clínicos (todos boolean)
            'diabetes' => 'nullable|boolean',
            'hipertension_arterial' => 'nullable|boolean',
            'cancer' => 'nullable|boolean',
            'artritis' => 'nullable|boolean',
            'otros_antecedentes' => 'nullable|string|max:1000',
            'tratamiento_actual' => 'nullable|string|max:1000',
            'intervenciones_quirurgicas' => 'nullable|string|max:1000',
            
            // Enfermedades Infecciosas
            'enfermedades_infectocontagiosas' => 'nullable|boolean',
            'infecciones_urinarias' => 'nullable|boolean',
            'infecciones_urinarias_detalle' => 'nullable|string|max:500',
            'pulmones' => 'nullable|boolean',
            'infec_gastrointestinal' => 'nullable|boolean',
            'enf_transmision_sexual' => 'nullable|boolean',
            'hepatitis' => 'nullable|boolean',
            'hepatitis_tipo' => 'nullable|string|max:50',
            'hiv' => 'nullable|boolean',
            'otros_enfermedades' => 'nullable|string|max:1000',
            
            // Alergias
            'medicamentos_alergia' => 'nullable|boolean',
            'medicamentos_alergia_detalle' => 'nullable|string|max:500',
            'alimentos_alergia' => 'nullable|boolean',
            'alimentos_alergia_detalle' => 'nullable|string|max:500',
            'otros_alergias' => 'nullable|string|max:500',
            
            // Fisiológicos
            'fecha_ultima_regla' => 'nullable|date|before_or_equal:today',
            'regular' => 'nullable|boolean',
            'irregular' => 'nullable|boolean',
            
            // Hábitos Nocivos
            'tabaco' => 'nullable|boolean',
            'alcohol' => 'nullable|boolean',
            'farmacos' => 'nullable|boolean',
            
            // Recomendado Por
            'instagram_dr_ivan_pareja' => 'nullable|boolean',
            'facebook_dr_ivan_pareja' => 'nullable|boolean',
            'radio' => 'nullable|boolean',
            'tv' => 'nullable|boolean',
            'internet' => 'nullable|boolean',
            'referencia_otro' => 'nullable|string|max:255',
            
            // Motivos de Consulta (todos boolean)
            'marcas_manchas_4k' => 'nullable|boolean',
            'flacidez' => 'nullable|boolean',
            'rellenos_faciales_corporales' => 'nullable|boolean',
            'aumento_labios' => 'nullable|boolean',
            'aumento_senos' => 'nullable|boolean',
            'ojeras' => 'nullable|boolean',
            'ptosis_facial' => 'nullable|boolean',
            'gluteos' => 'nullable|boolean',
            'levantamiento_mama' => 'nullable|boolean',
            'modelado_corporal' => 'nullable|boolean',
            'proptoplastia' => 'nullable|boolean',
            'lifting_facial' => 'nullable|boolean',
            'liposuccion' => 'nullable|boolean',
            'arrugas_alisox' => 'nullable|boolean',
            'rejuvenecimiento_facial' => 'nullable|boolean',
            'capilar' => 'nullable|boolean',
            'otros_motivos' => 'nullable|string|max:1000',
            
            // Evaluación Médica
            'examen_fisico' => 'nullable|string|max:5000',
            'diagnostico' => 'nullable|string|max:1000',
            'cie10' => 'nullable|string|max:20',
            'plan_tratamiento' => 'nullable|string|max:5000',
            'indicaciones' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string|max:2000',
            
            // Control
            'ficha_completada' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'atencion_id' => 'atención',
            'cantidad_hijos' => 'cantidad de hijos',
            'ultimo_embarazo' => 'último embarazo',
            'telefono_consulta' => 'teléfono',
            'direccion_consulta' => 'dirección',
            'ocupacion_actual' => 'ocupación actual',
            'fecha_ultima_regla' => 'fecha de última regla',
            'examen_fisico' => 'examen físico',
            'diagnostico' => 'diagnóstico',
            'cie10' => 'código CIE-10',
            'plan_tratamiento' => 'plan de tratamiento',
            'indicaciones' => 'indicaciones',
            'observaciones' => 'observaciones',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'atencion_id.required' => 'La atención es obligatoria.',
            'atencion_id.exists' => 'La atención seleccionada no existe.',
            
            'cantidad_hijos.integer' => 'La cantidad de hijos debe ser un número entero.',
            'cantidad_hijos.min' => 'La cantidad de hijos no puede ser negativa.',
            'cantidad_hijos.max' => 'La cantidad de hijos no puede ser mayor a 20.',
            
            'fecha_ultima_regla.date' => 'Debe ser una fecha válida.',
            'fecha_ultima_regla.before_or_equal' => 'La fecha no puede ser futura.',
            
            '*.boolean' => 'El campo debe ser verdadero o falso.',
            '*.string' => 'El campo debe ser texto.',
            '*.max' => 'El campo excede el tamaño máximo permitido.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convertir strings vacíos a null para campos de texto
        $textFields = [
            'ultimo_embarazo', 'telefono_consulta', 'direccion_consulta', 'ocupacion_actual',
            'otros_antecedentes', 'tratamiento_actual', 'intervenciones_quirurgicas',
            'infecciones_urinarias_detalle', 'hepatitis_tipo', 'otros_enfermedades',
            'medicamentos_alergia_detalle', 'alimentos_alergia_detalle', 'otros_alergias',
            'referencia_otro', 'otros_motivos',
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

        // Convertir 0/1 a boolean para campos boolean
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
            'arrugas_alisox', 'rejuvenecimiento_facial', 'capilar', 'ficha_completada'
        ];

        $booleanData = [];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);
                if ($value === '0' || $value === 0) {
                    $booleanData[$field] = false;
                } elseif ($value === '1' || $value === 1) {
                    $booleanData[$field] = true;
                } elseif ($value === 'false' || $value === false) {
                    $booleanData[$field] = false;
                } elseif ($value === 'true' || $value === true) {
                    $booleanData[$field] = true;
                }
            }
        }

        if (!empty($booleanData)) {
            $this->merge($booleanData);
        }
    }
}