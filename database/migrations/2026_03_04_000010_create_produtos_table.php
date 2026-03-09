<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('categoria_id')->constrained('tb_categorias');
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->decimal('custo', 10, 2);
            $table->string('codigo_barras', 50)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_produtos');
    }
};
