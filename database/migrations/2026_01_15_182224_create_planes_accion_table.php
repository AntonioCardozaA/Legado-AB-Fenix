<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paro_id')->constrained('paros')->onDelete('cascade');
            $table->string('actividad');
            $table->text('descripcion')->nullable();
            $table->date('fecha_planeada');
            $table->date('fecha_ejecucion')->nullable();
            $table->enum('estado', ['PENDIENTE', 'EN_PROCESO', 'COMPLETADA', 'ATRASADA']);
            $table->foreignId('responsable_id')->constrained('users');
            $table->string('plan_referencia')->nullable();
            $table->boolean('encontro_dano')->default(false);
            $table->text('observaciones_dano')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_accion');
    }
};
