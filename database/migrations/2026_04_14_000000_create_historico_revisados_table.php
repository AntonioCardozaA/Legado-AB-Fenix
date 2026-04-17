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
        Schema::create('historico_revisados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas')->onDelete('cascade');

            // Información del componente
            $table->string('componente')->comment('Código del componente (ANILLAS, EXCENTRICOS, etc)');
            $table->string('componente_nombre')->comment('Nombre completo del componente');

            // Estadísticas
            $table->integer('cantidad_total')->default(0)->comment('Total de piezas del componente');
            $table->integer('cantidad_revisada')->default(0)->comment('Cantidad de piezas revisadas');
            $table->integer('porcentaje')->default(0)->comment('Porcentaje de avance (0-100)');

            // Fecha de la última revisión
            $table->date('ultima_revision')->nullable()->comment('Última fecha en que se revisó este componente');
            $table->timestamp('proximo_vencimiento')->nullable()->comment('Cuándo vence el análisis');

            // Tipo de pasteurizadora (si es necesario)
            $table->string('tipo_pasteurizadora')->default('sencillo')->comment('sencillo o doble');

            // Estado del análisis
            $table->string('estado')->default('pendiente')->comment('pendiente, en_progreso, completo');

            // Auditoría
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Índices para búsquedas rápidas
            $table->index('linea_id');
            $table->index('componente');
            $table->index('ultima_revision');
            $table->index('proximo_vencimiento');
            $table->unique(['linea_id', 'componente']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_revisados');
    }
};
