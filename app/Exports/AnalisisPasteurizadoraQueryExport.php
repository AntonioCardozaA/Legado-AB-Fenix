<?php

namespace App\Exports;

use App\Exports\Concerns\MapsPasteurizadoraAnalysis;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnalisisPasteurizadoraQueryExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize
{
    use MapsPasteurizadoraAnalysis;

    public function __construct(private readonly Builder $analisis) {}

    public function query(): Builder
    {
        return (clone $this->analisis)->orderBy($this->analisis->getModel()->qualifyColumn('id'));
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
