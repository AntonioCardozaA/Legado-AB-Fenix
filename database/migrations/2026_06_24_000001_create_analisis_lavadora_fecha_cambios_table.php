<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_lavadora_fecha_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_lavadora_id')
                ->constrained('analisis_componentes')
                ->cascadeOnDelete();
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('fecha_anterior');
            $table->date('fecha_nueva');
            $table->timestamp('fecha_cambio')->useCurrent();
            $table->timestamps();

            $table->index(
                ['analisis_lavadora_id', 'fecha_cambio'],
                'al_fecha_cambios_analisis_fecha_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis_lavadora_fecha_cambios');
    }
};
