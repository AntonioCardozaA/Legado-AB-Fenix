<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('central_hidraulica_componentes')) {
            return;
        }

        if (!Schema::hasColumn('central_hidraulica_componentes', 'contabilizable')) {
            Schema::table('central_hidraulica_componentes', function (Blueprint $table) {
                $table->boolean('contabilizable')->default(true)->after('cantidad_editable');
            });
        }

        DB::table('central_hidraulica_componentes')
            ->where('codigo', 'ACEITE')
            ->update([
                'nombre' => 'Revision de aceite',
                'descripcion' => 'Revision de aceite por deposito T24.',
                'unidad' => 'lts',
                'requiere_lado' => false,
                'cantidad_editable' => false,
                'contabilizable' => false,
                'activo' => true,
                'updated_at' => now(),
            ]);

        if (!Schema::hasTable('central_hidraulica_configuraciones')) {
            return;
        }

        $aceiteId = DB::table('central_hidraulica_componentes')
            ->where('codigo', 'ACEITE')
            ->value('id');

        if (!$aceiteId) {
            return;
        }

        DB::table('central_hidraulica_configuraciones')
            ->where('componente_id', $aceiteId)
            ->update([
                'cantidad' => 300,
                'unidad' => 'lts',
                'detalle_excel' => '300 lts por deposito T24',
                'lado_requerido' => false,
                'activo' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('central_hidraulica_componentes')) {
            return;
        }

        $payload = [
            'nombre' => 'Aceite',
            'descripcion' => 'Aceite por deposito T24.',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('central_hidraulica_componentes', 'contabilizable')) {
            $payload['contabilizable'] = true;
        }

        DB::table('central_hidraulica_componentes')
            ->where('codigo', 'ACEITE')
            ->update($payload);

        if (Schema::hasColumn('central_hidraulica_componentes', 'contabilizable')) {
            Schema::table('central_hidraulica_componentes', function (Blueprint $table) {
                $table->dropColumn('contabilizable');
            });
        }
    }
};
