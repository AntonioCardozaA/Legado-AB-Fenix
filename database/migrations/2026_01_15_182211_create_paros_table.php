<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained('lineas');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('tipo'); // Programado, Emergencia
            $table->foreignId('supervisor_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paros');
    }
};
