<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LINEAS = ['L-05', 'L-12', 'L-13'];

    public function up(): void
    {
        if (!Schema::hasTable('componentes')) {
            return;
        }

        $this->reductoresSinFin()->each(function (object $componente): void {
            DB::table('componentes')
                ->where('id', $componente->id)
                ->update([
                    'nombre' => $this->nombreModelo((string) $componente->reductor),
                ]);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('componentes')) {
            return;
        }

        // La migración anterior había dejado todos estos registros como RV250.
        $this->reductoresSinFin()->each(function (object $componente): void {
            DB::table('componentes')
                ->where('id', $componente->id)
                ->update(['nombre' => 'RV250 Sin Fin Corona']);
        });
    }

    private function reductoresSinFin(): \Illuminate\Support\Collection
    {
        return DB::table('componentes')
            ->select(['id', 'reductor'])
            ->whereIn('linea', self::LINEAS)
            ->where(function (Builder $query): void {
                $query
                    ->where('codigo', 'like', '%RV200%')
                    ->orWhere('codigo', 'like', '%RV250%')
                    ->orWhere('nombre', 'like', '%Sin Fin%')
                    ->orWhere('nombre', 'like', '%RV200%')
                    ->orWhere('nombre', 'like', '%RV250%');
            })
            ->whereNotNull('reductor')
            ->get();
    }

    private function nombreModelo(string $reductor): string
    {
        return preg_match('/^Reductor\s+2$/i', trim($reductor)) === 1
            ? 'RV250 Sin Fin Corona'
            : 'RV200 Sin Fin Corona';
    }
};
