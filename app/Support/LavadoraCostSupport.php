<?php

namespace App\Support;

use Illuminate\Support\Str;

class LavadoraCostSupport
{
    public const COMPONENT_CODES = [
        'SERVO_CHICO',
        'SERVO_GRANDE',
        'BUJE_ESPIGA',
        'GUI_INF_TANQUE',
        'GUI_INT_TANQUE',
        'GUI_SUP_TANQUE',
        'CATARINAS',
        'RV200_SIN_FIN',
        'RV200',
    ];

    public const LAVADORA_LINEAS = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];

    public static function normalizeText(?string $value): string
    {
        $value = Str::upper(Str::ascii((string) $value));
        $value = preg_replace('/[^A-Z0-9_]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    public static function inferCategory(string $name): string
    {
        $normalized = self::normalizeText($name);

        return match (true) {
            Str::contains($normalized, ['ACEITE', 'MOBIL', 'GLYGOYLE']) => 'Lubricante',
            Str::contains($normalized, ['FILTRO', 'RESPIRADOR', 'ADAPTADOR']) => 'Filtracion',
            Str::contains($normalized, ['RETEN', 'SELLO', 'ORING', 'O RING', 'ANILLO']) => 'Sellado',
            Str::contains($normalized, ['CHUMACERA', 'BALERO', 'INSERO']) => 'Chumacera / Balero',
            Str::contains($normalized, ['CADENA', 'ESLABON', 'CANDADO']) => 'Cadena',
            Str::contains($normalized, ['CATARINA']) => 'Catarina',
            Str::contains($normalized, ['GUIA']) => 'Guia',
            Str::contains($normalized, ['BUJE', 'CASQUILLO', 'BAQUELITA']) => 'Buje / Espiga',
            Str::contains($normalized, ['REDUCTOR']) => 'Reductor',
            Str::contains($normalized, ['SERVO', 'MOTOREDUCTOR', 'FLECHA', 'EJE']) => 'Servo / Transmision',
            Str::contains($normalized, ['TORNILLO', 'DADO']) => 'Tornilleria',
            default => 'General',
        };
    }

    public static function inferAliases(string $name): array
    {
        $normalized = self::normalizeText($name);
        $aliases = [];

        foreach ([
            'ACEITE',
            'ADAPTADOR',
            'ANILLO',
            'BALERO',
            'BAQUELITA',
            'BUJE',
            'CADENA',
            'CANDADO',
            'CASQUILLO',
            'CATARINA',
            'CHUMACERA',
            'EJE',
            'ESLABON',
            'FILTRO',
            'FLECHA',
            'GUIA',
            'O RING',
            'ORING',
            'REDUCTOR',
            'RESPIRADOR',
            'RETEN',
            'SELLO',
            'SERVO',
            'TORNILLO',
        ] as $keyword) {
            if (self::keywordMatches($normalized, $keyword)) {
                $aliases[] = $keyword;
            }
        }

        return array_values(array_unique($aliases));
    }

    public static function formatAliases(array|string|null $aliases): array
    {
        if (is_string($aliases)) {
            $aliases = preg_split('/[,;\n]+/', $aliases) ?: [];
        }

        return collect($aliases ?? [])
            ->map(fn ($alias) => self::normalizeText((string) $alias))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function normalizeComponentCode(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return '';
        }

        $knownCodes = self::COMPONENT_CODES;
        usort($knownCodes, fn (string $left, string $right) => strlen($right) <=> strlen($left));

        foreach ($knownCodes as $knownCode) {
            if (str_contains($code, $knownCode)) {
                return $knownCode;
            }
        }

        return $code;
    }

    public static function keywordMatches(?string $text, ?string $keyword): bool
    {
        $text = self::normalizeText($text);
        $keyword = self::normalizeText($keyword);

        if ($text === '' || $keyword === '') {
            return false;
        }

        return str_contains(" {$text} ", " {$keyword} ");
    }

    public static function extractQuantity(?string $activity, ?string $unit, float $default = 1.0): float
    {
        $activity = self::normalizeText($activity);

        if ($activity === '') {
            return max($default, 0.01);
        }

        foreach (self::unitAliases($unit) as $alias) {
            $aliasPattern = preg_quote(self::normalizeText($alias), '/');
            $pattern = '/(?:^|\s)(\d+(?:[.,]\d+)?)\s*' . $aliasPattern . '(?:\s|$)/';

            if (preg_match($pattern, $activity, $matches) === 1) {
                return max((float) str_replace(',', '.', $matches[1]), 0.01);
            }
        }

        return max($default, 0.01);
    }

    public static function unitAliases(?string $unit): array
    {
        return match (self::normalizeText($unit)) {
            'METRO' => ['M', 'MT', 'MTS', 'METRO', 'METROS'],
            'LITRO' => ['L', 'LT', 'LTS', 'LITRO', 'LITROS'],
            'PIEZA' => ['PZA', 'PZ', 'PIEZA', 'PIEZAS'],
            'KIT' => ['KIT', 'KITS'],
            'JUEGO COMPLETO' => ['JUEGO', 'JUEGOS'],
            'COMPONENTE COMPLETO' => ['COMPONENTE', 'COMPONENTES'],
            default => [self::normalizeText($unit)],
        };
    }
}
