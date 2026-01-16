<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mediciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_id')->constrained('analisis')->onDelete('cascade');
            $table->string('tipo');
            $table->decimal('medicion_1', 6, 2);
            $table->decimal('medicion_2', 6, 2);
            $table->decimal('medicion_3', 6, 2);
            $table->decimal('medicion_4', 6, 2);
            $table->decimal('medicion_5', 6, 2);
            $table->decimal('medicion_6', 6, 2);
            $table->decimal('medicion_7', 6, 2);
            $table->decimal('medicion_8', 6, 2);
            $table->decimal('promedio', 6, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mediciones');
    }
};
