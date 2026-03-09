<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    protected function empresaId(Request $request): ?int
    {
        $empresaId = $request->header('X-Empresa-Id');
        if ($empresaId) {
            return (int) $empresaId;
        }

        $user = $request->user();
        if ($user && isset($user->empresa_id)) {
            return (int) $user->empresa_id;
        }

        return null;
    }

    public function vendasDia(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $data = $request->input('data', now()->toDateString());

        $query = Pedido::query()->whereDate('criado_em', $data);
        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        return [
            'data' => $data,
            'total' => (float) $query->sum('total'),
            'pedidos' => (int) $query->count(),
        ];
    }

    public function vendasProduto(Request $request)
    {
        $empresaId = $this->empresaId($request);

        $query = DB::table('tb_pedido_itens as pi')
            ->join('tb_produtos as p', 'p.id', '=', 'pi.produto_id')
            ->join('tb_pedidos as ped', 'ped.id', '=', 'pi.pedido_id')
            ->select('p.id', 'p.nome', DB::raw('SUM(pi.quantidade) as quantidade'), DB::raw('SUM(pi.quantidade * pi.preco) as total'));

        if ($empresaId) {
            $query->where('ped.empresa_id', $empresaId);
        }

        return $query->groupBy('p.id', 'p.nome')->orderByDesc('total')->get();
    }

    public function vendasFormaPagamento(Request $request)
    {
        $empresaId = $this->empresaId($request);

        $query = DB::table('tb_pagamentos as pg')
            ->join('tb_formas_pagamento as fp', 'fp.id', '=', 'pg.forma_pagamento_id')
            ->join('tb_pedidos as ped', 'ped.id', '=', 'pg.pedido_id')
            ->select('fp.id', 'fp.nome', DB::raw('SUM(pg.valor) as total'));

        if ($empresaId) {
            $query->where('ped.empresa_id', $empresaId);
        }

        return $query->groupBy('fp.id', 'fp.nome')->orderByDesc('total')->get();
    }

    public function ticketMedio(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $query = Pedido::query();

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        $total = (float) $query->sum('total');
        $count = (int) $query->count();

        return [
            'total' => $total,
            'pedidos' => $count,
            'ticket_medio' => $count > 0 ? round($total / $count, 2) : 0,
        ];
    }

    public function pedidosPorCanal(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $query = Pedido::query()->select('tipo', DB::raw('COUNT(*) as total'))->groupBy('tipo');

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        return $query->get();
    }

    public function movimentacoesDia(Request $request)
    {
        $empresaId = $this->empresaId($request);
        $data = $request->input('data', now()->toDateString());

        $pedidosQuery = DB::table('tb_pedidos as ped')
            ->leftJoin('tb_mesas as m', function ($join) {
                $join->on('m.id', '=', 'ped.mesa_id')
                    ->on('m.empresa_id', '=', 'ped.empresa_id');
            })
            ->leftJoin('tb_comandas as c', function ($join) {
                $join->on('c.id', '=', 'ped.comanda_id')
                    ->on('c.empresa_id', '=', 'ped.empresa_id');
            })
            ->leftJoin('tb_clientes as cli', function ($join) {
                $join->on('cli.id', '=', 'ped.cliente_id')
                    ->on('cli.empresa_id', '=', 'ped.empresa_id');
            })
            ->whereDate('ped.criado_em', $data)
            ->select(
                'ped.id',
                'ped.empresa_id',
                'ped.usuario_id',
                'ped.mesa_id',
                'ped.comanda_id',
                'ped.cliente_id',
                'ped.tipo',
                'ped.status',
                'ped.total',
                'ped.criado_em',
                'm.numero as mesa_numero',
                'm.status as mesa_status',
                'c.numero as comanda_numero',
                'c.status as comanda_status',
                'cli.nome as cliente_nome',
                'cli.telefone as cliente_telefone',
                'cli.email as cliente_email'
            )
            ->orderByDesc('ped.criado_em');

        if ($empresaId) {
            $pedidosQuery->where('ped.empresa_id', $empresaId);
        }

        if ($request->filled('status')) {
            $pedidosQuery->where('ped.status', $request->input('status'));
        }
        if ($request->filled('tipo')) {
            $pedidosQuery->where('ped.tipo', $request->input('tipo'));
        }
        if ($request->filled('mesa_id')) {
            $pedidosQuery->where('ped.mesa_id', $request->input('mesa_id'));
        }
        if ($request->filled('comanda_id')) {
            $pedidosQuery->where('ped.comanda_id', $request->input('comanda_id'));
        }
        if ($request->filled('cliente_id')) {
            $pedidosQuery->where('ped.cliente_id', $request->input('cliente_id'));
        }
        if ($request->filled('usuario_id')) {
            $pedidosQuery->where('ped.usuario_id', $request->input('usuario_id'));
        }

        $pedidos = $pedidosQuery->get();
        if ($pedidos->isEmpty()) {
            return [
                'data' => $data,
                'total_pedidos' => 0,
                'total_pago' => 0,
                'pedidos' => [],
            ];
        }

        $pedidoIds = $pedidos->pluck('id')->all();

        $itens = DB::table('tb_pedido_itens as pi')
            ->join('tb_produtos as p', 'p.id', '=', 'pi.produto_id')
            ->select(
                'pi.id',
                'pi.pedido_id',
                'pi.produto_id',
                'p.nome as produto',
                'pi.quantidade',
                'pi.preco',
                'pi.observacao'
            )
            ->whereIn('pi.pedido_id', $pedidoIds)
            ->get()
            ->groupBy('pedido_id');

        $pagamentos = DB::table('tb_pagamentos as pg')
            ->join('tb_formas_pagamento as fp', 'fp.id', '=', 'pg.forma_pagamento_id')
            ->select(
                'pg.id',
                'pg.pedido_id',
                'pg.forma_pagamento_id',
                'fp.nome as forma_pagamento',
                'pg.valor',
                'pg.created_at'
            )
            ->whereIn('pg.pedido_id', $pedidoIds)
            ->get()
            ->groupBy('pedido_id');

        $totalPago = 0;
        $pedidosDetalhados = $pedidos->map(function ($pedido) use ($itens, $pagamentos, &$totalPago) {
            $pedidoPagamentos = $pagamentos->get($pedido->id, collect());
            $totalPedidoPago = (float) $pedidoPagamentos->sum('valor');
            $totalPago += $totalPedidoPago;

            return [
                'pedido' => [
                    'id' => $pedido->id,
                    'empresa_id' => $pedido->empresa_id,
                    'usuario_id' => $pedido->usuario_id,
                    'mesa_id' => $pedido->mesa_id,
                    'comanda_id' => $pedido->comanda_id,
                    'cliente_id' => $pedido->cliente_id,
                    'tipo' => $pedido->tipo,
                    'status' => $pedido->status,
                    'total' => $pedido->total,
                    'criado_em' => $pedido->criado_em,
                    'mesa' => $pedido->mesa_id ? [
                        'id' => $pedido->mesa_id,
                        'numero' => $pedido->mesa_numero,
                        'status' => $pedido->mesa_status,
                    ] : null,
                    'comanda' => $pedido->comanda_id ? [
                        'id' => $pedido->comanda_id,
                        'numero' => $pedido->comanda_numero,
                        'status' => $pedido->comanda_status,
                    ] : null,
                    'cliente' => $pedido->cliente_id ? [
                        'id' => $pedido->cliente_id,
                        'nome' => $pedido->cliente_nome,
                        'telefone' => $pedido->cliente_telefone,
                        'email' => $pedido->cliente_email,
                    ] : null,
                ],
                'itens' => $itens->get($pedido->id, collect())->values(),
                'pagamentos' => $pedidoPagamentos->values(),
                'total_pago' => $totalPedidoPago,
            ];
        });

        return [
            'data' => $data,
            'total_pedidos' => (int) $pedidos->count(),
            'total_pago' => $totalPago,
            'pedidos' => $pedidosDetalhados,
        ];
    }
}
