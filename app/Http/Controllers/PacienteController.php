<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Services\PacienteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Http;
class PacienteController extends Controller
{
    protected PacienteService $pacienteService;

    public function __construct(PacienteService $pacienteService)
    {
        $this->pacienteService = $pacienteService;
    }

    /**
     * Display a listing of the resource.
     * POST /api/pacientes
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'tipo_documento' => $request->input('tipo_documento'),
            'genero' => $request->input('genero'),
            'tipo_seguro' => $request->input('tipo_seguro'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $pacientes = $this->pacienteService->getAllPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $pacientes,
        ]);
    }

    /**
     * Obtener todos los pacientes activos (sin paginación)
     * POST /api/pacientes/activos
     */
    public function activos(): JsonResponse
    {
        $pacientes = $this->pacienteService->getAllActive();

        return response()->json([
            'success' => true,
            'data' => $pacientes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/pacientes/store
     */
    public function store(StorePacienteRequest $request): JsonResponse
    {
        try {
            $paciente = $this->pacienteService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Paciente creado exitosamente',
                'data' => $paciente,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el paciente',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     * POST /api/pacientes/show
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $paciente = $this->pacienteService->getById($request->input('id'));

            return response()->json([
                'success' => true,
                'data' => $paciente,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * POST /api/pacientes/update
     */
  public function update(UpdatePacienteRequest $request): JsonResponse
{
    // Obtener el ID desde el body del request
    $id = $request->input('id'); 
    
    if (!$id) {
        return response()->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
    }

    try {
        $paciente = $this->pacienteService->update($id, $request->validated());
        return response()->json([
            'success' => true,
            'data' => $paciente,
            'message' => 'Paciente actualizado correctamente'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     * POST /api/pacientes/destroy
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $this->pacienteService->delete($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Paciente eliminado exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Activar/Desactivar paciente
     * POST /api/pacientes/toggle-status
     */
    public function toggleStatus(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|boolean',
        ]);

        try {
            $paciente = $this->pacienteService->toggleStatus(
                $request->input('id'),
                $request->input('status')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $paciente,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }

    /**
     * Obtener estadísticas de pacientes
     * POST /api/pacientes/stats
     */
    public function estadisticas(): JsonResponse
    {
        $estadisticas = $this->pacienteService->getEstadisticas();

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Buscar pacientes
     * POST /api/pacientes/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $pacientes = $this->pacienteService->search($term, $limit);

        return response()->json([
            'success' => true,
            'data' => $pacientes,
        ]);
    }

    /**
     * Buscar por documento de identidad
     * POST /api/pacientes/por-documento
     */
    public function porDocumento(Request $request): JsonResponse
    {
        $request->validate([
            'documento' => 'required|string',
        ]);

        try {
            $paciente = $this->pacienteService->getByDocumento($request->input('documento'));

            return response()->json([
                'success' => true,
                'data' => $paciente,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }

    /**
     * Buscar por número de historia
     * POST /api/pacientes/por-historia
     */
    public function porHistoria(Request $request): JsonResponse
    {
        $request->validate([
            'numero_historia' => 'required|string',
        ]);

        try {
            $paciente = $this->pacienteService->getByNumeroHistoria($request->input('numero_historia'));

            return response()->json([
                'success' => true,
                'data' => $paciente,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }

    /**
     * Restaurar paciente eliminado
     * POST /api/pacientes/restore
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $paciente = $this->pacienteService->restore($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Paciente restaurado exitosamente',
                'data' => $paciente,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }

    /**
     * Obtener pacientes eliminados
     * POST /api/pacientes/trashed
     */
    public function trashed(): JsonResponse
    {
        $pacientes = $this->pacienteService->getTrashed();

        return response()->json([
            'success' => true,
            'data' => $pacientes,
        ]);
    }

    /**
     * Obtener historial de atenciones del paciente
     * POST /api/pacientes/historial
     */
    public function historial(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $historial = $this->pacienteService->getHistorialAtenciones($request->input('id'));

            return response()->json([
                'success' => true,
                'data' => $historial,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paciente no encontrado',
            ], 404);
        }
    }
    public function consultarDniExterno(Request $request)
{
    $dni = $request->input('documento');

    if (strlen($dni) !== 8) {
        return response()->json(['success' => false, 'message' => 'DNI inválido'], 400);
    }

    try {
        $token = env('DECOLECTA_TOKEN');
        
        // Hacemos la petición a Decolecta desde el servidor
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->get("https://api.decolecta.com/v1/reniec/dni", [
            'numero' => $dni
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        }

        return response()->json([
            'success' => false, 
            'message' => 'No se encontraron datos en RENIEC'
        ], $response->status());

    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Error de conexión con el servicio externo'
        ], 500);
    }
}
}
