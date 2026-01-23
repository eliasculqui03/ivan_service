<?php

namespace App\Services;

use App\Models\ArchivosAdjuntos;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ArchivoAdjuntoService
{
    /**
     * Sube el archivo al disco y guarda el registro en BD
     */
    public function subirArchivo(array $data, UploadedFile $file)
    {
        // 1. Generar nombre único y ruta
        // Estructura sugerida: adjuntos/tipo_entidad/id_entidad/archivo.ext
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $nombreArchivo = "{$uuid}.{$extension}";
        
        // Limpiamos el nombre del modelo para la carpeta (ej: App\Models\Consulta -> Consulta)
        $carpetaEntidad = class_basename($data['adjuntable_type']);
        $ruta = "adjuntos/{$carpetaEntidad}/{$data['adjuntable_id']}";

        // 2. Guardar en disco (usando el disco 'public' o 'local' según privacidad)
        // Para historias clínicas, lo ideal es un disco privado ('local' o 's3' privado), no 'public'.
        $path = $file->storeAs($ruta, $nombreArchivo, 'public'); 

        // 3. Crear registro en BD
        return ArchivosAdjuntos::create([
            'adjuntable_type' => $data['adjuntable_type'],
            'adjuntable_id'   => $data['adjuntable_id'],
            'nombre_original' => $file->getClientOriginalName(),
            'nombre_archivo'  => $nombreArchivo,
            'ruta_archivo'    => $path,
            'tipo_mime'       => $file->getMimeType(),
            'extension'       => strtolower($extension),
            'tamanio'         => $file->getSize(),
            'categoria'       => $data['categoria'],
            'descripcion'     => $data['descripcion'] ?? null,
            'fecha_captura'   => $data['fecha_captura'] ?? now(),
            'uploaded_by'     => Auth::id(),
            'visible_paciente'=> $data['visible_paciente'] ?? false,
            'es_confidencial' => $data['es_confidencial'] ?? false,
        ]);
    }

    /**
     * Eliminar archivo lógico y físico
     */
    public function eliminar(ArchivosAdjuntos $archivo)
    {
        // Tu modelo ya tiene un método 'eliminarArchivo' que maneja el Storage
        // pero como usas SoftDeletes, decide si quieres borrar el archivo físico ahora
        // o solo marcarlo como borrado en BD.
        
        // Opción A: Borrado total (físico + lógico)
        return $archivo->eliminarArchivo(); 

        // Opción B: Solo SoftDelete (el archivo físico queda por seguridad legal)
        // return $archivo->delete();
    }
}