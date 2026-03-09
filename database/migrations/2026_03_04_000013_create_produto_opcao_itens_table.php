<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_produto_opcao_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opcao_id')->constrained('tb_produto_opcoes');
            $table->string('nome', 100);
            $table->decimal('preco_adicional', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_produto_opcao_itens');
    }
};
