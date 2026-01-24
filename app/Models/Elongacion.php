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
        'tipo',
        'bombas_1', 'bombas_2', 'bombas_3', 'bombas_4',
        'bombas_5', 'bombas_6', 'bombas_7', 'bombas_8',
        'bombas_promedio', 'bombas_porcentaje',
        'vapor_1', 'vapor_2', 'vapor_3', 'vapor_4',
        'vapor_promedio', 'vapor_porcentaje',
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
        'bombas_promedio' => 'decimal:2',
        'bombas_porcentaje' => 'decimal:2',
        'vapor_1' => 'decimal:1',
        'vapor_2' => 'decimal:1',
        'vapor_3' => 'decimal:1',
        'vapor_4' => 'decimal:1',
        'vapor_promedio' => 'decimal:2',
        'vapor_porcentaje' => 'decimal:2',
        'juego_rodaja_bombas' => 'decimal:2',
        'juego_rodaja_vapor' => 'decimal:2',
    ];

    // Calcular promedios automáticamente
    public static function calcularPromedio($mediciones)
    {
        $suma = 0;
        $contador = 0;
        
        foreach ($mediciones as $medicion) {
            if (!is_null($medicion) && $medicion != 0) {
                $suma += $medicion;
                $contador++;
            }
        }
        
        return $contador > 0 ? $suma / $contador : 0;
    }
    
    public static function calcularPorcentaje($promedio)
    {
        if ($promedio) {
            return (($promedio - 173) / 173) * 100;
        }
        return 0;
    }
}