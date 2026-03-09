<?php

namespace App\Http\Controllers\Produtos;

use App\Http\Controllers\BaseCrudController;
use App\Models\ProdutoOpcao;

class ProdutoOpcaoController extends BaseCrudController
{
    protected string $modelClass = ProdutoOpcao::class;

    protected array $rules = [
        'produto_id' => 'required|exists:tb_produtos,id',
        'nome' => 'required|string|max:100'
    ];

    protected array $filterable = ['produto_id'];
}
