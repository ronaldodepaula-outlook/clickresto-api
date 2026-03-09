<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pedido_item_opcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_item_id')->constrained('tb_pedido_itens');
            $table->foreignId('opcao_item_id')->constrained('tb_produto_opcao_itens');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pedido_item_opcoes');
    }
};
