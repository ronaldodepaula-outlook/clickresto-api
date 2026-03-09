<?php

namespace App\Http\Controllers\Usuarios;

use App\Http\Controllers\BaseCrudController;
use App\Models\UsuarioPerfil;

class UsuarioPerfilController extends BaseCrudController
{
    protected string $modelClass = UsuarioPerfil::class;

    protected array $rules = [
        'usuario_id' => 'required|exists:tb_usuarios,id',
        'perfil_id' => 'required|exists:tb_perfis,id'
    ];

    protected array $filterable = ['usuario_id', 'perfil_id'];
}
