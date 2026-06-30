<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AnalisisPasteurizadora extends Model
{
    use HasFactory, SoftDeletes, UppercasesActividad;

    protected $table = 'analisis_pasteurizadora';

    protected $fillable = [
        'area',
        'tipo_registro',
        'linea_id',
        'modulo',
        'nivel',
        'componente',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'responsable',
        'usuario_id',
        'observaciones',
        'evidencia_fotos',
        'cantidad_componentes_revisados',
        'componentes_revisados',
        'total_componentes',
        'brazos_torsion',
        'total_brazos_torsion',
        'valor_anterior_52',
        'valor_actual_52',
        'valor_anterior_12',
        'valor_actual_12',
        'valor_anterior_4',
        'valor_actual_4',
        'plan_accion_pcm1',
        'plan_accion_pcm2',
        'plan_accion_pcm3',
        'plan_accion_pcm4',
        'resuelto_por_cambio',
        'fecha_resolucion',
        'nota_resolucion',
        'id_registro_que_resolvio',
    ];

    protected $casts = [
        'area' => 'string',
        'tipo_registro' => 'string',
        'fecha_analisis' => 'date',
        'evidencia_fotos' => 'array',
        'cantidad_componentes_revisados' => 'integer',
        'componentes_revisados' => 'array',
        'total_componentes' => 'integer',
        'brazos_torsion' => 'array',
        'total_brazos_torsion' => 'integer',
        'plan_accion_pcm1' => 'array',
        'plan_accion_pcm2' => 'array',
        'plan_accion_pcm3' => 'array',
        'plan_accion_pcm4' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'resuelto_por_cambio' => 'boolean',
        'fecha_resolucion' => 'datetime',
        'id_registro_que_resolvio' => 'integer',
    ];

    // ============================================================
    // CONFIGURACIÃ“N DE PASTEURIZADORES
    // ============================================================

    public const AREA_MECANICA = 'mecanica';
    public const AREA_CENTRAL_HIDRAULICA = 'central_hidraulica';
    public const DEFAULT_AREA_GLOBAL_SCOPE = 'analisis_pasteurizadora_area_mecanica_default';
    public const TIPO_REGISTRO_QUICK = 'quick';
    public const TIPO_REGISTRO_NORMAL = 'normal';
    public const TIPOS_REGISTRO = [
        self::TIPO_REGISTRO_QUICK,
        self::TIPO_REGISTRO_NORMAL,
    ];

    const PASTEURIZADORES = [
        'P-03' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-04' => ['tipo' => 'sencillo', 'modulos' => 12],
        'P-05' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-06' => ['tipo' => 'doble', 'modulos' => 16],
        'P-07' => ['tipo' => 'doble', 'modulos' => 16],
        'P-08' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-09' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-10' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-11' => ['tipo' => 'doble', 'modulos' => 16],
        'P-12' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-13' => ['tipo' => 'sencillo', 'modulos' => 9],
        'P-14' => ['tipo' => 'sencillo', 'modulos' => 9],
    ];

    const REGLILLAS_POR_LINEA = [
        'P-03' => 10,
        'P-04' => 12,
        'P-05' => 10,
        'P-06' => 10,
        'P-07' => 12,
        'P-08' => 10,
        'P-09' => 10,
        'P-10' => 10,
        'P-11' => 12,
        'P-12' => 10,
        'P-13' => 10,
        'P-14' => 10,
    ];

    const COMPONENTE_BRAZO_TORSION = 'BRAZO_TORSION';

    const COMPONENTES_SENCILLOS = [
        'ANILLAS' => ['nombre' => 'Anillas (Ventanas-Cortinas)', 'cantidad' => 3],
        'EXCENTRICOS' => ['nombre' => 'Excéntricos', 'cantidad' => 2],
        'PISTAS' => ['nombre' => 'Pistas', 'cantidad' => 2],
        'VIGAS_FIJAS' => ['nombre' => 'Vigas Fijas', 'cantidad' => 4],
        'VIGA_MOVIMIENTO' => ['nombre' => 'Viga de Movimiento', 'cantidad' => 1],
        'PLACAS_PERNO' => ['nombre' => 'Placas Perno', 'cantidad' => 3],
        'ESPARRAGOS' => ['nombre' => 'Esparragos', 'cantidad' => 2],

    ];

    const COMPONENTES_DOBLES = [
        'ANILLAS' => ['nombre' => 'Anillas (Ventanas-Cortinas)', 'cantidad' => 5],
        'EXCENTRICOS' => ['nombre' => 'Excéntricos', 'cantidad' => 2],
        'RODAJAS' => ['nombre' => 'Rodajas', 'cantidad' => 2],
        'PLACAS_PERNO' => ['nombre' => 'Placas Perno', 'cantidad' => 5],
        'VIGAS_MOVIMIENTO' => ['nombre' => 'Vigas de Movimiento', 'cantidad' => 2],
        'PISTAS' => ['nombre' => 'Pistas', 'cantidad' => 4],
        'ESPARRAGOS' => ['nombre' => 'Esparragos', 'cantidad' => 4],
    ];

    const LADOS = ['VAPOR', 'PASILLO'];
    const NIVELES = ['SUPERIOR', 'INFERIOR'];
    const ESTADO_BUENO = 'Buen estado';
    const ESTADO_REQUIERE_REVISION = 'Requiere revisión';
    const ESTADOS_DESGASTE = ['Desgaste moderado', 'Desgaste severo'];
    const ESTADO_DANADO = 'Dañado - Requiere cambio';
    const ESTADO_CAMBIADO = 'Cambiado';
    const ESTADOS_DANADO_COMPATIBLES = [
        'Dañado - Requiere cambio',
        'Danado - Requiere cambio',
        'DaÃ±ado - Requiere cambio',
        'DaÃƒÂ±ado - Requiere cambio',
    ];
    const ESTADOS = [
        self::ESTADO_BUENO,
        self::ESTADO_REQUIERE_REVISION,
        'Desgaste moderado',
        'Desgaste severo',
        self::ESTADO_DANADO,
        self::ESTADO_CAMBIADO,
    ];

    // ============================================================
    // MÃ‰TODOS DE CONFIGURACIÃ“N
    // ============================================================

    public static function getComponentesPorLinea($lineaNombre)
    {
        $tipo = self::PASTEURIZADORES[$lineaNombre]['tipo'] ?? null;
        $componentes = [];

        if ($tipo === 'sencillo') {
            $componentes = self::COMPONENTES_SENCILLOS;
        } elseif ($tipo === 'doble') {
            $componentes = self::COMPONENTES_DOBLES;
        }

        if (empty($componentes)) {
            return [];
        }

        $cantidadReglillas = self::getCantidadReglillasPorLinea($lineaNombre);
        if ($cantidadReglillas > 0) {
            $componentes['REGLILLAS'] = [
                'nombre' => 'Reglillas / Camillas',
                'cantidad' => $cantidadReglillas,
            ];
        }

        return self::withBrazoTorsion($componentes, $lineaNombre);
    }

    public static function getPasteurizadoresConfiguracion(): array
    {
        return self::PASTEURIZADORES;
    }

    public static function estadosDanado(): array
    {
        return self::ESTADOS_DANADO_COMPATIBLES;
    }

    public static function getEstadoOpciones(): array
    {
        return [
            self::ESTADO_BUENO => '✅ Buen estado',
            self::ESTADO_REQUIERE_REVISION => '🔧 Requiere revisión',
            'Desgaste moderado' => '⚠️ Desgaste moderado',
            'Desgaste severo' => '⚠️ Desgaste severo',
            self::ESTADO_DANADO => '❌ Dañado - Requiere cambio',
            self::ESTADO_CAMBIADO => '🔄 Cambiado',
        ];
    }

    public static function esEstadoBueno(?string $estado): bool
    {
        return $estado === self::ESTADO_BUENO;
    }

    public static function esEstadoRequiereRevision(?string $estado): bool
    {
        return $estado === self::ESTADO_REQUIERE_REVISION;
    }

    public static function esEstadoDesgaste(?string $estado): bool
    {
        return in_array($estado, self::ESTADOS_DESGASTE, true);
    }

    public static function esEstadoDanado(?string $estado): bool
    {
        return in_array($estado, self::ESTADOS_DANADO_COMPATIBLES, true);
    }

    public static function esEstadoCambiado(?string $estado): bool
    {
        return $estado === self::ESTADO_CAMBIADO;
    }

    public static function normalizarEstado($estado): string
    {
        $estado = trim((string) $estado);

        if (in_array($estado, self::ESTADOS_DANADO_COMPATIBLES, true)) {
            return self::ESTADO_DANADO;
        }

        return $estado;
    }

    public static function normalizarArea(?string $area): string
    {
        return $area === self::AREA_CENTRAL_HIDRAULICA
            ? self::AREA_CENTRAL_HIDRAULICA
            : self::AREA_MECANICA;
    }

    public static function normalizarTipoRegistro(?string $tipoRegistro): string
    {
        return in_array($tipoRegistro, self::TIPOS_REGISTRO, true)
            ? $tipoRegistro
            : self::TIPO_REGISTRO_QUICK;
    }

    public function scopeForArea($query, ?string $area = null)
    {
        $area = self::normalizarArea($area);
        $query->withoutGlobalScope(self::DEFAULT_AREA_GLOBAL_SCOPE);

        if ($area === self::AREA_MECANICA) {
            return $query->where(function ($subQuery) {
                $subQuery->where('area', self::AREA_MECANICA)
                    ->orWhereNull('area');
            });
        }

        return $query->where('area', $area);
    }

    public static function queryForArea(?string $area = null)
    {
        return self::query()->forArea($area ?? self::AREA_MECANICA);
    }

    public static function getCantidadReglillasPorLinea($lineaNombre): int
    {
        return self::REGLILLAS_POR_LINEA[$lineaNombre] ?? 0;
    }

    public static function getCantidadBrazosTorsionPorLinea($lineaNombre): int
    {
        return max(0, self::getModulosPorLinea($lineaNombre) - 1);
    }

    public static function esBrazoTorsion($componente): bool
    {
        return strtoupper((string) $componente) === self::COMPONENTE_BRAZO_TORSION;
    }

    public static function getModuloCorrespondienteBrazoTorsion(int $brazo): int
    {
        return $brazo;
    }

    private static function withBrazoTorsion(array $componentes, $lineaNombre): array
    {
        $componentes[self::COMPONENTE_BRAZO_TORSION] = [
            'nombre' => 'Brazo de Torsion',
            'cantidad' => 1,
            'dinamico' => true,
            'descripcion' => 'Un brazo por modulo, excepto el ultimo',
        ];

        return $componentes;
    }

    public static function getModulosPorLinea($lineaNombre)
    {
        return self::PASTEURIZADORES[$lineaNombre]['modulos'] ?? 0;
    }

    public static function resolveComponentePorLinea($lineaNombre, $componente)
    {
        if (!$lineaNombre || !$componente) {
            return null;
        }

        $componentes = self::getComponentesPorLinea($lineaNombre);
        $componenteKey = strtoupper($componente);

        if (isset($componentes[$componenteKey])) {
            return [
                'key' => $componenteKey,
                'config' => $componentes[$componenteKey],
            ];
        }

        foreach ($componentes as $key => $config) {
            if (strtoupper($key) === $componenteKey) {
                return [
                    'key' => $key,
                    'config' => $config,
                ];
            }
        }

        return null;
    }

    public static function getTotalComponentesPorLineaYComponente($lineaNombre, $componente): int
    {
        $resolved = self::resolveComponentePorLinea($lineaNombre, $componente);
        return (int) ($resolved['config']['cantidad'] ?? 0);
    }

    public function getTotalComponentesPorComponente(): int
    {
        return self::getTotalComponentesPorLineaYComponente($this->linea?->nombre, $this->componente);
    }

    public static function normalizarComponentesRevisados($value, ?int $totalComponentes = null): array
    {
        $componentes = $value;

        if (is_string($componentes) && trim($componentes) !== '') {
            $decoded = json_decode($componentes, true);
            $componentes = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($componentes)) {
            return [];
        }

        return collect($componentes)
            ->map(fn($item) => is_numeric($item) ? (int) $item : null)
            ->filter(fn($item) => $item !== null && $item > 0 && ($totalComponentes === null || $item <= $totalComponentes))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function buildResumenCicloComponenteFromCollection($registros, int $totalComponentes): array
    {
        $registros = self::ordenarRegistrosCronologicamente($registros);
        $ciclos = [];
        $registrosCiclo = collect();
        $estadoCiclo = self::inicializarEstadoCiclo();

        foreach ($registros as $registro) {
            $registrosCiclo->push($registro);
            $estadoCiclo = self::agregarRegistroAlEstadoCiclo($estadoCiclo, $registro, $totalComponentes);

            if (self::estadoCicloCompleto($estadoCiclo, $totalComponentes)) {
                $ciclos[] = [
                    'registros' => $registrosCiclo->values(),
                    'estado' => $estadoCiclo,
                    'completado' => true,
                ];

                $registrosCiclo = collect();
                $estadoCiclo = self::inicializarEstadoCiclo();
            }
        }

        if ($registrosCiclo->isNotEmpty()) {
            $ciclos[] = [
                'registros' => $registrosCiclo->values(),
                'estado' => $estadoCiclo,
                'completado' => self::estadoCicloCompleto($estadoCiclo, $totalComponentes),
            ];
        }

        $cicloActivo = null;
        $ultimoCicloCompletado = null;

        foreach ($ciclos as $ciclo) {
            if ($ciclo['completado']) {
                $ultimoCicloCompletado = $ciclo;
                continue;
            }

            $cicloActivo = $ciclo;
        }

        $cicloVisible = $cicloActivo ?: $ultimoCicloCompletado;
        $estadoVisible = $cicloVisible['estado'] ?? self::inicializarEstadoCiclo();
        $resumenVisible = self::construirResumenEstadoCiclo($estadoVisible, $totalComponentes);
        $estadoActual = $cicloActivo['estado'] ?? self::inicializarEstadoCiclo();
        $resumenActual = self::construirResumenEstadoCiclo($estadoActual, $totalComponentes);

        return [
            'ciclos' => $ciclos,
            'ciclo_actual' => $cicloActivo,
            'ultimo_ciclo_completado' => $ultimoCicloCompletado,
            'registros_actuales' => collect($cicloActivo['registros'] ?? []),
            'registros_visibles' => collect($cicloVisible['registros'] ?? []),
            'estado_actual' => $estadoActual,
            'estado_visible' => $estadoVisible,
            'resumen_actual' => $resumenActual,
            'resumen_visible' => $resumenVisible,
            'tiene_ciclo_activo' => $cicloActivo !== null,
            'tiene_ciclo_completado' => $ultimoCicloCompletado !== null,
        ];
    }

    public static function getResumenCicloComponente($lineaId, $modulo, $componente, ?int $excludeId = null, ?string $area = null): array
    {
        $linea = Linea::find($lineaId);
        $totalComponentes = self::getTotalComponentesPorLineaYComponente($linea?->nombre, $componente);

        $registros = self::queryForArea($area)
            ->quick()
            ->where('linea_id', $lineaId)
            ->where('modulo', $modulo)
            ->where('componente', $componente)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->orderBy('fecha_analisis')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return self::buildResumenCicloComponenteFromCollection($registros, $totalComponentes);
    }

    public static function getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null, ?string $area = null): array
    {
        $linea = Linea::find($lineaId);
        $totalComponentes = self::getTotalComponentesPorLineaYComponente($linea?->nombre, $componente);

        return self::getResumenCicloComponente($lineaId, $modulo, $componente, $excludeId, $area)['registros_actuales']
            ->when($lado, fn (Collection $registros) => $registros->where('lado', $lado))
            ->when($nivel, fn (Collection $registros) => $registros->where('nivel', $nivel))
            ->flatMap(function ($registro) use ($totalComponentes) {
                $componentes = self::normalizarComponentesRevisados($registro->componentes_revisados, $totalComponentes);

                if (!empty($componentes)) {
                    return $componentes;
                }

                $cantidad = min((int) ($registro->cantidad_componentes_revisados ?? 0), $totalComponentes);

                return $cantidad > 0 ? range(1, $cantidad) : [];
            })
            ->map(fn($item) => (int) $item)
            ->filter(fn($item) => $item > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function getComponentesPendientes($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null, ?string $area = null): array
    {
        $linea = Linea::find($lineaId);
        $totalComponentes = self::getTotalComponentesPorLineaYComponente($linea?->nombre, $componente);

        if ($totalComponentes <= 0) {
            return [];
        }

        return array_values(array_diff(
            range(1, $totalComponentes),
            self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId, $area)
        ));
    }

    // ============================================================
    // MÃ‰TODOS PARA CONTEO POR LADO Y NIVEL
    // ============================================================

    public static function getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null, ?string $area = null): int
    {
        return count(self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId, $area));
    }

    public static function getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null, ?string $area = null): int
    {
        $linea = Linea::find($lineaId);
        if (!$linea) {
            return 0;
        }

        $total = self::getTotalComponentesPorLineaYComponente($linea->nombre, $componente);
        $alreadyReviewed = self::getCantidadComponentesRevisados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId, $area);

        return max(0, $total - $alreadyReviewed);
    }

    public static function getComponentesYaRevisados($lineaId, $modulo, $componente, $lado = null, $nivel = null, ?int $excludeId = null, ?string $area = null): array
    {
        return self::getComponentesRevisadosRegistrados($lineaId, $modulo, $componente, $lado, $nivel, $excludeId, $area);
    }

    public static function getLadosPendientes($lineaId, $modulo, $componente, $nivel = null, ?string $area = null)
    {
        $ladosPendientes = [];

        foreach (self::LADOS as $lado) {
            $remaining = self::getCantidadComponentesPendientes($lineaId, $modulo, $componente, $lado, $nivel, null, $area);
            if ($remaining > 0) {
                $ladosPendientes[] = $lado;
            }
        }

        return $ladosPendientes;
    }

    // ============================================================
    // MÃ‰TODOS PARA GESTIÃ“N DE LADOS Y NIVELES
    // ============================================================

    /**
     * Obtiene el siguiente lado a revisar para un nivel específico
     * @return string|null El siguiente lado (VAPOR o PASILLO) o null si ambos están completos
     */
    public static function getSiguienteLado($lineaId, $modulo, $componente, $ladoActual = null, $nivel = null, ?string $area = null)
    {
        $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel, $area);

        if (empty($ladosPendientes)) {
            return null; // Ambos lados están completos para este nivel
        }

        if (!$ladoActual) {
            return reset($ladosPendientes); // Retorna el primer lado pendiente
        }

        // Si el lado actual es VAPOR, intenta PASILLO
        if ($ladoActual === 'VAPOR') {
            return in_array('PASILLO', $ladosPendientes) ? 'PASILLO' : null;
        }

        // Si el lado actual es PASILLO, intenta VAPOR
        if ($ladoActual === 'PASILLO') {
            return in_array('VAPOR', $ladosPendientes) ? 'VAPOR' : null;
        }

        return null;
    }

    public static function getSiguienteRevisionContexto($lineaId, $modulo, $componente, $nivelActual = null, $ladoActual = null, ?string $area = null)
    {
        $niveles = self::NIVELES;

        if ($nivelActual && in_array($nivelActual, $niveles, true)) {
            $siguienteLado = self::getSiguienteLado($lineaId, $modulo, $componente, $ladoActual, $nivelActual, $area);

            if ($siguienteLado) {
                return [
                    'nivel' => $nivelActual,
                    'lado' => $siguienteLado,
                ];
            }

            $indiceActual = array_search($nivelActual, $niveles, true);
            if ($indiceActual !== false) {
                $niveles = array_slice($niveles, $indiceActual + 1);
            }
        }

        foreach ($niveles as $nivel) {
            $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel, $area);

            if (!empty($ladosPendientes)) {
                return [
                    'nivel' => $nivel,
                    'lado' => reset($ladosPendientes),
                ];
            }
        }

        return null;
    }

    /**
     * Obtiene el siguiente nivel a revisar
     * @return string|null El siguiente nivel (SUPERIOR o INFERIOR) o null si ambos están completos
     */
    public static function getSiguienteNivel($lineaId, $modulo, $componente, $nivelActual = null, ?string $area = null)
    {
        $siguiente = self::getSiguienteRevisionContexto($lineaId, $modulo, $componente, $nivelActual, null, $area);

        if (!$siguiente) {
            return null;
        }

        return $siguiente['nivel'] !== $nivelActual ? $siguiente['nivel'] : null;
    }

    /**
     * Verifica si un nivel está completamente revisado
     */
    public static function nivelCompletado($lineaId, $modulo, $componente, $nivel, ?string $area = null)
    {
        $ladosPendientes = self::getLadosPendientes($lineaId, $modulo, $componente, $nivel, $area);
        return empty($ladosPendientes);
    }

    /**
     * Obtiene información del estado de revisión de lados y niveles
     */
    public static function getEstadoRevision($lineaId, $modulo, $componente, $nivel = null, ?string $area = null)
    {
        $niveles = self::NIVELES;
        $estado = [];

        foreach ($niveles as $niv) {
            $estado[$niv] = [
                'completado' => self::nivelCompletado($lineaId, $modulo, $componente, $niv, $area),
                'lados_pendientes' => self::getLadosPendientes($lineaId, $modulo, $componente, $niv, $area)
            ];
        }

        return $estado;
    }

    /**
     * Obtiene los componentes revisados únicos agrupados por clave (linea|componente|modulo|nivel|lado)
     * Optimizado para extracción de datos del histórico de revisados
     */
    public static function getComponentesRevisadosAgrupadosParaHistorico($lineaIds, ?string $area = null): array
    {
        if (empty($lineaIds)) {
            return [];
        }

        $registros = self::queryForArea($area)
            ->quick()
            ->select(['linea_id', 'componente', 'modulo', 'nivel', 'lado', 'componentes_revisados'])
            ->whereIn('linea_id', $lineaIds)
            ->orderBy('created_at', 'desc')
            ->get();

        $agrupado = $registros->groupBy(function ($registro) {
            return implode('|', [
                $registro->linea_id,
                strtoupper((string) $registro->componente),
                (int) $registro->modulo,
                strtoupper(trim((string) $registro->nivel)),
                strtoupper(trim((string) $registro->lado)),
            ]);
        });

        $resultado = [];
        foreach ($agrupado as $clave => $items) {
            $componentesUnicos = collect();
            foreach ($items as $item) {
                if (!empty($item->componentes_revisados)) {
                    if (is_array($item->componentes_revisados)) {
                        $componentesUnicos = $componentesUnicos->merge($item->componentes_revisados);
                    } elseif (is_string($item->componentes_revisados)) {
                        $decoded = json_decode($item->componentes_revisados, true);
                        if (is_array($decoded)) {
                            $componentesUnicos = $componentesUnicos->merge($decoded);
                        }
                    }
                }
            }
            $resultado[$clave] = $componentesUnicos->unique()->count();
        }

        return $resultado;
    }

    /**
     * Obtiene el último registro de análisis para una combinación específica
     * Útil para mostrar información actualizada en el histórico
     */
    public static function getUltimoRegistro($lineaId, $modulo, $componente, $nivel, $lado, ?string $area = null)
    {
        return self::queryForArea($area)
            ->quick()
            ->where('linea_id', $lineaId)
            ->where('modulo', $modulo)
            ->where('componente', $componente)
            ->where('nivel', $nivel)
            ->where('lado', $lado)
            ->where('resuelto_por_cambio', false)
            ->latest('fecha_analisis')
            ->latest('created_at')
            ->first();
    }

    // ============================================================
    // RELACIONES
    // ============================================================

    public function linea()
    {
        return $this->belongsTo(Linea::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function registroResolutor()
    {
        return $this->belongsTo(self::class, 'id_registro_que_resolvio');
    }

    public function registrosResueltos()
    {
        return $this->hasMany(self::class, 'id_registro_que_resolvio');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePorLinea($query, $lineaId)
    {
        return $query->where('linea_id', $lineaId);
    }

    public function scopePorModulo($query, $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorComponente($query, $componente)
    {
        return $query->where('componente', $componente);
    }

    public function scopePorLado($query, $lado)
    {
        return $query->where('lado', $lado);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel', $nivel);
    }

    public function scopeEntreFechas($query, $inicio, $fin)
    {
        return $query->whereBetween('fecha_analisis', [$inicio, $fin]);
    }

    public function scopeTipoRegistro($query, ?string $tipoRegistro)
    {
        return $query->where('tipo_registro', self::normalizarTipoRegistro($tipoRegistro));
    }

    public function scopeQuick($query)
    {
        return $query->where(function ($subQuery) {
            $subQuery->where('tipo_registro', self::TIPO_REGISTRO_QUICK)
                ->orWhereNull('tipo_registro');
        });
    }

    public function scopeNormal($query)
    {
        return $query->where('tipo_registro', self::TIPO_REGISTRO_NORMAL);
    }

    public function scopeActivos($query)
    {
        return $query->where('resuelto_por_cambio', false);
    }

    public function scopeResueltos($query)
    {
        return $query->where('resuelto_por_cambio', true);
    }

    public function scopeRequiereAtencion($query)
    {
        return $query->whereIn('estado', self::estadosDanado())->where('resuelto_por_cambio', false);
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getComponenteNombreAttribute()
    {
        $lineaNombre = $this->linea ? $this->linea->nombre : null;
        $componentes = self::getComponentesPorLinea($lineaNombre);
        return $componentes[$this->componente]['nombre'] ?? $this->componente;
    }

    public function getModuloNombreAttribute()
    {
        return "Módulo {$this->modulo}";
    }

    public function getFechaFormateadaAttribute()
    {
        return $this->fecha_analisis ? $this->fecha_analisis->format('d/m/Y') : 'Sin fecha';
    }

    public function getHoraFormateadaAttribute()
    {
        return $this->created_at ? $this->created_at->format('H:i') : '';
    }

    public function getTieneImagenesAttribute()
    {
        return !empty($this->evidencia_fotos);
    }

    public function getCantidadImagenesAttribute()
    {
        return $this->evidencia_fotos ? count($this->evidencia_fotos) : 0;
    }

    public function getFechaResolucionFormateadaAttribute()
    {
        return $this->fecha_resolucion ? $this->fecha_resolucion->format('d/m/Y H:i') : null;
    }

    public function getEsCambioAttribute()
    {
        return self::esEstadoCambiado($this->estado);
    }

    public function getEsDanioAttribute()
    {
        return self::esEstadoDanado($this->estado);
    }

    public function getLadoIconoAttribute()
    {
        return $this->lado === 'VAPOR' ? 'fa-wind' : 'fa-walking';
    }

    public function getLadoColorAttribute()
    {
        return $this->lado === 'VAPOR' ? 'red' : 'blue';
    }

    public function getLadoClaseAttribute()
    {
        return $this->lado === 'VAPOR' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
    }

    public function getEstadoBadgeAttribute()
    {
        if ($this->es_danio) {
            return ['class' => 'bg-red-100 text-red-800', 'icon' => 'fa-times-circle'];
        }

        return match ($this->estado) {
            self::ESTADO_BUENO => ['class' => 'bg-green-100 text-green-800', 'icon' => 'fa-check-circle'],
            self::ESTADO_REQUIERE_REVISION => ['class' => 'bg-yellow-100 text-yellow-800', 'icon' => 'fa-tools'],
            'Desgaste moderado', 'Desgaste severo' => ['class' => 'bg-orange-100 text-orange-800', 'icon' => 'fa-exclamation-triangle'],
            self::ESTADO_CAMBIADO => ['class' => 'bg-blue-100 text-blue-800', 'icon' => 'fa-exchange-alt'],
            default => ['class' => 'bg-gray-100 text-gray-800', 'icon' => 'fa-question-circle'],
        };
    }

    public function getEstadoAttribute($value)
    {
        return self::normalizarEstado($value);
    }

    public function getTipoRegistroAttribute($value): string
    {
        return self::normalizarTipoRegistro($value);
    }

    public function getEsRegistroQuickAttribute(): bool
    {
        return $this->tipo_registro === self::TIPO_REGISTRO_QUICK;
    }

    public function getEsRegistroNormalAttribute(): bool
    {
        return $this->tipo_registro === self::TIPO_REGISTRO_NORMAL;
    }

    public function getTipoRegistroLabelAttribute(): string
    {
        return $this->es_registro_normal
            ? 'Analisis normal'
            : 'Bitacora de revision';
    }

    public function getComponentesRevisadosListaAttribute(): array
    {
        $totalComponentes = $this->total_componentes ?: $this->getTotalComponentesPorComponente();

        return self::normalizarComponentesRevisados($this->componentes_revisados, $totalComponentes);
    }

    public function getNumeroComponentePrincipalAttribute(): ?int
    {
        return $this->componentes_revisados_lista[0] ?? null;
    }

    public function getPorcentajeAvanceAttribute()
    {
        $total = $this->total_componentes ?? 0;
        $revisadas = count(self::normalizarComponentesRevisados($this->componentes_revisados, $total));

        if ($revisadas === 0) {
            $revisadas = $this->cantidad_componentes_revisados ?? 0;
        }

        if ($total > 0) {
            return round(($revisadas / $total) * 100, 1);
        }
        return 0;
    }

    // ============================================================
    // MÃ‰TODOS DE UTILIDAD
    // ============================================================

    public function isAnalisisCompleto()
    {
        $total = $this->total_componentes ?? 0;
        $revisadas = $this->cantidad_componentes_revisados ?? 0;
        return $revisadas >= $total;
    }

    public function marcarComoResuelto($registroResolutor, $nota = null)
    {
        $numeroOrden = $registroResolutor->numero_orden ?: 'sin numero de orden';

        $this->update([
            'resuelto_por_cambio' => true,
            'fecha_resolucion' => now(),
            'id_registro_que_resolvio' => $registroResolutor->id,
            'nota_resolucion' => $nota ?: "Resuelto por orden #{$numeroOrden}"
        ]);
    }

    public function getDaniosPendientes()
    {
        return self::queryForArea($this->area)
            ->where('linea_id', $this->linea_id)
            ->where('modulo', $this->modulo)
            ->where('componente', $this->componente)
            ->whereIn('estado', self::estadosDanado())
            ->where('resuelto_por_cambio', false)
            ->where('id', '!=', $this->id)
            ->get();
    }

    public function getHistorialCompleto()
    {
        return self::queryForArea($this->area)
            ->where('linea_id', $this->linea_id)
            ->where('modulo', $this->modulo)
            ->where('componente', $this->componente)
            ->orderBy('fecha_analisis', 'desc')
            ->get();
    }

    // ============================================================
    // ANÃLISIS 52-12-4
    // ============================================================

    public function getAnalisis52124()
    {
        return [
            '52_semanas' => [
                'anterior' => $this->valor_anterior_52,
                'actual' => $this->valor_actual_52,
                'variacion' => $this->calcularVariacion($this->valor_anterior_52, $this->valor_actual_52)
            ],
            '12_semanas' => [
                'anterior' => $this->valor_anterior_12,
                'actual' => $this->valor_actual_12,
                'variacion' => $this->calcularVariacion($this->valor_anterior_12, $this->valor_actual_12)
            ],
            '4_semanas' => [
                'anterior' => $this->valor_anterior_4,
                'actual' => $this->valor_actual_4,
                'variacion' => $this->calcularVariacion($this->valor_anterior_4, $this->valor_actual_4)
            ],
        ];
    }

    private function calcularVariacion($anterior, $actual)
    {
        if ($anterior === null || $actual === null) return null;
        if ($anterior == 0) return 100;
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }

    // ============================================================
    // EVENTOS
    // ============================================================

    protected static function booted()
    {
        static::addGlobalScope(self::DEFAULT_AREA_GLOBAL_SCOPE, function ($builder) {
            $builder->where(function ($query) {
                $query->where('area', self::AREA_MECANICA)
                    ->orWhereNull('area');
            });
        });

        static::creating(function ($analisis) {
            $analisis->area = self::normalizarArea($analisis->area ?? self::AREA_MECANICA);
        });

        static::created(function ($analisis) {
            \Log::info("Nuevo análisis creado ID: {$analisis->id}");
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });

        static::updated(function ($analisis) {
            \Log::info("Análisis actualizado ID: {$analisis->id}");
            event(new \App\Events\AnalisisPasteurizadoraCreado($analisis));
        });
    }

    public function setComponentesRevisadosAttribute($value)
    {
        $componentes = self::normalizarComponentesRevisados($value, $this->attributes['total_componentes'] ?? null);

        $this->attributes['componentes_revisados'] = json_encode($componentes);
        $this->attributes['cantidad_componentes_revisados'] = count($componentes);
    }

    public function setEstadoAttribute($value)
    {
        $this->attributes['estado'] = self::normalizarEstado($value);
    }

    private static function ordenarRegistrosCronologicamente($registros): Collection
    {
        return collect($registros)
            ->sortBy(function ($registro) {
                $fechaAnalisis = $registro->fecha_analisis?->format('Ymd') ?? '00000000';
                $createdAt = str_pad((string) ($registro->created_at?->timestamp ?? 0), 12, '0', STR_PAD_LEFT);
                $id = str_pad((string) ($registro->id ?? 0), 10, '0', STR_PAD_LEFT);

                return $fechaAnalisis . '-' . $createdAt . '-' . $id;
            })
            ->values();
    }

    private static function inicializarEstadoCiclo(): array
    {
        $estado = [];

        foreach (self::NIVELES as $nivel) {
            foreach (self::LADOS as $lado) {
                $estado[$nivel][$lado] = [];
            }
        }

        return $estado;
    }

    private static function agregarRegistroAlEstadoCiclo(array $estado, $registro, int $totalComponentes): array
    {
        $nivel = $registro->nivel;
        $lado = $registro->lado;

        if (!isset($estado[$nivel][$lado])) {
            return $estado;
        }

        $componentes = self::normalizarComponentesRevisados($registro->componentes_revisados, $totalComponentes);

        if (empty($componentes)) {
            $cantidad = min((int) ($registro->cantidad_componentes_revisados ?? 0), $totalComponentes);
            $componentes = $cantidad > 0 ? range(1, $cantidad) : [];
        }

        $estado[$nivel][$lado] = collect(array_merge($estado[$nivel][$lado], $componentes))
            ->map(fn ($item) => (int) $item)
            ->filter(fn ($item) => $item > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return $estado;
    }

    private static function estadoCicloCompleto(array $estado, int $totalComponentes): bool
    {
        if ($totalComponentes <= 0) {
            return false;
        }

        foreach (self::NIVELES as $nivel) {
            foreach (self::LADOS as $lado) {
                if (count($estado[$nivel][$lado] ?? []) < $totalComponentes) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function construirResumenEstadoCiclo(array $estado, int $totalComponentes): array
    {
        $estadoPorNivel = [];
        $siguienteRevision = null;
        $completado = $totalComponentes > 0;

        foreach (self::NIVELES as $nivel) {
            $ladosPendientes = [];

            foreach (self::LADOS as $lado) {
                $revisados = count($estado[$nivel][$lado] ?? []);

                if ($revisados < $totalComponentes) {
                    $ladosPendientes[] = $lado;
                    $completado = false;
                }
            }

            $estadoPorNivel[$nivel] = [
                'completado' => empty($ladosPendientes),
                'lados_pendientes' => $ladosPendientes,
            ];

            if (!$siguienteRevision && !empty($ladosPendientes)) {
                $siguienteRevision = [
                    'nivel' => $nivel,
                    'lado' => $ladosPendientes[0],
                ];
            }
        }

        return [
            'completado' => $completado,
            'estado_por_nivel' => $estadoPorNivel,
            'siguiente_revision' => $siguienteRevision,
        ];
    }
}
