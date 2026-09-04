<?php

namespace App\Services;

use App\Models\AnalisisLavadora;
use App\Models\Componente;
use App\Models\Linea;
use App\Models\User;
use App\Support\LavadoraCatalog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LavadoraRevisionGeneralService
{
    public const COMPONENTES_REVISION_GENERAL = [
        'GUI_INF_TANQUE',
        'GUI_INT_TANQUE',
        'GUI_SUP_TANQUE',
        'CATARINAS',
    ];

    public const ACTIVIDAD_GUIA_BUEN_ESTADO = 'SE REVISA GUIA, ENCONTRANDOSE EN BUEN ESTADO';
    public const ACTIVIDAD_CATARINA_BUEN_ESTADO = 'REVISION DE CATARINA, ENCONTRANDOSE EN BUEN ESTADO';

    public function __construct(
        private readonly LavadoraRevisionPeriodicityService $periodicityService,
        private readonly LavadoraCostSyncService $costSyncService
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function componentesDisponibles(Linea $linea): array
    {
        $componentesLinea = LavadoraCatalog::componentesDeLinea($linea->nombre);

        return collect(self::COMPONENTES_REVISION_GENERAL)
            ->filter(fn (string $codigo): bool => array_key_exists($codigo, $componentesLinea))
            ->mapWithKeys(fn (string $codigo): array => [$codigo => $componentesLinea[$codigo]])
            ->all();
    }

    public function totalUbicaciones(Linea $linea): int
    {
        return collect(array_keys($this->componentesDisponibles($linea)))
            ->sum(fn (string $codigo): int => $this->periodicityService
                ->ubicacionesEsperadasConLado($linea, $codigo)
                ->count());
    }

    /**
     * @return array<string, int>
     */
    public function totalesPorComponente(Linea $linea): array
    {
        return collect(array_keys($this->componentesDisponibles($linea)))
            ->mapWithKeys(fn (string $codigo): array => [
                $codigo => $this->periodicityService
                    ->ubicacionesEsperadasConLado($linea, $codigo)
                    ->count(),
            ])
            ->all();
    }

    /**
     * @return array{
     *     creados: int,
     *     omitidos: int,
     *     duplicados: int,
     *     analisis: Collection<int, AnalisisLavadora>,
     *     omitidos_detalle: Collection<int, array<string, mixed>>,
     *     duplicados_detalle: Collection<int, array<string, mixed>>,
     *     codigo_base: string|null,
     *     componente: string|null
     * }
     */
    public function guardarBuenEstadoPorComponente(
        Linea $linea,
        string $codigoBase,
        string $fechaAnalisis,
        string $numeroOrden,
        ?User $user = null,
        ?CarbonInterface $fechaReferencia = null
    ): array {
        return $this->guardarCodigosBuenEstado(
            $linea,
            [$this->resolverCodigoDisponible($linea, $codigoBase)],
            $fechaAnalisis,
            $numeroOrden,
            $user,
            $fechaReferencia
        );
    }

    /**
     * @return array{
     *     creados: int,
     *     omitidos: int,
     *     duplicados: int,
     *     analisis: Collection<int, AnalisisLavadora>,
     *     omitidos_detalle: Collection<int, array<string, mixed>>,
     *     duplicados_detalle: Collection<int, array<string, mixed>>,
     *     codigo_base: string|null,
     *     componente: string|null
     * }
     */
    public function guardarBuenEstado(
        Linea $linea,
        string $fechaAnalisis,
        string $numeroOrden,
        ?User $user = null,
        ?CarbonInterface $fechaReferencia = null
    ): array {
        return $this->guardarCodigosBuenEstado(
            $linea,
            array_keys($this->componentesDisponibles($linea)),
            $fechaAnalisis,
            $numeroOrden,
            $user,
            $fechaReferencia
        );
    }

    /**
     * @param  array<int, string>  $codigos
     * @return array{
     *     creados: int,
     *     omitidos: int,
     *     duplicados: int,
     *     analisis: Collection<int, AnalisisLavadora>,
     *     omitidos_detalle: Collection<int, array<string, mixed>>,
     *     duplicados_detalle: Collection<int, array<string, mixed>>,
     *     codigo_base: string|null,
     *     componente: string|null
     * }
     */
    private function guardarCodigosBuenEstado(
        Linea $linea,
        array $codigos,
        string $fechaAnalisis,
        string $numeroOrden,
        ?User $user = null,
        ?CarbonInterface $fechaReferencia = null
    ): array {
        $fechaAnalisis = Carbon::parse($fechaAnalisis)->toDateString();
        $numeroOrden = trim($numeroOrden);
        $fechaReferencia ??= Carbon::now();
        $ultimosPorIdentidad = $this->ultimosPorIdentidad($linea, $codigos);
        $creados = collect();
        $omitidos = collect();
        $duplicados = collect();

        DB::transaction(function () use (
            $linea,
            $fechaAnalisis,
            $numeroOrden,
            $user,
            $fechaReferencia,
            $codigos,
            $ultimosPorIdentidad,
            $creados,
            $omitidos,
            $duplicados
        ): void {
            foreach ($codigos as $codigoBase) {
                foreach ($this->periodicityService->ubicacionesEsperadasConLado($linea, $codigoBase) as $ubicacion) {
                    $ultimoAnalisis = $ultimosPorIdentidad->get($ubicacion['key']);

                    if ($this->debeOmitirPorEstadoVigente($ultimoAnalisis, $fechaReferencia)) {
                        $omitidos->push($this->detalleUbicacion($linea, $codigoBase, $ubicacion, $ultimoAnalisis));
                        continue;
                    }

                    $componente = $this->resolverOcrearComponente(
                        $linea,
                        $codigoBase,
                        (string) $ubicacion['reductor']
                    );
                    $actividad = $this->actividadParaComponente($codigoBase);

                    if ($this->existeRevisionExacta(
                        $linea,
                        $componente,
                        (string) $ubicacion['reductor'],
                        (string) $ubicacion['lado'],
                        $fechaAnalisis,
                        $numeroOrden,
                        $actividad
                    )) {
                        $duplicados->push($this->detalleUbicacion($linea, $codigoBase, $ubicacion, null));
                        continue;
                    }

                    $analisis = AnalisisLavadora::create([
                        'linea_id' => $linea->id,
                        'componente_id' => $componente->id,
                        'reductor' => $ubicacion['reductor'],
                        'lado' => $ubicacion['lado'],
                        'fecha_analisis' => $fechaAnalisis,
                        'numero_orden' => $numeroOrden,
                        'estado' => AnalisisLavadora::ESTADO_BUENO,
                        'actividad' => $actividad,
                        'usuario_id' => $user?->id,
                    ]);

                    $this->costSyncService->syncForAnalysis($analisis->fresh(['linea', 'componente', 'costEntries']));
                    $creados->push($analisis);
                }
            }
        });

        return [
            'creados' => $creados->count(),
            'omitidos' => $omitidos->count(),
            'duplicados' => $duplicados->count(),
            'analisis' => $creados,
            'omitidos_detalle' => $omitidos,
            'duplicados_detalle' => $duplicados,
            'codigo_base' => count($codigos) === 1 ? $codigos[0] : null,
            'componente' => count($codigos) === 1
                ? ($this->componentesDisponibles($linea)[$codigos[0]] ?? LavadoraCatalog::nombreComponente($codigos[0]))
                : null,
        ];
    }

    private function resolverCodigoDisponible(Linea $linea, string $codigoBase): string
    {
        $codigoBase = strtoupper(trim($codigoBase));
        $componentesDisponibles = $this->componentesDisponibles($linea);

        if (!array_key_exists($codigoBase, $componentesDisponibles)) {
            throw new InvalidArgumentException('El componente seleccionado no esta disponible para esta lavadora.');
        }

        return $codigoBase;
    }

    public function actividadParaComponente(string $codigoBase): string
    {
        return $codigoBase === 'CATARINAS'
            ? self::ACTIVIDAD_CATARINA_BUEN_ESTADO
            : self::ACTIVIDAD_GUIA_BUEN_ESTADO;
    }

    /**
     * @param  array<int, string>  $codigosBase
     * @return Collection<string, AnalisisLavadora>
     */
    private function ultimosPorIdentidad(Linea $linea, array $codigosBase): Collection
    {
        $componenteIds = $this->resolverComponenteIds($linea, $codigosBase);

        if ($componenteIds->isEmpty()) {
            return collect();
        }

        return AnalisisLavadora::ultimosPorComponente()
            ->with(['componente', 'historialRestablecimientos'])
            ->where('linea_id', $linea->id)
            ->whereIn('componente_id', $componenteIds->all())
            ->get()
            ->mapWithKeys(function (AnalisisLavadora $analisis): array {
                $key = $this->periodicityService->identidadComponente($analisis);

                return $key ? [$key => $analisis] : [];
            });
    }

    /**
     * @param  array<int, string>  $codigosBase
     * @return Collection<int, int>
     */
    private function resolverComponenteIds(Linea $linea, array $codigosBase): Collection
    {
        return Componente::query()
            ->select('id', 'codigo', 'tipo_equipo')
            ->where(function ($query): void {
                $query->where('tipo_equipo', AnalisisLavadora::TIPO_EQUIPO)
                    ->orWhereNull('tipo_equipo');
            })
            ->get()
            ->filter(fn (Componente $componente): bool => in_array(
                AnalisisLavadora::codigoBaseComponente($componente->codigo),
                $codigosBase,
                true
            ))
            ->pluck('id')
            ->values();
    }

    private function debeOmitirPorEstadoVigente(?AnalisisLavadora $analisis, CarbonInterface $fechaReferencia): bool
    {
        if (!$analisis || AnalisisLavadora::esEstadoBueno($analisis->estado_operativo)) {
            return false;
        }

        return $this->periodicityService->analisisVigente($analisis, $fechaReferencia);
    }

    private function resolverOcrearComponente(Linea $linea, string $codigoBase, string $reductor): Componente
    {
        return Componente::firstOrCreate(
            ['codigo' => $this->codigoComponente($linea, $reductor, $codigoBase)],
            [
                'nombre' => LavadoraCatalog::nombreComponente($codigoBase),
                'reductor' => $reductor,
                'ubicacion' => $reductor,
                'linea' => $linea->nombre,
                'cantidad_total' => 1,
                'tipo_equipo' => AnalisisLavadora::TIPO_EQUIPO,
                'activo' => true,
            ]
        );
    }

    private function codigoComponente(Linea $linea, string $reductor, string $codigoBase): string
    {
        $lineaFormateada = str_replace('-', '', $linea->nombre);
        $reductorFormateado = strtolower(str_replace(' ', '_', $reductor));

        return $lineaFormateada . '_' . $reductorFormateado . '_' . $codigoBase;
    }

    private function existeRevisionExacta(
        Linea $linea,
        Componente $componente,
        string $reductor,
        string $lado,
        string $fechaAnalisis,
        string $numeroOrden,
        string $actividad
    ): bool {
        return AnalisisLavadora::query()
            ->where('linea_id', $linea->id)
            ->where('componente_id', $componente->id)
            ->where('reductor', $reductor)
            ->where('lado', $lado)
            ->whereDate('fecha_analisis', $fechaAnalisis)
            ->where('numero_orden', $numeroOrden)
            ->where('estado', AnalisisLavadora::ESTADO_BUENO)
            ->where('actividad', $actividad)
            ->exists();
    }

    /**
     * @param  array{key: string, reductor: string|null, lado: string|null}  $ubicacion
     * @return array<string, mixed>
     */
    private function detalleUbicacion(
        Linea $linea,
        string $codigoBase,
        array $ubicacion,
        ?AnalisisLavadora $analisis
    ): array {
        return [
            'linea_id' => $linea->id,
            'linea' => $linea->nombre,
            'codigo_base' => $codigoBase,
            'componente' => LavadoraCatalog::nombreComponente($codigoBase),
            'reductor' => $ubicacion['reductor'],
            'lado' => $ubicacion['lado'],
            'analisis_id' => $analisis?->id,
            'estado_vigente' => $analisis?->estado_operativo,
        ];
    }
}
