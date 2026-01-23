<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePacienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pacienteId = $this->input('id');

        return [
            'id' => ['required', 'integer', 'exists:pacientes,id'],

            // Datos personales
            'nombres' => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_paterno' => ['sometimes', 'required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'documento_identidad' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('pacientes', 'documento_identidad')->ignore($pacienteId),
            ],
            'tipo_documento' => ['sometimes', 'required', 'in:DNI,CE,Pasaporte,Otro'],
            'fecha_nacimiento' => ['sometimes', 'required', 'date', 'before:today'],
            'genero' => ['sometimes', 'required', 'in:M,F,Otro'],

            // Datos opcionales
            'numero_historia' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('pacientes', 'numero_historia')->ignore($pacienteId),
            ],
            'grupo_sanguineo' => ['nullable', 'string', 'max:10'],

            // Contacto
            'telefono' => ['nullable', 'string', 'max:20'],
            'celular' => ['nullable', 'string', 'max:20'],
            'telefono_domicilio' => ['nullable', 'string', 'max:20'],
            'telefono_oficina' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:100'],
            'correo_electronico' => ['nullable', 'email', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'distrito' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:100'],
            'departamento' => ['nullable', 'string', 'max:100'],
            'lugar_nacimiento' => ['nullable', 'string', 'max:255'],

            // Información adicional
            'ocupacion' => ['nullable', 'string', 'max:100'],

            // Contacto de emergencia
            'contacto_emergencia_nombre' => ['nullable', 'string', 'max:100'],
            'contacto_emergencia_telefono' => ['nullable', 'string', 'max:20'],
            'contacto_emergencia_parentesco' => ['nullable', 'string', 'max:50'],

            // Seguro
            'tipo_seguro' => ['nullable', 'in:SIS,EsSalud,Privado,Particular,Otro'],
            'numero_seguro' => ['nullable', 'string', 'max:50'],

            // Historial médico
            'alergias' => ['nullable', 'string'],
            'antecedentes_personales' => ['nullable', 'string'],
            'antecedentes_familiares' => ['nullable', 'string'],

            'foto_url' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombres' => 'nombres',
            'apellido_paterno' => 'apellido paterno',
            'apellido_materno' => 'apellido materno',
            'documento_identidad' => 'documento de identidad',
            'tipo_documento' => 'tipo de documento',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'genero' => 'género',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El ID del paciente es obligatorio.',
            'id.exists' => 'El paciente no existe.',
            'documento_identidad.unique' => 'Este documento de identidad ya está registrado.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'tipo_documento.in' => 'El tipo de documento no es válido.',
            'genero.in' => 'El género no es válido.',
            'email.email' => 'El correo electrónico no es válido.',
            'tipo_seguro.in' => 'El tipo de seguro no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nombres')) {
            $this->merge(['nombres' => trim($this->nombres)]);
        }

        if ($this->has('apellido_paterno')) {
            $this->merge(['apellido_paterno' => trim($this->apellido_paterno)]);
        }

        if ($this->has('documento_identidad')) {
            $this->merge(['documento_identidad' => trim($this->documento_identidad)]);
        }
    }
}
