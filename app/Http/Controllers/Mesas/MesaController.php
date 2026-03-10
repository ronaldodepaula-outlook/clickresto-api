<?php

namespace App\Http\Controllers\Mesas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Mesa;
use App\Models\Pedido;
use Illuminate\Http\Request;

class MesaController extends BaseCrudController
{
    protected string $modelClass = Mesa::class;

    protected array $rules = [
        'numero' => 'required|integer|min:1',
        'status' => 'required|in:livre,ocupada'
    ];

    protected array $filterable = ['status'];

    public function gestao(Request $request, int $mesaId)
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'usuario nao autenticado');
        }

        $mesaQuery = Mesa::query();
        $this->applyEmpresaScope($mesaQuery, $request);
        $mesa = $mesaQuery->findOrFail($mesaId);

        $pedidoQuery = Pedido::query();
        $this->applyEmpresaScope($pedidoQuery, $request);
        $pedidos = $pedidoQuery
            ->where('mesa_id', $mesa->id)
            ->where('usuario_id', $user->id)
            ->whereIn('status', ['aberto', 'preparo', 'pronto'])
            ->orderByDesc('criado_em')
            ->get();

        $totalPedidos = (float) $pedidos->sum('total');
        $statusEsperado = $pedidos->isEmpty() ? 'livre' : 'ocupada';

        if ($mesa->status !== $statusEsperado) {
            $mesa->status = $statusEsperado;
            $mesa->save();
        }

        return response()->json([
            'mesa' => [
                'id' => $mesa->id,
                'numero' => $mesa->numero,
                'status' => $mesa->status,
            ],
            'usuario_id' => $user->id,
            'total_pedidos_abertos' => $totalPedidos,
            'pedidos' => $pedidos,
        ]);
    }
}
