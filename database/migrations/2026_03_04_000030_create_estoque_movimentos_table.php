<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_estoque_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('tb_produtos');
            $table->enum('tipo', ['entrada', 'saida']);
            $table->decimal('quantidade', 10, 2);
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_estoque_movimentos');
    }
};
