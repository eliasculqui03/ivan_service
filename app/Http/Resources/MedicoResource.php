<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            
            // Información del médico
            'numero_colegiatura' => $this->numero_colegiatura,
            'rne' => $this->rne,
            'documento_identidad' => $this->documento_identidad,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'fecha_nacimiento' => $this->fecha_nacimiento?->toDateString(),
            'genero' => $this->genero,
            'genero_texto' => $this->genero === 'M' ? 'Masculino' : ($this->genero === 'F' ? 'Femenino' : 'Otro'),
            'edad' => $this->edad,
            'firma_digital' => $this->firma_digital,
            'sello_digital' => $this->sello_digital,
            'status' => $this->status,
            'status_texto' => $this->status ? 'Activo' : 'Inactivo',
            
            // Información del usuario
            'nombre_completo' => $this->nombre_completo,
            'titulo_profesional' => $this->titulo_profesional,
            'email' => $this->when($this->relationLoaded('user'), $this->user?->email),
            'username' => $this->when($this->relationLoaded('user'), $this->user?->username),
            'phone' => $this->when($this->relationLoaded('user'), $this->user?->phone),
            
            // Usuario completo (opcional)
            'user' => $this->when(
                $request->has('include') && str_contains($request->include, 'user'),
                fn() => [
                    'id' => $this->user?->id,
                    'name' => $this->user?->name,
                    'email' => $this->user?->email,
                    'username' => $this->user?->username,
                    'phone' => $this->user?->phone,
                    'status' => $this->user?->status,
                    'last_login_at' => $this->user?->last_login_at?->toISOString(),
                ]
            ),
            
            // Especialidad
            'especialidad_id' => $this->especialidad_id,
            'especialidad' => $this->when(
                $this->relationLoaded('especialidad'),
                fn() => [
                    'id' => $this->especialidad?->id,
                    'nombre' => $this->especialidad?->nombre,
                    'codigo' => $this->especialidad?->codigo,
                ]
            ),
            
            // Estadísticas (si están cargadas)
            'total_atenciones' => $this->when(
                $this->relationLoaded('atenciones'),
                fn() => $this->atenciones->count()
            ),
            'total_cirugias' => $this->when(
                $this->relationLoaded('cirugias'),
                fn() => $this->cirugias->count()
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->when(
                $this->deleted_at,
                $this->deleted_at?->toISOString()
            ),
            
            // Formatos amigables
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at_formatted' => $this->updated_at?->format('d/m/Y H:i'),
            'fecha_nacimiento_formatted' => $this->fecha_nacimiento?->format('d/m/Y'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
            ],
        ];
    }
}