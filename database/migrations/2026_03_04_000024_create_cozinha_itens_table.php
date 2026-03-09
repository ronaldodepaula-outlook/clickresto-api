<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_cozinha_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_item_id')->constrained('tb_pedido_itens');
            $table->foreignId('estacao_id')->constrained('tb_cozinha_estacoes');
            $table->enum('status', ['recebido', 'preparo', 'pronto'])->default('recebido');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_cozinha_itens');
    }
};
