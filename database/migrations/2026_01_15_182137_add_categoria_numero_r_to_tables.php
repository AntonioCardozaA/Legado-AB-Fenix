<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Primero agregar a 'analisis'
        Schema::table('analisis', function (Blueprint $table) {
            // Verificar si ya existen las columnas para evitar errores
            if (!Schema::hasColumn('analisis', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('analisis', 'numero_r_id')) {
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->nullOnDelete();
            }
        });

        // 2. Luego agregar a 'analisis_componentes'
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_componentes', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            }
            
            if (!Schema::hasColumn('analisis_componentes', 'numero_r_id')) {
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        // Eliminar en orden inverso
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