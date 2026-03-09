<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\BaseCrudController;
use App\Models\ClienteEndereco;

class ClienteEnderecoController extends BaseCrudController
{
    protected string $modelClass = ClienteEndereco::class;

    protected array $rules = [
        'cliente_id' => 'required|exists:tb_clientes,id',
        'endereco' => 'required|string|max:200',
        'numero' => 'nullable|string|max:20',
        'bairro' => 'nullable|string|max:100',
        'cidade' => 'nullable|string|max:100',
        'referencia' => 'nullable|string|max:200'
    ];

    protected array $filterable = ['cliente_id', 'cidade'];
}
