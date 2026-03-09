<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('pedido_id')->nullable()->constrained('tb_pedidos');
            $table->foreignId('mesa_id')->nullable()->constrained('tb_mesas');
            $table->foreignId('comanda_id')->nullable()->constrained('tb_comandas');
            $table->foreignId('cliente_id')->nullable()->constrained('tb_clientes');
            $table->foreignId('usuario_id')->nullable()->constrained('tb_usuarios');
            $table->foreignId('estacao_id')->nullable()->constrained('tb_cozinha_estacoes');
            $table->enum('destino', ['cozinha', 'operacao', 'mesa', 'comanda'])->default('operacao');
            $table->enum('tipo', ['pedido_status', 'item_status', 'mensagem'])->default('pedido_status');
            $table->enum('status', ['pendente', 'enviada', 'lida'])->default('pendente');
            $table->enum('prioridade', ['baixa', 'normal', 'alta'])->default('normal');
            $table->string('titulo', 120)->nullable();
            $table->text('mensagem')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'destino', 'status']);
            $table->index(['empresa_id', 'usuario_id']);
            $table->index(['empresa_id', 'estacao_id']);
            $table->index(['empresa_id', 'pedido_id']);
            $table->index(['empresa_id', 'mesa_id']);
            $table->index(['empresa_id', 'comanda_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_notificacoes');
    }
};
