<?php

namespace App\Exports;

use App\Exports\Sheets\Lef52124WorkbookSheet;
use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class Lef52124SingleImportExport implements WithMultipleSheets
{
    public function __construct(private readonly Lef52124Import $import)
    {
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ([
            Lef52124Item::TYPE_MACHINE => 'Maquinas',
            Lef52124Item::TYPE_PART => 'Partes',
            Lef52124Item::TYPE_LINE => 'Lineas',
        ] as $type => $label) {
            $rows = [[$label, '1 - 52 SEM', '2 - 12 SEM', '3 - 4 SEM']];

            foreach ($this->import->items->where('type', $type)->sortBy('item_name') as $item) {
                $rows[] = [
                    $item->item_name,
                    (float) $item->value_52_weeks,
                    (float) $item->value_12_weeks,
                    (float) $item->value_4_weeks,
                ];
            }

            $sheets[] = new Lef52124WorkbookSheet(
                $label,
                'Importacion individual 52-12-4',
                'Linea: '.($this->import->linea?->nombre ?? 'Sin linea').' | Fecha: '.($this->import->data_date?->format('d/m/Y') ?? 'N/A'),
                $rows,
                5
            );
        }

        return $sheets;
    }
}
