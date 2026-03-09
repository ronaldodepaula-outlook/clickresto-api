<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class TrialPlanoSeeder extends Seeder
{
    public function run(): void
    {
        Plano::updateOrCreate(
            ['nome' => 'Plano Trial 3 Meses'],
            [
                'limite_usuarios' => 1,
                'limite_produtos' => 50,
                'valor' => 0,
                'ativo' => true,
            ]
        );
    }
}
