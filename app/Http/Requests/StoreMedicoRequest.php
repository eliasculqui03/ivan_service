<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMedicoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar permisos
        // return $this->user()->can('crear_medicos');
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
            // Datos del usuario
            'nombre_completo' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:users,email',
            ],
            'username' => [
                'nullable',
                'string',
                'max:50',
                'unique:users,username',
                'alpha_dash',
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
            ],
            
            // Datos del médico
            'especialidad_id' => [
                'required',
                'integer',
                'exists:especialidades,id',
            ],
            'numero_colegiatura' => [
                'required',
                'string',
                'max:50',
                'unique:medicos,numero_colegiatura',
            ],
            'rne' => [
                'nullable',
                'string',
                'max:50',
            ],
            'documento_identidad' => [
                'required',
                'string',
                'max:20',
                'unique:medicos,documento_identidad',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'fecha_nacimiento' => [
                'nullable',
                'date',
                'before:today',
            ],
            'genero' => [
                'nullable',
                Rule::in(['M', 'F', 'Otro']),
            ],
            'firma_digital' => [
                'nullable',
                'string',
            ],
            'sello_digital' => [
                'nullable',
                'string',
            ],
            'status' => [
                'nullable',
                'boolean',
            ],
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
            'nombre_completo' => 'nombre completo',
            'email' => 'correo electrónico',
            'username' => 'nombre de usuario',
            'password' => 'contraseña',
            'especialidad_id' => 'especialidad',
            'numero_colegiatura' => 'número de colegiatura',
            'rne' => 'RNE',
            'documento_identidad' => 'documento de identidad',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'genero' => 'género',
            'firma_digital' => 'firma digital',
            'sello_digital' => 'sello digital',
            'status' => 'estado',
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
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'nombre_completo.max' => 'El nombre completo no puede tener más de 255 caracteres.',
            
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ser un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está en uso.',
            
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'username.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.',
            
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            
            'especialidad_id.required' => 'La especialidad es obligatoria.',
            'especialidad_id.exists' => 'La especialidad seleccionada no existe.',
            
            'numero_colegiatura.required' => 'El número de colegiatura es obligatorio.',
            'numero_colegiatura.unique' => 'Este número de colegiatura ya está en uso.',
            
            'documento_identidad.required' => 'El documento de identidad es obligatorio.',
            'documento_identidad.unique' => 'Este documento de identidad ya está en uso.',
            
            'fecha_nacimiento.date' => 'Debe ser una fecha válida.',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            
            'genero.in' => 'El género debe ser M, F u Otro.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar y formatear datos
        if ($this->has('nombre_completo')) {
            $this->merge([
                'nombre_completo' => trim($this->nombre_completo),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        if ($this->has('numero_colegiatura')) {
            $this->merge([
                'numero_colegiatura' => strtoupper(trim($this->numero_colegiatura)),
            ]);
        }

        if ($this->has('documento_identidad')) {
            $this->merge([
                'documento_identidad' => trim($this->documento_identidad),
            ]);
        }

        // Generar username si no se proporciona
        if (!$this->has('username') && $this->has('nombre_completo')) {
            $username = strtolower(str_replace(' ', '.', $this->nombre_completo));
            $this->merge(['username' => $username]);
        }

        // Status por defecto
        if (!$this->has('status')) {
            $this->merge(['status' => true]);
        }
    }
}
