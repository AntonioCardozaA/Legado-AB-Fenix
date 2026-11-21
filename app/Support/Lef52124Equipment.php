<?php

namespace App\Support;

use App\Models\Linea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

enum Lef52124Equipment: string
{
    case Lavadora = 'lavadora';
    case Pasteurizadora = 'pasteurizadora';

    public function linePrefix(): string
    {
        return match ($this) {
            self::Lavadora => 'L',
            self::Pasteurizadora => 'P',
        };
    }

    public function lineas(): Builder
    {
        return Linea::where('nombre', 'like', $this->linePrefix().'-%');
    }

    public function routePrefix(): string
    {
        return match ($this) {
            self::Lavadora => 'lef52124.',
            self::Pasteurizadora => 'pasteurizadora.lef52124.',
        };
    }

    public function matchesMachine(string $name): bool
    {
        $term = match ($this) {
            self::Lavadora => 'lavadora',
            self::Pasteurizadora => 'pasteurizador',
        };

        return str_contains(Str::lower(Str::ascii($name)), $term);
    }
}
