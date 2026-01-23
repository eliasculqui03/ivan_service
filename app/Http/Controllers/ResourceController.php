<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResourceController extends Controller
{
    public function getRoles(): JsonResponse
    {
        $roles = DB::select("SELECT id, name FROM roles WHERE status = 1 ORDER BY name ASC");

        if (empty($roles)) {
            return response()->json([
                'status' => 204,
                'message' => 'No content'
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Roles retrieved successfully',
            'data' => $roles
        ], 200);
    }
}
