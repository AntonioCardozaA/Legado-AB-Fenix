<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analisis_pasteurizadora')) {
            return;
        }

        DB::table('analisis_pasteurizadora')
            ->whereIn('estado', [
                'Danado - Requiere cambio',
                'DaÃ±ado - Requiere cambio',
                'DaÃƒÂ±ado - Requiere cambio',
            ])
            ->update(['estado' => 'Dañado - Requiere cambio']);
    }

    public function down(): void
    {
        //
    }
};
