<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_componentes', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('linea_id')
                ->constrained('lineas')
                ->cascadeOnDelete();
                
            $table->foreignId('componente_id')
                ->constrained('componentes')
                ->cascadeOnDelete();

            // Datos específicos del análisis
            $table->string('reductor');
            $table->date('fecha_analisis');
            $table->string('numero_orden');
            $table->string('actividad');
            
            // Evidencia
            $table->json('evidencia_fotos')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_componentes');
    }
};