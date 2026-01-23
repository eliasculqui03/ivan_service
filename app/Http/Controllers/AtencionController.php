<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAtencionRequest;
use App\Http\Requests\UpdateAtencionRequest;
use App\Services\AtencionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AtencionController extends Controller
{
    protected AtencionService $atencionService;

    public function __construct(AtencionService $atencionService)
    {
        $this->atencionService = $atencionService;
    }

    /**
     * Display a listing of the resource.
     * POST /api/atenciones
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'paciente_id' => $request->input('paciente_id'),
            'medico_id' => $request->input('medico_id'),
            'tipo_atencion' => $request->input('tipo_atencion'),
            'tipo_cobertura' => $request->input('tipo_cobertura'),
            'estado' => $request->input('estado'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'sort_by' => $request->input('sort_by', 'fecha_atencion'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $atenciones = $this->atencionService->getAllPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Obtener atenciones del día
     * POST /api/atenciones/hoy
     */
    public function hoy(Request $request): JsonResponse
    {
        $medicoId = $request->input('medico_id');
        $atenciones = $this->atencionService->getAtencionesHoy($medicoId);

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/atenciones/store
     */
    public function store(StoreAtencionRequest $request): JsonResponse
    {
        try {
            $atencion = $this->atencionService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Atención creada exitosamente',
                'data' => $atencion,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la atención',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     * POST /api/atenciones/show
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $atencion = $this->atencionService->getById($request->input('id'));

            return response()->json([
                'success' => true,
                'data' => $atencion,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     * POST /api/atenciones/update
     */
    public function update(UpdateAtencionRequest $request): JsonResponse
    {
        try {
            $atencion = $this->atencionService->update(
                $request->input('id'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Atención actualizada exitosamente',
                'data' => $atencion,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la atención',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     * POST /api/atenciones/destroy
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $this->atencionService->delete($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Atención eliminada exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cambiar estado de la atención
     * POST /api/atenciones/cambiar-estado
     */
    public function cambiarEstado(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'estado' => 'required|in:Programada,En Espera,En Atención,Atendida,Cancelada,No Asistió',
        ]);

        try {
            $atencion = $this->atencionService->cambiarEstado(
                $request->input('id'),
                $request->input('estado')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $atencion,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        }
    }

    /**
     * Registrar hora de salida
     * POST /api/atenciones/registrar-salida
     */
    public function registrarSalida(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'hora_salida' => 'nullable|date_format:H:i',
        ]);

        try {
            $atencion = $this->atencionService->registrarSalida(
                $request->input('id'),
                $request->input('hora_salida')
            );

            return response()->json([
                'success' => true,
                'message' => 'Salida registrada exitosamente',
                'data' => $atencion,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        }
    }

    /**
     * Obtener estadísticas de atenciones
     * POST /api/atenciones/stats
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $filters = [
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'medico_id' => $request->input('medico_id'),
        ];

        $estadisticas = $this->atencionService->getEstadisticas($filters);

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Buscar atenciones
     * POST /api/atenciones/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $atenciones = $this->atencionService->search($term, $limit);

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Obtener atenciones por paciente
     * POST /api/atenciones/por-paciente
     */
    public function porPaciente(Request $request): JsonResponse
    {
        $request->validate([
            'paciente_id' => 'required|integer',
        ]);

        $atenciones = $this->atencionService->getByPaciente($request->input('paciente_id'));

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Obtener atenciones por médico
     * POST /api/atenciones/por-medico
     */
    public function porMedico(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer',
            'fecha' => 'nullable|date',
        ]);

        $atenciones = $this->atencionService->getByMedico(
            $request->input('medico_id'),
            $request->input('fecha')
        );

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Restaurar atención eliminada
     * POST /api/atenciones/restore
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $atencion = $this->atencionService->restore($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Atención restaurada exitosamente',
                'data' => $atencion,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Atención no encontrada',
            ], 404);
        }
    }

    /**
     * Obtener atenciones eliminadas
     * POST /api/atenciones/trashed
     */
    public function trashed(): JsonResponse
    {
        $atenciones = $this->atencionService->getTrashed();

        return response()->json([
            'success' => true,
            'data' => $atenciones,
        ]);
    }

    /**
     * Obtener agenda del día por médico
     * POST /api/atenciones/agenda
     */
    public function agenda(Request $request): JsonResponse
    {
        $request->validate([
            'medico_id' => 'required|integer',
            'fecha' => 'nullable|date',
        ]);

        $agenda = $this->atencionService->getAgenda(
            $request->input('medico_id'),
            $request->input('fecha')
        );

        return response()->json([
            'success' => true,
            'data' => $agenda,
        ]);
    }
}
