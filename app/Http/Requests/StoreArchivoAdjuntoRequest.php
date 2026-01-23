<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchivoAdjuntoRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Ajustar según tus Policies si es necesario
    }

    public function rules()
    {
        return [
            // El archivo es obligatorio al crear
            'archivo' => [
                'required', 
                'file', 
                'max:20480', // Máximo 20MB (ajustar según tu php.ini)
                'mimes:jpg,jpeg,png,webp,pdf,mp4,mov' // Extensiones permitidas
            ],
            
            // Relación Polimórfica (Ej: App\Models\ConsultaExterna)
            'adjuntable_id' => 'required|integer',
            'adjuntable_type' => 'required|string',
            
            // Metadatos
            'categoria' => 'required|string|max:50', // Ej: Examen Auxiliar, Foto Herida
            'descripcion' => 'nullable|string|max:255',
            'fecha_captura' => 'nullable|date',
            'visible_paciente' => 'boolean',
            'es_confidencial' => 'boolean',
        ];
    }
}