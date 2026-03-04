<?php
// database/migrations/[timestamp]_create_analisis_tendencia_mensual_lavadoras_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalisisTendenciaMensualLavadorasTable extends Migration
{
    public function up()
    {
        Schema::create('analisis_tendencia_mensual_lavadoras', function (Blueprint $table) {
            $table->id();
            
            // Relación con línea
            $table->unsignedBigInteger('linea_id');
            $table->foreign('linea_id')->references('id')->on('lineas')->onDelete('cascade');
            
            // Período del análisis (mes/año)
            $table->integer('anio');
            $table->integer('mes');
            
            // Datos capturados del mes - AHORA CON DECIMALES (10,2)
            $table->decimal('total_danos_52_semanas', 10, 2)->default(0);
            $table->decimal('total_danos_12_semanas', 10, 2)->default(0);
            $table->decimal('total_danos_4_semanas', 10, 2)->default(0);
            
            // Fechas de corte
            $table->date('fecha_corte_52')->nullable();
            $table->date('fecha_corte_12')->nullable();
            $table->date('fecha_corte_4')->nullable();
            
            // Observaciones
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Índices para búsqueda rápida
            $table->index(['linea_id', 'anio', 'mes']);
            $table->unique(['linea_id', 'anio', 'mes']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('analisis_tendencia_mensual_lavadoras');
    }
}