<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_usuario_perfis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('tb_usuarios');
            $table->foreignId('perfil_id')->constrained('tb_perfis');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_usuario_perfis');
    }
};
