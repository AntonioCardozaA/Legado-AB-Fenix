<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NEW_NAME = 'RV250 Sin Fin Corona';

    /**
     * @var array<int, string>
     */
    private const LEGACY_NAMES = [
        'Reductor RV200',
        'RV200 Sin Fin Corona',
        'RV200 Sin Fin-Corona',
        'Reductor Sin Fin-Corona RV200',
        'Reductor Sin Fin Corona RV200',
        'RV200 Sin Fin',
        'Servo-Reductor RV200',
        'Servo-Reductor Sin Fin-Corona RV200',
        'Servo-Reductor Sin Fin Corona RV200',
    ];

    /**
     * @var array<string, string>
     */
    private const NOTE_REPLACEMENTS = [
        'Costo sugerido para reductor RV200.' => 'Costo sugerido para RV250 Sin Fin Corona.',
        'Costo sugerido para reductor sinfin-corona.' => 'Costo sugerido para RV250 Sin Fin Corona.',
        'Costo sugerido para servo-reductor RV200.' => 'Costo sugerido para RV250 Sin Fin Corona.',
        'Costo sugerido para servo-reductor sinfin-corona.' => 'Costo sugerido para RV250 Sin Fin Corona.',
        'Consumible detectado por actividad de lubricacion en RV200.' => 'Consumible detectado por actividad de lubricacion en RV250 Sin Fin Corona.',
        'Consumible detectado por actividad de lubricacion en RV200 sin fin.' => 'Consumible detectado por actividad de lubricacion en RV250 Sin Fin Corona.',
        'Filtro detectado en mantenimiento de RV200.' => 'Filtro detectado en mantenimiento de RV250 Sin Fin Corona.',
        'Filtro detectado en mantenimiento de RV200 sin fin.' => 'Filtro detectado en mantenimiento de RV250 Sin Fin Corona.',
        'Respirador detectado en actividad de reductor.' => 'Respirador detectado en actividad de RV250 Sin Fin Corona.',
        'Respirador detectado en actividad de reductor sin fin.' => 'Respirador detectado en actividad de RV250 Sin Fin Corona.',
        'Respirador detectado en actividad de servo-reductor.' => 'Respirador detectado en actividad de RV250 Sin Fin Corona.',
        'Respirador detectado en actividad de servo-reductor sin fin.' => 'Respirador detectado en actividad de RV250 Sin Fin Corona.',
        'Reten detectado en actividad de reductor.' => 'Reten detectado en actividad de RV250 Sin Fin Corona.',
        'Reten detectado en actividad de reductor sin fin.' => 'Reten detectado en actividad de RV250 Sin Fin Corona.',
        'Reten detectado en actividad de servo-reductor.' => 'Reten detectado en actividad de RV250 Sin Fin Corona.',
        'Reten detectado en actividad de servo-reductor sin fin.' => 'Reten detectado en actividad de RV250 Sin Fin Corona.',
    ];

    public function up(): void
    {
        $this->renameExact('componentes', 'nombre');
        $this->renameExact('historico_revisados', 'componente_nombre');
        $this->renameExact('lavadora_cost_entries', 'component_snapshot');

        $this->replaceAutomationNotes();
        $this->replaceJsonText('cost_catalog_item_histories', 'datos_anteriores');
        $this->replaceJsonText('cost_catalog_item_histories', 'datos_nuevos');
    }

    public function down(): void
    {
        if (Schema::hasTable('componentes') && Schema::hasColumn('componentes', 'nombre')) {
            DB::table('componentes')
                ->where('nombre', self::NEW_NAME)
                ->where('codigo', 'like', '%RV200_SIN_FIN%')
                ->update(['nombre' => 'Reductor Sin Fin-Corona RV200']);

            DB::table('componentes')
                ->where('nombre', self::NEW_NAME)
                ->where('codigo', 'like', '%RV200%')
                ->update(['nombre' => 'Reductor RV200']);
        }

        if (Schema::hasTable('historico_revisados') && Schema::hasColumn('historico_revisados', 'componente_nombre')) {
            DB::table('historico_revisados')
                ->where('componente_nombre', self::NEW_NAME)
                ->where('componente', 'RV200_SIN_FIN')
                ->update(['componente_nombre' => 'Reductor Sin Fin-Corona RV200']);

            DB::table('historico_revisados')
                ->where('componente_nombre', self::NEW_NAME)
                ->where('componente', 'RV200')
                ->update(['componente_nombre' => 'Reductor RV200']);
        }

        if (Schema::hasTable('lavadora_cost_entries') && Schema::hasColumn('lavadora_cost_entries', 'component_snapshot')) {
            DB::table('lavadora_cost_entries')
                ->where('component_snapshot', self::NEW_NAME)
                ->update(['component_snapshot' => 'Reductor RV200']);
        }

        if (Schema::hasTable('cost_automation_rules') && Schema::hasColumn('cost_automation_rules', 'notas')) {
            foreach (self::NOTE_REPLACEMENTS as $legacy => $updated) {
                DB::table('cost_automation_rules')
                    ->where('notas', $updated)
                    ->update(['notas' => $legacy]);
            }
        }
    }

    private function renameExact(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->whereIn($column, self::LEGACY_NAMES)
            ->update([$column => self::NEW_NAME]);
    }

    private function replaceAutomationNotes(): void
    {
        if (!Schema::hasTable('cost_automation_rules') || !Schema::hasColumn('cost_automation_rules', 'notas')) {
            return;
        }

        foreach (self::NOTE_REPLACEMENTS as $legacy => $updated) {
            DB::table('cost_automation_rules')
                ->where('notas', $legacy)
                ->update(['notas' => $updated]);
        }
    }

    private function replaceJsonText(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        foreach ([...self::LEGACY_NAMES, ...array_keys(self::NOTE_REPLACEMENTS)] as $legacy) {
            $replacement = self::NOTE_REPLACEMENTS[$legacy] ?? self::NEW_NAME;

            DB::table($table)
                ->whereNotNull($column)
                ->where($column, 'like', '%' . $legacy . '%')
                ->update([
                    $column => DB::raw("REPLACE({$column}, " . DB::getPdo()->quote($legacy) . ', ' . DB::getPdo()->quote($replacement) . ')'),
                ]);
        }
    }
};
