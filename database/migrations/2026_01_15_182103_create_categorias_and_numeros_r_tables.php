<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear tabla categorias primero
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 2. Crear tabla numeros_r después (depende de categorias y lineas)
        Schema::create('numeros_r', function (Blueprint $table) {
            $table->id();

            // Relación con categorias
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();

            // Relación con lineas (nueva columna)
            $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();

            $table->string('codigo'); // R1, R2, R3, etc.
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Unicidad por categoria y código
            $table->unique(['categoria_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numeros_r');
        Schema::dropIfExists('categorias');
    }
};
