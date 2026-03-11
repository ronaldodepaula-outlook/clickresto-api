<?php

namespace App\Http\Controllers\Cozinha;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CozinhaDashboardController extends Controller
{
    public function indicadores(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            return response()->json(['message' => 'empresa_id nao informado'], Response::HTTP_BAD_REQUEST);
        }

        $data = $request->input('data', now()->toDateString());

        $totalPedidosDia = Pedido::where('empresa_id', $empresaId)
            ->whereDate('criado_em', $data)
            ->count();

        $totalPreparoDia = Pedido::where('empresa_id', $empresaId)
            ->whereDate('criado_em', $data)
            ->where('status', 'preparo')
            ->count();

        $totalProntosDia = Pedido::where('empresa_id', $empresaId)
            ->whereDate('criado_em', $data)
            ->where('status', 'pronto')
            ->count();

        $rows = $this->queryPedidosCozinha($empresaId, $data)->get();
        $pedidos = $this->mapPedidosCozinha($rows);

        $pedidosEmPreparo = $pedidos->where('pedido.status', 'preparo')->values();
        $pedidosProntos = $pedidos->where('pedido.status', 'pronto')->values();

        return response()->json([
            'data' => $data,
            'totais' => [
                'pedidos_dia' => (int) $totalPedidosDia,
                'pedidos_preparo_dia' => (int) $totalPreparoDia,
                'pedidos_prontos_dia' => (int) $totalProntosDia,
            ],
            'pedidos_em_preparo' => $pedidosEmPreparo,
            'pedidos_prontos' => $pedidosProntos,
        ]);
    }

    protected function queryPedidosCozinha(int $empresaId, string $data)
    {
        return DB::table('tb_pedidos as p')
            ->join('tb_pedido_itens as pi', 'pi.pedido_id', '=', 'p.id')
            ->join('tb_cozinha_itens as ci', 'ci.pedido_item_id', '=', 'pi.id')
            ->join('tb_cozinha_estacoes as ce', 'ce.id', '=', 'ci.estacao_id')
            ->leftJoin('tb_produtos as pr', 'pr.id', '=', 'pi.produto_id')
            ->leftJoin('tb_mesas as m', function ($join) {
                $join->on('m.id', '=', 'p.mesa_id')
                    ->on('m.empresa_id', '=', 'p.empresa_id');
            })
            ->leftJoin('tb_comandas as c', function ($join) {
                $join->on('c.id', '=', 'p.comanda_id')
                    ->on('c.empresa_id', '=', 'p.empresa_id');
            })
            ->leftJoin('tb_clientes as cli', function ($join) {
                $join->on('cli.id', '=', 'p.cliente_id')
                    ->on('cli.empresa_id', '=', 'p.empresa_id');
            })
            ->where('p.empresa_id', $empresaId)
            ->whereDate('p.criado_em', $data)
            ->whereIn('p.status', ['preparo', 'pronto'])
            ->select(
                'p.id as pedido_id',
                'p.empresa_id',
                'p.usuario_id',
                'p.mesa_id',
                'p.comanda_id',
                'p.cliente_id',
                'p.tipo',
                'p.status as pedido_status',
                'p.total',
                'p.criado_em',
                'm.numero as mesa_numero',
                'm.status as mesa_status',
                'c.numero as comanda_numero',
                'c.status as comanda_status',
                'cli.nome as cliente_nome',
                'cli.telefone as cliente_telefone',
                'cli.email as cliente_email',
                'pi.id as pedido_item_id',
                'pi.produto_id',
                'pr.nome as produto_nome',
                'pi.quantidade',
                'pi.preco',
                'pi.observacao',
                'ci.id as cozinha_item_id',
                'ci.status as cozinha_status',
                'ci.created_at as cozinha_created_at',
                'ci.updated_at as cozinha_updated_at',
                'ce.id as estacao_id',
                'ce.nome as estacao_nome'
            )
            ->orderByDesc('p.criado_em');
    }

    protected function mapPedidosCozinha($rows)
    {
        if ($rows->isEmpty()) {
            return collect();
        }

        return $rows->groupBy('pedido_id')->map(function ($items) {
            $first = $items->first();

            return [
                'pedido' => [
                    'id' => $first->pedido_id,
                    'empresa_id' => $first->empresa_id,
                    'usuario_id' => $first->usuario_id,
                    'mesa_id' => $first->mesa_id,
                    'comanda_id' => $first->comanda_id,
                    'cliente_id' => $first->cliente_id,
                    'tipo' => $first->tipo,
                    'status' => $first->pedido_status,
                    'total' => $first->total,
                    'criado_em' => $first->criado_em,
                    'mesa' => $first->mesa_id ? [
                        'id' => $first->mesa_id,
                        'numero' => $first->mesa_numero,
                        'status' => $first->mesa_status,
                    ] : null,
                    'comanda' => $first->comanda_id ? [
                        'id' => $first->comanda_id,
                        'numero' => $first->comanda_numero,
                        'status' => $first->comanda_status,
                    ] : null,
                    'cliente' => $first->cliente_id ? [
                        'id' => $first->cliente_id,
                        'nome' => $first->cliente_nome,
                        'telefone' => $first->cliente_telefone,
                        'email' => $first->cliente_email,
                    ] : null,
                ],
                'itens' => $items->map(function ($row) {
                    return [
                        'pedido_item_id' => $row->pedido_item_id,
                        'produto_id' => $row->produto_id,
                        'produto_nome' => $row->produto_nome,
                        'quantidade' => (int) $row->quantidade,
                        'preco' => $row->preco,
                        'observacao' => $row->observacao,
                        'cozinha_item' => [
                            'id' => $row->cozinha_item_id,
                            'status' => $row->cozinha_status,
                            'created_at' => $row->cozinha_created_at,
                            'updated_at' => $row->cozinha_updated_at,
                        ],
                        'estacao' => [
                            'id' => $row->estacao_id,
                            'nome' => $row->estacao_nome,
                        ],
                    ];
                })->values(),
            ];
        })->values();
    }

    protected function resolveEmpresaId(Request $request): ?int
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
}
