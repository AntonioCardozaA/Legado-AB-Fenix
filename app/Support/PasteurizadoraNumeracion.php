<?php

namespace App\Support;

use App\Models\AnalisisPasteurizadora;
use InvalidArgumentException;

/** Identidad pública por equipo y tipo; módulo, nivel y lado conservan su ubicación. */
final class PasteurizadoraNumeracion
{
    public static function total(?string $linea, string $componente): int
    {
        if (AnalisisPasteurizadora::esBrazoTorsion($componente)) {
            return AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea);
        }

        return AnalisisPasteurizadora::getTotalComponentesPorLineaYComponente($linea, $componente)
            * AnalisisPasteurizadora::getModulosPorLinea($linea);
    }

    public static function numeros(?string $linea, string $componente, int $modulo): array
    {
        $cantidad = AnalisisPasteurizadora::getTotalComponentesPorLineaYComponente($linea, $componente);
        $modulos = AnalisisPasteurizadora::getModulosPorLinea($linea);

        if ($cantidad < 1 || $modulo < 1 || $modulo > $modulos) {
            return [];
        }

        // Los brazos tienen su propia distribución entre módulos, sin cambiar este flujo.
        if (AnalisisPasteurizadora::esBrazoTorsion($componente)) {
            return $modulo <= AnalisisPasteurizadora::getCantidadBrazosTorsionPorLinea($linea) ? [1] : [];
        }

        $inicio = ($modulo - 1) * $cantidad + 1;

        return range($inicio, $inicio + $cantidad - 1);
    }

    public static function desdePosiciones($value, ?string $linea, string $componente, int $modulo): array
    {
        $numeros = self::numeros($linea, $componente, $modulo);
        if (is_string($value)) {
            $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        if ($value !== null && !is_array($value)) {
            throw new InvalidArgumentException('El registro no contiene una lista de posiciones válida.');
        }
        foreach ($value ?? [] as $posicion) {
            if (filter_var($posicion, FILTER_VALIDATE_INT) === false || (int) $posicion < 1 || !isset($numeros[(int) $posicion - 1])) {
                throw new InvalidArgumentException('Posición de componente fuera del módulo configurado.');
            }
        }
        $posiciones = AnalisisPasteurizadora::normalizarComponentesRevisados($value);
        $resultado = [];

        foreach ($posiciones as $posicion) {
            if (!isset($numeros[$posicion - 1])) {
                throw new InvalidArgumentException('Posición de componente fuera del módulo configurado.');
            }
            $resultado[] = $numeros[$posicion - 1];
        }

        return $resultado;
    }

    public static function validarSeleccion($value, ?string $linea, string $componente, int $modulo): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (!is_array($value)) {
            throw new InvalidArgumentException('Seleccione una lista de componentes válida.');
        }

        $permitidos = self::numeros($linea, $componente, $modulo);
        foreach ($value as $numero) {
            if (filter_var($numero, FILTER_VALIDATE_INT) === false || !in_array((int) $numero, $permitidos, true)) {
                throw new InvalidArgumentException('La selección incluye componentes fuera del módulo seleccionado.');
            }
        }

        return AnalisisPasteurizadora::normalizarComponentesRevisados($value);
    }

    public static function configuracion(?string $linea, string $componente): array
    {
        $resultado = [];
        for ($modulo = 1; $modulo <= AnalisisPasteurizadora::getModulosPorLinea($linea); $modulo++) {
            $resultado[$modulo] = self::numeros($linea, $componente, $modulo);
        }

        return $resultado;
    }
}
