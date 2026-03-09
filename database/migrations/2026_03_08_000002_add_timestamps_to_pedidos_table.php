<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tb_pedidos', 'created_at')) {
            Schema::table('tb_pedidos', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasColumn('tb_pedidos', 'updated_at')) {
            Schema::table('tb_pedidos', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tb_pedidos', 'updated_at')) {
            Schema::table('tb_pedidos', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }

        if (Schema::hasColumn('tb_pedidos', 'created_at')) {
            Schema::table('tb_pedidos', function (Blueprint $table) {
                $table->dropColumn('created_at');
            });
        }
    }
};
