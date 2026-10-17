<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión con número de empleado y contraseña.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'employee_number' => [
                'required',
                'string',
                'max:30',
            ],
            'password' => [
                'required',
                'string',
                'max:128',
            ],
            'device_name' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $user = User::where(
            'employee_number',
            $credentials['employee_number']
        )->first();

        if (
            !$user ||
            !Hash::check($credentials['password'], $user->password) ||
            !$user->is_active
        ) {
            throw ValidationException::withMessages([
                'employee_number' => [
                    'Las credenciales proporcionadas no son válidas.',
                ],
            ]);
        }

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        // Las abilities describen el tipo de token,
        // pero no sustituyen la autorización por roles.
        $token = $user->createToken(
            $credentials['device_name'],
            ['*']
        );

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token_type' => 'Bearer',
            'access_token' => $token->plainTextToken,
            'expires_at' => now()->addMinutes(
                (int) config('sanctum.expiration', 0)
            )->toISOString(),
            'user' => [
                'id' => $user->id,
                'employee_number' => $user->employee_number,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Obtener los datos del usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'employee_number' => $user->employee_number,
                'name' => $user->name,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    /**
     * Cerrar la sesión actual revocando su token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    /**
     * Revocar todos los tokens personales del usuario.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Se han revocado todos los tokens.',
        ]);
    }
}
