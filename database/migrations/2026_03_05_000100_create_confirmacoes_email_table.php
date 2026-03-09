<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_confirmacoes_email', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('usuario_id')->constrained('tb_usuarios');
            $table->string('token', 80)->unique();
            $table->dateTime('expira_em');
            $table->dateTime('confirmado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_confirmacoes_email');
    }
};
