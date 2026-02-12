<?php
// database/migrations/2024_01_01_000001_create_analisis_pasteurizadoras_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('analisis_pasteurizadoras', function (Blueprint $table) {
            $table->id();
            
            // Información de línea y módulo
            $table->string('linea')->default('L-07');
            $table->integer('modulo')->nullable(); // 1-11
            
            // Componentes específicos de pasteurizadora
            $table->enum('componente', [
                'anillas_pernos',
                'placas_perno',
                'parrillas',
                'rodamientos',
                'excentricos_levas',
                'reglillas'
            ])->nullable();
            
            // Actividad y fechas
            $table->date('fecha')->nullable();
            $table->text('actividad')->nullable();
            $table->integer('cantidad')->nullable(); // Cantidad intervenida
            
            // Análisis 52-12-4
            $table->decimal('valor_anterior_52', 8, 2)->nullable();
            $table->decimal('valor_actual_52', 8, 2)->nullable();
            $table->decimal('valor_anterior_12', 8, 2)->nullable();
            $table->decimal('valor_actual_12', 8, 2)->nullable();
            $table->decimal('valor_anterior_4', 8, 2)->nullable();
            $table->decimal('valor_actual_4', 8, 2)->nullable();
            
            // Histórico de revisados
            $table->integer('total_anillas')->default(12);
            $table->integer('revisadas_anillas')->default(5);
            $table->integer('total_placas')->default(12);
            $table->integer('revisadas_placas')->default(5);
            $table->integer('total_parrillas')->default(12);
            $table->integer('revisadas_parrillas')->default(5);
            $table->integer('total_rodamientos')->default(12);
            $table->integer('revisadas_rodamientos')->default(5);
            $table->integer('total_excentricos')->default(12);
            $table->integer('revisadas_excentricos')->default(5);
            $table->integer('total_reglillas')->default(12);
            $table->integer('revisadas_reglillas')->default(5);
            
            // Plan de acción PCM
            $table->json('plan_accion_pcm1')->nullable();
            $table->json('plan_accion_pcm2')->nullable();
            $table->json('plan_accion_pcm3')->nullable();
            $table->json('plan_accion_pcm4')->nullable();
            
            // Metadata
            $table->json('fotos')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('responsable')->nullable();
            $table->enum('estado', ['pendiente', 'completado', 'en_proceso'])->default('pendiente');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('linea');
            $table->index('componente');
            $table->index('fecha');
            $table->index('estado');
        });
    }

    public function down()
    {
        Schema::dropIfExists('analisis_pasteurizadoras');
    }
};