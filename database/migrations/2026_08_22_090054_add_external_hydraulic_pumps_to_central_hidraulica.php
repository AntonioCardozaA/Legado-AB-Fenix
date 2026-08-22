<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COMPONENTE_BOMBA_EXTERNA = 'BOMBAS_HIDRAULICAS_EXTERNAS';
    private const COMPONENTE_BOMBA_INUNDADA = 'BOMBAS_HIDRAULICAS_INUNDADAS';
    private const PASTEURIZADORES_DOBLES = ['P-06', 'P-07', 'P-11'];
    private const PISOS = ['SUPERIOR', 'INFERIOR'];

    public function up(): void
    {
        if (
            !Schema::hasTable('central_hidraulica_componentes')
            || !Schema::hasTable('central_hidraulica_configuraciones')
        ) {
            return;
        }

        $now = now();
        $componentPayload = [
            'nombre' => 'Bombas hidraulicas externas',
            'descripcion' => 'Bombas externas distribuidas por piso y lado.',
            'unidad' => 'pza',
            'requiere_lado' => true,
            'cantidad_editable' => false,
            'contabilizable' => true,
            'activo' => true,
            'orden' => 4,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('central_hidraulica_componentes', 'deleted_at')) {
            $componentPayload['deleted_at'] = null;
        }

        $componentKeys = ['codigo' => self::COMPONENTE_BOMBA_EXTERNA];
        $componentExists = DB::table('central_hidraulica_componentes')
            ->where($componentKeys)
            ->exists();

        if ($componentExists) {
            DB::table('central_hidraulica_componentes')
                ->where($componentKeys)
                ->update($componentPayload);
        } else {
            DB::table('central_hidraulica_componentes')
                ->insert(array_merge($componentKeys, $componentPayload, ['created_at' => $now]));
        }

        $bombaExternaId = DB::table('central_hidraulica_componentes')
            ->where('codigo', self::COMPONENTE_BOMBA_EXTERNA)
            ->value('id');

        if (!$bombaExternaId) {
            return;
        }

        foreach (self::PASTEURIZADORES_DOBLES as $pasteurizador) {
            foreach (self::PISOS as $piso) {
                $keys = [
                    'pasteurizador' => $pasteurizador,
                    'piso' => $piso,
                    'componente_id' => $bombaExternaId,
                ];

                $values = [
                    'cantidad' => 1,
                    'unidad' => 'pza',
                    'detalle_excel' => '2 bombas por piso (1 por lado)',
                    'lado_requerido' => true,
                    'activo' => true,
                    'orden' => 4,
                    'updated_at' => $now,
                ];

                $exists = DB::table('central_hidraulica_configuraciones')
                    ->where($keys)
                    ->exists();

                if ($exists) {
                    DB::table('central_hidraulica_configuraciones')
                        ->where($keys)
                        ->update($values);
                } else {
                    DB::table('central_hidraulica_configuraciones')
                        ->insert(array_merge($keys, $values, ['created_at' => $now]));
                }
            }
        }

        DB::table('central_hidraulica_configuraciones')
            ->where('componente_id', $bombaExternaId)
            ->whereNotIn('pasteurizador', self::PASTEURIZADORES_DOBLES)
            ->update([
                'activo' => false,
                'updated_at' => $now,
            ]);

        $bombaInundadaId = DB::table('central_hidraulica_componentes')
            ->where('codigo', self::COMPONENTE_BOMBA_INUNDADA)
            ->value('id');

        if ($bombaInundadaId) {
            DB::table('central_hidraulica_configuraciones')
                ->where('componente_id', $bombaInundadaId)
                ->whereIn('pasteurizador', self::PASTEURIZADORES_DOBLES)
                ->update([
                    'activo' => false,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (
            !Schema::hasTable('central_hidraulica_componentes')
            || !Schema::hasTable('central_hidraulica_configuraciones')
        ) {
            return;
        }

        $now = now();
        $bombaExternaId = DB::table('central_hidraulica_componentes')
            ->where('codigo', self::COMPONENTE_BOMBA_EXTERNA)
            ->value('id');

        if ($bombaExternaId) {
            DB::table('central_hidraulica_configuraciones')
                ->where('componente_id', $bombaExternaId)
                ->update([
                    'activo' => false,
                    'updated_at' => $now,
                ]);

            DB::table('central_hidraulica_componentes')
                ->where('id', $bombaExternaId)
                ->update([
                    'activo' => false,
                    'updated_at' => $now,
                ]);
        }

        $bombaInundadaId = DB::table('central_hidraulica_componentes')
            ->where('codigo', self::COMPONENTE_BOMBA_INUNDADA)
            ->value('id');

        if ($bombaInundadaId) {
            DB::table('central_hidraulica_configuraciones')
                ->where('componente_id', $bombaInundadaId)
                ->whereIn('pasteurizador', self::PASTEURIZADORES_DOBLES)
                ->update([
                    'activo' => true,
                    'updated_at' => $now,
                ]);
        }
    }
};
