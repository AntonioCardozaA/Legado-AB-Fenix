<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AnalisisPasteurizadoraExport implements FromCollection, WithHeadings, WithMapping
{
    use \App\Exports\Concerns\MapsPasteurizadoraAnalysis;

    public function __construct(private Collection $analisis)
    {
    }

    public function collection(): Collection
    {
        return $this->analisis;
    }
}
