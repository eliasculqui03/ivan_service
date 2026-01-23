<?php

namespace App\Http\Controllers;

use App\Models\ArchivosAdjuntos;
use App\Http\Requests\StoreArchivoAdjuntoRequest;
use App\Http\Resources\ArchivoAdjuntoResource;
use App\Services\ArchivoAdjuntoService;
use Illuminate\Http\Request;

class ArchivoAdjuntoController extends Controller
{
    protected $archivoService;

    public function __construct(ArchivoAdjuntoService $archivoService)
    {
        $this->archivoService = $archivoService;
    }

    /**
     * Listar archivos de una entidad (Ej: ConsultaExterna #5)
     */
    public function index(Request $request)
    {
        $request->validate([
            'adjuntable_type' => 'required|string',
            'adjuntable_id' => 'required|integer',
        ]);

        $archivos = ArchivosAdjuntos::query()
            ->where('adjuntable_type', $request->adjuntable_type)
            ->where('adjuntable_id', $request->adjuntable_id)
            ->with('uploader') // Eager loading
            ->orderBy('created_at', 'desc')
            ->get();

        return ArchivoAdjuntoResource::collection($archivos);
    }

    /**
     * Subir nuevo archivo
     */
    public function store(StoreArchivoAdjuntoRequest $request)
    {
        try {
            $archivo = $this->archivoService->subirArchivo(
                $request->validated(),
                $request->file('archivo')
            );

            return response()->json([
                'message' => 'Archivo subido correctamente',
                'data' => new ArchivoAdjuntoResource($archivo)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al subir archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar archivo (útil si los archivos son privados)
     */
    public function download($id)
    {
        $archivo = ArchivosAdjuntos::findOrFail($id);

        // Aquí podrías agregar validación de policies (si el usuario puede ver este archivo)
        // $this->authorize('view', $archivo);

        return $archivo->descargar();
    }

    /**
     * Eliminar archivo
     */
    public function destroy($id)
    {
        $archivo = ArchivosAdjuntos::findOrFail($id);

        // $this->authorize('delete', $archivo);

        $this->archivoService->eliminar($archivo);

        return response()->json(['message' => 'Archivo eliminado correctamente']);
    }
}
