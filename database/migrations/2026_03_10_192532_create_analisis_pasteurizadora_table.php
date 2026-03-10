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
        Schema::create('analisis_pasteurizadora', function (Blueprint $table) {

            $table->id();

            // Relación con línea
            $table->foreignId('linea_id')
                ->constrained('lineas')
                ->cascadeOnDelete();

            $table->integer('modulo')->nullable();

            $table->string('componente')->nullable();

            $table->date('fecha_analisis')->nullable();

            $table->string('numero_orden')->nullable();

            $table->text('actividad')->nullable();

            $table->string('estado')->nullable();

            $table->string('lado')->nullable();

            $table->string('responsable')->nullable();

            $table->text('observaciones')->nullable();

            /*
            |--------------------------------------------------------------------------
            | EVIDENCIAS
            |--------------------------------------------------------------------------
            */

            $table->json('evidencia_fotos')->nullable();

            /*
            |--------------------------------------------------------------------------
            | ANALISIS 52 - 12 - 4
            |--------------------------------------------------------------------------
            */

            $table->decimal('valor_anterior_52', 8, 2)->nullable();
            $table->decimal('valor_actual_52', 8, 2)->nullable();

            $table->decimal('valor_anterior_12', 8, 2)->nullable();
            $table->decimal('valor_actual_12', 8, 2)->nullable();

            $table->decimal('valor_anterior_4', 8, 2)->nullable();
            $table->decimal('valor_actual_4', 8, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | COMPONENTES - 7 PRINCIPALES (como en la imagen)
            |--------------------------------------------------------------------------
            */

            // 1. ANILLAS (Ventanas-Cortinas) - AHORA POR MÓDULO también
            $table->integer('total_anillas')->default(16); // Cambiado a 16 por módulo
            $table->integer('revisadas_anillas')->default(0);

            // 2. PLACAS PERNO - Por módulo
            $table->integer('total_placas_perno')->default(16);
            $table->integer('revisadas_placas_perno')->default(0);

            // 3. REGLILLAS (Parrillas) - Por módulo
            $table->integer('total_reglillas')->default(16);
            $table->integer('revisadas_reglillas')->default(0);

            // 4. RODAMIENTOS - Por módulo
            $table->integer('total_rodamientos')->default(16);
            $table->integer('revisadas_rodamientos')->default(0);

            // 5. EXCÉNTRICOS - Por módulo
            $table->integer('total_excentricos')->default(16);
            $table->integer('revisadas_excentricos')->default(0);

            // 6. PISTAS - Por módulo
            $table->integer('total_pistas')->default(16);
            $table->integer('revisadas_pistas')->default(0);

            // 7. ESPARRAGOS - Por módulo
            $table->integer('total_esparragos')->default(16);
            $table->integer('revisadas_esparragos')->default(0);

            /*
            |--------------------------------------------------------------------------
            | PLAN ACCION PCM
            |--------------------------------------------------------------------------
            */

            $table->json('plan_accion_pcm1')->nullable();
            $table->json('plan_accion_pcm2')->nullable();
            $table->json('plan_accion_pcm3')->nullable();
            $table->json('plan_accion_pcm4')->nullable();

            /*
            |--------------------------------------------------------------------------
            | SISTEMA
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | INDEXES
            |--------------------------------------------------------------------------
            */

            $table->index('linea_id');
            $table->index('modulo');
            $table->index('componente');
            $table->index('fecha_analisis');
            $table->index('estado');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analisis_pasteurizadora');
    }
};