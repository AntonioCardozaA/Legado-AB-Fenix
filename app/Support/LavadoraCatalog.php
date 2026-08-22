<?php

namespace App\Support;

use Illuminate\Support\Str;

class LavadoraCatalog
{
    public const LINEAS = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];
    public const LINEAS_CON_REDUCTOR = ['L-05', 'L-12', 'L-13'];

    public const REDUCTOR_PRINCIPAL = 'Reductor Principal';
    public const FLECHA_LOCA = 'Flecha Loca';

    public const PASO_CADENA_POR_LINEA = [
        'L-04' => 173,
        'L-05' => 140,
        'L-06' => 173,
        'L-07' => 173,
        'L-08' => 125,
        'L-09' => 140,
        'L-12' => 140,
        'L-13' => 140,
    ];

    public const COMPONENTE_CODIGOS_BASE = [
        'RV200_SIN_FIN',
        'SERVO_CHICO',
        'SERVO_GRANDE',
        'BUJE_ESPIGA',
        'GUI_INF_TANQUE',
        'GUI_INT_TANQUE',
        'GUI_SUP_TANQUE',
        'CATARINAS',
        'RV200',
    ];

    public const COMPONENTE_NOMBRES = [
        'SERVO_CHICO' => 'Servo Chico',
        'SERVO_GRANDE' => 'Servo Grande',
        'BUJE_ESPIGA' => 'Buje Baquelita-Espiga',
        'GUI_INF_TANQUE' => 'Guia Inf Tanque',
        'GUI_INT_TANQUE' => 'Guia Int Tanque',
        'GUI_INT_TAQNQUE' => 'Guia Int Tanque',
        'GUI_SUP_TANQUE' => 'Guia Sup Tanque',
        'CATARINAS' => 'Catarinas',
        'RV200' => 'RV250 Sin Fin Corona',
        'RV200_SIN_FIN' => 'RV250 Sin Fin Corona',
    ];

    public const COMPONENTE_SKUS = [
        'SERVO_CHICO' => '4172293',
        'SERVO_GRANDE' => '4147930',
        'BUJE_ESPIGA' => '4017810',
        'GUI_INF_TANQUE' => '4066462',
        'GUI_INT_TANQUE' => '4066460',
        'GUI_SUP_TANQUE' => '4066459',
        'RV200' => '4067643',
        'RV200_SIN_FIN' => '4067643',
    ];

    public const COMPONENTE_SKUS_POR_PASO = [
        'CATARINAS' => [
            125 => '4064265',
            140 => '4065310',
            173 => '4094364',
        ],
        'GUI_INF_TANQUE' => [
            125 => '4066462',
            140 => '4066462',
            173 => '4066462',
        ],
        'GUI_INT_TANQUE' => [
            125 => '4066460',
            140 => '4066460',
            173 => '4066460',
        ],
        'GUI_SUP_TANQUE' => [
            125 => '4066459',
            140 => '4066459',
            173 => '4066459',
        ],
    ];

    public const COMPONENTES_POR_LINEA = [
        'L-04' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-05' => [
            'RV200' => 'RV250 Sin Fin Corona',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-06' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-07' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-08' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-09' => [
            'SERVO_CHICO' => 'Servo Chico',
            'SERVO_GRANDE' => 'Servo Grande',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-12' => [
            'RV200_SIN_FIN' => 'RV250 Sin Fin Corona',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
        'L-13' => [
            'RV200' => 'RV250 Sin Fin Corona',
            'BUJE_ESPIGA' => 'Buje Baquelita-Espiga de flecha',
            'GUI_INF_TANQUE' => 'Guia Inferior',
            'GUI_INT_TANQUE' => 'Guia Intermedia',
            'GUI_SUP_TANQUE' => 'Guia Superior',
            'CATARINAS' => 'Catarinas',
        ],
    ];

    public const REDUCTORES_POR_LINEA = [
        'L-04' => [
            'Reductor 1',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            'Reductor 13',
            'Reductor 14',
            'Reductor 15',
            'Reductor 16',
            'Reductor 17',
            'Reductor 18',
            'Reductor 19',
            self::FLECHA_LOCA,
        ],
        'L-05' => [
            'Reductor 1',
            'Reductor 2',
            'Reductor 3',
            'Reductor 4',
            'Reductor 5',
            'Reductor 6',
            'Reductor 7',
            'Reductor 8',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            self::REDUCTOR_PRINCIPAL,
            self::FLECHA_LOCA,
        ],
        'L-06' => [
            'Reductor 1',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            'Reductor 13',
            'Reductor 14',
            'Reductor 15',
            'Reductor 16',
            'Reductor 17',
            'Reductor 18',
            'Reductor 19',
            'Reductor 20',
            'Reductor 21',
            'Reductor 22',
        ],
        'L-07' => [
            'Reductor 1',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            'Reductor 13',
            'Reductor 14',
            'Reductor 15',
            'Reductor 16',
            'Reductor 17',
            'Reductor 18',
            'Reductor 19',
            'Reductor 20',
            'Reductor 21',
            'Reductor 22',
        ],
        'L-08' => [
            'Reductor 1',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            'Reductor 13',
            'Reductor 14',
            'Reductor 15',
            'Reductor 16',
            'Reductor 17',
            'Reductor 18',
            'Reductor 19',
            self::FLECHA_LOCA,
        ],
        'L-09' => [
            'Reductor 1',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            'Reductor 13',
            'Reductor 14',
            'Reductor 15',
            'Reductor 16',
            'Reductor 17',
            'Reductor 18',
            'Reductor 19',
            self::FLECHA_LOCA,
        ],
        'L-12' => [
            'Reductor 1',
            'Reductor 2',
            'Reductor 3',
            'Reductor 4',
            'Reductor 5',
            'Reductor 6',
            'Reductor 7',
            'Reductor 8',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            self::FLECHA_LOCA,
            self::REDUCTOR_PRINCIPAL,
        ],
        'L-13' => [
            'Reductor 1',
            'Reductor 2',
            'Reductor 3',
            'Reductor 4',
            'Reductor 5',
            'Reductor 6',
            'Reductor 7',
            'Reductor 8',
            'Reductor 9',
            'Reductor 10',
            'Reductor 11',
            'Reductor 12',
            self::FLECHA_LOCA,
            self::REDUCTOR_PRINCIPAL,
        ],
    ];

    public static function lineas(): array
    {
        return self::LINEAS;
    }

    public static function componentesPorLinea(): array
    {
        return self::COMPONENTES_POR_LINEA;
    }

    public static function componentesDeLinea(string $lineaNombre): array
    {
        return self::COMPONENTES_POR_LINEA[$lineaNombre] ?? [];
    }

    public static function todosComponentes(): array
    {
        $componentes = [];

        foreach (self::COMPONENTES_POR_LINEA as $componentesLinea) {
            $componentes = array_replace($componentes, $componentesLinea);
        }

        return $componentes;
    }

    public static function nombreComponente(string $codigo): string
    {
        return self::COMPONENTE_NOMBRES[$codigo] ?? $codigo;
    }

    public static function pasoCadenaLinea(?string $lineaNombre): ?int
    {
        $lineaNombre = self::normalizarLineaSku($lineaNombre);

        return $lineaNombre ? (self::PASO_CADENA_POR_LINEA[$lineaNombre] ?? null) : null;
    }

    public static function skuComponente(?string $lineaNombre, ?string $codigo): ?string
    {
        $codigo = self::normalizarCodigoSku($codigo);

        if ($codigo === '') {
            return null;
        }

        $paso = self::pasoCadenaLinea($lineaNombre);

        if (
            $paso !== null
            && isset(self::COMPONENTE_SKUS_POR_PASO[$codigo][$paso])
        ) {
            return self::COMPONENTE_SKUS_POR_PASO[$codigo][$paso];
        }

        return self::COMPONENTE_SKUS[$codigo] ?? null;
    }

    public static function skusComponente(?string $lineaNombre, ?string $codigo): array
    {
        $sku = self::skuComponente($lineaNombre, $codigo);

        return $sku ? [$sku] : [];
    }

    public static function resumenSkuComponente(?string $lineaNombre, ?string $codigo): array
    {
        $sku = self::skuComponente($lineaNombre, $codigo);

        if (!$sku) {
            return [
                'label' => 'SKU: Sin SKU',
                'title' => 'SKU: Sin SKU',
                'sku' => null,
            ];
        }

        return [
            'label' => 'SKU: ' . $sku,
            'title' => 'SKU: ' . $sku,
            'sku' => $sku,
        ];
    }

    public static function resumenSkusComponente(?string $lineaNombre, ?string $codigo): array
    {
        return self::resumenSkuComponente($lineaNombre, $codigo);
    }

    public static function reductoresPorLinea(string $lineaNombre): array
    {
        return self::REDUCTORES_POR_LINEA[$lineaNombre] ?? [];
    }

    public static function lineaTieneReductoresReales(?string $lineaNombre): bool
    {
        return in_array((string) $lineaNombre, self::LINEAS_CON_REDUCTOR, true);
    }

    public static function etiquetaReductor(?string $lineaNombre = null, bool $plural = false, bool $uppercase = false): string
    {
        if ($lineaNombre === null || $lineaNombre === '') {
            $label = $plural ? 'Reductores / Servo-Reductores' : 'Reductor / Servo-Reductor';
        } elseif (self::lineaTieneReductoresReales($lineaNombre)) {
            $label = $plural ? 'Reductores' : 'Reductor';
        } else {
            $label = $plural ? 'Servo-Reductores' : 'Servo-Reductor';
        }

        return $uppercase ? Str::of($label)->upper()->value() : $label;
    }

    public static function etiquetaReductorParaValor(?string $lineaNombre, ?string $reductor, bool $uppercase = false): string
    {
        $label = self::normalizarReductor($reductor) === self::FLECHA_LOCA
            ? self::FLECHA_LOCA
            : self::etiquetaReductor($lineaNombre);

        return $uppercase ? Str::of($label)->upper()->value() : $label;
    }

    public static function nombreReductorParaLinea(?string $lineaNombre, ?string $reductor): ?string
    {
        $reductor = self::normalizarReductor($reductor);

        if (!$reductor || $lineaNombre === null || $lineaNombre === '' || self::lineaTieneReductoresReales($lineaNombre)) {
            return $reductor;
        }

        if (preg_match('/^Reductor\s+([0-9]+)$/i', $reductor, $matches) === 1) {
            return 'Servo-Reductor ' . (int) $matches[1];
        }

        return $reductor;
    }

    public static function normalizarReductor(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        $normalizado = Str::of($valor)->ascii()->upper()->squish()->value();

        if ($normalizado === 'LOCA' || str_contains($normalizado, 'REDUCTOR LOCA')) {
            return self::FLECHA_LOCA;
        }

        if (str_contains($normalizado, 'FLECHA LOCA')) {
            return self::FLECHA_LOCA;
        }

        if (str_contains($normalizado, 'PRINCIPAL')) {
            return self::REDUCTOR_PRINCIPAL;
        }

        if (preg_match('/(?:REDUCTOR|RED)\s*[-#]?\s*0*([0-9]+)/i', $valor, $matches) === 1) {
            return 'Reductor ' . (int) $matches[1];
        }

        if (preg_match('/^0*([0-9]+)$/', $valor, $matches) === 1) {
            return 'Reductor ' . (int) $matches[1];
        }

        return $valor;
    }

    public static function reductorValidoParaLinea(string $lineaNombre, ?string $reductor): bool
    {
        $reductor = self::normalizarReductor($reductor);

        if (!$reductor) {
            return false;
        }

        return in_array($reductor, self::reductoresPorLinea($lineaNombre), true);
    }

    public static function tieneReductorPrincipal(string $lineaNombre): bool
    {
        return in_array(self::REDUCTOR_PRINCIPAL, self::reductoresPorLinea($lineaNombre), true);
    }

    private static function normalizarCodigoSku(?string $codigo): string
    {
        $codigo = strtoupper(trim((string) $codigo));

        if ($codigo === '') {
            return '';
        }

        $knownCodes = self::COMPONENTE_CODIGOS_BASE;
        usort($knownCodes, fn (string $left, string $right) => strlen($right) <=> strlen($left));

        foreach ($knownCodes as $knownCode) {
            if (str_contains($codigo, $knownCode)) {
                return $knownCode;
            }
        }

        return $codigo;
    }

    private static function normalizarLineaSku(?string $lineaNombre): ?string
    {
        $lineaNombre = strtoupper(trim((string) $lineaNombre));

        if ($lineaNombre === '') {
            return null;
        }

        if (preg_match('/^L-?0?([0-9]+)$/', $lineaNombre, $matches) === 1) {
            return 'L-' . str_pad((string) (int) $matches[1], 2, '0', STR_PAD_LEFT);
        }

        return $lineaNombre;
    }

}
