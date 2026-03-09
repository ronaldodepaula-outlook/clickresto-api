<?php

namespace App\Http\Controllers\Cozinha;

use App\Http\Controllers\BaseCrudController;
use App\Models\CozinhaItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CozinhaItemController extends BaseCrudController
{
    protected string $modelClass = CozinhaItem::class;

    protected array $rules = [
        'pedido_item_id' => 'required|exists:tb_pedido_itens,id',
        'estacao_id' => 'required|exists:tb_cozinha_estacoes,id',
        'status' => 'required|in:recebido,preparo,pronto'
    ];

    protected array $filterable = ['status', 'estacao_id'];

    public function pedidos(Request $request)
    {
        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $query = DB::table('tb_pedidos as p')
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

        if ($request->filled('status')) {
            $query->where('ci.status', $request->input('status'));
        }
        if ($request->filled('estacao_id')) {
            $query->where('ci.estacao_id', $request->input('estacao_id'));
        }
        if ($request->filled('pedido_status')) {
            $query->where('p.status', $request->input('pedido_status'));
        }
        if ($request->filled('data')) {
            $query->whereDate('p.criado_em', $request->input('data'));
        }

        if ($request->filled('order_by')) {
            $orderBy = $request->input('order_by');
            if ($orderBy === 'estacao') {
                $query->orderBy('ce.nome')->orderByDesc('p.criado_em');
            } elseif ($orderBy === 'status') {
                $query->orderBy('ci.status')->orderByDesc('p.criado_em');
            }
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $pedidos = $rows->groupBy('pedido_id')->map(function ($items) {
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

        return $pedidos;
    }

    public function pedidosPorUsuario(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'usuario nao autenticado');
        }

        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $query = DB::table('tb_pedidos as p')
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
            ->where('p.usuario_id', $user->id)
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

        if ($request->filled('status')) {
            $query->where('ci.status', $request->input('status'));
        }
        if ($request->filled('estacao_id')) {
            $query->where('ci.estacao_id', $request->input('estacao_id'));
        }
        if ($request->filled('pedido_status')) {
            $query->where('p.status', $request->input('pedido_status'));
        }
        if ($request->filled('data')) {
            $query->whereDate('p.criado_em', $request->input('data'));
        }
        if ($request->filled('order_by')) {
            $orderBy = $request->input('order_by');
            if ($orderBy === 'estacao') {
                $query->orderBy('ce.nome')->orderByDesc('p.criado_em');
            } elseif ($orderBy === 'status') {
                $query->orderBy('ci.status')->orderByDesc('p.criado_em');
            }
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $pedidos = $rows->groupBy('pedido_id')->map(function ($items) {
            $first = $items->first();

            $itens = $items->map(function ($row) {
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
            })->values();

            $estacoes = $items->map(function ($row) {
                return [
                    'id' => $row->estacao_id,
                    'nome' => $row->estacao_nome,
                ];
            })->unique('id')->values();

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
                'estacoes' => $estacoes,
                'itens' => $itens,
            ];
        })->values();

        return $pedidos;
    }

    public function atualizarStatus(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => 'required|in:recebido,preparo,pronto',
        ]);

        $item = CozinhaItem::findOrFail($id);
        $item->status = $data['status'];
        $item->save();

        return $item;
    }
}
