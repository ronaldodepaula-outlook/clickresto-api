<?php

namespace App\Http\Controllers\Configuracoes;

use App\Http\Controllers\BaseCrudController;
use App\Models\Configuracao;

class ConfiguracaoController extends BaseCrudController
{
    protected string $modelClass = Configuracao::class;

    protected array $rules = [
        'chave' => 'required|string|max:100',
        'valor' => 'nullable|string'
    ];

    protected array $filterable = ['chave'];
}
