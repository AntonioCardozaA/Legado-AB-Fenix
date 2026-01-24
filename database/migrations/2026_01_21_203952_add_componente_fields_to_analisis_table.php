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
        Schema::table('analisis', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis', 'categoria_id')) {
                $table->bigInteger('categoria_id')->unsigned()->nullable();
            }

            if (!Schema::hasColumn('analisis', 'componente_id')) {
                $table->bigInteger('componente_id')->unsigned()->nullable();
            }

            if (!Schema::hasColumn('analisis', 'reductor')) {
                $table->string('reductor')->nullable();
            }

            if (!Schema::hasColumn('analisis', 'fecha_analisis')) {
                $table->date('fecha_analisis')->nullable();
            }

            if (!Schema::hasColumn('analisis', 'numero_orden')) {
                $table->string('numero_orden')->nullable();
            }

            if (!Schema::hasColumn('analisis', 'actividad')) {
                $table->string('actividad')->nullable();
            }

            if (!Schema::hasColumn('analisis', 'estado')) {
                $table->string('estado')->default('pendiente');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analisis', function (Blueprint $table) {
            if (Schema::hasColumn('analisis', 'categoria_id')) {
                $table->dropColumn('categoria_id');
            }

            if (Schema::hasColumn('analisis', 'componente_id')) {
                $table->dropColumn('componente_id');
            }

            if (Schema::hasColumn('analisis', 'reductor')) {
                $table->dropColumn('reductor');
            }

            if (Schema::hasColumn('analisis', 'fecha_analisis')) {
                $table->dropColumn('fecha_analisis');
            }

            if (Schema::hasColumn('analisis', 'numero_orden')) {
                $table->dropColumn('numero_orden');
            }

            if (Schema::hasColumn('analisis', 'actividad')) {
                $table->dropColumn('actividad');
            }

            if (Schema::hasColumn('analisis', 'estado')) {
                $table->dropColumn('estado');
            }
        });
    }
};
