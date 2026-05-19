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
            $table->string('numero_orden')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('analisis_pasteurizadora')
            ->whereNull('numero_orden')
            ->update(['numero_orden' => '']);

        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->string('numero_orden')->nullable(false)->change();
        });
    }
};
