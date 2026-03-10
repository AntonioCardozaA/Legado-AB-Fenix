<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
        public function up()
        {
            Schema::table('elongaciones', function (Blueprint $table) {
                $table->boolean('requiere_cambio')->default(false)->after('vapor_porcentaje');
            });
        }

        public function down()
        {
            Schema::table('elongaciones', function (Blueprint $table) {
                $table->dropColumn('requiere_cambio');
            });
        }
};
