<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_componentes', 'usuario_id')) {
                $table->foreignId('usuario_id')
                    ->nullable()
                    ->after('actividad')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (!Schema::hasColumn('analisis_pasteurizadora', 'usuario_id')) {
                $column = $table->foreignId('usuario_id')->nullable();

                if (Schema::hasColumn('analisis_pasteurizadora', 'responsable')) {
                    $column->after('responsable');
                } else {
                    $column->after('actividad');
                }

                $column->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_componentes', 'usuario_id')) {
                $table->dropConstrainedForeignId('usuario_id');
            }
        });

        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            if (Schema::hasColumn('analisis_pasteurizadora', 'usuario_id')) {
                $table->dropConstrainedForeignId('usuario_id');
            }
        });
    }
};
