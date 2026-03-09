<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    public function __construct(private AuthService $authService) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Nao autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        // Usuários globais (sem empresa_id) ou com perfil admin_master
        if ($user->empresa_id === null || $this->authService->isAdminMaster($user)) {
            return $next($request);
        }

        return response()->json(['message' => 'Acesso restrito ao admin_master'], Response::HTTP_FORBIDDEN);
    }
}
