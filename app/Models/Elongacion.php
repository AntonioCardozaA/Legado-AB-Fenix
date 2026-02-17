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
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Constantes
    const PASO_INICIAL = 173; // mm
    const LIMITE_ADVERTENCIA = 2.0; // %
    const LIMITE_PELIGRO = 2.4; // %

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
     * Scope para filtrar por estado
     */
    public function scopePorEstado($query, $estado)
    {
        if (!$estado) return $query;
        
        switch ($estado) {
            case 'normal':
                return $query->where('bombas_porcentaje', '<', self::LIMITE_ADVERTENCIA)
                            ->where('vapor_porcentaje', '<', self::LIMITE_ADVERTENCIA);
            case 'alerta':
                return $query->where(function($q) {
                    $q->whereBetween('bombas_porcentaje', [self::LIMITE_ADVERTENCIA, self::LIMITE_PELIGRO])
                      ->orWhereBetween('vapor_porcentaje', [self::LIMITE_ADVERTENCIA, self::LIMITE_PELIGRO]);
                });
            case 'critico':
                return $query->where(function($q) {
                    $q->where('bombas_porcentaje', '>=', self::LIMITE_PELIGRO)
                      ->orWhere('vapor_porcentaje', '>=', self::LIMITE_PELIGRO);
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
     * Calcular porcentaje de elongación
     */
    public static function calcularPorcentaje($promedio)
    {
        if ($promedio && $promedio > 0) {
            return round((($promedio - self::PASO_INICIAL) / self::PASO_INICIAL) * 100, 2);
        }
        return 0;
    }

    /**
     * Obtener estado basado en porcentaje
     */
    public function getEstadoBombasAttribute()
    {
        return $this->getEstado($this->bombas_porcentaje);
    }

    public function getEstadoVaporAttribute()
    {
        return $this->getEstado($this->vapor_porcentaje);
    }

    private function getEstado($porcentaje)
    {
        if ($porcentaje < self::LIMITE_ADVERTENCIA) return 'normal';
        if ($porcentaje < self::LIMITE_PELIGRO) return 'alerta';
        return 'critico';
    }

    /**
     * Verificar si requiere cambio
     */
    public function getRequiereCambioAttribute()
    {
        return $this->bombas_porcentaje >= self::LIMITE_PELIGRO || 
               $this->vapor_porcentaje >= self::LIMITE_PELIGRO;
    }
}