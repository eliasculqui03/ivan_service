<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCirugiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->input('id');

        return [
            'id' => 'required|integer|exists:cirugias,id',
            'atencion_id' => 'nullable|integer|exists:atenciones,id',
            'medico_cirujano_id' => 'nullable|integer|exists:medicos,id',
            'codigo_cirugia' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('cirugias', 'codigo_cirugia')->ignore($id),
            ],
            'nombre_cirugia' => 'nullable|string|max:255',
            'descripcion_procedimiento' => 'nullable|string',
            'tipo_cirugia' => 'nullable|in:Electiva,Urgencia,Emergencia',
            'clasificacion' => 'nullable|in:Menor,Mayor,Especializada',
            'fecha_programada' => 'nullable|date',
            'hora_programada' => 'nullable|date_format:H:i',
            'fecha_inicio_real' => 'nullable|date',
            'fecha_fin_real' => 'nullable|date',
            'duracion_minutos' => 'nullable|integer|min:0',
            'sala_operaciones' => 'nullable|string|max:50',
            'equipo_quirurgico' => 'nullable|array',
            'tipo_anestesia' => 'nullable|in:General,Regional,Local,Sedación',
            'medicamentos_anestesia' => 'nullable|string',
            'diagnostico_preoperatorio' => 'nullable|string',
            'cie10_preoperatorio' => 'nullable|string|max:20',
            'diagnostico_postoperatorio' => 'nullable|string',
            'cie10_postoperatorio' => 'nullable|string|max:20',
            'descripcion_tecnica_quirurgica' => 'nullable|string',
            'hallazgos_operatorios' => 'nullable|string',
            'complicaciones' => 'nullable|string',
            'muestras_enviadas_patologia' => 'nullable|string',
            'requiere_estudio_patologico' => 'nullable|boolean',
            'indicaciones_postoperatorias' => 'nullable|string',
            'pronostico' => 'nullable|string',
            'estado_cirugia' => 'nullable|in:Programada,En Proceso,Completada,Suspendida,Cancelada',
            'consentimiento_firmado' => 'nullable|boolean',
            'fecha_consentimiento' => 'nullable|date',
            'costo_estimado' => 'nullable|numeric|min:0',
            'costo_real' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'El ID de la cirugía es requerido',
            'id.exists' => 'La cirugía no existe',
            'atencion_id.exists' => 'La atención seleccionada no existe',
            'medico_cirujano_id.exists' => 'El cirujano seleccionado no existe',
            'tipo_cirugia.in' => 'El tipo de cirugía no es válido',
            'clasificacion.in' => 'La clasificación no es válida',
            'estado_cirugia.in' => 'El estado no es válido',
        ];
    }
}
