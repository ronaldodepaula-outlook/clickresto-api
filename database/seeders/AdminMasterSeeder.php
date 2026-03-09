<?php

namespace Database\Seeders;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Usuario;
use App\Models\UsuarioPerfil;
use App\Models\Perfil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminMasterSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_MASTER_EMAIL', 'admin@clickresto.com');
        $senha = env('ADMIN_MASTER_PASSWORD', 'admin123');
        $empresaNome = env('ADMIN_MASTER_EMPRESA', 'ClickResto Admin');

        $plano = Plano::where('ativo', true)->orderBy('valor')->first();
        if (!$plano) {
            $plano = Plano::create([
                'nome' => 'Plano Basico',
                'limite_usuarios' => 5,
                'limite_produtos' => 100,
                'valor' => 0,
                'ativo' => true,
            ]);
        }

        $empresa = Empresa::updateOrCreate(
            ['nome' => $empresaNome],
            [
                'nome_fantasia' => $empresaNome,
                'plano_id' => $plano->id,
                'status' => 'ativo',
            ]
        );

        Assinatura::updateOrCreate(
            ['empresa_id' => $empresa->id, 'plano_id' => $plano->id],
            [
                'data_inicio' => now()->toDateString(),
                'data_fim' => now()->addYear()->toDateString(),
                'status' => 'ativa',
            ]
        );

        $usuario = Usuario::updateOrCreate(
            ['email' => $email],
            [
                'empresa_id' => $empresa->id,
                'nome' => 'Admin Master',
                'senha' => Hash::make($senha),
                'ativo' => true,
            ]
        );

        $perfil = Perfil::where('nome', 'admin_master')->first();
        if ($perfil) {
            UsuarioPerfil::updateOrCreate(
                ['usuario_id' => $usuario->id, 'perfil_id' => $perfil->id],
                []
            );
        }
    }
}
