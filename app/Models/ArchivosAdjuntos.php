<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ArchivosAdjuntos extends Model
{

    use HasFactory, SoftDeletes;

    protected $table = 'archivos_adjuntos';

    protected $fillable = [
        'adjuntable_type',
        'adjuntable_id',
        'nombre_original',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_mime',
        'extension',
        'tamanio',
        'categoria',
        'descripcion',
        'orden',
        'uploaded_by',
        'fecha_captura',
        'es_confidencial',
        'visible_paciente',
    ];

    protected $casts = [
        'fecha_captura' => 'datetime',
        'es_confidencial' => 'boolean',
        'visible_paciente' => 'boolean',
    ];

    // ==================== RELACIONES ====================

    /**
     * Relación polimórfica - puede pertenecer a múltiples modelos
     */
    public function adjuntable()
    {
        return $this->morphTo();
    }

    /**
     * Usuario que subió el archivo
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ==================== SCOPES ====================

    /**
     * Scope por categoría
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    /**
     * Scope para archivos visibles al paciente
     */
    public function scopeVisiblesParaPaciente($query)
    {
        return $query->where('visible_paciente', true);
    }

    /**
     * Scope para archivos confidenciales
     */
    public function scopeConfidenciales($query)
    {
        return $query->where('es_confidencial', true);
    }

    /**
     * Scope para fotos
     */
    public function scopeFotos($query)
    {
        return $query->whereIn('categoria', ['Foto Paciente', 'Foto Procedimiento']);
    }

    /**
     * Scope para videos
     */
    public function scopeVideos($query)
    {
        return $query->where('categoria', 'Video Cirugía');
    }

    /**
     * Scope para PDFs
     */
    public function scopePdfs($query)
    {
        return $query->where('extension', 'pdf');
    }

    // ==================== ACCESSORS ====================

    /**
     * Obtener tamaño formateado
     */
    public function getTamanioFormateadoAttribute()
    {
        $bytes = $this->tamanio;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Obtener URL completa del archivo
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->ruta_archivo);
    }

    /**
     * Verificar si es una imagen
     */
    public function getEsImagenAttribute()
    {
        return in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    /**
     * Verificar si es un video
     */
    public function getEsVideoAttribute()
    {
        return in_array($this->extension, ['mp4', 'avi', 'mov', 'wmv', 'flv']);
    }

    /**
     * Verificar si es un PDF
     */
    public function getEsPdfAttribute()
    {
        return $this->extension === 'pdf';
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Eliminar archivo del storage
     */
    public function eliminarArchivo()
    {
        if (Storage::exists($this->ruta_archivo)) {
            Storage::delete($this->ruta_archivo);
        }
        return $this->delete();
    }

    /**
     * Generar thumbnail para imágenes
     */
    public function generarThumbnail()
    {
        // Implementar lógica de generación de thumbnails si es necesario
        // Usar paquetes como Intervention Image
    }

    /**
     * Descargar archivo
     */
    public function descargar()
    {
        return Storage::download($this->ruta_archivo, $this->nombre_original);
    }
}
