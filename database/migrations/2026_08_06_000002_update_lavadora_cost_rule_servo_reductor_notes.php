<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cost_automation_rules')) {
            return;
        }

        foreach ($this->replacements() as $legacy => $updated) {
            DB::table('cost_automation_rules')
                ->where('notas', $legacy)
                ->update(['notas' => $updated]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cost_automation_rules')) {
            return;
        }

        foreach ($this->replacements() as $legacy => $updated) {
            DB::table('cost_automation_rules')
                ->where('notas', $updated)
                ->update(['notas' => $legacy]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function replacements(): array
    {
        return [
            'Costo sugerido para reductor RV200.' => 'Costo sugerido para servo-reductor RV200.',
            'Costo sugerido para reductor sinfin-corona.' => 'Costo sugerido para servo-reductor sinfin-corona.',
            'Respirador detectado en actividad de reductor.' => 'Respirador detectado en actividad de servo-reductor.',
            'Respirador detectado en actividad de reductor sin fin.' => 'Respirador detectado en actividad de servo-reductor sin fin.',
            'Reten detectado en actividad de reductor.' => 'Reten detectado en actividad de servo-reductor.',
            'Reten detectado en actividad de reductor sin fin.' => 'Reten detectado en actividad de servo-reductor sin fin.',
        ];
    }
};
