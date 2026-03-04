<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            $table->enum('lado', ['VAPOR', 'PASILLO'])->nullable()->after('reductor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analisis_componentes', function (Blueprint $table) {
            $table->dropColumn('lado');
        });
    }
};