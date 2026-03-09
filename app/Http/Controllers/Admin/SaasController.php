<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaasController extends Controller
{
    /**
     * Retorna indicadores gerais do ecossistema SaaS para users admin_master ou admin (empresa).
     *
     * - total_empresas: número total de cadastros
     * - empresas_ativas: status = ativo
     * - empresas_suspensas: status = suspenso
     * - empresas_trial: não possuem plano associado (trial)
     * - mrr: soma dos valores de planos vinculados a empresas ativas
     * - licencas_ativas: total de usuários ativos
     */
    public function overview(Request $request)
    {
        $total = Empresa::count();
        $ativas = Empresa::where('status', 'ativo')->count();
        $suspensas = Empresa::where('status', 'suspenso')->count();
        $trial = Empresa::whereNull('plano_id')->count();

        $mrr = Empresa::where('status', 'ativo')
            ->whereNotNull('plano_id')
            ->join('tb_planos', 'tb_empresas.plano_id', '=', 'tb_planos.id')
            ->sum('tb_planos.valor');

        $licencas = Usuario::where('ativo', true)->count();

        return response()->json([
            'total_empresas' => $total,
            'empresas_ativas' => $ativas,
            'empresas_suspensas' => $suspensas,
            'empresas_trial' => $trial,
            'mrr' => $mrr,
            'licencas_ativas' => $licencas,
        ]);
    }
}
