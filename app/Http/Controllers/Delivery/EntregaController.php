<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\BaseCrudController;
use App\Models\Entrega;

class EntregaController extends BaseCrudController
{
    protected string $modelClass = Entrega::class;

    protected array $rules = [
        'pedido_id' => 'required|exists:tb_pedidos,id',
        'entregador_id' => 'required|exists:tb_entregadores,id',
        'taxa' => 'nullable|numeric|min:0',
        'status' => 'required|in:pendente,saiu,entregue'
    ];

    protected array $filterable = ['status', 'entregador_id'];
}
