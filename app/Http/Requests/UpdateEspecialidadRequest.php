<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEspecialidadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Verificar que el usuario tenga permiso
        // return $this->user()->can('editar_especialidades');
        
        // Por ahora permitimos a todos los usuarios autenticados
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $especialidadId = $this->route('especialidad'); // ID de la ruta

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('especialidades', 'nombre')->ignore($especialidadId),
            ],
            'codigo' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('especialidades', 'codigo')->ignore($especialidadId),
                'alpha_dash',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
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
            'nombre' => 'nombre de la especialidad',
            'codigo' => 'código',
            'descripcion' => 'descripción',
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
            'nombre.required' => 'El nombre de la especialidad es obligatorio.',
            'nombre.unique' => 'Ya existe una especialidad con este nombre.',
            'nombre.max' => 'El nombre no puede tener más de 100 caracteres.',
            
            'codigo.unique' => 'Este código ya está en uso.',
            'codigo.max' => 'El código no puede tener más de 20 caracteres.',
            'codigo.alpha_dash' => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            
            'descripcion.max' => 'La descripción no puede tener más de 500 caracteres.',
            
            'status.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar el nombre (eliminar espacios extra)
        if ($this->has('nombre')) {
            $this->merge([
                'nombre' => trim($this->nombre),
            ]);
        }

        // Convertir el código a mayúsculas
        if ($this->has('codigo') && !empty($this->codigo)) {
            $this->merge([
                'codigo' => strtoupper(trim($this->codigo)),
            ]);
        }
    }
}
