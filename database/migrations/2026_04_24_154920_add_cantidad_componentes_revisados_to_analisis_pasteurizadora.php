<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
        if (!Schema::hasColumn('analisis_pasteurizadora', 'cantidad_componentes_revisados')) {
            $table->integer('cantidad_componentes_revisados')->nullable();
        }
    });
}

    public function down()
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->dropColumn('cantidad_componentes_revisados');
        });
    }
};
