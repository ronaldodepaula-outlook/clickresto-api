<?php

namespace App\Http\Controllers\Produtos;

use App\Http\Controllers\BaseCrudController;
use App\Models\ProdutoImagem;

class ProdutoImagemController extends BaseCrudController
{
    protected string $modelClass = ProdutoImagem::class;

    protected array $rules = [
        'produto_id' => 'required|exists:tb_produtos,id',
        'url' => 'required|url|max:255'
    ];

    protected array $filterable = ['produto_id'];
}
