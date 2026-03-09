<?php

namespace App\Http\Controllers\Estoque;

use App\Http\Controllers\BaseCrudController;
use App\Models\Estoque;

class EstoqueController extends BaseCrudController
{
    protected string $modelClass = Estoque::class;

    protected array $rules = [
        'produto_id' => 'required|exists:tb_produtos,id',
        'quantidade' => 'required|numeric|min:0'
    ];

    protected array $filterable = ['produto_id'];
}
