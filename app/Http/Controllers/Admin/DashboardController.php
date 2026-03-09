<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Retorna dados operacionais para dashboard.
     * Apenas admin_master ou admin (empresa).
     */
    public function operational(Request $request)
    {
        $todayStart = now()->startOfDay();
        $yesterdayStart = now()->subDay()->startOfDay();

        // pedidos do dia e comparação
        $pedidosDia = Pedido::where('criado_em', '>=', $todayStart)->count();
        $pedidosOntem = Pedido::whereBetween('criado_em', [$yesterdayStart, $todayStart])->count();
        $deltaPedidos = $pedidosOntem ? round((($pedidosDia - $pedidosOntem) / $pedidosOntem) * 100, 2) : null;

        $ticketMedio = (float) Pedido::where('criado_em', '>=', $todayStart)->avg('total');

        // mesas
        $totalMesas = DB::table('tb_mesas')->count();
        $mesasOcupadas = DB::table('tb_mesas')->where('status', 'ocupada')->count();

        // entregas e itens cozinha
        $entregasRota = Pedido::where('tipo', 'delivery')
            ->whereNotIn('status', ['entregue', 'fechado'])
            ->count();

        $itensCozinha = DB::table('tb_pedido_itens')
            ->join('tb_pedidos', 'tb_pedido_itens.pedido_id', '=', 'tb_pedidos.id')
            ->whereIn('tb_pedidos.status', ['aberto', 'preparo'])
            ->count();

        // estoque crítico - arbitrário <10
        $estoqueCritico = DB::table('tb_estoque')->where('quantidade', '<', 10)->count();

        // faturamento dia e meta (parametrizada)
        $faturamentoDia = Pedido::where('criado_em', '>=', $todayStart)->sum('total');
        $meta = config('saas.daily_goal', 12500); // exemplo
        $metaPercent = $meta ? round($faturamentoDia / $meta * 100, 2) : null;

        // cancelamentos não modelados, deixar zero
        $cancelamentos = 0;

        // canais de venda
        $totaisTipo = Pedido::where('criado_em', '>=', $todayStart)
            ->select('tipo', DB::raw('count(*) as cnt'))
            ->groupBy('tipo')
            ->pluck('cnt', 'tipo');
        $totalPedidos = $totaisTipo->sum();
        $canais = [];
        foreach ($totaisTipo as $tipo => $cnt) {
            $canais[$tipo] = $totalPedidos ? round($cnt / $totalPedidos * 100, 2) : 0;
        }

        return response()->json([
            'pedidos_dia' => $pedidosDia,
            'pedidos_delta_percent' => $deltaPedidos,
            'ticket_medio' => $ticketMedio,
            'mesas_ocupadas' => $mesasOcupadas,
            'total_mesas' => $totalMesas,
            'entregas_rota' => $entregasRota,
            'itens_cozinha' => $itensCozinha,
            'estoque_critico' => $estoqueCritico,
            'faturamento_dia' => $faturamentoDia,
            'meta_percent' => $metaPercent,
            'cancelamentos' => $cancelamentos,
            'canais' => $canais,
        ]);
    }
}
