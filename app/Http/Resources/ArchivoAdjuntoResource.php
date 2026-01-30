<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArchivoAdjuntoResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre_original' => $this->nombre_original,
            'categoria' => $this->categoria,
            'descripcion' => $this->descripcion,

            // URLs y Formatos (Usando tus Accessors)
            'url' => $this->url,
            'tamanio_legible' => $this->tamanio_formateado,
            'tipo' => [
                'mime' => $this->tipo_mime,
                'extension' => $this->extension,
                'es_imagen' => $this->es_imagen,
                'es_video' => $this->es_video,
                'es_pdf' => $this->es_pdf,
            ],
            // Metadatos de auditoría
            'fecha_captura' => $this->fecha_captura ? $this->fecha_captura->format('Y-m-d H:i') : null,
            'subido_por' => $this->whenLoaded('uploader', function () {
                return $this->uploader->name; // Asumiendo que User tiene 'name'
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i'),

            // Flags de permisos
            'visible_paciente' => $this->visible_paciente,
            'es_confidencial' => $this->es_confidencial,
        ];
    }
}
