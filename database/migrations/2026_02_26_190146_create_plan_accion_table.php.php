<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanAccionTable extends Migration
{
    public function up()
    {
        Schema::create('plan_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('linea_id')->constrained()->onDelete('cascade');
            $table->text('actividad');
            $table->date('fecha_pcm1')->nullable();
            $table->date('fecha_pcm2')->nullable();
            $table->date('fecha_pcm3')->nullable();
            $table->date('fecha_pcm4')->nullable();
            $table->boolean('notificacion_enviada')->default(false);
            $table->date('fecha_recordatorio')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('plan_accion');
    }
}