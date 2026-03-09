<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_cliente_enderecos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('tb_clientes');
            $table->string('endereco', 200);
            $table->string('numero', 20);
            $table->string('bairro', 100);
            $table->string('cidade', 100);
            $table->string('referencia', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_cliente_enderecos');
    }
};
