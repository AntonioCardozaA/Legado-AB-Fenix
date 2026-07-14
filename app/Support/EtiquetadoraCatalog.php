<?php

namespace App\Support;

use Illuminate\Support\Str;

class EtiquetadoraCatalog
{
    public const TIPO_EQUIPO = 'etiquetadora';

    public static function data(): array
    {
        return require database_path('data/etiquetadora_catalog.php');
    }

    public static function lineas(): array
    {
        return array_keys(self::data()['lineas'] ?? []);
    }

    public static function maquinas(): array
    {
        return self::data()['maquinas'] ?? ['A', 'B', 'C'];
    }

    public static function maquinaLabel(string $maquina): string
    {
        return 'Máquina ' . strtoupper(trim($maquina));
    }

    public static function componentes(): array
    {
        return self::data()['componentes'] ?? [];
    }

    public static function cantidadPorMaquina(?string $cantidadOriginal): int
    {
        $valor = trim((string) $cantidadOriginal);

        if ($valor === '') {
            return 0;
        }

        if (preg_match_all('/(?<![a-z0-9])(\d+)\s*\*\s*maquina/i', $valor, $matches) && !empty($matches[1])) {
            return (int) end($matches[1]);
        }

        if (preg_match('/\d+/', $valor, $match)) {
            return (int) $match[0];
        }

        return 0;
    }

    public static function codigo(string $linea, string $maquina, string $grupo, string $nombre): string
    {
        $lineaCodigo = str_replace('-', '', strtoupper($linea));
        $maquinaCodigo = strtoupper(trim($maquina));
        $slug = Str::upper(Str::slug(Str::ascii($nombre), '_')) ?: 'COMPONENTE';
        $hash = substr(sha1($grupo . '|' . $nombre), 0, 6);

        return implode('_', [
            'ETQ',
            $lineaCodigo,
            $maquinaCodigo,
            substr($slug, 0, 82),
            $hash,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function expandedComponentRows(): array
    {
        $rows = [];

        foreach (self::componentes() as $componente) {
            foreach (($componente['cantidades'] ?? []) as $linea => $cantidadOriginal) {
                if (blank($cantidadOriginal)) {
                    continue;
                }

                foreach (self::maquinas() as $maquina) {
                    $rows[] = [
                        'linea' => $linea,
                        'maquina' => $maquina,
                        'maquina_label' => self::maquinaLabel($maquina),
                        'grupo' => $componente['grupo'],
                        'mecanismo' => $componente['mecanismo'],
                        'nombre' => $componente['nombre'],
                        'cantidad_total' => self::cantidadPorMaquina($cantidadOriginal),
                        'cantidad_original' => $cantidadOriginal,
                        'codigo' => self::codigo(
                            $linea,
                            $maquina,
                            $componente['grupo'],
                            $componente['nombre']
                        ),
                    ];
                }
            }
        }

        return $rows;
    }
}
