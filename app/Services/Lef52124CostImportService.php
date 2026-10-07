<?php

namespace App\Services;

use App\Models\Lef52124CostEntry;
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

class Lef52124CostImportService
{
    private const REQUIRED_HEADERS = [
        'orden' => ['orden', 'orden'],
        'object_description' => ['denominacion del objeto', 'denominación del objeto'],
        'quantity' => ['ctd.reg.', 'ctd reg', 'cantidad'],
        'amount' => ['val./mi', 'val/mi', 'valor'],
        'accounting_date' => ['fe.contab.', 'fe contab', 'fecha contable'],
    ];

    public function import(UploadedFile $file, ?Linea $fallbackLinea, ?User $user): array
    {
        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->getRealPath());
        $lineas = Linea::query()
            ->where('activo', true)
            ->where('nombre', 'like', 'L-%')
            ->get(['id', 'nombre']);
        $now = now();
        $rows = collect();

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $headerMap = $this->headerMap($sheet);
            $missing = $this->missingRequiredHeaders($headerMap);

            if ($missing !== []) {
                continue;
            }

            $sheetYear = $this->yearFromSheetTitle($sheet->getTitle());
            $highestRow = $sheet->getHighestRow();

            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                $amount = $this->numberValue($this->cell($sheet, $headerMap['amount'], $rowNumber));
                $accountingDate = $this->dateValue($this->cell($sheet, $headerMap['accounting_date'], $rowNumber));

                if ($amount === null || !$accountingDate) {
                    continue;
                }

                $description = $this->textValue($this->cell($sheet, $headerMap['object_description'], $rowNumber));
                $equipment = $this->textValue($this->optionalCell($sheet, $headerMap, 'equipment', $rowNumber));
                $technicalDescription = $this->textValue($this->optionalCell($sheet, $headerMap, 'technical_description', $rowNumber));
                $technicalLocation = $this->textValue($this->optionalCell($sheet, $headerMap, 'technical_location', $rowNumber));
                $materialText = $this->textValue($this->optionalCell($sheet, $headerMap, 'material_text', $rowNumber));
                $orderText = $this->textValue($this->optionalCell($sheet, $headerMap, 'order_text', $rowNumber));
                // En el Excel la lavadora no necesariamente aparece en la
                // descripción de la orden. La fuente correcta es la
                // denominación del objeto técnico (columna AB), seguida del
                // equipo (columna AA).
                $lineaId = $this->resolveLineaId(
                    $lineas,
                    implode(' ', array_filter([$technicalLocation, $technicalDescription, $equipment, $description, $materialText, $orderText]))
                ) ?? $fallbackLinea?->id;
                $year = $sheetYear ?: (int) $accountingDate->year;
                $syncParts = [
                    $file->getClientOriginalName(),
                    $sheet->getTitle(),
                    $rowNumber,
                    $this->textValue($this->cell($sheet, $headerMap['orden'], $rowNumber)),
                    $this->textValue($this->optionalCell($sheet, $headerMap, 'material', $rowNumber)),
                    $accountingDate->toDateString(),
                    number_format($amount, 2, '.', ''),
                ];

