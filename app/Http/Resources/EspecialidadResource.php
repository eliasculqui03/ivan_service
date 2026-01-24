<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EspecialidadResource extends JsonResource
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
            'nombre' => $this->nombre,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'status' => $this->status,
            'status_texto' => $this->status ? 'Activa' : 'Inactiva',
            
            // Información de médicos (solo si está cargada la relación)
            'cantidad_medicos' => $this->when(
                $this->relationLoaded('medicos'),
                fn() => $this->medicos->count()
            ),
            
            // Cargar médicos si está solicitado
            'medicos' => $this->when(
                $request->has('include') && str_contains($request->include, 'medicos'),
                fn() => $this->medicos->map(function($medico) {
                    return [
                        'id' => $medico->id,
                        'nombre_completo' => $medico->nombre_completo,
                        'numero_colegiatura' => $medico->numero_colegiatura,
                        'status' => $medico->status,
                    ];
                })
            ),
            
            // Contador de médicos si está disponible
            'medicos_count' => $this->when(
                isset($this->medicos_count),
                $this->medicos_count
            ),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->when(
                $this->deleted_at,
                $this->deleted_at?->toISOString()
            ),
            
            // Formato amigable de fechas
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at_formatted' => $this->updated_at?->format('d/m/Y H:i'),
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