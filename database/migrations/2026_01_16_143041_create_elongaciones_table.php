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
            $table->string('linea'); // Sin valor por defecto
            $table->string('seccion')->default('LAVADORA');
            
            // Mediciones lado bombas (10 mediciones)
            $table->decimal('bombas_1', 5, 1)->nullable();
            $table->decimal('bombas_2', 5, 1)->nullable();
            $table->decimal('bombas_3', 5, 1)->nullable();
            $table->decimal('bombas_4', 5, 1)->nullable();
            $table->decimal('bombas_5', 5, 1)->nullable();
            $table->decimal('bombas_6', 5, 1)->nullable();
            $table->decimal('bombas_7', 5, 1)->nullable();
            $table->decimal('bombas_8', 5, 1)->nullable();
            $table->decimal('bombas_9', 5, 1)->nullable();
            $table->decimal('bombas_10', 5, 1)->nullable();
            $table->decimal('bombas_promedio', 5, 2)->nullable();
            $table->decimal('bombas_porcentaje', 5, 2)->nullable();
            
            // Mediciones lado vapor (10 mediciones)
            $table->decimal('vapor_1', 5, 1)->nullable();
            $table->decimal('vapor_2', 5, 1)->nullable();
            $table->decimal('vapor_3', 5, 1)->nullable();
            $table->decimal('vapor_4', 5, 1)->nullable();
            $table->decimal('vapor_5', 5, 1)->nullable();
            $table->decimal('vapor_6', 5, 1)->nullable();
            $table->decimal('vapor_7', 5, 1)->nullable();
            $table->decimal('vapor_8', 5, 1)->nullable();
            $table->decimal('vapor_9', 5, 1)->nullable();
            $table->decimal('vapor_10', 5, 1)->nullable();
            $table->decimal('vapor_promedio', 5, 2)->nullable();
            $table->decimal('vapor_porcentaje', 5, 2)->nullable();
            
            // Hodómetro y juego de rodaja
            $table->bigInteger('hodometro')->nullable();
            $table->decimal('juego_rodaja_bombas', 5, 2)->nullable();
            $table->decimal('juego_rodaja_vapor', 5, 2)->nullable();
            
            $table->timestamps();
            
            // Índice para búsquedas por línea
            $table->index('linea');
        });
    }

    public function down()
    {
        Schema::dropIfExists('elongaciones');
    }
};