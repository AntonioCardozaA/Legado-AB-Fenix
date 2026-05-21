<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('elongaciones', 'linea_id')) {
            Schema::table('elongaciones', function (Blueprint $table) {
                $table->foreignId('linea_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('lineas')
                    ->cascadeOnDelete();
            });
        }

        $lineas = DB::table('lineas')->pluck('id', 'nombre');

        foreach ($lineas as $nombre => $lineaId) {
            DB::table('elongaciones')
                ->where('linea', $nombre)
                ->whereNull('linea_id')
                ->update(['linea_id' => $lineaId]);
        }

        $driver = Schema::getConnection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                DB::statement('CREATE INDEX idx_elongaciones_linea_fecha ON elongaciones (linea, created_at)');
            } else {
                DB::statement('CREATE INDEX idx_elongaciones_linea_fecha ON elongaciones (`linea`, `created_at`)');
            }
        } catch (\Throwable) {
            // El indice puede existir en bases previamente actualizadas.
        }
    }

    public function down(): void
    {
        try {
            if (Schema::hasColumn('elongaciones', 'linea_id')) {
                Schema::table('elongaciones', function (Blueprint $table) {
                    $table->dropConstrainedForeignId('linea_id');
                });
            }
        } catch (\Throwable) {
            // Algunas plataformas recrean la tabla al eliminar llaves foraneas.
        }

        try {
            DB::statement('DROP INDEX idx_elongaciones_linea_fecha');
        } catch (\Throwable) {
            try {
                DB::statement('DROP INDEX idx_elongaciones_linea_fecha ON elongaciones');
            } catch (\Throwable) {
                // Ignorar si el indice no existe.
            }
        }
    }
};
