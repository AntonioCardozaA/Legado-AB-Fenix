<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_pasteurizadora', 'brazos_torsion')) {
                $table->json('brazos_torsion')->nullable();
            }

            if (!Schema::hasColumn('analisis_pasteurizadora', 'total_brazos_torsion')) {
                $table->integer('total_brazos_torsion')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_pasteurizadora', 'brazos_torsion')) {
                $table->dropColumn('brazos_torsion');
            }

            if (Schema::hasColumn('analisis_pasteurizadora', 'total_brazos_torsion')) {
                $table->dropColumn('total_brazos_torsion');
            }
        });
    }
};
