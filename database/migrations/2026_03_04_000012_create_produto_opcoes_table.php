<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_produto_opcoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained('tb_produtos');
            $table->string('nome', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_produto_opcoes');
    }
};
