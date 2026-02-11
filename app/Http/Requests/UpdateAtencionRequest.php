<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $atencionId = $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:atenciones,id'],

            'paciente_id' => ['sometimes', 'required', 'integer', 'exists:pacientes,id'],
            'medico_id' => ['sometimes', 'required', 'integer', 'exists:medicos,id'],

            'numero_atencion' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('atenciones', 'numero_atencion')->ignore($atencionId),
            ],
            'fecha_atencion' => ['sometimes', 'required', 'date'],
            'hora_ingreso' => ['sometimes', 'required', 'date_format:H:i'],
            'hora_salida' => ['nullable', 'date_format:H:i'],

            'tipo_atencion' => [
                'sometimes',
                'required',
                'in:Consulta Externa,Emergencia,Hospitalización,Cirugía,Procedimiento,Control',
            ],
            'tipo_cobertura' => [
                'sometimes',
                'required',
                'in:SIS,EsSalud,Privado,Particular,Otro',
            ],
            'numero_autorizacion' => ['nullable', 'string', 'max:100'],

            'estado' => [
                'nullable',
                'in:Programada,En Espera,En Atención,Atendida,Cancelada,No Asistió',
            ],

            'motivo_consulta' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],

            'monto_total' => ['nullable', 'numeric', 'min:0'],
            'monto_cobertura' => ['nullable', 'numeric', 'min:0'],
            'monto_copago' => ['nullable', 'numeric', 'min:0'],
            'medio_captacion'=>['nullable', 'string'],
            'monto'=>['nullable', 'numeric', 'min:0'],

            'status' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'paciente_id' => 'paciente',
            'medico_id' => 'médico',
            'numero_atencion' => 'número de atención',
            'fecha_atencion' => 'fecha de atención',
            'hora_ingreso' => 'hora de ingreso',
            'hora_salida' => 'hora de salida',
            'tipo_atencion' => 'tipo de atención',
            'tipo_cobertura' => 'tipo de cobertura',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El ID de la atención es obligatorio.',
            'id.exists' => 'La atención no existe.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'medico_id.exists' => 'El médico seleccionado no existe.',
            'hora_ingreso.date_format' => 'La hora de ingreso debe tener el formato HH:MM.',
            'hora_salida.date_format' => 'La hora de salida debe tener el formato HH:MM.',
            'tipo_atencion.in' => 'El tipo de atención no es válido.',
            'tipo_cobertura.in' => 'El tipo de cobertura no es válido.',
            'estado.in' => 'El estado no es válido.',
            'monto_total.min' => 'El monto total no puede ser negativo.',
        ];
    }
}
