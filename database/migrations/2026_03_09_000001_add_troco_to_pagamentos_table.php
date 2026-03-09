<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('tb_pagamentos', 'troco')) {
            Schema::table('tb_pagamentos', function (Blueprint $table) {
                $table->decimal('troco', 10, 2)->nullable()->after('valor');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tb_pagamentos', 'troco')) {
            Schema::table('tb_pagamentos', function (Blueprint $table) {
                $table->dropColumn('troco');
            });
        }
    }
};
