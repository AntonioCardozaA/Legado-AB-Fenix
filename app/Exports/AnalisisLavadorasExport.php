<?php

namespace App\Exports;

use App\Models\Linea;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AnalisisLavadorasExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $sheets = [];

        $lavadoras = Linea::where('tipo', 'lavadora')->get();

        foreach ($lavadoras as $lavadora) {
            $sheets[] = new AnalisisLavadoraSheet($lavadora);
        }

        return $sheets;
    }
}
