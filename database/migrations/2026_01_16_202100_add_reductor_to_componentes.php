<?php
// database/migrations/2026_01_17_add_reductor_to_componentes.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->string('reductor')->nullable()->after('codigo');
            $table->string('ubicacion')->nullable()->after('reductor');
        });
    }

    public function down()
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropColumn(['reductor', 'ubicacion']);
        });
    }
};