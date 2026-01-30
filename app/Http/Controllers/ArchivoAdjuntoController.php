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
                $request->file('file') // 👈 IMPORTANTE: Debe decir 'file'
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
    public function getGaleriaPaciente($pacienteId)
    {
        try {
            // 1. Buscar IDs de todas las consultas externas de este paciente
            // (Relación: ConsultaExterna -> pertenece a Atencion -> pertenece a Paciente)
            $consultasIds = \App\Models\ConsultaExterna::whereHas('atencion', function ($q) use ($pacienteId) {
                $q->where('paciente_id', $pacienteId);
            })->pluck('id');

            // 2. Traer archivos que sean de esas consultas O del paciente directo
            $archivos = \App\Models\ArchivosAdjuntos::where(function ($query) use ($consultasIds) {
                $query->where('adjuntable_type', 'ConsultaExterna') // Ajusta si guardas el namespace completo 'App\Models\ConsultaExterna'
                    ->whereIn('adjuntable_id', $consultasIds);
            })
                ->orWhere(function ($query) use ($pacienteId) {
                    $query->where('adjuntable_type', 'Paciente')
                        ->where('adjuntable_id', $pacienteId);
                })
                ->with('uploader') // Cargar quién subió la foto
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['success' => true, 'data' => $archivos]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar galería: ' . $e->getMessage()
            ], 500);
        }
    }
}
