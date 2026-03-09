<?php

namespace App\Http\Controllers\Pedidos;

use App\Http\Controllers\BaseCrudController;
use App\Models\PedidoItem;

class PedidoItemController extends BaseCrudController
{
    protected string $modelClass = PedidoItem::class;

    protected array $rules = [
        'pedido_id' => 'required|exists:tb_pedidos,id',
        'produto_id' => 'required|exists:tb_produtos,id',
        'quantidade' => 'required|integer|min:1',
        'preco' => 'required|numeric|min:0',
        'observacao' => 'nullable|string'
    ];

    protected array $filterable = ['pedido_id', 'produto_id'];
}
