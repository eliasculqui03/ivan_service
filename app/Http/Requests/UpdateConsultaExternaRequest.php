<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultaExternaRequest extends FormRequest
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
            // Datos de la consulta actual
            'cantidad_hijos' => 'nullable|integer|min:0|max:20',
            'ultimo_embarazo' => 'nullable|string|max:100',
            'telefono_consulta' => 'nullable|string|max:20',
            'direccion_consulta' => 'nullable|string|max:500',
            'ocupacion_actual' => 'nullable|string|max:100',
            
            // Todos los campos boolean
            'diabetes' => 'nullable|boolean',
            'hipertension_arterial' => 'nullable|boolean',
            'cancer' => 'nullable|boolean',
            'artritis' => 'nullable|boolean',
            'otros_antecedentes' => 'nullable|string|max:1000',
            'tratamiento_actual' => 'nullable|string|max:1000',
            'intervenciones_quirurgicas' => 'nullable|string|max:1000',
            
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
            
            'medicamentos_alergia' => 'nullable|boolean',
            'medicamentos_alergia_detalle' => 'nullable|string|max:500',
            'alimentos_alergia' => 'nullable|boolean',
            'alimentos_alergia_detalle' => 'nullable|string|max:500',
            'otros_alergias' => 'nullable|string|max:500',
            
            'fecha_ultima_regla' => 'nullable|date|before_or_equal:today',
            'regular' => 'nullable|boolean',
            'irregular' => 'nullable|boolean',
            
            'tabaco' => 'nullable|boolean',
            'alcohol' => 'nullable|boolean',
            'farmacos' => 'nullable|boolean',
            
            'instagram_dr_ivan_pareja' => 'nullable|boolean',
            'facebook_dr_ivan_pareja' => 'nullable|boolean',
            'radio' => 'nullable|boolean',
            'tv' => 'nullable|boolean',
            'internet' => 'nullable|boolean',
            'referencia_otro' => 'nullable|string|max:255',
            
            // Motivos
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
            
            // Evaluación
            'examen_fisico' => 'nullable|string|max:5000',
            'diagnostico' => 'nullable|string|max:1000',
            'cie10' => 'nullable|string|max:20',
            'plan_tratamiento' => 'nullable|string|max:5000',
            'indicaciones' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string|max:2000',
            
            'ficha_completada' => 'nullable|boolean',
            'force_update' => 'nullable|boolean', // Para forzar actualización de ficha completada
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
            'cantidad_hijos' => 'cantidad de hijos',
            'ultimo_embarazo' => 'último embarazo',
            'fecha_ultima_regla' => 'fecha de última regla',
            'examen_fisico' => 'examen físico',
            'diagnostico' => 'diagnóstico',
            'cie10' => 'código CIE-10',
            'plan_tratamiento' => 'plan de tratamiento',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Misma lógica que StoreRequest
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
    }
}