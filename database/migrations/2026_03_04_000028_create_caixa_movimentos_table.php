<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_caixa_movimentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caixa_id')->constrained('tb_caixas');
            $table->enum('tipo', ['entrada', 'saida']);
            $table->decimal('valor', 10, 2);
            $table->string('descricao', 200);
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_caixa_movimentos');
    }
};
