<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elongaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analisis_id')
                  ->constrained('analisis')
                  ->cascadeOnDelete();

            $table->integer('horometro');

            $table->decimal('elongacion_bombas_mm', 6, 2)->nullable();
            $table->decimal('elongacion_vapor_mm', 6, 2)->nullable();

            $table->decimal('elongacion_bombas_pct', 5, 2)->nullable();
            $table->decimal('elongacion_vapor_pct', 5, 2)->nullable();

            $table->string('estado_bombas')->nullable();
            $table->string('estado_vapor')->nullable();

            $table->decimal('juego_rodaja_bombas', 5, 2)->nullable();
            $table->decimal('juego_rodaja_vapor', 5, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elongaciones');
    }
};

