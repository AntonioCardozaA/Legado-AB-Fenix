<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mttr_indicadores', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 12)->unique();
            $table->decimal('minutos', 14, 4)->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mttr_actualizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->constrained('mttr_indicadores')->restrictOnDelete();
            $table->decimal('minutos_anteriores', 14, 4)->nullable();
            $table->decimal('minutos_nuevos', 14, 4);
            // Conserva el identificador del autor aunque se elimine su cuenta.
            $table->unsignedBigInteger('usuario_id');
            $table->timestamp('created_at');
        });

        $claves = ['GENERAL', 'L-04', 'L-05', 'L-06', 'L-07', 'L-09', 'L-12', 'L-13'];
        $creado = now();

        DB::table('mttr_indicadores')->insert(array_map(
            fn (string $clave) => ['clave' => $clave, 'created_at' => $creado],
            $claves,
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('mttr_actualizaciones');
        Schema::dropIfExists('mttr_indicadores');
    }
};
