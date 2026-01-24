<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis', function (Blueprint $table) {
            // Verificar si las columnas ya existen antes de agregarlas
            if (!Schema::hasColumn('analisis', 'componente_id')) {
                $table->foreignId('componente_id')->nullable()->constrained('componentes')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('analisis', 'categoria_id')) {
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('analisis', 'numero_r_id')) {
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('analisis', 'reductor')) {
                $table->string('reductor')->nullable();
            }
            
            if (!Schema::hasColumn('analisis', 'actividad')) {
                $table->text('actividad')->nullable();
            }
            
            if (!Schema::hasColumn('analisis', 'fotos')) {
                $table->json('fotos')->nullable();
            }
            
            // Si quieres mantener elongacion_promedio y juego_rodaja, déjalas
            // O si quieres eliminarlas, puedes hacerlo aquí
        });
    }

    public function down(): void
    {
        Schema::table('analisis', function (Blueprint $table) {
            // Solo eliminar las columnas si existen
            $columns = ['componente_id', 'categoria_id', 'numero_r_id', 'reductor', 'actividad', 'fotos'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('analisis', $column)) {
                    if (in_array($column, ['componente_id', 'categoria_id', 'numero_r_id'])) {
                        $table->dropForeign(['analisis_' . $column . '_foreign']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};