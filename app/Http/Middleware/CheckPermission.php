<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private PermissionService $permissionService,
        private AuthService $authService
    ) {}

    public function handle(Request $request, Closure $next, string $permissao)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Nao autenticado'], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->authService->isAdminMaster($user)) {
            return $next($request);
        }

        if (!$this->permissionService->hasPermission($user, $permissao)) {
            return response()->json(['message' => 'Sem permissao'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
