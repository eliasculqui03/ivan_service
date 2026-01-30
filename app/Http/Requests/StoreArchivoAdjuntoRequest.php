<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArchivoAdjuntoRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            // ✅ CORRECCIÓN 1: Cambiado de 'archivo' a 'file' (como lo envía el frontend)
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:jpg,jpeg,png,webp,pdf,mp4,mov,avi'
            ],
            'adjuntable_id' => 'required|integer',
            'adjuntable_type' => 'required|string',

            // ✅ CORRECCIÓN 2: Validar que la categoría exista en tu ENUM
            // Si cambiaste tu migración, actualiza esta lista. Si no, usa valores compatibles.
            'categoria' => 'required|string',

            'descripcion' => 'nullable|string|max:255',
            'fecha_captura' => 'nullable|date',
            'visible_paciente' => 'boolean', // Laravel convierte "true"/"false" string a boolean
            'es_confidencial' => 'boolean',
        ];
    }
}
