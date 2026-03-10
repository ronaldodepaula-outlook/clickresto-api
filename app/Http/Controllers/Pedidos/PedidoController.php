<?php

namespace App\Http\Controllers\Pedidos;

use App\Http\Controllers\BaseCrudController;
use App\Models\CozinhaItem;
use App\Models\Mesa;
use App\Models\Pagamento;
use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\Produto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PedidoController extends BaseCrudController
{
    protected string $modelClass = Pedido::class;

    protected array $rules = [
        'usuario_id' => 'required|exists:tb_usuarios,id',
        'mesa_id' => 'nullable|exists:tb_mesas,id',
        'comanda_id' => 'nullable|exists:tb_comandas,id',
        'cliente_id' => 'nullable|exists:tb_clientes,id',
        'tipo' => 'required|in:balcao,mesa,delivery,auto',
        'status' => 'required|in:aberto,preparo,pronto,entregue,fechado',
        'total' => 'nullable|numeric|min:0',
    ];

    protected array $filterable = ['status', 'tipo', 'mesa_id', 'comanda_id', 'cliente_id'];

    public function store(Request $request)
    {
        $data = $request->validate($this->rules);
        $this->injectEmpresaId($data, $request);
        $data['total'] = $data['total'] ?? 0;

        $pedido = Pedido::create($data);

        if (!empty($pedido->mesa_id)) {
            $this->atualizarStatusMesa($request, (int) $pedido->mesa_id, 'ocupada');
        }

        return response()->json($pedido, Response::HTTP_CREATED);
    }

    public function abrir(Request $request)
    {
        $data = $request->validate([
            'usuario_id' => 'nullable|exists:tb_usuarios,id',
            'mesa_id' => 'nullable|exists:tb_mesas,id',
            'comanda_id' => 'nullable|exists:tb_comandas,id',
            'cliente_id' => 'nullable|exists:tb_clientes,id',
            'tipo' => 'required|in:balcao,mesa,delivery,auto',
        ]);

        if (!isset($data['usuario_id']) && $request->user()) {
            $data['usuario_id'] = $request->user()->id;
        }

        $this->injectEmpresaId($data, $request);
        $data['status'] = 'aberto';
        $data['total'] = 0;

        $pedido = Pedido::create($data);

        if (!empty($pedido->mesa_id)) {
            $this->atualizarStatusMesa($request, (int) $pedido->mesa_id, 'ocupada');
        }

        return response()->json($pedido, Response::HTTP_CREATED);
    }

    public function adicionarItem(Request $request, int $pedidoId)
    {
        $data = $request->validate([
            'produto_id' => 'required|exists:tb_produtos,id',
            'quantidade' => 'required|integer|min:1',
            'preco' => 'nullable|numeric|min:0',
            'observacao' => 'nullable|string',
        ]);

        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $pedido = $query->findOrFail($pedidoId);

        $produto = Produto::findOrFail($data['produto_id']);
        if ($produto->empresa_id !== $pedido->empresa_id) {
            return response()->json(['message' => 'Produto nao pertence a empresa do pedido'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        if (!isset($data['preco'])) {
            $data['preco'] = $produto->preco;
        }

        $item = PedidoItem::create([
            'pedido_id' => $pedido->id,
            'produto_id' => $data['produto_id'],
            'quantidade' => $data['quantidade'],
            'preco' => $data['preco'],
            'observacao' => $data['observacao'] ?? null,
        ]);

        $this->recalcularTotal($pedido);

        return response()->json($item, Response::HTTP_CREATED);
    }

    public function enviarCozinha(Request $request, int $pedidoId)
    {
        $data = $request->validate([
            'estacao_id' => 'required|exists:tb_cozinha_estacoes,id',
        ]);

        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $pedido = $query->findOrFail($pedidoId);

        $estacao = DB::table('tb_cozinha_estacoes')->where('id', $data['estacao_id'])->first();
        if ($estacao && $estacao->empresa_id !== $pedido->empresa_id) {
            return response()->json(['message' => 'Estacao nao pertence a empresa do pedido'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $itens = PedidoItem::where('pedido_id', $pedido->id)->get();
        foreach ($itens as $item) {
            CozinhaItem::firstOrCreate(
                ['pedido_item_id' => $item->id],
                ['estacao_id' => $data['estacao_id'], 'status' => 'recebido']
            );
        }

        $pedido->status = 'preparo';
        $pedido->save();

        return response()->json(['message' => 'Pedido enviado para cozinha']);
    }

    public function enviarCozinhaAuto(Request $request)
    {
        $data = $request->validate([
            'pedido_id' => 'required|integer|exists:tb_pedidos,id',
            'estacao_id' => 'nullable|integer|exists:tb_cozinha_estacoes,id',
        ]);

        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $pedido = $query->findOrFail($data['pedido_id']);

        if (!empty($data['estacao_id'])) {
            $estacao = DB::table('tb_cozinha_estacoes')
                ->where('empresa_id', $pedido->empresa_id)
                ->where('id', $data['estacao_id'])
                ->first();
        } else {
            $estacao = DB::table('tb_cozinha_estacoes')
                ->where('empresa_id', $pedido->empresa_id)
                ->orderByDesc('id')
                ->first();
        }

        if (!$estacao) {
            return response()->json(['message' => 'Nenhuma estacao de cozinha cadastrada'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $itens = PedidoItem::where('pedido_id', $pedido->id)->get();
        foreach ($itens as $item) {
            CozinhaItem::firstOrCreate(
                ['pedido_item_id' => $item->id],
                ['estacao_id' => $estacao->id, 'status' => 'recebido']
            );
        }

        $pedido->status = 'preparo';
        $pedido->save();

        return response()->json([
            'message' => 'Pedido enviado para cozinha',
            'pedido_id' => $pedido->id,
            'estacao_id' => $estacao->id,
            'itens' => $itens->count(),
        ]);
    }

    public function atualizarStatus(Request $request, int $pedidoId)
    {
        $data = $request->validate([
            'status' => 'required|in:aberto,preparo,pronto,entregue,fechado',
        ]);

        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $pedido = $query->findOrFail($pedidoId);

        $pedido->status = $data['status'];
        $pedido->save();

        if ($data['status'] === 'fechado') {
            $this->liberarMesaSeSemPedidosAbertos($request, $pedido);
        }

        return $pedido;
    }

    public function fechar(Request $request, int $pedidoId)
    {
        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $pedido = $query->findOrFail($pedidoId);

        $totalPago = Pagamento::where('pedido_id', $pedido->id)->sum('valor');
        if ($totalPago < $pedido->total) {
            return response()->json([
                'message' => 'Pagamento insuficiente para fechar o pedido',
                'total' => $pedido->total,
                'pago' => $totalPago,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pedido->status = 'fechado';
        $pedido->save();

        $this->liberarMesaSeSemPedidosAbertos($request, $pedido);

        return response()->json(['message' => 'Pedido fechado com sucesso']);
    }

    public function pedidosUsuario(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'usuario nao autenticado');
        }

        $empresaId = $this->resolveEmpresaId($request);
        if (!$empresaId) {
            abort(400, 'empresa_id nao informado');
        }

        $pedidos = Pedido::query()
            ->where('empresa_id', $empresaId)
            ->where('usuario_id', $user->id)
            ->orderByDesc('criado_em')
            ->get();

        if ($pedidos->isEmpty()) {
            return [];
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
            ->orderBy('pi.id')
            ->get()
            ->groupBy('pedido_id');

        return $pedidos->map(function ($pedido) use ($itens) {
            return [
                'pedido' => $pedido,
                'itens' => $itens->get($pedido->id, collect())->values(),
            ];
        })->values();
    }

    protected function recalcularTotal(Pedido $pedido): void
    {
        $total = PedidoItem::where('pedido_id', $pedido->id)
            ->select(DB::raw('SUM(quantidade * preco) as total'))
            ->value('total');

        $pedido->total = $total ?? 0;
        $pedido->save();
    }

    protected function atualizarStatusMesa(Request $request, int $mesaId, string $status): void
    {
        $query = Mesa::query();
        $this->applyEmpresaScope($query, $request);
        $mesa = $query->find($mesaId);

        if (!$mesa || $mesa->status === $status) {
            return;
        }

        $mesa->status = $status;
        $mesa->save();
    }

    protected function liberarMesaSeSemPedidosAbertos(Request $request, Pedido $pedido): void
    {
        if (empty($pedido->mesa_id) || empty($pedido->usuario_id)) {
            return;
        }

        $query = Pedido::query();
        $this->applyEmpresaScope($query, $request);
        $emAberto = $query
            ->where('mesa_id', $pedido->mesa_id)
            ->where('usuario_id', $pedido->usuario_id)
            ->whereIn('status', ['aberto', 'preparo', 'pronto'])
            ->exists();

        if (!$emAberto) {
            $this->atualizarStatusMesa($request, (int) $pedido->mesa_id, 'livre');
        }
    }
}
