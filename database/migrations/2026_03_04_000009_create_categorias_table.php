<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->string('nome', 120);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_categorias');
    }
};
