<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_pasteurizadora', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('lado');
            }

            if (!Schema::hasColumn('analisis_pasteurizadora', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_pasteurizadora', 'fecha_fin')) {
                $table->dropColumn('fecha_fin');
            }

            if (Schema::hasColumn('analisis_pasteurizadora', 'fecha_inicio')) {
                $table->dropColumn('fecha_inicio');
            }
        });
    }
};
