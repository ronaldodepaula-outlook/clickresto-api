<?php

namespace App\Http\Controllers\Produtos;

use App\Http\Controllers\BaseCrudController;
use App\Models\ProdutoOpcaoItem;

class ProdutoOpcaoItemController extends BaseCrudController
{
    protected string $modelClass = ProdutoOpcaoItem::class;

    protected array $rules = [
        'opcao_id' => 'required|exists:tb_produto_opcoes,id',
        'nome' => 'required|string|max:100',
        'preco_adicional' => 'nullable|numeric|min:0'
    ];

    protected array $filterable = ['opcao_id'];
}
