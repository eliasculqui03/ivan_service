<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamenLaboratorioRequest;
use App\Http\Requests\UpdateExamenLaboratorioRequest;
use App\Services\ExamenLaboratorioService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ExamenLaboratorioController extends Controller
{
    protected ExamenLaboratorioService $examenService;

    public function __construct(ExamenLaboratorioService $examenService)
    {
        $this->examenService = $examenService;
    }

    /**
     * Listar exámenes con paginación
     * POST /api/v1/examenes
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'estado' => $request->input('estado'),
            'tipo_examen' => $request->input('tipo_examen'),
            'prioridad' => $request->input('prioridad'),
            'medico_id' => $request->input('medico_id'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'fecha_solicitud'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $perPage = $request->input('per_page', 15);

        $examenes = $this->examenService->getAllPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }

    /**
     * Crear examen
     * POST /api/v1/examenes/store
     */
    public function store(StoreExamenLaboratorioRequest $request): JsonResponse
    {
        try {
            $examen = $this->examenService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Examen creado exitosamente',
                'data' => $examen,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el examen',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Mostrar examen
     * POST /api/v1/examenes/show
     */
    public function show(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $examen = $this->examenService->getById($request->input('id'));

            return response()->json([
                'success' => true,
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Actualizar examen
     * POST /api/v1/examenes/update
     */
    public function update(UpdateExamenLaboratorioRequest $request): JsonResponse
    {
        try {
            $examen = $this->examenService->update(
                $request->input('id'),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Examen actualizado exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el examen',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Eliminar examen
     * POST /api/v1/examenes/destroy
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $this->examenService->delete($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Examen eliminado exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Exámenes pendientes
     * POST /api/v1/examenes/pendientes
     */
    public function pendientes(): JsonResponse
    {
        $examenes = $this->examenService->getPendientes();

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }

    /**
     * Exámenes urgentes
     * POST /api/v1/examenes/urgentes
     */
    public function urgentes(): JsonResponse
    {
        $examenes = $this->examenService->getUrgentes();

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }

    /**
     * Cambiar estado
     * POST /api/v1/examenes/cambiar-estado
     */
    public function cambiarEstado(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'estado' => 'required|in:Solicitado,Muestra Tomada,En Proceso,Resultado Parcial,Completado,Cancelado',
        ]);

        try {
            $examen = $this->examenService->cambiarEstado(
                $request->input('id'),
                $request->input('estado')
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Registrar toma de muestra
     * POST /api/v1/examenes/registrar-muestra
     */
    public function registrarMuestra(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'fecha' => 'nullable|date',
        ]);

        try {
            $examen = $this->examenService->registrarMuestra(
                $request->input('id'),
                $request->input('fecha')
            );

            return response()->json([
                'success' => true,
                'message' => 'Muestra registrada exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Registrar resultados
     * POST /api/v1/examenes/registrar-resultados
     */
    public function registrarResultados(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer',
            'resultados' => 'required|array',
            'interpretacion' => 'nullable|string',
            'valores_criticos' => 'nullable|string',
        ]);

        try {
            $examen = $this->examenService->registrarResultados(
                $request->input('id'),
                $request->input('resultados'),
                $request->input('interpretacion'),
                $request->input('valores_criticos')
            );

            return response()->json([
                'success' => true,
                'message' => 'Resultados registrados exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Validar resultados
     * POST /api/v1/examenes/validar
     */
    public function validar(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $userId = auth()->id();
            $examen = $this->examenService->validar($request->input('id'), $userId);

            return response()->json([
                'success' => true,
                'message' => 'Examen validado exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Buscar exámenes
     * POST /api/v1/examenes/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $term = $request->input('q');
        $limit = $request->input('limit', 10);

        $examenes = $this->examenService->search($term, $limit);

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }

    /**
     * Estadísticas
     * POST /api/v1/examenes/stats
     */
    public function estadisticas(Request $request): JsonResponse
    {
        $filters = [
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ];

        $estadisticas = $this->examenService->getEstadisticas($filters);

        return response()->json([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Exámenes por paciente
     * POST /api/v1/examenes/por-paciente
     */
    public function porPaciente(Request $request): JsonResponse
    {
        $request->validate(['paciente_id' => 'required|integer']);

        $examenes = $this->examenService->getByPaciente($request->input('paciente_id'));

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }

    /**
     * Restaurar examen
     * POST /api/v1/examenes/restore
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate(['id' => 'required|integer']);

        try {
            $examen = $this->examenService->restore($request->input('id'));

            return response()->json([
                'success' => true,
                'message' => 'Examen restaurado exitosamente',
                'data' => $examen,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Examen no encontrado',
            ], 404);
        }
    }

    /**
     * Exámenes eliminados
     * POST /api/v1/examenes/trashed
     */
    public function trashed(): JsonResponse
    {
        $examenes = $this->examenService->getTrashed();

        return response()->json([
            'success' => true,
            'data' => $examenes,
        ]);
    }
}
