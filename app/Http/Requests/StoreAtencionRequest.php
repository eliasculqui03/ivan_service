<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paciente_id' => ['required', 'integer', 'exists:pacientes,id'],
            'medico_id' => ['required', 'integer', 'exists:medicos,id'],

            'numero_atencion' => ['nullable', 'string', 'max:50', 'unique:atenciones,numero_atencion'],
            'fecha_atencion' => ['required', 'date'],
            'hora_ingreso' => ['required', 'date_format:H:i'],
            'hora_salida' => ['nullable', 'date_format:H:i', 'after:hora_ingreso'],

            'tipo_atencion' => [
                'required',
                'in:Consulta Externa,Emergencia,Hospitalización,Cirugía,Procedimiento,Control',
            ],
            'tipo_cobertura' => [
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
            'numero_autorizacion' => 'número de autorización',
            'motivo_consulta' => 'motivo de consulta',
            'monto_total' => 'monto total',
            'monto_cobertura' => 'monto de cobertura',
            'monto_copago' => 'monto de copago',
        ];
    }

    public function messages(): array
    {
        return [
            'paciente_id.required' => 'El paciente es obligatorio.',
            'paciente_id.exists' => 'El paciente seleccionado no existe.',
            'medico_id.required' => 'El médico es obligatorio.',
            'medico_id.exists' => 'El médico seleccionado no existe.',
            'fecha_atencion.required' => 'La fecha de atención es obligatoria.',
            'hora_ingreso.required' => 'La hora de ingreso es obligatoria.',
            'hora_ingreso.date_format' => 'La hora de ingreso debe tener el formato HH:MM.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la hora de ingreso.',
            'tipo_atencion.required' => 'El tipo de atención es obligatorio.',
            'tipo_atencion.in' => 'El tipo de atención no es válido.',
            'tipo_cobertura.required' => 'El tipo de cobertura es obligatorio.',
            'tipo_cobertura.in' => 'El tipo de cobertura no es válido.',
            'estado.in' => 'El estado no es válido.',
            'monto_total.numeric' => 'El monto total debe ser un número.',
            'monto_total.min' => 'El monto total no puede ser negativo.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('estado')) {
            $this->merge(['estado' => 'Programada']);
        }

        if (!$this->has('status')) {
            $this->merge(['status' => true]);
        }

        if (!$this->has('monto_total')) {
            $this->merge(['monto_total' => 0]);
        }

        if (!$this->has('monto_cobertura')) {
            $this->merge(['monto_cobertura' => 0]);
        }

        if (!$this->has('monto_copago')) {
            $this->merge(['monto_copago' => 0]);
        }
    }
}
