<?php

namespace App\Http\Controllers\Comandas;

use App\Http\Controllers\BaseCrudController;
use App\Models\Comanda;

class ComandaController extends BaseCrudController
{
    protected string $modelClass = Comanda::class;

    protected array $rules = [
        'numero' => 'required|string|max:50',
        'status' => 'required|in:aberta,fechada'
    ];

    protected array $filterable = ['status'];
}
