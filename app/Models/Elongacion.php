<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Elongacion extends Model
{
    use HasFactory;

    protected $table = 'elongaciones';
    
    protected $fillable = [
        'linea',
        'seccion',
        // Bombas
        'bombas_1', 'bombas_2', 'bombas_3', 'bombas_4', 'bombas_5',
        'bombas_6', 'bombas_7', 'bombas_8', 'bombas_9', 'bombas_10',
        'bombas_promedio', 'bombas_porcentaje',
        // Vapor
        'vapor_1', 'vapor_2', 'vapor_3', 'vapor_4', 'vapor_5',
        'vapor_6', 'vapor_7', 'vapor_8', 'vapor_9', 'vapor_10',
        'vapor_promedio', 'vapor_porcentaje',
        // Otros
        'hodometro',
        'juego_rodaja_bombas',
        'juego_rodaja_vapor'
    ];

    protected $casts = [
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
        'juego_rodaja_bombas' => 'decimal:2',
        'juego_rodaja_vapor' => 'decimal:2',
        'paso_inicial' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // NUEVOS LÍMITES según especificaciones
    const LIMITE_COMPRAR = 1.3;     // Alerta para comprar cadena
    const LIMITE_DANO = 1.46;       // Inicio de daño
    const LIMITE_CAMBIO = 1.8;      // Límite máximo de cambio

    // Pasos iniciales por línea
    const PASOS_INICIALES = [
        'L-04' => 173,
        'L-05' => 140,
        'L-06' => 140,
        'L-07' => 173,
        'L-08' => 125,
        'L-09' => 140,
        'L-12' => 140,
        'L-13' => 140,
    ];

    /**
     * Obtener paso inicial para una línea específica
     */
    public static function getPasoInicial($linea)
    {
        return self::PASOS_INICIALES[$linea] ?? 173;
    }

    /**
     * Scope para filtrar por línea
     */
    public function scopePorLinea($query, $linea)
    {
        if ($linea) {
            return $query->where('linea', $linea);
        }
        return $query;
    }

    /**
     * Scope para filtrar por estado - ACTUALIZADO
     */
    public function scopePorEstado($query, $estado)
    {
        if (!$estado) return $query;
        
        switch ($estado) {
            case 'normal':
                return $query->where('bombas_porcentaje', '<', self::LIMITE_COMPRAR)
                            ->where('vapor_porcentaje', '<', self::LIMITE_COMPRAR);
            case 'comprar':
                return $query->where(function($q) {
                    $q->whereBetween('bombas_porcentaje', [self::LIMITE_COMPRAR, self::LIMITE_DANO - 0.01])
                      ->orWhereBetween('vapor_porcentaje', [self::LIMITE_COMPRAR, self::LIMITE_DANO - 0.01]);
                });
            case 'dano':
                return $query->where(function($q) {
                    $q->whereBetween('bombas_porcentaje', [self::LIMITE_DANO, self::LIMITE_CAMBIO - 0.01])
                      ->orWhereBetween('vapor_porcentaje', [self::LIMITE_DANO, self::LIMITE_CAMBIO - 0.01]);
                });
            case 'critico':
                return $query->where(function($q) {
                    $q->where('bombas_porcentaje', '>=', self::LIMITE_CAMBIO)
                      ->orWhere('vapor_porcentaje', '>=', self::LIMITE_CAMBIO);
                });
            default:
                return $query;
        }
    }

    /**
     * Calcular promedio de mediciones
     */
    public static function calcularPromedio($mediciones)
    {
        $suma = 0;
        $contador = 0;
        
        foreach ($mediciones as $medicion) {
            if (!is_null($medicion) && is_numeric($medicion) && $medicion > 0) {
                $suma += floatval($medicion);
                $contador++;
            }
        }
        
        return $contador > 0 ? round($suma / $contador, 2) : 0;
    }
    
    /**
     * Calcular porcentaje de elongación - CORREGIDO
     * Fórmula: ((L_medido - L_nominal) / L_nominal) * 100
     */
    public static function calcularPorcentaje($promedio, $pasoInicial = null)
    {
        if ($promedio <= 0) return 0;
        
        // Si no se proporciona paso inicial, usar el de la línea (esto se debe pasar desde el controlador)
        if ($pasoInicial === null) {
            $pasoInicial = self::PASO_INICIAL; // Valor por defecto (obsoleto, mejor siempre pasar el valor)
        }
        
        if ($pasoInicial <= 0) return 0;
        
        // Fórmula correcta: ((medido - nominal) / nominal) * 100
        $porcentaje = (($promedio - $pasoInicial) / $pasoInicial) * 100;
        
        // Redondear a 2 decimales y asegurar que no sea negativo
        // (si es negativo, significa que la medición es menor al paso inicial - posible error de medición)
        return round(max($porcentaje, 0), 2);
    }

    /**
     * Obtener estado detallado basado en porcentaje
     */
    public function getEstadoBombasAttribute()
    {
        return $this->getEstadoDetallado($this->bombas_porcentaje);
    }

    public function getEstadoVaporAttribute()
    {
        return $this->getEstadoDetallado($this->vapor_porcentaje);
    }

    /**
     * Obtener estado detallado (4 niveles)
     */
    private function getEstadoDetallado($porcentaje)
    {
        if ($porcentaje < self::LIMITE_COMPRAR) return 'normal';
        if ($porcentaje < self::LIMITE_DANO) return 'comprar';
        if ($porcentaje < self::LIMITE_CAMBIO) return 'dano';
        return 'critico';
    }

    /**
     * Obtener estado general (3 niveles para compatibilidad)
     */
    public function getEstadoGeneralAttribute()
    {
        $maxPorcentaje = max($this->bombas_porcentaje, $this->vapor_porcentaje);
        
        if ($maxPorcentaje >= self::LIMITE_CAMBIO) return 'critico';
        if ($maxPorcentaje >= self::LIMITE_COMPRAR) return 'alerta';
        return 'normal';
    }

    /**
     * Verificar si requiere cambio
     */
    public function getRequiereCambioAttribute()
    {
        return $this->bombas_porcentaje >= self::LIMITE_CAMBIO || 
               $this->vapor_porcentaje >= self::LIMITE_CAMBIO;
    }

    /**
     * Verificar si tiene daño
     */
    public function getTieneDanoAttribute()
    {
        return $this->bombas_porcentaje >= self::LIMITE_DANO || 
               $this->vapor_porcentaje >= self::LIMITE_DANO;
    }

    /**
     * Verificar si requiere compra
     */
    public function getRequiereCompraAttribute()
    {
        $maxPorcentaje = max($this->bombas_porcentaje, $this->vapor_porcentaje);
        return $maxPorcentaje >= self::LIMITE_COMPRAR && $maxPorcentaje < self::LIMITE_CAMBIO;
    }

    /**
     * Obtener el color del estado para UI
     */
    public function getColorEstadoAttribute()
    {
        $estado = $this->estado_detallado ?? $this->getEstadoGeneralAttribute();
        
        switch ($estado) {
            case 'critico': return 'red';
            case 'dano': return 'orange';
            case 'comprar': return 'yellow';
            case 'alerta': return 'amber';
            default: return 'green';
        }
    }

    /**
     * Obtener el icono del estado
     */
    public function getIconoEstadoAttribute()
    {
        $estado = $this->estado_detallado ?? $this->getEstadoGeneralAttribute();
        
        switch ($estado) {
            case 'critico': return 'fa-exclamation-circle';
            case 'dano': return 'fa-exclamation-triangle';
            case 'comprar': return 'fa-shopping-cart';
            case 'alerta': return 'fa-exclamation-triangle';
            default: return 'fa-check-circle';
        }
    }
}