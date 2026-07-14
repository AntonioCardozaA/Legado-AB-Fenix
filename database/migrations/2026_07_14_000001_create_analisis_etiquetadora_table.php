<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('analisis_etiquetadora')) {
            Schema::create('analisis_etiquetadora', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('linea_id')->constrained('lineas')->cascadeOnDelete();
                $table->foreignId('componente_id')->constrained('componentes')->cascadeOnDelete();
                $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
                $table->foreignId('numero_r_id')->nullable()->constrained('numeros_r')->nullOnDelete();
                $table->string('reductor')->nullable();
                $table->string('maquina')->nullable();
                $table->string('lado')->nullable();
                $table->date('fecha_analisis')->nullable();
                $table->string('numero_orden')->nullable();
                $table->string('estado', 50)->default('Buen estado');
                $table->text('actividad')->nullable();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('evidencia_fotos')->nullable();
                $table->timestamps();

                $table->index(['linea_id', 'maquina']);
                $table->index(['linea_id', 'componente_id', 'maquina']);
                $table->index('fecha_analisis');
            });
        }

        $this->moverAnalisisEtiquetadoraDesdeComponentes();
    }

    public function down(): void
    {
        $this->restaurarAnalisisEtiquetadoraEnComponentes();

        Schema::dropIfExists('analisis_etiquetadora');
    }

    private function moverAnalisisEtiquetadoraDesdeComponentes(): void
    {
        if (
            !Schema::hasTable('analisis_componentes')
            || !Schema::hasColumn('analisis_componentes', 'tipo_equipo')
            || !Schema::hasTable('analisis_etiquetadora')
        ) {
            return;
        }

        $sourceColumns = $this->existingColumns('analisis_componentes', [
            'id',
            'linea_id',
            'componente_id',
            'categoria_id',
            'numero_r_id',
            'reductor',
            'maquina',
            'lado',
            'fecha_analisis',
            'numero_orden',
            'estado',
            'actividad',
            'usuario_id',
            'evidencia_fotos',
            'created_at',
            'updated_at',
        ]);

        DB::transaction(function () use ($sourceColumns): void {
            DB::table('analisis_componentes')
                ->select($sourceColumns)
                ->where('tipo_equipo', 'etiquetadora')
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    $records = $rows
                        ->map(fn (object $row): array => $this->toEtiquetadoraRow($row))
                        ->all();

                    if ($records !== []) {
                        DB::table('analisis_etiquetadora')->insert($records);
                    }
                });

            DB::table('analisis_componentes')
                ->where('tipo_equipo', 'etiquetadora')
                ->delete();
        });
    }

    private function restaurarAnalisisEtiquetadoraEnComponentes(): void
    {
        if (
            !Schema::hasTable('analisis_componentes')
            || !Schema::hasTable('analisis_etiquetadora')
        ) {
            return;
        }

        $targetColumns = $this->existingColumns('analisis_componentes', [
            'id',
            'linea_id',
            'componente_id',
            'categoria_id',
            'numero_r_id',
            'reductor',
            'maquina',
            'lado',
            'fecha_analisis',
            'numero_orden',
            'estado',
            'actividad',
            'usuario_id',
            'evidencia_fotos',
            'tipo_equipo',
            'created_at',
            'updated_at',
        ]);

        DB::transaction(function () use ($targetColumns): void {
            DB::table('analisis_etiquetadora')
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($targetColumns): void {
                    $records = $rows
                        ->map(fn (object $row): array => $this->toComponentesRow($row, $targetColumns))
                        ->all();

                    if ($records !== []) {
                        DB::table('analisis_componentes')->insert($records);
                    }
                });
        });
    }

    private function toEtiquetadoraRow(object $row): array
    {
        $maquina = $this->normalizarMaquina(
            $this->value($row, 'maquina'),
            $this->value($row, 'reductor')
        );

        return [
            'id' => $this->value($row, 'id'),
            'linea_id' => $this->value($row, 'linea_id'),
            'componente_id' => $this->value($row, 'componente_id'),
            'categoria_id' => $this->value($row, 'categoria_id'),
            'numero_r_id' => $this->value($row, 'numero_r_id'),
            'reductor' => $this->value($row, 'reductor') ?: $this->maquinaLabel($maquina),
            'maquina' => $maquina,
            'lado' => $this->value($row, 'lado'),
            'fecha_analisis' => $this->value($row, 'fecha_analisis'),
            'numero_orden' => $this->value($row, 'numero_orden'),
            'estado' => $this->value($row, 'estado') ?: 'Buen estado',
            'actividad' => $this->value($row, 'actividad'),
            'usuario_id' => $this->value($row, 'usuario_id'),
            'evidencia_fotos' => $this->value($row, 'evidencia_fotos'),
            'created_at' => $this->value($row, 'created_at') ?? now(),
            'updated_at' => $this->value($row, 'updated_at') ?? now(),
        ];
    }

    /**
     * @param  array<int, string>  $targetColumns
     */
    private function toComponentesRow(object $row, array $targetColumns): array
    {
        $record = [
            'id' => $this->value($row, 'id'),
            'linea_id' => $this->value($row, 'linea_id'),
            'componente_id' => $this->value($row, 'componente_id'),
            'categoria_id' => $this->value($row, 'categoria_id'),
            'numero_r_id' => $this->value($row, 'numero_r_id'),
            'reductor' => $this->value($row, 'reductor'),
            'maquina' => $this->value($row, 'maquina'),
            'lado' => $this->value($row, 'lado'),
            'fecha_analisis' => $this->value($row, 'fecha_analisis'),
            'numero_orden' => $this->value($row, 'numero_orden'),
            'estado' => $this->value($row, 'estado') ?: 'Buen estado',
            'actividad' => $this->value($row, 'actividad'),
            'usuario_id' => $this->value($row, 'usuario_id'),
            'evidencia_fotos' => $this->value($row, 'evidencia_fotos'),
            'tipo_equipo' => 'etiquetadora',
            'created_at' => $this->value($row, 'created_at') ?? now(),
            'updated_at' => $this->value($row, 'updated_at') ?? now(),
        ];

        return array_intersect_key($record, array_flip($targetColumns));
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<int, string>
     */
    private function existingColumns(string $table, array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn($table, $column)
        ));
    }

    private function value(object $row, string $column, mixed $default = null): mixed
    {
        return property_exists($row, $column) ? $row->{$column} : $default;
    }

    private function normalizarMaquina(mixed $maquina, mixed $reductor): ?string
    {
        $maquina = strtoupper(trim((string) $maquina));

        if (in_array($maquina, ['A', 'B', 'C'], true)) {
            return $maquina;
        }

        if (preg_match('/\b([ABC])\b/i', (string) $reductor, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    private function maquinaLabel(?string $maquina): ?string
    {
        return $maquina ? 'Maquina ' . $maquina : null;
    }
};
