<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamenLaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->input('id');

        return [
            'id' => 'required|integer|exists:examenes_laboratorios,id',
            'atencion_id' => 'nullable|integer|exists:atenciones,id',
            'medico_solicitante_id' => 'nullable|integer|exists:medicos,id',
            'numero_orden' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('examenes_laboratorios', 'numero_orden')->ignore($id),
            ],
            'fecha_solicitud' => 'nullable|date',
            'fecha_toma_muestra' => 'nullable|date',
            'fecha_resultado' => 'nullable|date',
            'tipo_examen' => 'nullable|in:Hematología,Bioquímica,Inmunología,Microbiología,Parasitología,Urianálisis,Coagulación,Gasometría,Hormonas,Marcadores Tumorales,Otro',
            'nombre_examen' => 'nullable|string|max:255',
            'examenes_detalle' => 'nullable|array',
            'prioridad' => 'nullable|in:Rutina,Urgente,STAT',
            'estado' => 'nullable|in:Solicitado,Muestra Tomada,En Proceso,Resultado Parcial,Completado,Cancelado',
            'tipo_muestra' => 'nullable|string|max:100',
            'condiciones_muestra' => 'nullable|string',
            'resultados' => 'nullable|array',
            'valores_criticos' => 'nullable|string',
            'interpretacion' => 'nullable|string',
            'observaciones_laboratorio' => 'nullable|string',
            'laboratorio_externo' => 'nullable|string|max:255',
            'laboratorista' => 'nullable|string|max:255',
            'resultado_impreso' => 'nullable|boolean',
            'resultado_enviado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El ID del examen es requerido',
            'id.exists' => 'El examen no existe',
            'atencion_id.exists' => 'La atención seleccionada no existe',
            'medico_solicitante_id.exists' => 'El médico seleccionado no existe',
            'tipo_examen.in' => 'El tipo de examen no es válido',
            'prioridad.in' => 'La prioridad no es válida',
            'estado.in' => 'El estado no es válido',
        ];
    }
}
