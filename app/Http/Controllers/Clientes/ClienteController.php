<?php

namespace App\Http\Controllers\Clientes;

use App\Http\Controllers\BaseCrudController;
use App\Models\Cliente;

class ClienteController extends BaseCrudController
{
    protected string $modelClass = Cliente::class;

    protected array $rules = [
        'nome' => 'required|string|max:150',
        'telefone' => 'nullable|string|max:20',
        'email' => 'nullable|email|max:150'
    ];

    protected array $filterable = ['nome', 'telefone'];
}
