<?php

namespace App\Http\Controllers\Delivery;

use App\Http\Controllers\BaseCrudController;
use App\Models\Entregador;

class EntregadorController extends BaseCrudController
{
    protected string $modelClass = Entregador::class;

    protected array $rules = [
        'nome' => 'required|string|max:150',
        'telefone' => 'nullable|string|max:20',
        'ativo' => 'required|boolean'
    ];

    protected array $filterable = ['ativo'];
}
