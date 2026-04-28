<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PASOS_INICIALES = [
        'L-04' => 173,
        'L-05' => 140,
        'L-06' => 173,
        'L-07' => 173,
        'L-08' => 125,
        'L-09' => 140,
        'L-12' => 140,
        'L-13' => 140,
    ];

    public function up(): void
    {
        if (!Schema::hasTable('cadena_ciclos')) {
            Schema::create('cadena_ciclos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('linea_id')->nullable()->constrained('lineas')->nullOnDelete();
                $table->string('linea');
                $table->string('codigo')->unique();
                $table->unsignedInteger('numero_ciclo');
                $table->string('proveedor');
                $table->unsignedInteger('paso_inicial')->nullable();
                $table->bigInteger('hodometro_inicial')->nullable();
                $table->timestamp('instalada_en')->nullable();
                $table->timestamp('retirada_en')->nullable();
                $table->boolean('activa')->default(true);
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->unique(['linea', 'numero_ciclo']);
                $table->index(['linea', 'activa']);
            });
        }

        Schema::table('elongaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('elongaciones', 'cadena_ciclo_id')) {
                $table->foreignId('cadena_ciclo_id')
                    ->nullable()
                    ->after('linea')
                    ->constrained('cadena_ciclos')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('elongaciones', 'proveedor')) {
                $table->string('proveedor')->nullable()->after('cadena_ciclo_id');
            }

            if (!Schema::hasColumn('elongaciones', 'estado')) {
                $table->string('estado', 50)->default('normal')->after('requiere_cambio');
            }

            if (!Schema::hasColumn('elongaciones', 'estado_detallado')) {
                $table->string('estado_detallado', 50)->default('normal')->after('estado');
            }

            if (!Schema::hasColumn('elongaciones', 'paso_inicial')) {
                $table->unsignedInteger('paso_inicial')->nullable()->after('estado_detallado');
            }

            if (!Schema::hasColumn('elongaciones', 'hodometro_ciclo')) {
                $table->bigInteger('hodometro_ciclo')->nullable()->after('hodometro');
            }
        });

        DB::transaction(function () {
            $lineas = DB::table('elongaciones')
                ->select(
                    'linea',
                    DB::raw('MIN(created_at) as primera_fecha'),
                    DB::raw('MIN(hodometro) as hodometro_inicial')
                )
                ->groupBy('linea')
                ->get();

            foreach ($lineas as $linea) {
                $lineaId = DB::table('lineas')->where('nombre', $linea->linea)->value('id');
                $cicloId = DB::table('cadena_ciclos')
                    ->where('linea', $linea->linea)
                    ->where('numero_ciclo', 1)
                    ->value('id');

                if (!$cicloId) {
                    $cicloId = DB::table('cadena_ciclos')->insertGetId([
                        'linea_id' => $lineaId,
                        'linea' => $linea->linea,
                        'codigo' => $this->buildCodigoCiclo($linea->linea, 1),
                        'numero_ciclo' => 1,
                        'proveedor' => 'Historial sin proveedor',
                        'paso_inicial' => $this->getPasoInicial($linea->linea),
                        'hodometro_inicial' => $linea->hodometro_inicial,
                        'instalada_en' => $linea->primera_fecha,
                        'activa' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $registros = DB::table('elongaciones')
                    ->where('linea', $linea->linea)
                    ->orderBy('id')
                    ->get(['id', 'hodometro', 'bombas_porcentaje', 'vapor_porcentaje']);

                foreach ($registros as $registro) {
                    $estadoDetallado = $this->resolverEstadoDetallado(
                        (float) $registro->bombas_porcentaje,
                        (float) $registro->vapor_porcentaje
                    );

                    DB::table('elongaciones')
                        ->where('id', $registro->id)
                        ->update([
                            'cadena_ciclo_id' => $cicloId,
                            'proveedor' => 'Historial sin proveedor',
                            'estado' => $this->resolverEstadoGeneral($estadoDetallado),
                            'estado_detallado' => $estadoDetallado,
                            'paso_inicial' => $this->getPasoInicial($linea->linea),
                            'hodometro_ciclo' => $this->calcularHodometroCiclo(
                                $registro->hodometro,
                                $linea->hodometro_inicial
                            ),
                        ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('elongaciones', function (Blueprint $table) {
            if (Schema::hasColumn('elongaciones', 'cadena_ciclo_id')) {
                $table->dropConstrainedForeignId('cadena_ciclo_id');
            }

            foreach (['proveedor', 'estado', 'estado_detallado', 'paso_inicial', 'hodometro_ciclo'] as $column) {
                if (Schema::hasColumn('elongaciones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('cadena_ciclos');
    }

    private function buildCodigoCiclo(string $linea, int $numeroCiclo): string
    {
        return sprintf('%s-C%03d', $linea, $numeroCiclo);
    }

    private function getPasoInicial(string $linea): int
    {
        return self::PASOS_INICIALES[$linea] ?? 173;
    }

    private function resolverEstadoDetallado(float $bombas, float $vapor): string
    {
        $maximo = max($bombas, $vapor);

        if ($maximo >= 1.46) {
            return 'cambio';
        }

        if ($maximo >= 1.30) {
            return 'comprar';
        }

        return 'normal';
    }

    private function resolverEstadoGeneral(string $estadoDetallado): string
    {
        return match ($estadoDetallado) {
            'cambio' => 'critico',
            'comprar' => 'alerta',
            default => 'normal',
        };
    }

    private function calcularHodometroCiclo($hodometroActual, $hodometroInicial): ?int
    {
        if ($hodometroActual === null) {
            return null;
        }

        if ($hodometroInicial === null) {
            return (int) $hodometroActual;
        }

        return max((int) $hodometroActual - (int) $hodometroInicial, 0);
    }
};
