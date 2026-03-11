<?php

namespace App\Http\Controllers\Relatorios;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

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

    public function dashboardPagamentos(Request $request)
    {
        $empresaId = $this->empresaId($request);

        [$periodo, $inicio, $fim] = $this->resolvePeriodo($request);
        if (!$inicio || !$fim) {
            return response()->json(['message' => 'Periodo invalido'], Response::HTTP_BAD_REQUEST);
        }

        $base = $this->pagamentosBase($request, $inicio, $fim, $empresaId);
        $statusFiltro = $this->parseStatusFilter($request);
        $hasTroco = Schema::hasColumn('tb_pagamentos', 'troco');

        $totalApurado = (float) (clone $base)->sum('pg.valor');
        $totalPedidos = (int) (clone $base)->distinct('pg.pedido_id')->count('pg.pedido_id');
        $ticketMedio = $totalPedidos > 0 ? round($totalApurado / $totalPedidos, 2) : 0;
        $totalTroco = $hasTroco ? (float) (clone $base)->sum('pg.troco') : 0;

        $totaisPorForma = (clone $base)
            ->select('fp.id', 'fp.nome', DB::raw('SUM(pg.valor) as total'))
            ->groupBy('fp.id', 'fp.nome')
            ->orderByDesc('total')
            ->get();

        $serie = (clone $base)
            ->select(DB::raw('DATE(pg.created_at) as data'), DB::raw('SUM(pg.valor) as total_apurado'), DB::raw('COUNT(DISTINCT pg.pedido_id) as total_pedidos'))
            ->groupBy(DB::raw('DATE(pg.created_at)'))
            ->orderBy('data')
            ->get()
            ->map(function ($row) {
                $totalPedidosDia = (int) $row->total_pedidos;
                $totalApuradoDia = (float) $row->total_apurado;

                return [
                    'data' => $row->data,
                    'total_apurado' => $totalApuradoDia,
                    'total_pedidos' => $totalPedidosDia,
                    'ticket_medio' => $totalPedidosDia > 0 ? round($totalApuradoDia / $totalPedidosDia, 2) : 0,
                ];
            });

        $seriePorForma = (clone $base)
            ->select(
                DB::raw('DATE(pg.created_at) as data'),
                'fp.id as forma_pagamento_id',
                'fp.nome as forma_pagamento',
                DB::raw('SUM(pg.valor) as total_apurado'),
                DB::raw('COUNT(DISTINCT pg.pedido_id) as total_pedidos')
            )
            ->groupBy(DB::raw('DATE(pg.created_at)'), 'fp.id', 'fp.nome')
            ->orderBy('data')
            ->orderBy('forma_pagamento')
            ->get()
            ->map(function ($row) {
                return [
                    'data' => $row->data,
                    'forma_pagamento_id' => $row->forma_pagamento_id,
                    'forma_pagamento' => $row->forma_pagamento,
                    'total_apurado' => (float) $row->total_apurado,
                    'total_pedidos' => (int) $row->total_pedidos,
                    'ticket_medio' => (int) $row->total_pedidos > 0
                        ? round(((float) $row->total_apurado) / (int) $row->total_pedidos, 2)
                        : 0,
                ];
            });

        $pedidoIds = (clone $base)->distinct('pg.pedido_id')->pluck('pg.pedido_id')->all();
        $pedidosDetalhados = [];
        $totalTaxaEntrega = 0;

        if (!empty($pedidoIds)) {
            $pedidos = DB::table('tb_pedidos as ped')
                ->leftJoin('tb_entregas as e', 'e.pedido_id', '=', 'ped.id')
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
                ->whereIn('ped.id', $pedidoIds)
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
                    'e.taxa as entrega_taxa',
                    'e.status as entrega_status',
                    'm.numero as mesa_numero',
                    'm.status as mesa_status',
                    'c.numero as comanda_numero',
                    'c.status as comanda_status',
                    'cli.nome as cliente_nome',
                    'cli.telefone as cliente_telefone',
                    'cli.email as cliente_email'
                )
                ->orderByDesc('ped.criado_em')
                ->get();

            if (Schema::hasTable('tb_entregas')) {
                $totalTaxaEntrega = (float) DB::table('tb_entregas')
                    ->whereIn('pedido_id', $pedidoIds)
                    ->sum('taxa');
            }

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
                ->whereBetween('pg.created_at', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()])
                ->get()
                ->groupBy('pedido_id');

            $pedidosDetalhados = $pedidos->map(function ($pedido) use ($itens, $pagamentos) {
                $pedidoPagamentos = $pagamentos->get($pedido->id, collect());
                $totalPedidoPago = (float) $pedidoPagamentos->sum('valor');

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
                        'entrega' => $pedido->entrega_taxa !== null ? [
                            'taxa' => $pedido->entrega_taxa,
                            'status' => $pedido->entrega_status,
                        ] : null,
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
                    'total_pago_periodo' => $totalPedidoPago,
                ];
            });
            }

            return [
                'periodo' => $periodo,
            'status' => empty($statusFiltro) ? null : implode(',', $statusFiltro),
            'intervalo' => [
                'inicio' => $inicio->toDateString(),
                'fim' => $fim->toDateString(),
            ],
            'total_apurado' => $totalApurado,
            'total_pedidos' => $totalPedidos,
            'ticket_medio' => $ticketMedio,
            'total_troco' => $totalTroco,
            'total_taxa_entrega' => $totalTaxaEntrega,
            'serie_diaria' => $serie,
            'serie_diaria_por_forma' => $seriePorForma,
            'totais_por_forma_pagamento' => $totaisPorForma,
            'pedidos' => $pedidosDetalhados,
        ];
    }

    public function exportPagamentosDashboard(Request $request)
    {
        $empresaId = $this->empresaId($request);
        [$periodo, $inicio, $fim] = $this->resolvePeriodo($request);
        if (!$inicio || !$fim) {
            return response()->json(['message' => 'Periodo invalido'], Response::HTTP_BAD_REQUEST);
        }

        $base = $this->pagamentosBase($request, $inicio, $fim, $empresaId);
        $hasTroco = Schema::hasColumn('tb_pagamentos', 'troco');

        $rows = (clone $base)
            ->select(
                'pg.id as pagamento_id',
                'pg.pedido_id',
                'pg.forma_pagamento_id',
                'fp.nome as forma_pagamento',
                'pg.valor',
                $hasTroco ? 'pg.troco' : DB::raw('NULL as troco'),
                'pg.created_at as pagamento_em',
                'ped.status as pedido_status',
                'ped.total as pedido_total',
                'ped.criado_em as pedido_criado_em'
            )
            ->orderBy('pg.created_at')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'pagamento_id',
            'pedido_id',
            'forma_pagamento_id',
            'forma_pagamento',
            'valor',
            'troco',
            'pagamento_em',
            'pedido_status',
            'pedido_total',
            'pedido_criado_em',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->pagamento_id,
                $row->pedido_id,
                $row->forma_pagamento_id,
                $row->forma_pagamento,
                $row->valor,
                $row->troco,
                $row->pagamento_em,
                $row->pedido_status,
                $row->pedido_total,
                $row->pedido_criado_em,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $formato = strtolower((string) $request->input('formato', 'csv'));
        $nomeBase = 'pagamentos-dashboard-' . $inicio->format('Ymd') . '-' . $fim->format('Ymd');

        if (in_array($formato, ['excel', 'xlsx', 'xls'], true)) {
            $filename = $nomeBase . '.xls';
            return response($csv, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        }

        $filename = $nomeBase . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function pagamentosBase(Request $request, Carbon $inicio, Carbon $fim, ?int $empresaId)
    {
        $query = DB::table('tb_pagamentos as pg')
            ->join('tb_formas_pagamento as fp', 'fp.id', '=', 'pg.forma_pagamento_id')
            ->join('tb_pedidos as ped', 'ped.id', '=', 'pg.pedido_id')
            ->whereBetween('pg.created_at', [$inicio->copy()->startOfDay(), $fim->copy()->endOfDay()]);

        if ($empresaId) {
            $query->where('ped.empresa_id', $empresaId);
        }

        $status = $this->parseStatusFilter($request);
        if (!empty($status)) {
            $query->whereIn('ped.status', $status);
        }

        return $query;
    }

    protected function parseStatusFilter(Request $request): array
    {
        if (! $request->filled('status')) {
            return [];
        }

        return collect(explode(',', $request->input('status')))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }

    protected function resolvePeriodo(Request $request): array
    {
        $periodo = $request->input('periodo', 'dia');

        if ($periodo === 'dia') {
            $data = $request->input('data', now()->toDateString());
            $inicio = Carbon::parse($data);
            $fim = Carbon::parse($data);
            return [$periodo, $inicio, $fim];
        }

        if ($periodo === 'mes') {
            $mes = $request->input('mes');
            if ($mes) {
                $base = Carbon::createFromFormat('Y-m', $mes)->startOfMonth();
            } else {
                $base = Carbon::parse($request->input('data', now()->toDateString()))->startOfMonth();
            }
            return [$periodo, $base->copy()->startOfMonth(), $base->copy()->endOfMonth()];
        }

        if ($periodo === 'ano') {
            $ano = $request->input('ano');
            if ($ano) {
                $inicio = Carbon::createFromDate((int) $ano, 1, 1);
                $fim = Carbon::createFromDate((int) $ano, 12, 31);
                return [$periodo, $inicio, $fim];
            }

            $base = Carbon::parse($request->input('data', now()->toDateString()));
            return [$periodo, $base->copy()->startOfYear(), $base->copy()->endOfYear()];
        }

        if ($periodo === 'intervalo') {
            $dataInicio = $request->input('data_inicio');
            $dataFim = $request->input('data_fim');
            if (!$dataInicio || !$dataFim) {
                return [$periodo, null, null];
            }

            $inicio = Carbon::parse($dataInicio);
            $fim = Carbon::parse($dataFim);
            if ($inicio->greaterThan($fim)) {
                return [$periodo, null, null];
            }

            return [$periodo, $inicio, $fim];
        }

        return [$periodo, null, null];
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
