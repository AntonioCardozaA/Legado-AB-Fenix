<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plan_accion')) {
            return;
        }

        if (!Schema::hasColumn('plan_accion', 'area_pasteurizadora')) {
            Schema::table('plan_accion', function (Blueprint $table) {
                $table->string('area_pasteurizadora')
                    ->nullable()
                    ->after('tipo_equipo')
                    ->index();
            });
        }

        DB::table('plan_accion')
            ->where('tipo_equipo', 'pasteurizadora')
            ->whereNull('area_pasteurizadora')
            ->update(['area_pasteurizadora' => 'mecanica']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('plan_accion') || !Schema::hasColumn('plan_accion', 'area_pasteurizadora')) {
            return;
        }

        Schema::table('plan_accion', function (Blueprint $table) {
            $table->dropIndex(['area_pasteurizadora']);
            $table->dropColumn('area_pasteurizadora');
        });
    }
};
