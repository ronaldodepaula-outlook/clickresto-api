<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_caixas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('usuario_id')->constrained('tb_usuarios');
            $table->timestamp('aberto_em')->useCurrent();
            $table->timestamp('fechado_em')->nullable();
            $table->decimal('saldo_inicial', 10, 2);
            $table->decimal('saldo_final', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_caixas');
    }
};
