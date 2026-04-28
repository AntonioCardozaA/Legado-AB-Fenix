<?php

namespace App\Support;

class HodometroHoras
{
    public static function formatear($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        $valor = (int) $valor;
        $horas = intdiv($valor, 100);
        $segundos = abs($valor % 100);

        return number_format($horas, 0) . ':' . str_pad((string) $segundos, 2, '0', STR_PAD_LEFT) . ' h';
    }
}
