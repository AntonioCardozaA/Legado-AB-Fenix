<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('componentes')) {
            DB::table('componentes')
                ->whereIn('linea', ['L-05', 'L-13'])
                ->where('nombre', 'Servo-Reductor RV200')
                ->update(['nombre' => 'Reductor RV200']);

            DB::table('componentes')
                ->where('linea', 'L-12')
                ->where('nombre', 'Servo-Reductor Sin Fin-Corona RV200')
                ->update(['nombre' => 'Reductor Sin Fin-Corona RV200']);
        }

        if (Schema::hasTable('cost_catalog_items')) {
            DB::table('cost_catalog_items')
                ->where('categoria', 'Servo-Reductor')
                ->update(['categoria' => 'Reductor']);
        }

        if (Schema::hasTable('cost_catalog_item_histories')) {
            DB::table('cost_catalog_item_histories')
                ->whereNotNull('datos_nuevos')
                ->where('datos_nuevos', 'like', '%"categoria":"Servo-Reductor"%')
                ->update([
                    'datos_nuevos' => DB::raw("REPLACE(datos_nuevos, '\"categoria\":\"Servo-Reductor\"', '\"categoria\":\"Reductor\"')"),
                ]);
        }

        if (Schema::hasTable('cost_automation_rules')) {
            foreach ($this->ruleNoteReplacements() as $servo => $reductor) {
                DB::table('cost_automation_rules')
                    ->where('notas', $servo)
                    ->update(['notas' => $reductor]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('componentes')) {
            DB::table('componentes')
                ->whereIn('linea', ['L-05', 'L-13'])
                ->where('nombre', 'Reductor RV200')
                ->update(['nombre' => 'Servo-Reductor RV200']);

            DB::table('componentes')
                ->where('linea', 'L-12')
                ->where('nombre', 'Reductor Sin Fin-Corona RV200')
                ->update(['nombre' => 'Servo-Reductor Sin Fin-Corona RV200']);
        }

        if (Schema::hasTable('cost_catalog_items')) {
            DB::table('cost_catalog_items')
                ->where('categoria', 'Reductor')
                ->update(['categoria' => 'Servo-Reductor']);
        }

        if (Schema::hasTable('cost_catalog_item_histories')) {
            DB::table('cost_catalog_item_histories')
                ->whereNotNull('datos_nuevos')
                ->where('datos_nuevos', 'like', '%"categoria":"Reductor"%')
                ->update([
                    'datos_nuevos' => DB::raw("REPLACE(datos_nuevos, '\"categoria\":\"Reductor\"', '\"categoria\":\"Servo-Reductor\"')"),
                ]);
        }

        if (Schema::hasTable('cost_automation_rules')) {
            foreach ($this->ruleNoteReplacements() as $servo => $reductor) {
                DB::table('cost_automation_rules')
                    ->where('notas', $reductor)
                    ->update(['notas' => $servo]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function ruleNoteReplacements(): array
    {
        return [
            'Costo sugerido para servo-reductor RV200.' => 'Costo sugerido para reductor RV200.',
            'Costo sugerido para servo-reductor sinfin-corona.' => 'Costo sugerido para reductor sinfin-corona.',
            'Respirador detectado en actividad de servo-reductor.' => 'Respirador detectado en actividad de reductor.',
            'Respirador detectado en actividad de servo-reductor sin fin.' => 'Respirador detectado en actividad de reductor sin fin.',
            'Reten detectado en actividad de servo-reductor.' => 'Reten detectado en actividad de reductor.',
            'Reten detectado en actividad de servo-reductor sin fin.' => 'Reten detectado en actividad de reductor sin fin.',
        ];
    }
};
