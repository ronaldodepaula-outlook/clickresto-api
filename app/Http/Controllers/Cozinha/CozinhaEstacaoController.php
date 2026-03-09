<?php

namespace App\Http\Controllers\Cozinha;

use App\Http\Controllers\BaseCrudController;
use App\Models\CozinhaEstacao;

class CozinhaEstacaoController extends BaseCrudController
{
    protected string $modelClass = CozinhaEstacao::class;

    protected array $rules = [
        'nome' => 'required|string|max:100'
    ];

    protected array $filterable = ['nome'];
}
