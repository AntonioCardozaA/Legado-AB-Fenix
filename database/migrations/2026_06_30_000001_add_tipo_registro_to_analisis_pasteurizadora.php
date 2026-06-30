<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->string('tipo_registro', 20)
                ->default('quick')
                ->after('area');
        });

        DB::table('analisis_pasteurizadora')
            ->whereNull('tipo_registro')
            ->update([
                'tipo_registro' => 'quick',
            ]);
    }

    public function down(): void
    {
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->dropColumn('tipo_registro');
        });
    }
};
