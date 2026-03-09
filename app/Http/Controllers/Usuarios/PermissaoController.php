<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\Permissao;

class PermissaoController extends BaseCrudController
{
    protected string $modelClass = Permissao::class;

    protected array $rules = [
        'nome' => 'required|string|max:100',
        'descricao' => 'nullable|string|max:200'
    ];

    protected array $filterable = ['nome'];
}
