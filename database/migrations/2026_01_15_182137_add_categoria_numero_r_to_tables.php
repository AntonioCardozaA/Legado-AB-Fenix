<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Analisis
        Schema::table('analisis', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            }

            if (!Schema::hasColumn('analisis', 'numero_r_id')) {
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->nullOnDelete();
            }
        });

        // 2. Analisis_componentes
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_componentes', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            }

            if (!Schema::hasColumn('analisis_componentes', 'numero_r_id')) {
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->nullOnDelete();
            }
        });

        // 3. Modificar numeros_r para que categoria_id sea nullable y sin FK
        Schema::table('numeros_r', function (Blueprint $table) {
            // Quitar FK si existe
            try {
                $table->dropForeign(['categoria_id']);
            } catch (\Exception $e) {
                // Si no existe, ignorar
            }

            // Hacer nullable
            $table->integer('categoria_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('numeros_r', function (Blueprint $table) {
            $table->foreignId('categoria_id')->constrained('categorias')->change();
        });

        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_componentes', 'numero_r_id')) {
                $table->dropForeign(['numero_r_id']);
                $table->dropColumn('numero_r_id');
            }
            if (Schema::hasColumn('analisis_componentes', 'categoria_id')) {
                $table->dropForeign(['categoria_id']);
                $table->dropColumn('categoria_id');
            }
        });

        Schema::table('analisis', function (Blueprint $table) {
            if (Schema::hasColumn('analisis', 'numero_r_id')) {
                $table->dropForeign(['numero_r_id']);
                $table->dropColumn('numero_r_id');
            }
            if (Schema::hasColumn('analisis', 'categoria_id')) {
                $table->dropForeign(['categoria_id']);
                $table->dropColumn('categoria_id');
            }
        });
    }
};
