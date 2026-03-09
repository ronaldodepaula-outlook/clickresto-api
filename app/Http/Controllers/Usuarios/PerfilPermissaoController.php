<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\PerfilPermissao;

class PerfilPermissaoController extends BaseCrudController
{
    protected string $modelClass = PerfilPermissao::class;

    protected array $rules = [
        'perfil_id' => 'required|exists:tb_perfis,id',
        'permissao_id' => 'required|exists:tb_permissoes,id'
    ];

    protected array $filterable = ['perfil_id', 'permissao_id'];
}
