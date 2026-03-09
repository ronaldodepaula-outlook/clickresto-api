<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->foreignId('plano_id')->constrained('tb_planos');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('status', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_assinaturas');
    }
};
