<?php

use App\Models\AnalisisPasteurizadora;
use App\Support\PasteurizadoraNumeracion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('analisis_pasteurizadora', 'numeracion_version')) {
            Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
                $table->unsignedTinyInteger('numeracion_version')->default(1);
            });
        }

        // Query Builder incluye registros eliminados y evita eventos, notificaciones y cambios de fechas.
        DB::transaction(function () {
            $lineas = DB::table('lineas')->pluck('nombre', 'id');
            DB::table('analisis_pasteurizadora')->where('numeracion_version', 1)
                ->orderBy('id')->chunkById(250, function ($registros) use ($lineas) {
                    foreach ($registros as $registro) {
                        $linea = $lineas[$registro->linea_id] ?? null;
                        if (AnalisisPasteurizadora::normalizarArea($registro->area) !== AnalisisPasteurizadora::AREA_MECANICA
                            || !AnalisisPasteurizadora::resolveComponentePorLinea($linea, $registro->componente)) {
                            continue;
                        }

                        try {
                            $numeros = PasteurizadoraNumeracion::desdePosiciones(
                                $registro->componentes_revisados, $linea, $registro->componente, (int) $registro->modulo
                            );
                        } catch (\InvalidArgumentException|\JsonException $exception) {
                            throw new \RuntimeException("No se puede renumerar el análisis {$registro->id}: {$exception->getMessage()}", 0, $exception);
                        }

                        DB::table('analisis_pasteurizadora')->where('id', $registro->id)->update([
                            'componentes_revisados' => $registro->componentes_revisados === null ? null : json_encode($numeros),
                            'numeracion_version' => 2,
                        ]);
                    }
                });
            $this->actualizarTotalesHistoricos();
        });
        Schema::table('analisis_pasteurizadora', function (Blueprint $table) {
            $table->unsignedTinyInteger('numeracion_version')->default(2)->change();
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $lineas = DB::table('lineas')->pluck('nombre', 'id');
            DB::table('analisis_pasteurizadora')->where('numeracion_version', 2)
                ->orderBy('id')->chunkById(250, function ($registros) use ($lineas) {
                    foreach ($registros as $registro) {
                        $permitidos = PasteurizadoraNumeracion::numeros(
                            $lineas[$registro->linea_id] ?? null, $registro->componente, (int) $registro->modulo
                        );
                        if (!$permitidos || AnalisisPasteurizadora::normalizarArea($registro->area) !== AnalisisPasteurizadora::AREA_MECANICA) {
                            continue;
                        }
                        $numeros = AnalisisPasteurizadora::normalizarComponentesRevisados($registro->componentes_revisados);
                        if (array_diff($numeros, $permitidos)) {
                            throw new \RuntimeException("No se puede restaurar la numeración del análisis {$registro->id}.");
                        }
                        $posiciones = array_map(fn ($numero) => array_search($numero, $permitidos, true) + 1, $numeros);
                        DB::table('analisis_pasteurizadora')->where('id', $registro->id)->update([
                            'componentes_revisados' => $registro->componentes_revisados === null ? null : json_encode($posiciones),
                            'numeracion_version' => 1,
                        ]);
                    }
                });
            $this->actualizarTotalesHistoricos(false);
        });
        Schema::table('analisis_pasteurizadora', fn (Blueprint $table) => $table->dropColumn('numeracion_version'));
    }

    private function actualizarTotalesHistoricos(bool $consecutiva = true): void
    {
        $lineas = DB::table('lineas')->pluck('nombre', 'id');
        foreach (DB::table('historico_revisados')->get() as $historico) {
            $linea = $lineas[$historico->linea_id] ?? null;
            if (AnalisisPasteurizadora::normalizarArea($historico->area) !== AnalisisPasteurizadora::AREA_MECANICA
                || !AnalisisPasteurizadora::resolveComponentePorLinea($linea, $historico->componente)) {
                continue;
            }
            $esBrazo = AnalisisPasteurizadora::esBrazoTorsion($historico->componente);
            if ($esBrazo) {
                continue;
            }
            $total = $consecutiva ? PasteurizadoraNumeracion::total($linea, $historico->componente)
                : AnalisisPasteurizadora::getTotalComponentesPorLineaYComponente($linea, $historico->componente);
            $registros = AnalisisPasteurizadora::queryForArea()->with('linea')
                ->where('linea_id', $historico->linea_id)->where('componente', $historico->componente)->get();
            $revisados = $consecutiva ? $registros->flatMap(fn ($registro) => $registro->componentes_revisados_lista)->unique()->count()
                : $registros->sum('cantidad_componentes_revisados');
            // Algunos históricos fueron cargados sin conservar los análisis de origen.
            // Sus cantidades son información histórica y no deben ponerse a cero.
            $revisados = min(max($revisados, (int) $historico->cantidad_revisada), $total);
            DB::table('historico_revisados')->where('id', $historico->id)->update([
                'cantidad_total' => $total,
                'cantidad_revisada' => $revisados,
                'porcentaje' => $total > 0 ? round($revisados / $total * 100, 1) : 0,
                'estado' => $revisados >= $total ? 'completo' : 'en_progreso',
            ]);
        }
    }
};
