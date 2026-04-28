<?php

namespace App\Models;

use App\Support\HodometroHoras;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elongacion extends Model
{
    use HasFactory;

    protected $table = 'elongaciones';

    protected $fillable = [
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

    public function cadenaCiclo()
    {
        return $this->belongsTo(CadenaCiclo::class, 'cadena_ciclo_id');
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
}
