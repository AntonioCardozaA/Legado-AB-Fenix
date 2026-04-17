<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            // Campo JSON para almacenar qué componentes específicos se revisaron
            // Ejemplo: ["1", "2"] para indicar que se revisaron los componentes 1 y 2
            $table->json('componentes_revisados')->nullable()->after('revisadas_piezas');
        });
    }

    public function down(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->dropColumn('componentes_revisados');
        });
    }
};
