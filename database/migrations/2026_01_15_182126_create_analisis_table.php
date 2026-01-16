<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas');
            $table->date('fecha_analisis');
            $table->string('numero_orden');
            $table->text('observaciones')->nullable();
            $table->integer('horometro')->nullable();
            $table->decimal('elongacion_promedio', 5, 2)->nullable();
            $table->decimal('juego_rodaja', 5, 2)->nullable();
            $table->foreignId('usuario_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analisis');
    }
};
