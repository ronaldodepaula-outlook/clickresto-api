<?php

namespace App\Http\Controllers\Pagamentos;

use App\Http\Controllers\BaseCrudController;
use App\Models\Pagamento;

class PagamentoController extends BaseCrudController
{
    protected string $modelClass = Pagamento::class;

    protected array $rules = [
        'pedido_id' => 'required|exists:tb_pedidos,id',
        'forma_pagamento_id' => 'required|exists:tb_formas_pagamento,id',
        'valor' => 'required|numeric|min:0',
        'troco' => 'nullable|numeric|min:0'
    ];

    protected array $filterable = ['pedido_id', 'forma_pagamento_id'];
}
