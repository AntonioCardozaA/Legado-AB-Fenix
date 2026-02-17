<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoMaquinaToPlanAccionTable extends Migration
{
    public function up()
    {
        Schema::table('plan_accion', function (Blueprint $table) {
            $table->json('tipo_maquina')->nullable()->after('observaciones');
        });
    }

    public function down()
    {
        Schema::table('plan_accion', function (Blueprint $table) {
            $table->dropColumn('tipo_maquina');
        });
    }
}