<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_planos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100);
            $table->integer('limite_usuarios');
            $table->integer('limite_produtos');
            $table->decimal('valor', 10, 2);
            $table->boolean('ativo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_planos');
    }
};
