<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Esta migracion quedo versionada con una definicion duplicada de
        // "analisis_pasteurizadora". La creacion valida de la tabla vive en la
        // migracion 2026_04_13_153435_create_analisis_pasteurizadora_table.php.php.
        // La dejamos como no-op para no romper instalaciones nuevas ni testing.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
