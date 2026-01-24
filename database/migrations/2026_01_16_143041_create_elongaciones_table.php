<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('elongaciones', function (Blueprint $table) {
            $table->id();
            $table->string('linea')->default('L-07'); // Por defecto L-07 como en la imagen
            $table->string('seccion')->default('LAVADORA'); // Por defecto LAVADORA
            $table->enum('tipo', ['bombas', 'vapor']);
            
            // Mediciones lado bombas (8 mediciones como en la imagen)
            $table->decimal('bombas_1', 5, 1)->nullable();
            $table->decimal('bombas_2', 5, 1)->nullable();
            $table->decimal('bombas_3', 5, 1)->nullable();
            $table->decimal('bombas_4', 5, 1)->nullable();
            $table->decimal('bombas_5', 5, 1)->nullable();
            $table->decimal('bombas_6', 5, 1)->nullable();
            $table->decimal('bombas_7', 5, 1)->nullable();
            $table->decimal('bombas_8', 5, 1)->nullable();
            $table->decimal('bombas_promedio', 5, 2)->nullable();
            $table->decimal('bombas_porcentaje', 5, 2)->nullable();
            
            // Mediciones lado vapor (4 mediciones como en la imagen)
            $table->decimal('vapor_1', 5, 1)->nullable();
            $table->decimal('vapor_2', 5, 1)->nullable();
            $table->decimal('vapor_3', 5, 1)->nullable();
            $table->decimal('vapor_4', 5, 1)->nullable();
            $table->decimal('vapor_promedio', 5, 2)->nullable();
            $table->decimal('vapor_porcentaje', 5, 2)->nullable();
            
            // Hodómetro y juego de rodaja
            $table->bigInteger('hodometro')->nullable();
            $table->decimal('juego_rodaja_bombas', 5, 2)->nullable();
            $table->decimal('juego_rodaja_vapor', 5, 2)->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('elongaciones');
    }
};