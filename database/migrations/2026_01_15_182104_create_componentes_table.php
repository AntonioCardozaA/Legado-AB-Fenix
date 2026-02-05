<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('componentes', function (Blueprint $table) {
            $table->id();

            // Columnas usadas en el seeder
            $table->string('linea')->nullable();      // L-04, L-05, etc. o NULL para generales
            $table->string('nombre');
            $table->string('codigo')->unique();
            $table->string('reductor')->nullable();   // cadena: 1,2,3, loca...
            $table->string('ubicacion')->nullable();  // Lavadora, Eje Principal, etc.

            $table->integer('cantidad_total')->default(0);
            $table->boolean('activo')->default(true);

            // Nueva columna: relación con numeros_r
            $table->foreignId('numero_r_id')
                  ->nullable()
                  ->constrained('numeros_r')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('componentes');
    }
};
