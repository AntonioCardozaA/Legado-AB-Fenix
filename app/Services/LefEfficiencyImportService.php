<?php

namespace App\Services;

use App\Models\LefEfficiencyEntry;
use App\Models\LefEfficiencyImport;
use App\Models\Linea;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LefEfficiencyImportService
{
    private const BLOCK_START_COLUMNS = [1, 4, 7, 10, 13, 16, 19];

    public function import(UploadedFile $file, ?User $user): array
    {
        $this->validateExtension($file);

        try {
            $reader = IOFactory::createReaderForFile($file->getRealPath());
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file->getRealPath());
        } catch (\Throwable) {
            throw new \RuntimeException('No se pudo leer el archivo Excel. Verifica que no este corrupto y que sea .xls o .xlsx.');
        }

        $lineas = Linea::query()
            ->where('activo', true)
            ->where('nombre', 'like', 'L-%')
            ->get(['id', 'nombre']);
        $rows = collect();
        $periods = collect();

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $sheetRows = $this->parseSheet($sheet, $lineas);
            $rows = $rows->concat($sheetRows);
            $periods = $periods->merge($sheetRows->pluck('data_date'));
        }

        if ($rows->isEmpty()) {
            throw new \RuntimeException('No se encontraron bloques de eficiencia con la estructura esperada. Se esperan hojas mensuales con columnas MAQUINA y valor por linea.');
        }

        return DB::transaction(function () use ($file, $user, $rows, $periods): array {
            $import = LefEfficiencyImport::create([
                'source_filename' => $file->getClientOriginalName(),
                'periods_count' => $periods->unique()->count(),
                'rows_count' => $rows->count(),
                'imported_by' => $user?->id,
            ]);

            $periodKeys = $rows
                ->map(fn (array $row): string => $row['linea_id'].'|'.$row['data_date'])
                ->unique()
                ->values();

            $periodKeys->each(function (string $periodKey): void {
                [$lineaId, $dataDate] = explode('|', $periodKey, 2);

                LefEfficiencyEntry::query()
                    ->where('linea_id', (int) $lineaId)
                    ->whereDate('data_date', $dataDate)
                    ->delete();
            });

            $rows->each(fn (array $row) => $import->entries()->create($row));

            return [
                'processed' => $rows->count(),
                'periods' => $periods->unique()->count(),
                'filename' => $file->getClientOriginalName(),
            ];
        });
    }

    private function parseSheet(Worksheet $sheet, Collection $lineas): Collection
    {
        $rows = collect();
        $highestRow = $sheet->getHighestDataRow();

        foreach (self::BLOCK_START_COLUMNS as $startColumn) {
            $title = trim((string) $this->cellValue($sheet, $startColumn, 1));
            $machineHeader = $this->normalizeText((string) $this->cellValue($sheet, $startColumn, 2));
            $date = $this->dateValue($this->cellValue($sheet, $startColumn + 1, 2));

            if ($title === '' && $machineHeader === '' && $date === null) {
                continue;
            }

            if ($machineHeader !== 'maquina' || !$date) {
                throw new \RuntimeException("Hoja {$sheet->getTitle()}: el bloque de columnas {$startColumn}-".($startColumn + 1).' no tiene encabezados MAQUINA y fecha validos.');
            }

            $linea = $this->resolveLinea($lineas, $title);
            if (!$linea) {
                throw new \RuntimeException("Hoja {$sheet->getTitle()}: no se pudo identificar la linea en el titulo \"{$title}\".");
            }

            for ($rowNumber = 3; $rowNumber <= $highestRow; $rowNumber++) {
                $machineName = trim((string) $this->cellValue($sheet, $startColumn, $rowNumber));
                $rawValue = $this->cellValue($sheet, $startColumn + 1, $rowNumber);

                if ($machineName === '' && ($rawValue === null || trim((string) $rawValue) === '')) {
                    continue;
                }

                if ($machineName === '') {
                    throw new \RuntimeException("Hoja {$sheet->getTitle()}, fila {$rowNumber}: falta el nombre del equipo.");
                }

                $value = $this->numberValue($rawValue);
                if ($value === null || $value < 0) {
                    throw new \RuntimeException("Hoja {$sheet->getTitle()}, fila {$rowNumber}: el valor de LEF para \"{$machineName}\" no es numerico o es negativo.");
                }

                $rows->push([
                    'linea_id' => $linea->id,
                    'data_date' => $date->toDateString(),
                    'machine_name' => $machineName,
                    'value' => $value,
                    'source_sheet' => $sheet->getTitle(),
                    'source_row' => $rowNumber,
                    'source_column' => $startColumn,
                ]);
            }
        }

        return $rows;
    }

    private function resolveLinea(Collection $lineas, string $title): ?Linea
    {
        preg_match('/linea\s*[- ]?\s*0?(\d+)/i', Str::ascii($title), $matches);
        if (!isset($matches[1])) {
            return null;
        }

        $number = (int) $matches[1];

        return $lineas->first(function (Linea $linea) use ($number): bool {
            preg_match('/(\d+)/', $linea->nombre, $matches);

            return isset($matches[1]) && (int) $matches[1] === $number;
        });
    }

    private function dateValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        if (preg_match('/(20\d{2})\s*[-\/]\s*(\d{1,2})/', (string) $value, $matches)) {
            return Carbon::create((int) $matches[1], (int) $matches[2], 1)->startOfDay();
        }

        return null;
    }

    private function numberValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = trim(str_replace(["\u{00A0}", ' ', '%'], '', (string) $value));
        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '.', $normalized);
        }

        return is_numeric($normalized) ? round((float) $normalized, 10) : null;
    }

    private function cellValue(Worksheet $sheet, int $column, int $row): mixed
    {
        $coordinate = Coordinate::stringFromColumnIndex($column).$row;

        return $sheet->getCell($coordinate)->getCalculatedValue();
    }

    private function validateExtension(UploadedFile $file): void
    {
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['xls', 'xlsx'], true)) {
            throw new \RuntimeException('El archivo debe tener extension .xls o .xlsx.');
        }

        if (strtolower($file->getClientOriginalExtension()) === 'xlsx' && !extension_loaded('zip')) {
            throw new \RuntimeException('El servidor PHP no tiene habilitada la extension zip, necesaria para leer archivos .xlsx.');
        }
    }

    private function normalizeText(string $value): string
    {
        return Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
