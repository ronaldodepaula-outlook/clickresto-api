<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_mesas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('tb_empresas');
            $table->integer('numero');
            $table->enum('status', ['livre', 'ocupada'])->default('livre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_mesas');
    }
};
