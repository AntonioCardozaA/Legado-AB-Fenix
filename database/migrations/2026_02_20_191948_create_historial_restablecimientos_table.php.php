<?php
// database/migrations/YYYY_MM_DD_HHmmss_create_historial_restablecimientos_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_restablecimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_id')->constrained('analisis_componentes')->onDelete('cascade');
            $table->foreignId('linea_id')->constrained('lineas')->onDelete('cascade');
            $table->foreignId('componente_id')->constrained('componentes')->onDelete('cascade');
            $table->string('reductor');
            $table->string('lado')->nullable();
            $table->date('fecha_analisis_original');
            $table->timestamp('fecha_restablecimiento');
            $table->string('motivo')->default('periodicidad');
            $table->integer('periodo_meses');
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('fecha_restablecimiento');
            $table->index(['linea_id', 'componente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_restablecimientos');
    }
};