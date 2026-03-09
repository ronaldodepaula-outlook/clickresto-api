<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('usuario_id')->constrained('tb_usuarios');
            $table->foreignId('mesa_id')->nullable()->constrained('tb_mesas');
            $table->foreignId('comanda_id')->nullable()->constrained('tb_comandas');
            $table->foreignId('cliente_id')->nullable()->constrained('tb_clientes');
            $table->enum('tipo', ['balcao', 'mesa', 'delivery', 'auto'])->default('balcao');
            $table->enum('status', ['aberto', 'preparo', 'pronto', 'entregue', 'fechado'])->default('aberto');
            $table->decimal('total', 10, 2);
            $table->timestamp('criado_em')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_pedidos');
    }
};