                $rows->push([
                    'linea_id' => $lineaId,
                    'year' => $year,
                    'month' => (int) $accountingDate->month,
                    'accounting_date' => $accountingDate->toDateString(),
                    'order_number' => $this->textValue($this->cell($sheet, $headerMap['orden'], $rowNumber)),
                    'object_description' => $description,
                    'material' => $this->textValue($this->optionalCell($sheet, $headerMap, 'material', $rowNumber)),
                    'material_text' => $materialText,
                    'quantity' => $this->numberValue($this->optionalCell($sheet, $headerMap, 'quantity', $rowNumber)) ?? 0,
                    'unit' => $this->textValue($this->optionalCell($sheet, $headerMap, 'unit', $rowNumber)),
                    'amount' => $amount,
                    'cost_class' => $this->textValue($this->optionalCell($sheet, $headerMap, 'cost_class', $rowNumber)),
                    'purchase_doc' => $this->textValue($this->optionalCell($sheet, $headerMap, 'purchase_doc', $rowNumber)),
                    'order_text' => $orderText,
                    'source_filename' => $file->getClientOriginalName(),
                    'source_sheet' => $sheet->getTitle(),
                    'source_row' => $rowNumber,
                    'imported_by' => $user?->id,
                    'sync_key' => sha1(implode('|', $syncParts)),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($rows->isEmpty()) {
            throw new \RuntimeException('No se encontraron renglones de costos con los encabezados esperados del Excel.');
        }

        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($rows, &$inserted, &$updated): void {
            $rows->chunk(250)->each(function (Collection $chunk) use (&$inserted, &$updated): void {
                foreach ($chunk as $row) {
                    $entry = Lef52124CostEntry::query()->updateOrCreate(
                        ['sync_key' => $row['sync_key']],
                        $row
                    );

                    $entry->wasRecentlyCreated ? $inserted++ : $updated++;
                }
            });
        });

        return [
            'processed' => $rows->count(),
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    private function headerMap($sheet): array
    {
        $map = [];
        $maxColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($column = 1; $column <= $maxColumn; $column++) {
            $header = $this->normalizeHeader((string) $sheet->getCellByColumnAndRow($column, 1)->getValue());

            foreach ([
                'orden' => ['orden'],
                'object_description' => ['denominacion del objeto', 'denominación del objeto'],
                'material' => ['material'],
                'material_text' => ['texto breve de material'],
                'quantity' => ['ctd.reg.', 'ctd reg', 'cantidad'],
                'unit' => ['ucc', 'unidad'],
                'amount' => ['val./mi', 'val/mi', 'valor'],
                'accounting_date' => ['fe.contab.', 'fe contab', 'fecha contable'],
                'cost_class' => ['cl.coste', 'cl coste', 'clase coste'],
                'purchase_doc' => ['doc.compr.', 'doc compr'],
                'order_text' => ['texto de pedido'],
                'equipment' => ['equipo'],
                'technical_description' => ['denominacion de objeto tecnico', 'denominaciÃ³n de objeto tÃ©cnico'],
                'technical_location' => ['denom.ubic.tecnica', 'denom ubic tecnica', 'denominacion ubicacion tecnica'],
            ] as $key => $aliases) {
                if (in_array($header, array_map(fn (string $alias) => $this->normalizeHeader($alias), $aliases), true)) {
                    $map[$key] = $column;
                }
            }
        }

        return $map;
    }

    private function missingRequiredHeaders(array $headerMap): array
    {
        return collect(array_keys(self::REQUIRED_HEADERS))
            ->reject(fn (string $key) => isset($headerMap[$key]))
            ->values()
            ->all();
    }

    private function cell($sheet, int $column, int $row): mixed
    {
        return $sheet->getCellByColumnAndRow($column, $row)->getValue();
    }

    private function optionalCell($sheet, array $headerMap, string $key, int $row): mixed
    {
        return isset($headerMap[$key]) ? $this->cell($sheet, $headerMap[$key], $row) : null;
    }

    private function textValue(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function numberValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([',', '$', ' '], ['', '', ''], (string) $value);

        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }

    private function dateValue(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function yearFromSheetTitle(string $title): ?int
    {
        preg_match('/20\d{2}/', $title, $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    private function resolveLineaId(Collection $lineas, string $text): ?int
    {
        $normalized = Str::of(Str::ascii($text))->upper()->toString();

        foreach ($lineas as $linea) {
            $number = ltrim(preg_replace('/\D+/', '', $linea->nombre) ?? '', '0');
            if ($number === '') {
                continue;
            }

            $patterns = [
                '/\bL\s*[-]?\s*0?'.$number.'\b/',
                '/\bLINEA\s*0?'.$number.'\b/',
                '/\bLAV\s*0?'.$number.'\b/',
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $normalized)) {
                    return $linea->id;
                }
            }
        }

        return null;
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of(Str::ascii($value))
            ->lower()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }
}
