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

    public static function presentaciones(): array
    {
        return [
            '04' => [
                [
                    'label' => 'Corona Mega',
                    'image' => 'SoloEtiquetas/linea04-corona-mega.png',
                    'botella' => [
                        'forma' => 'mega-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 1.12,
                        'image' => 'Botellas/linea04-mega-botella.png',
                        'image_labeled' => 'Botellas/linea04-corona-mega-etiquetada.png',
                        'labeled_scale' => '0.82',
                        'label_width' => '48%',
                        'label_height' => '30%',
                        'label_bottom' => '24%',
                        'label_curve_inset' => '9%',
                        'label_scale_x' => '0.92',
                        'label_rotate_y' => '0deg',
                        'label_fit' => 'fill',
                    ],
                ],
                [
                    'label' => 'Victoria',
                    'image' => 'SoloEtiquetas/linea04-victoria-mega.png',
                    'botella' => [
                        'forma' => 'mega-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 1.12,
                        'image' => 'Botellas/linea04-mega-botella.png',
                        'image_labeled' => 'Botellas/linea04-victoria-mega-etiquetada.png',
                        'labeled_scale' => '0.92',
                        'label_width' => '50%',
                        'label_height' => '39%',
                        'label_bottom' => '18%',
                        'label_curve_inset' => '8%',
                        'label_scale_x' => '0.94',
                        'label_rotate_y' => '0deg',
                        'label_fit' => 'fill',
                    ],
                ],
            ],
            '05' => [
                [
                    'label' => 'Victoria Cuarto',
                    'image' => 'SoloEtiquetas/linea05-victoria-cuarto.png',
                    'botella' => [
                        'forma' => 'victoria-cuarto-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 0.86,
                        'image' => 'Botellas/linea05-victoria-cuarto-botella-tight.png',
                        'image_labeled' => 'Botellas/linea05-victoria-cuarto-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
            ],
            '06' => [
                [
                    'label' => 'Negra Modelo',
                    'image' => 'SoloEtiquetas/linea06-modelo-negra-grande.png',
                    'botella' => [
                        'forma' => 'modelo-negra-grande-real',
                        'tono' => 'dark',
                        'tapa' => 'gold',
                        'escala' => 1.0,
                        'image' => 'Botellas/linea06-modelo-negra-grande-botella-tight.png',
                        'image_labeled' => 'Botellas/linea06-modelo-negra-grande-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Modelo Especial',
                    'image' => 'SoloEtiquetas/linea06-modelo-especial-grande.png',
                    'botella' => [
                        'forma' => 'modelo-especial-grande-real',
                        'tono' => 'gold-clear',
                        'tapa' => 'gold',
                        'escala' => 1.0,
                        'image' => 'Botellas/linea06-modelo-especial-grande-botella-tight.png',
                        'image_labeled' => 'Botellas/linea06-modelo-especial-grande-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Corona Extra',
                    'image' => 'SoloEtiquetas/linea06-corona-extra-grande.png',
                    'botella' => [
                        'forma' => 'corona-extra-grande-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 1.0,
                        'image' => 'Botellas/linea06-corona-extra-grande-botella-tight.png',
                        'image_labeled' => 'Botellas/linea06-corona-extra-grande-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Bud Light',
                    'image' => 'SoloEtiquetas/linea06-bud-light-grande.png',
                    'botella' => [
                        'forma' => 'bud-light-grande-real',
                        'tono' => 'amber',
                        'tapa' => 'blue',
                        'escala' => 1.0,
                        'image' => 'Botellas/linea06-bud-light-grande-botella-tight.png',
                        'image_labeled' => 'Botellas/linea06-bud-light-grande-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
            ],
            '10' => [
                [
                    'label' => 'Barrilito',
                    'image' => 'SoloEtiquetas/linea10-barrilito.png',
                    'botella' => [
                        'forma' => 'barrilito-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 0.94,
                        'image' => 'Botellas/linea10-barrilito-botella-tight.png',
                        'image_labeled' => 'Botellas/linea10-barrilito-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
            ],
            '12' => [
                [
                    'label' => 'Modelo Especial',
                    'image' => 'SoloEtiquetas/linea12-modelo-especial-355ml.png',
                    'botella' => [
                        'forma' => 'modelo-especial-355ml-real',
                        'tono' => 'gold-clear',
                        'tapa' => 'gold',
                        'escala' => 0.90,
                        'image' => 'Botellas/linea12-modelo-especial-355ml-botella-tight.png',
                        'image_labeled' => 'Botellas/linea12-modelo-especial-355ml-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Modelito Especial',
                    'image' => 'SoloEtiquetas/linea12-modelito-especial-210ml.png',
                    'botella' => [
                        'forma' => 'modelito-especial-210ml-real',
                        'tono' => 'gold-clear',
                        'tapa' => 'gold',
                        'escala' => 0.78,
                        'image' => 'Botellas/linea12-modelito-especial-210ml-botella-tight.png',
                        'image_labeled' => 'Botellas/linea12-modelito-especial-210ml-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Negra Modelo',
                    'image' => 'SoloEtiquetas/linea12-negra-modelo-355ml.png',
                    'botella' => [
                        'forma' => 'negra-modelo-355ml-real',
                        'tono' => 'dark',
                        'tapa' => 'gold',
                        'escala' => 0.90,
                        'image' => 'Botellas/linea12-negra-modelo-355ml-botella-tight.png',
                        'image_labeled' => 'Botellas/linea12-negra-modelo-355ml-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
            ],
            '13' => [
                [
                    'label' => 'Michelob Ultra',
                    'image' => 'SoloEtiquetas/linea13-michelob-ultra.png',
                    'botella' => [
                        'forma' => 'michelob-ultra-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 0.86,
                        'image' => 'Botellas/linea13-botella-compartida-tight.png',
                        'image_labeled' => 'Botellas/linea13-michelob-ultra-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
                [
                    'label' => 'Pacifico Clara',
                    'image' => 'SoloEtiquetas/linea13-pacifico-clara.png',
                    'botella' => [
                        'forma' => 'pacifico-clara-real',
                        'tono' => 'amber',
                        'tapa' => 'gold',
                        'escala' => 0.86,
                        'image' => 'Botellas/linea13-botella-compartida-tight.png',
                        'image_labeled' => 'Botellas/linea13-pacifico-clara-etiquetada-tight.png',
                        'labeled_scale' => '1',
                    ],
                ],
            ],
        ];
    }

    public static function presentacionesPorLinea(mixed $linea): array
    {
        $codigo = self::normalizarCodigoLinea($linea);

        return $codigo ? (self::presentaciones()[$codigo] ?? []) : [];
    }

    public static function normalizarCodigoLinea(mixed $linea): ?string
    {
        $lineaNombre = is_object($linea)
            ? (string) ($linea->nombre ?? '')
            : (string) $linea;

        if ($lineaNombre === '') {
            return null;
        }

        if (preg_match('/(\d{1,2})/', $lineaNombre, $matches) !== 1) {
            return null;
        }

        return str_pad((string) ((int) $matches[1]), 2, '0', STR_PAD_LEFT);
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
                            $componente['codigo_nombre'] ?? $componente['nombre']
                        ),
                    ];
                }
            }
        }

        return $rows;
    }

    public static function expandedComponentRowsByCode(): array
    {
        return collect(self::expandedComponentRows())
            ->keyBy('codigo')
            ->all();
    }

    public static function isGeneratedComponentCode(?string $codigo): bool
    {
        return preg_match('/^ETQ_L\d{2}_[A-Z]_.+_[a-f0-9]{6}$/i', trim((string) $codigo)) === 1;
    }
}
