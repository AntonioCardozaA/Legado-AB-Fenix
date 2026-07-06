<?php

namespace App\Models;

use App\Support\HodometroHoras;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elongacion extends Model
{
    use HasFactory;

    protected $table = 'elongaciones';

    protected $appends = [
        'bombas_incremento_base_mm',
        'vapor_incremento_base_mm',
        'bombas_variacion_revision_mm',
        'vapor_variacion_revision_mm',
    ];

    protected $fillable = [
        'linea_id',
        'linea',
        'cadena_ciclo_id',
        'proveedor',
        'seccion',
        'bombas_1',
        'bombas_2',
        'bombas_3',
        'bombas_4',
        'bombas_5',
        'bombas_6',
        'bombas_7',
        'bombas_8',
        'bombas_9',
        'bombas_10',
        'bombas_promedio',
        'bombas_porcentaje',
        'vapor_1',
        'vapor_2',
        'vapor_3',
        'vapor_4',
        'vapor_5',
        'vapor_6',
        'vapor_7',
        'vapor_8',
        'vapor_9',
        'vapor_10',
        'vapor_promedio',
        'vapor_porcentaje',
        'requiere_cambio',
        'estado',
        'estado_detallado',
        'paso_inicial',
        'hodometro',
        'hodometro_ciclo',
        'juego_rodaja_bombas',
        'juego_rodaja_vapor',
    ];

    protected $casts = [
        'linea_id' => 'integer',
        'cadena_ciclo_id' => 'integer',
        'bombas_1' => 'decimal:1',
        'bombas_2' => 'decimal:1',
        'bombas_3' => 'decimal:1',
        'bombas_4' => 'decimal:1',
        'bombas_5' => 'decimal:1',
        'bombas_6' => 'decimal:1',
        'bombas_7' => 'decimal:1',
        'bombas_8' => 'decimal:1',
        'bombas_9' => 'decimal:1',
        'bombas_10' => 'decimal:1',
        'bombas_promedio' => 'decimal:2',
        'bombas_porcentaje' => 'decimal:2',
        'vapor_1' => 'decimal:1',
        'vapor_2' => 'decimal:1',
        'vapor_3' => 'decimal:1',
        'vapor_4' => 'decimal:1',
        'vapor_5' => 'decimal:1',
        'vapor_6' => 'decimal:1',
        'vapor_7' => 'decimal:1',
        'vapor_8' => 'decimal:1',
        'vapor_9' => 'decimal:1',
        'vapor_10' => 'decimal:1',
        'vapor_promedio' => 'decimal:2',
        'vapor_porcentaje' => 'decimal:2',
        'requiere_cambio' => 'boolean',
        'paso_inicial' => 'integer',
        'hodometro' => 'integer',
        'hodometro_ciclo' => 'integer',
        'juego_rodaja_bombas' => 'decimal:2',
        'juego_rodaja_vapor' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const LIMITE_COMPRAR = 1.3;
    const LIMITE_CAMBIO = 1.46;

    const PASOS_INICIALES = [
        'L-04' => 173,
        'L-05' => 140,
        'L-06' => 173,
        'L-07' => 173,
        'L-08' => 125,
        'L-09' => 140,
        'L-12' => 140,
        'L-13' => 140,
    ];

    protected ?self $revisionAnteriorCache = null;

    protected bool $revisionAnteriorResuelta = false;

    public function cadenaCiclo()
    {
        return $this->belongsTo(CadenaCiclo::class, 'cadena_ciclo_id');
    }

    public function lineaModel()
    {
        return $this->belongsTo(Linea::class, 'linea_id');
    }

    public static function getPasoInicial($linea)
    {
        return self::PASOS_INICIALES[$linea] ?? 173;
    }

    public function scopePorLinea($query, $linea)
    {
        if ($linea) {
            return $query->where('linea', $linea);
        }

        return $query;
    }

    public function scopePorEstado($query, $estado)
    {
        if (!$estado) {
            return $query;
        }

        switch ($estado) {
            case 'normal':
                return $query->where('bombas_porcentaje', '<', self::LIMITE_COMPRAR)
                    ->where('vapor_porcentaje', '<', self::LIMITE_COMPRAR);

            case 'alerta':
            case 'comprar':
                return $query->where(function ($subQuery) {
                    $subQuery->whereBetween('bombas_porcentaje', [self::LIMITE_COMPRAR, self::LIMITE_CAMBIO - 0.01])
                        ->orWhereBetween('vapor_porcentaje', [self::LIMITE_COMPRAR, self::LIMITE_CAMBIO - 0.01]);
                });

            case 'critico':
            case 'cambio':
                return $query->where(function ($subQuery) {
                    $subQuery->where('bombas_porcentaje', '>=', self::LIMITE_CAMBIO)
                        ->orWhere('vapor_porcentaje', '>=', self::LIMITE_CAMBIO);
                });

            default:
                return $query;
        }
    }

    public function scopeDelCiclo($query, int $cicloId)
    {
        return $query->where('cadena_ciclo_id', $cicloId);
    }

    public static function calcularPromedio($mediciones)
    {
        $suma = 0;
        $contador = 0;

        foreach ($mediciones as $medicion) {
            if (!is_null($medicion) && is_numeric($medicion) && $medicion > 0) {
                $suma += (float) $medicion;
                $contador++;
            }
        }

        return $contador > 0 ? round($suma / $contador, 2) : 0;
    }

    public static function calcularPorcentaje($promedio, $pasoInicial = null)
    {
        if ($promedio <= 0 || $pasoInicial === null || $pasoInicial <= 0) {
            return 0;
        }

        $porcentaje = (($promedio - $pasoInicial) / $pasoInicial) * 100;

        return round(max($porcentaje, 0), 2);
    }

    public static function calcularIncrementoMm($promedio, $pasoInicial = null)
    {
        if ($promedio <= 0 || $pasoInicial === null || $pasoInicial <= 0) {
            return 0;
        }

        return round(max(((float) $promedio - (float) $pasoInicial), 0), 2);
    }

    public static function calcularVariacionMm($valorActual, $valorAnterior)
    {
        if ($valorActual === null || $valorAnterior === null) {
            return null;
        }

        if (!is_numeric($valorActual) || !is_numeric($valorAnterior)) {
            return null;
        }

        return round((float) $valorActual - (float) $valorAnterior, 2);
    }

    public function getEstadoBombasAttribute()
    {
        return $this->getEstadoDetallado($this->bombas_porcentaje);
    }

    public function getEstadoVaporAttribute()
    {
        return $this->getEstadoDetallado($this->vapor_porcentaje);
    }

    public function getEstadoGeneralAttribute()
    {
        $maxPorcentaje = max($this->bombas_porcentaje, $this->vapor_porcentaje);

        if ($maxPorcentaje >= self::LIMITE_CAMBIO) {
            return 'critico';
        }

        if ($maxPorcentaje >= self::LIMITE_COMPRAR) {
            return 'alerta';
        }

        return 'normal';
    }

    public function getRequiereCambioAttribute()
    {
        return $this->bombas_porcentaje >= self::LIMITE_CAMBIO
            || $this->vapor_porcentaje >= self::LIMITE_CAMBIO;
    }

    public function getRequiereCompraAttribute()
    {
        $maxPorcentaje = max($this->bombas_porcentaje, $this->vapor_porcentaje);

        return $maxPorcentaje >= self::LIMITE_COMPRAR
            && $maxPorcentaje < self::LIMITE_CAMBIO;
    }

    public function getColorEstadoAttribute()
    {
        return match ($this->estado_detallado ?? $this->estado_general) {
            'cambio', 'critico' => 'red',
            'comprar', 'alerta' => 'yellow',
            default => 'green',
        };
    }

    public function getIconoEstadoAttribute()
    {
        return match ($this->estado_detallado ?? $this->estado_general) {
            'cambio', 'critico' => 'fa-exclamation-circle',
            'comprar', 'alerta' => 'fa-shopping-cart',
            default => 'fa-check-circle',
        };
    }

    public function getProveedorActualAttribute(): ?string
    {
        return $this->proveedor ?: $this->cadenaCiclo?->proveedor;
    }

    public function getHodometroFormateadoAttribute(): ?string
    {
        return HodometroHoras::formatear($this->hodometro);
    }

    public function getHodometroCicloFormateadoAttribute(): ?string
    {
        return HodometroHoras::formatear($this->hodometro_ciclo);
    }

    public function getBombasIncrementoBaseMmAttribute(): float
    {
        return self::calcularIncrementoMm($this->bombas_promedio, $this->paso_inicial);
    }

    public function getVaporIncrementoBaseMmAttribute(): float
    {
        return self::calcularIncrementoMm($this->vapor_promedio, $this->paso_inicial);
    }

    public function getBombasVariacionRevisionMmAttribute(): ?float
    {
        $revisionAnterior = $this->resolverRevisionAnterior();

        return $revisionAnterior
            ? self::calcularVariacionMm($this->bombas_promedio, $revisionAnterior->bombas_promedio)
            : null;
    }

    public function getVaporVariacionRevisionMmAttribute(): ?float
    {
        $revisionAnterior = $this->resolverRevisionAnterior();

        return $revisionAnterior
            ? self::calcularVariacionMm($this->vapor_promedio, $revisionAnterior->vapor_promedio)
            : null;
    }

    public function getRevisionDueAtAttribute(): ?CarbonImmutable
    {
        if (!$this->created_at) {
            return null;
        }

        return CarbonImmutable::instance($this->created_at)
            ->setTimezone($this->revisionTimezone())
            ->addMonthsNoOverflow($this->revisionIntervalMonths())
            ->startOfDay();
    }

    public function getRevisionDaysRemainingAttribute(): ?int
    {
        if (!$this->revision_due_at) {
            return null;
        }

        $today = CarbonImmutable::now($this->revisionTimezone())->startOfDay();

        return $today->diffInDays($this->revision_due_at, false);
    }

    public function getRevisionNeedsAlertAttribute(): bool
    {
        $daysRemaining = $this->revision_days_remaining;

        return $daysRemaining !== null && $daysRemaining <= $this->revisionLeadDays();
    }

    public function getRevisionStatusAttribute(): string
    {
        $daysRemaining = $this->revision_days_remaining;

        if ($daysRemaining === null || $daysRemaining > $this->revisionLeadDays()) {
            return 'normal';
        }

        return match (true) {
            $daysRemaining > 0 => 'upcoming',
            $daysRemaining === 0 => 'due_today',
            default => 'overdue',
        };
    }

    public function getRevisionStatusLabelAttribute(): ?string
    {
        if (!$this->revision_needs_alert) {
            return null;
        }

        $daysRemaining = $this->revision_days_remaining;

        return match (true) {
            $daysRemaining > 1 => "Faltan {$daysRemaining} dias",
            $daysRemaining === 1 => 'Falta 1 dia',
            $daysRemaining === 0 => 'Vence hoy',
            $daysRemaining === -1 => 'Vencida por 1 dia',
            default => 'Vencida por ' . abs((int) $daysRemaining) . ' dias',
        };
    }

    private function getEstadoDetallado($porcentaje)
    {
        if ($porcentaje < self::LIMITE_COMPRAR) {
            return 'normal';
        }

        if ($porcentaje < self::LIMITE_CAMBIO) {
            return 'comprar';
        }

        return 'cambio';
    }

    private function resolverRevisionAnterior(): ?self
    {
        if ($this->revisionAnteriorResuelta) {
            return $this->revisionAnteriorCache;
        }

        if (!$this->exists) {
            $this->revisionAnteriorResuelta = true;

            return null;
        }

        $query = static::query()
            ->whereKeyNot($this->getKey())
            ->when(
                $this->cadena_ciclo_id,
                fn ($builder) => $builder->where('cadena_ciclo_id', $this->cadena_ciclo_id),
                fn ($builder) => $builder->where('linea', $this->linea)
            );

        if ($this->created_at) {
            $query->where(function ($builder) {
                $builder->where('created_at', '<', $this->created_at)
                    ->orWhere(function ($sameTimestamp) {
                        $sameTimestamp->where('created_at', $this->created_at)
                            ->where('id', '<', $this->getKey());
                    });
            });
        } else {
            $query->where('id', '<', $this->getKey());
        }

        $this->revisionAnteriorCache = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $this->revisionAnteriorResuelta = true;

        return $this->revisionAnteriorCache;
    }

    private function revisionTimezone(): string
    {
        return (string) config('elongacion-alerts.timezone', config('app.timezone', 'UTC'));
    }

    private function revisionIntervalMonths(): int
    {
        return max(1, (int) config('elongacion-alerts.interval_months', 2));
    }

    private function revisionLeadDays(): int
    {
        return max(0, (int) config('elongacion-alerts.lead_days', 3));
    }
}
