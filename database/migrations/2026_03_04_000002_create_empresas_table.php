<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150);
            $table->string('nome_fantasia', 150);
            $table->string('cnpj', 20);
            $table->string('telefone', 20);
            $table->string('email', 120);
            $table->string('endereco', 200);
            $table->string('cidade', 100);
            $table->string('estado', 50);
            $table->foreignId('plano_id')->nullable()->constrained('tb_planos');
            $table->enum('status', ['ativo', 'suspenso'])->default('ativo');
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_empresas');
    }
};
