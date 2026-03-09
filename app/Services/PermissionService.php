<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function hasPermission(Usuario $usuario, string $permissao): bool
    {
        return DB::table('tb_usuario_perfis as up')
            ->join('tb_perfil_permissoes as pp', 'pp.perfil_id', '=', 'up.perfil_id')
            ->join('tb_permissoes as perm', 'perm.id', '=', 'pp.permissao_id')
            ->where('up.usuario_id', $usuario->id)
            ->where('perm.nome', $permissao)
            ->exists();
    }
}
