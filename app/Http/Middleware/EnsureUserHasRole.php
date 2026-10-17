<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'La cuenta está inactiva.',
            ], 403);
        }

        if (!in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'No tienes permisos para realizar esta operación.',
            ], 403);
        }

        return $next($request);
    }
}
