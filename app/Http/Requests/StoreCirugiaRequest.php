<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCirugiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'atencion_id' => 'required|integer|exists:atenciones,id',
            'medico_cirujano_id' => 'required|integer|exists:medicos,id',
            'codigo_cirugia' => 'nullable|string|max:50|unique:cirugias,codigo_cirugia',
            'nombre_cirugia' => 'required|string|max:255',
            'descripcion_procedimiento' => 'required|string',
            'tipo_cirugia' => 'required|in:Electiva,Urgencia,Emergencia',
            'clasificacion' => 'required|in:Menor,Mayor,Especializada',
            'fecha_programada' => 'required|date',
            'hora_programada' => 'required|date_format:H:i',
            'sala_operaciones' => 'nullable|string|max:50',
            'equipo_quirurgico' => 'nullable|array',
            'tipo_anestesia' => 'nullable|in:General,Regional,Local,Sedación',
            'medicamentos_anestesia' => 'nullable|string',
            'diagnostico_preoperatorio' => 'required|string',
            'cie10_preoperatorio' => 'nullable|string|max:20',
            'diagnostico_postoperatorio' => 'required|string',
            'cie10_postoperatorio' => 'nullable|string|max:20',
            'descripcion_tecnica_quirurgica' => 'required|string',
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
            'atencion_id.required' => 'La atención es requerida',
            'atencion_id.exists' => 'La atención seleccionada no existe',
            'medico_cirujano_id.required' => 'El cirujano es requerido',
            'medico_cirujano_id.exists' => 'El cirujano seleccionado no existe',
            'nombre_cirugia.required' => 'El nombre de la cirugía es requerido',
            'descripcion_procedimiento.required' => 'La descripción del procedimiento es requerida',
            'tipo_cirugia.required' => 'El tipo de cirugía es requerido',
            'tipo_cirugia.in' => 'El tipo de cirugía no es válido',
            'clasificacion.required' => 'La clasificación es requerida',
            'clasificacion.in' => 'La clasificación no es válida',
            'fecha_programada.required' => 'La fecha programada es requerida',
            'hora_programada.required' => 'La hora programada es requerida',
            'diagnostico_preoperatorio.required' => 'El diagnóstico preoperatorio es requerido',
            'diagnostico_postoperatorio.required' => 'El diagnóstico postoperatorio es requerido',
            'descripcion_tecnica_quirurgica.required' => 'La descripción técnica quirúrgica es requerida',
        ];
    }
}
