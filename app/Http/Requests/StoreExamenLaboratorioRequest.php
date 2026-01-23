<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamenLaboratorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'atencion_id' => 'required|integer|exists:atenciones,id',
            'medico_solicitante_id' => 'required|integer|exists:medicos,id',
            'numero_orden' => 'nullable|string|max:50|unique:examenes_laboratorios,numero_orden',
            'fecha_solicitud' => 'nullable|date',
            'tipo_examen' => 'required|in:Hematología,Bioquímica,Inmunología,Microbiología,Parasitología,Urianálisis,Coagulación,Gasometría,Hormonas,Marcadores Tumorales,Otro',
            'nombre_examen' => 'required|string|max:255',
            'examenes_detalle' => 'nullable|array',
            'prioridad' => 'nullable|in:Rutina,Urgente,STAT',
            'estado' => 'nullable|in:Solicitado,Muestra Tomada,En Proceso,Resultado Parcial,Completado,Cancelado',
            'tipo_muestra' => 'nullable|string|max:100',
            'condiciones_muestra' => 'nullable|string',
            'laboratorio_externo' => 'nullable|string|max:255',
            'laboratorista' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'atencion_id.required' => 'La atención es requerida',
            'atencion_id.exists' => 'La atención seleccionada no existe',
            'medico_solicitante_id.required' => 'El médico solicitante es requerido',
            'medico_solicitante_id.exists' => 'El médico seleccionado no existe',
            'tipo_examen.required' => 'El tipo de examen es requerido',
            'tipo_examen.in' => 'El tipo de examen no es válido',
            'nombre_examen.required' => 'El nombre del examen es requerido',
            'prioridad.in' => 'La prioridad no es válida',
            'estado.in' => 'El estado no es válido',
        ];
    }
}
