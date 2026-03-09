<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_perfil_permissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('tb_perfis');
            $table->foreignId('permissao_id')->constrained('tb_permissoes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_perfil_permissoes');
    }
};
