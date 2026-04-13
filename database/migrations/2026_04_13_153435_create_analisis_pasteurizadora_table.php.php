<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_pasteurizadora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('linea_id');
            $table->integer('modulo');
            $table->string('nivel')->nullable();
            $table->string('componente');
            $table->string('lado')->nullable();
            $table->date('fecha_analisis');
            $table->string('numero_orden');
            $table->text('actividad');
            $table->string('estado');
            $table->string('responsable')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('evidencia_fotos')->nullable();

            // Campos de análisis
            $table->decimal('valor_anterior_52', 8, 2)->nullable();
            $table->decimal('valor_actual_52', 8, 2)->nullable();
            $table->decimal('valor_anterior_12', 8, 2)->nullable();
            $table->decimal('valor_actual_12', 8, 2)->nullable();
            $table->decimal('valor_anterior_4', 8, 2)->nullable();
            $table->decimal('valor_actual_4', 8, 2)->nullable();

            // Cantidades dinámicas
            $table->integer('total_piezas')->nullable();
            $table->integer('revisadas_piezas')->nullable();

            // Planes PCM
            $table->json('plan_accion_pcm1')->nullable();
            $table->json('plan_accion_pcm2')->nullable();
            $table->json('plan_accion_pcm3')->nullable();
            $table->json('plan_accion_pcm4')->nullable();

            // Resolución
            $table->boolean('resuelto_por_cambio')->default(false);
            $table->timestamp('fecha_resolucion')->nullable();
            $table->text('nota_resolucion')->nullable();
            $table->unsignedBigInteger('id_registro_que_resolvio')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('linea_id')->references('id')->on('lineas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_pasteurizadora');
    }
};