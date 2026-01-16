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
            $table->foreignId('analisis_id')->constrained('analisis')->onDelete('cascade');
            $table->foreignId('componente_id')->constrained('componentes');
            $table->integer('cantidad_revisada')->default(0);
            $table->enum('estado', ['BUENO', 'REGULAR', 'DAÑADO', 'REEMPLAZADO']);
            $table->string('actividad')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('evidencia_fotos')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index('analisis_id');
            $table->index('componente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_componentes');
    }
};