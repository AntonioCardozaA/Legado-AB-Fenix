<?php

namespace App\Models;

use App\Models\Concerns\UppercasesActividad;
use App\Support\EtiquetadoraCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class AnalisisEtiquetadora extends Model
{
    use HasFactory, UppercasesActividad;

    public const TIPO_EQUIPO = EtiquetadoraCatalog::TIPO_EQUIPO;
    public const MAQUINAS = ['A', 'B', 'C'];
    public const ESTADO_BUENO = AnalisisLavadora::ESTADO_BUENO;
    public const ESTADO_REQUIERE_REVISION = AnalisisLavadora::ESTADO_REQUIERE_REVISION;
    public const ESTADOS_DESGASTE = AnalisisLavadora::ESTADOS_DESGASTE;
    public const ESTADO_DANADO = AnalisisLavadora::ESTADO_DANADO;
    public const ESTADO_CAMBIADO = AnalisisLavadora::ESTADO_CAMBIADO;
    public const ESTADOS = AnalisisLavadora::ESTADOS;

    protected $table = 'analisis_etiquetadora';

    protected $fillable = [
        'linea_id',
        'componente_id',
        'reductor',
        'maquina',
        'lado',
        'fecha_analisis',
        'numero_orden',
        'estado',
        'actividad',
        'usuario_id',
        'evidencia_fotos',
        'total_componentes',
        'cantidad_componentes_revisados',
        'componentes_revisados',
        'categoria_id',
        'numero_r_id',
    ];

    protected $casts = [
        'evidencia_fotos' => 'array',
        'componentes_revisados' => 'array',
        'total_componentes' => 'integer',
        'cantidad_componentes_revisados' => 'integer',
        'fecha_analisis' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $analisis): void {
            if ($analisis->maquina) {
                $analisis->maquina = strtoupper(trim((string) $analisis->maquina));
            }

            if ($analisis->maquina && blank($analisis->reductor)) {
                $analisis->reductor = EtiquetadoraCatalog::maquinaLabel($analisis->maquina);
            }
        });
    }

    public static function getEstadoOpciones(): array
    {
        return AnalisisLavadora::getEstadoOpciones();
    }

    public static function estados(): array
    {
        return AnalisisLavadora::ESTADOS;
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
            ->map(fn ($item) => is_numeric($item) ? (int) $item : null)
            ->filter(fn ($item) => $item !== null && $item > 0 && ($totalComponentes === null || $item <= $totalComponentes))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function esEstadoBueno(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoBueno($estado);
    }

    public static function esEstadoRequiereRevision(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoRequiereRevision($estado);
    }

    public static function esEstadoDesgaste(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoDesgaste($estado);
    }

    public static function esEstadoDanado(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoDanado($estado);
    }

    public static function esEstadoCambiado(?string $estado): bool
    {
        return AnalisisLavadora::esEstadoCambiado($estado);
    }

    public function linea(): BelongsTo
    {
        return $this->belongsTo(Linea::class);
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Componente::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getComponentesRevisadosListaAttribute(): array
    {
        $totalComponentes = $this->total_componentes ?: (int) ($this->componente?->cantidad_total ?? 0);

        return $this->piezasRevisadasParaTotal($totalComponentes ?: null);
    }

    public function piezasRevisadasParaTotal(?int $totalComponentes = null): array
    {
        $totalComponentes = (int) ($totalComponentes ?: $this->total_componentes ?: (int) ($this->componente?->cantidad_total ?? 0));
        $totalComponentes = max(0, $totalComponentes);

        $componentes = self::normalizarComponentesRevisados(
            $this->componentes_revisados,
            $totalComponentes > 0 ? $totalComponentes : null
        );

        if (!empty($componentes)) {
            return $componentes;
        }

        $cantidadRevisada = (int) ($this->cantidad_componentes_revisados ?? 0);

        if ($cantidadRevisada > 0 && $totalComponentes > 0) {
            return range(1, min($cantidadRevisada, $totalComponentes));
        }

        if ($totalComponentes === 1 && $this->exists) {
            return [1];
        }

        return [];
    }

    public function scopeUltimosPorComponente(Builder $query): Builder
    {
        $table = $this->getTable();

        $latestIds = DB::table($table . ' as actual')
            ->leftJoin($table . ' as mas_reciente', function ($join): void {
                $join->on('actual.linea_id', '=', 'mas_reciente.linea_id')
                    ->on('actual.componente_id', '=', 'mas_reciente.componente_id')
                    ->whereRaw("COALESCE(actual.maquina, '') = COALESCE(mas_reciente.maquina, '')")
                    ->where(function ($subQuery): void {
                        $subQuery->whereColumn('mas_reciente.fecha_analisis', '>', 'actual.fecha_analisis')
                            ->orWhere(function ($tieBreaker): void {
                                $tieBreaker->whereColumn('mas_reciente.fecha_analisis', '=', 'actual.fecha_analisis')
                                    ->whereColumn('mas_reciente.id', '>', 'actual.id');
                            });
                    });
            })
            ->whereNull('mas_reciente.id')
            ->select('actual.id');

        return $query->whereIn($this->qualifyColumn('id'), $latestIds);
    }
}
