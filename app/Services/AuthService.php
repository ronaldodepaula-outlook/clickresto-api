<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function isAdminMaster(Usuario $usuario): bool
    {
        $perfilAdmin = DB::table('tb_usuario_perfis as up')
            ->join('tb_perfis as p', 'p.id', '=', 'up.perfil_id')
            ->where('up.usuario_id', $usuario->id)
            ->where('p.nome', 'admin_master')
            ->exists();

        if ($perfilAdmin) {
            return true;
        }

        return DB::table('tb_usuario_perfis as up')
            ->join('tb_perfil_permissoes as pp', 'pp.perfil_id', '=', 'up.perfil_id')
            ->join('tb_permissoes as perm', 'perm.id', '=', 'pp.permissao_id')
            ->where('up.usuario_id', $usuario->id)
            ->where('perm.nome', 'admin_master')
            ->exists();
    }

    public function isEmpresaAdmin(Usuario $usuario): bool
    {
        if (isset($usuario->perfil_id) && (int) $usuario->perfil_id === 2) {
            return true;
        }

        return DB::table('tb_usuario_perfis as up')
            ->join('tb_perfis as p', 'p.id', '=', 'up.perfil_id')
            ->where('up.usuario_id', $usuario->id)
            ->where(function ($query) {
                $query->where('p.id', 2)
                    ->orWhere('p.nome', 'admin')
                    ->orWhere('p.nome', 'administrador')
                    ->orWhere('p.descricao', 'Administrador da empresa');
            })
            ->exists();
    }
}
