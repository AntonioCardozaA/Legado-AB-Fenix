<?php

namespace App\Services;

use App\Models\Lef52124Import;
use App\Models\Lef52124Item;
use App\Models\Linea;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Lef52124ImportService
{
    private const SHEETS = [
        'machines' => [
            'type' => Lef52124Item::TYPE_MACHINE,
            'label' => 'Maquinas',
            'sheet_terms' => ['maquina'],
            'first_header_terms' => ['maquina'],
        ],
        'parts' => [
            'type' => Lef52124Item::TYPE_PART,
            'label' => 'Partes',
            'sheet_terms' => ['parte'],
            'first_header_terms' => ['parte'],
        ],
        'lines' => [
            'type' => Lef52124Item::TYPE_LINE,
            'label' => 'Lineas',
            'sheet_terms' => ['linea'],
            'first_header_terms' => ['linea'],
        ],
    ];

    public function import(UploadedFile $file, Linea $linea, CarbonInterface $dataDate, ?string $observations, User $user): Lef52124Import
    {
        $this->validateExtension($file);

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\Throwable $exception) {
            throw new \RuntimeException('No se pudo leer el archivo Excel. Verifica que no este corrupto y que sea .xls o .xlsx.');
        }

        $sheets = $this->resolveSheets($spreadsheet->getAllSheets());
        $rows = [];

        foreach (self::SHEETS as $key => $config) {
            $rows[$key] = $this->parseSheet($sheets[$key], $config);
        }

        return DB::transaction(function () use ($linea, $dataDate, $observations, $user, $file, $rows) {
            $import = Lef52124Import::create([
                'linea_id' => $linea->id,
                'data_date' => $dataDate->toDateString(),
                'source_filename' => $file->getClientOriginalName(),
                'observations' => $observations,
                'status' => 'success',
                'machines_count' => count($rows['machines']),
                'parts_count' => count($rows['parts']),
                'line_items_count' => count($rows['lines']),
                'imported_by' => $user->id,
            ]);

            foreach (self::SHEETS as $key => $config) {
                foreach ($rows[$key] as $row) {
                    Lef52124Item::create([
                        'lef52124_import_id' => $import->id,
                        'linea_id' => $linea->id,
                        'type' => $config['type'],
                        'item_name' => $row['name'],
                        'value_52_weeks' => $row['value_52_weeks'],
                        'value_12_weeks' => $row['value_12_weeks'],
                        'value_4_weeks' => $row['value_4_weeks'],
                    ]);
                }
            }

            return $import;
        });
    }

    private function validateExtension(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xls', 'xlsx'], true)) {
            throw new \RuntimeException('El archivo debe tener extension .xls o .xlsx.');
        }

        if ($extension === 'xlsx' && !extension_loaded('zip')) {
            throw new \RuntimeException('El servidor PHP no tiene habilitada la extension zip, necesaria para leer archivos .xlsx.');
        }
    }

    /**
     * @param array<int, Worksheet> $worksheets
     * @return array<string, Worksheet>
     */
    private function resolveSheets(array $worksheets): array
    {
        $resolved = [];

        foreach (self::SHEETS as $key => $config) {
            foreach ($worksheets as $sheet) {
                $normalizedTitle = $this->normalizeText($sheet->getTitle());

                if (collect($config['sheet_terms'])->every(fn (string $term) => str_contains($normalizedTitle, $term))) {
                    $resolved[$key] = $sheet;
                    continue 2;
                }
            }

            throw new \RuntimeException("Falta la hoja esperada: {$config['label']}.");
        }

        return $resolved;
    }

    /**
     * @param array{type:string,label:string,sheet_terms:array<int,string>,first_header_terms:array<int,string>} $config
     * @return array<int, array{name:string,value_52_weeks:string,value_12_weeks:string,value_4_weeks:string}>
     */
    private function parseSheet(Worksheet $sheet, array $config): array
    {
        $headerRow = $this->findHeaderRow($sheet, $config);
        $highestRow = $sheet->getHighestDataRow();
        $rows = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $name = trim((string) $this->cellValue($sheet, 1, $row));
            $values = [
                'value_52_weeks' => $this->cellValue($sheet, 2, $row),
                'value_12_weeks' => $this->cellValue($sheet, 3, $row),
                'value_4_weeks' => $this->cellValue($sheet, 4, $row),
            ];

            $rowIsEmpty = $name === '' && collect($values)->every(fn ($value) => $value === null || trim((string) $value) === '');

            if ($rowIsEmpty) {
                continue;
            }

            if ($name === '') {
                throw new \RuntimeException("Hoja {$sheet->getTitle()}, fila {$row}: falta el nombre del elemento.");
            }

            foreach ($values as $field => $value) {
                if ($value === null || trim((string) $value) === '') {
                    throw new \RuntimeException("Hoja {$sheet->getTitle()}, fila {$row}: el valor de {$this->periodLabel($field)} esta vacio.");
                }

                if (!is_numeric($value)) {
                    throw new \RuntimeException("Hoja {$sheet->getTitle()}, fila {$row}: el valor de {$this->periodLabel($field)} no es numerico.");
                }

                $values[$field] = (string) $value;
            }

            $rows[] = [
                'name' => $name,
                ...$values,
            ];
        }

        if (count($rows) === 0) {
            throw new \RuntimeException("La hoja {$sheet->getTitle()} no contiene filas de datos.");
        }

        return $rows;
    }

    /**
     * @param array{type:string,label:string,sheet_terms:array<int,string>,first_header_terms:array<int,string>} $config
     */
    private function findHeaderRow(Worksheet $sheet, array $config): int
    {
        for ($row = 1; $row <= min(5, $sheet->getHighestDataRow()); $row++) {
            $first = $this->normalizeText((string) $this->cellValue($sheet, 1, $row));
            $second = $this->normalizeHeader((string) $this->cellValue($sheet, 2, $row));
            $third = $this->normalizeHeader((string) $this->cellValue($sheet, 3, $row));
            $fourth = $this->normalizeHeader((string) $this->cellValue($sheet, 4, $row));

            $firstMatches = collect($config['first_header_terms'])->contains(fn (string $term) => str_contains($first, $term));

            if ($firstMatches && $second === '1 52 sem' && $third === '2 12 sem' && $fourth === '3 4 sem') {
                return $row;
            }
        }

        throw new \RuntimeException("Hoja {$sheet->getTitle()}: encabezados invalidos. Se esperan columnas A-D: {$config['label']}/1 - 52 SEM/2 - 12 SEM/3 - 4 SEM.");
    }

    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $coordinate = Coordinate::stringFromColumnIndex($column).$row;

        return $sheet->getCell($coordinate)->getCalculatedValue();
    }

    private function normalizeText(string $value): string
    {
        return Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function normalizeHeader(string $value): string
    {
        return $this->normalizeText($value);
    }

    private function periodLabel(string $field): string
    {
        return match ($field) {
            'value_52_weeks' => '1 - 52 SEM',
            'value_12_weeks' => '2 - 12 SEM',
            'value_4_weeks' => '3 - 4 SEM',
            default => $field,
        };
    }
}
