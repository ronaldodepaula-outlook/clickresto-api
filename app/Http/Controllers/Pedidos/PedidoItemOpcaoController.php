<?php

namespace App\Http\Controllers\Pedidos;

use App\Http\Controllers\BaseCrudController;
use App\Models\PedidoItemOpcao;

class PedidoItemOpcaoController extends BaseCrudController
{
    protected string $modelClass = PedidoItemOpcao::class;

    protected array $rules = [
        'pedido_item_id' => 'required|exists:tb_pedido_itens,id',
        'opcao_item_id' => 'required|exists:tb_produto_opcao_itens,id'
    ];

    protected array $filterable = ['pedido_item_id'];
}
