<?php
// database/migrations/2026_01_17_adjust_analisis_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('analisis', function (Blueprint $table) {
            $table->unsignedBigInteger('linea_id')->nullable()->change();
            $table->string('reductor')->nullable()->after('numero_r_id');
            $table->json('fotos')->nullable()->after('observaciones');
            $table->string('actividad')->nullable()->after('horometro');
            $table->dropColumn('elongacion_promedio');
            $table->dropColumn('juego_rodaja');
        });
    }

    public function down()
    {
        Schema::table('analisis', function (Blueprint $table) {
            $table->unsignedBigInteger('linea_id')->nullable(false)->change();
            $table->dropColumn(['reductor', 'fotos', 'actividad']);
            $table->decimal('elongacion_promedio', 8, 2)->nullable();
            $table->decimal('juego_rodaja', 8, 2)->nullable();
        });
    }
};