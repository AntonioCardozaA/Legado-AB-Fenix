<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalisisMultiExport implements WithMultipleSheets
{
    protected $analisis;

    public function __construct($analisis)
    {
        $this->analisis = $analisis;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->analisis as $lavadora => $items) {
            $sheets[] = new AnalisisPorLavadoraSheet($lavadora, $items);
        }

        return $sheets;
    }
}