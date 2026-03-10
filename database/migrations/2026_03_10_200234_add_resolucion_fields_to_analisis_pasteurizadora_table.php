<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->boolean('resuelto_por_cambio')->default(false)->after('estado');
            $table->timestamp('fecha_resolucion')->nullable()->after('resuelto_por_cambio');
            $table->text('nota_resolucion')->nullable()->after('fecha_resolucion');
        });
    }

    public function down()
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->dropColumn(['resuelto_por_cambio', 'fecha_resolucion', 'nota_resolucion']);
        });
    }
};