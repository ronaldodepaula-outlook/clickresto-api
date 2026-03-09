<?php

namespace App\Http\Controllers\Caixa;

use App\Http\Controllers\BaseCrudController;
use App\Models\CaixaMovimento;

class MovimentoCaixaController extends BaseCrudController
{
    protected string $modelClass = CaixaMovimento::class;

    protected array $rules = [
        'caixa_id' => 'required|exists:tb_caixas,id',
        'tipo' => 'required|in:entrada,saida',
        'valor' => 'required|numeric|min:0',
        'descricao' => 'nullable|string|max:200'
    ];

    protected array $filterable = ['caixa_id', 'tipo'];
}
