<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 200);
        }

        try {
            $credentials = [
                'email' => $request->email,
                'password' => $request->password
            ];

            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid credentials'
                ], 200);
            }

            // Actualizar last_login_at
            $user = Auth::user();
            $user->update(['last_login_at' => now()]);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Could not create token',
                'errors' => $e->getMessage(),
            ], 200);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user' => $user,
            ],
        ], 200);
    }

    public function me()
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found'
                ], 200);
            }

            // Actualizar last_activity_at
            $user->update(['last_activity_at' => now()]);

            return response()->json([
                'status' => 200,
                'message' => 'User retrieved successfully',
                'data' => $user
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 401,
                'message' => 'Token is invalid or expired',
                'errors' => $e->getMessage()
            ], 200);
        }
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json([
                'status' => 200,
                'message' => 'Successfully logged out'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to logout',
                'errors' => $e->getMessage(),
            ], 200);
        }
    }

    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'status' => 200,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                ]
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Could not refresh token',
                'errors' => $e->getMessage()
            ], 200);
        }
    }
}
