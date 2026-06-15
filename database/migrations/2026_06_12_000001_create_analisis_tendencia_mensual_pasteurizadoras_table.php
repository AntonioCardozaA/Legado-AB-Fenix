<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_tendencia_mensual_pasteurizadoras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linea_id');
            $table->integer('anio');
            $table->integer('mes');
            $table->decimal('total_danos_52_semanas', 10, 2)->default(0);
            $table->decimal('total_danos_12_semanas', 10, 2)->default(0);
            $table->decimal('total_danos_4_semanas', 10, 2)->default(0);
            $table->date('fecha_corte_52')->nullable();
            $table->date('fecha_corte_12')->nullable();
            $table->date('fecha_corte_4')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('linea_id')->references('id')->on('lineas')->onDelete('cascade');
            $table->index(['linea_id', 'anio', 'mes'], 'atm_past_linea_periodo_idx');
            $table->unique(['linea_id', 'anio', 'mes'], 'atm_past_linea_periodo_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_tendencia_mensual_pasteurizadoras');
    }
};
