<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmpresa
{
    public function __construct(private AuthService $authService) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !isset($user->empresa_id)) {
            return response()->json(['message' => 'Empresa nao identificada'], Response::HTTP_FORBIDDEN);
        }

        if ($this->authService->isAdminMaster($user)) {
            return $next($request);
        }

        $empresaHeader = $request->header('X-Empresa-Id');
        if ($empresaHeader && (int) $empresaHeader !== (int) $user->empresa_id) {
            return response()->json(['message' => 'Empresa nao permitida'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
