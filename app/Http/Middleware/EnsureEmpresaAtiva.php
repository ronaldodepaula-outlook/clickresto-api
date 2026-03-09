<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmpresaAtiva
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !isset($user->empresa_id)) {
            return response()->json(['message' => 'Empresa nao identificada'], Response::HTTP_FORBIDDEN);
        }

        $empresa = Empresa::find($user->empresa_id);
        if (!$empresa || $empresa->status !== 'ativo') {
            return response()->json(['message' => 'Empresa nao ativada'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
