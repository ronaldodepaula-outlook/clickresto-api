<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('tb_pedidos');
            $table->foreignId('entregador_id')->nullable()->constrained('tb_entregadores');
            $table->decimal('taxa', 10, 2);
            $table->enum('status', ['pendente', 'saiu', 'entregue'])->default('pendente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_entregas');
    }
};
