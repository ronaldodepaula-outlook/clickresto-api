<?php

namespace App\Http\Controllers\Estoque;

use App\Http\Controllers\BaseCrudController;
use App\Models\EstoqueMovimento;

class EstoqueMovimentoController extends BaseCrudController
{
    protected string $modelClass = EstoqueMovimento::class;

    protected array $rules = [
        'produto_id' => 'required|exists:tb_produtos,id',
        'tipo' => 'required|in:entrada,saida',
        'quantidade' => 'required|numeric|min:0'
    ];

    protected array $filterable = ['produto_id', 'tipo'];
}
