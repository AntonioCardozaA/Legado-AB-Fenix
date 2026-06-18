<?php

namespace App\Exports;

use App\Models\AnalisisLavadora;
use App\Models\Elongacion;
use App\Models\Linea;
use App\Models\Paro;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReporteLavadoraGeneralExcel implements FromArray, WithEvents, WithColumnWidths, WithTitle
{
    protected Carbon $fechaInicio;
    protected Carbon $fechaFin;
    protected $lineaId;
    protected $analisis;
    protected $elongaciones;
    protected $paros;
    protected $lineas;
    protected string $platformName = 'Legado AB Fenix';

    protected array $lavadoras = ['L-04', 'L-05', 'L-06', 'L-07', 'L-08', 'L-09', 'L-12', 'L-13'];

    public function __construct($fechaInicio = null, $fechaFin = null, $lineaId = null)
    {
        $this->fechaInicio = $fechaInicio ? Carbon::parse($fechaInicio)->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $this->fechaFin = $fechaFin ? Carbon::parse($fechaFin)->endOfDay() : Carbon::now()->endOfDay();
        $this->lineaId = $lineaId;

        $this->lineas = Linea::whereIn('nombre', $this->lavadoras)
            ->when($this->lineaId, fn ($query) => $query->where('id', $this->lineaId))
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $lineaIds = $this->lineas->pluck('id');
        $lineaNombres = $this->lineas->pluck('nombre');

        $this->analisis = AnalisisLavadora::with(['linea:id,nombre', 'componente:id,nombre,codigo'])
            ->whereIn('linea_id', $lineaIds)
            ->whereBetween('fecha_analisis', [$this->fechaInicio, $this->fechaFin])
            ->orderByDesc('fecha_analisis')
            ->orderByDesc('created_at')
            ->get();

        $this->elongaciones = Elongacion::whereIn('linea', $lineaNombres)
            ->whereBetween('created_at', [$this->fechaInicio, $this->fechaFin])
            ->orderByDesc('created_at')
            ->get();

        $this->paros = Paro::with(['linea:id,nombre', 'supervisor:id,name', 'planesAccion'])
            ->whereIn('linea_id', $lineaIds)
            ->whereDate('fecha_inicio', '<=', $this->fechaFin)
            ->whereDate('fecha_fin', '>=', $this->fechaInicio)
            ->orderByDesc('fecha_inicio')
            ->get();
    }

    public function title(): string
    {
        return $this->lineaId ? 'Lavadora' : 'Lavadoras';
    }

    public function array(): array
    {
        $rows = [];
        $titulo = $this->lineaId && $this->lineas->first()
            ? 'REPORTE DE LAVADORA - ' . $this->lineas->first()->nombre
            : 'REPORTE GENERAL DE LAVADORAS';

        $rows[] = [$this->platformName];
        $rows[] = [$titulo];
        $rows[] = ['Periodo: ' . $this->fechaInicio->format('d/m/Y') . ' - ' . $this->fechaFin->format('d/m/Y')];
        $rows[] = ['Generado: ' . Carbon::now()->format('d/m/Y H:i:s')];
        $rows[] = [];

        $rows[] = ['RESUMEN POR LINEA'];
        $rows[] = ['LINEA', 'ANALISIS', 'PAROS', 'HORAS PARO', 'CRITICOS', 'REVISION', 'DESGASTE', 'ESTADO ACTUAL'];

        foreach ($this->lineas as $linea) {
            $analisisLinea = $this->analisis->where('linea_id', $linea->id);
            $parosLinea = $this->paros->where('linea_id', $linea->id);

            $rows[] = [
                $linea->nombre,
                $analisisLinea->count(),
                $parosLinea->count(),
                $parosLinea->sum(fn ($paro) => $this->calcularHorasParo($paro)),
                $analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoDanado($item->estado))->count(),
                $analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoRequiereRevision($item->estado))->count(),
                $analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoDesgaste($item->estado))->count(),
                $this->estadoGeneralLinea($analisisLinea),
            ];
        }

        $rows[] = [];
        $rows[] = ['ANALISIS DE COMPONENTES'];
        $rows[] = ['FECHA', 'LINEA', 'COMPONENTE', 'CODIGO', 'REDUCTOR', 'ESTADO', 'ORDEN', 'ACTIVIDAD'];

        foreach ($this->analisis as $analisis) {
            $rows[] = [
                $analisis->fecha_analisis?->format('d/m/Y') ?? 'N/A',
                $analisis->linea?->nombre ?? 'N/A',
                $analisis->componente?->nombre ?? 'N/A',
                $analisis->componente?->codigo ?? 'N/A',
                $analisis->reductor ?: 'N/A',
                $analisis->estado ?: 'N/A',
                $analisis->numero_orden ?: 'N/A',
                $analisis->actividad ?: '',
            ];
        }

        $rows[] = [];
        $rows[] = ['ELONGACIONES'];
        $rows[] = ['FECHA', 'LINEA', 'BOMBAS %', 'VAPOR %', 'HODOMETRO', 'ESTADO', 'PROVEEDOR', ''];

        foreach ($this->elongaciones as $elongacion) {
            $porcentajeMaximo = max((float) $elongacion->bombas_porcentaje, (float) $elongacion->vapor_porcentaje);

            $rows[] = [
                $elongacion->created_at?->format('d/m/Y') ?? 'N/A',
                $elongacion->linea,
                $elongacion->bombas_porcentaje ?? 0,
                $elongacion->vapor_porcentaje ?? 0,
                $elongacion->hodometro ?? 'N/A',
                $this->estadoElongacion($porcentajeMaximo),
                $elongacion->proveedor_actual ?? '',
                '',
            ];
        }

        $rows[] = [];
        $rows[] = ['PAROS DE MANTENIMIENTO'];
        $rows[] = ['LINEA', 'INICIO', 'FIN', 'TIPO', 'HORAS', 'SUPERVISOR', 'PLANES', 'ACCIONES'];

        foreach ($this->paros as $paro) {
            $acciones = $paro->planesAccion
                ->pluck('actividad')
                ->filter()
                ->implode('; ');

            $rows[] = [
                $paro->linea?->nombre ?? 'N/A',
                $paro->fecha_inicio?->format('d/m/Y') ?? 'N/A',
                $paro->fecha_fin?->format('d/m/Y') ?? 'N/A',
                $paro->tipo ?: 'N/A',
                $this->calcularHorasParo($paro),
                $paro->supervisor?->name ?? 'N/A',
                $paro->planesAccion->count(),
                $acciones,
            ];
        }

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 18,
            'C' => 24,
            'D' => 18,
            'E' => 18,
            'F' => 24,
            'G' => 22,
            'H' => 46,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');
                $sheet->mergeCells('A4:H4');
                $sheet->getRowDimension(1)->setRowHeight(34);
                $sheet->getRowDimension(2)->setRowHeight(24);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A3:A4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getStyle('A1:H' . $highestRow)->getAlignment()->setWrapText(true);
                $sheet->getStyle('A1:H' . $highestRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->freezePane('A7');
                $sheet->setAutoFilter('A7:H7');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getHeaderFooter()->setOddHeader('&L' . $this->platformName . '&RReporte de Lavadoras');
                $sheet->getHeaderFooter()->setOddFooter('&L' . $this->platformName . '&CDocumento generado automaticamente&RPagina &P de &N');

                if ($logoPath = $this->logoPath()) {
                    $drawing = new Drawing();
                    $drawing->setName($this->platformName);
                    $drawing->setDescription('Logo ' . $this->platformName);
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(28);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(8);
                    $drawing->setOffsetY(4);
                    $drawing->setWorksheet($sheet);
                }

                for ($row = 1; $row <= $highestRow; $row++) {
                    $value = (string) $sheet->getCell('A' . $row)->getValue();

                    if (in_array($value, ['RESUMEN POR LINEA', 'ANALISIS DE COMPONENTES', 'ELONGACIONES', 'PAROS DE MANTENIMIENTO'], true)) {
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                        ]);

                        $headerRow = $row + 1;
                        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }
                }

                $sheet->getStyle('A1:H' . $highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);
            },
        ];
    }

    private function estadoGeneralLinea($analisisLinea): string
    {
        if ($analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoDanado($item->estado))->isNotEmpty()) {
            return 'CRITICO';
        }

        if ($analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoDesgaste($item->estado))->isNotEmpty()) {
            return 'SEVERO / MODERADO';
        }

        if ($analisisLinea->filter(fn ($item) => AnalisisLavadora::esEstadoRequiereRevision($item->estado))->isNotEmpty()) {
            return 'REQUIERE REVISION';
        }

        return $analisisLinea->isEmpty() ? 'SIN DATOS' : 'ESTABLE';
    }

    private function estadoElongacion(float $porcentaje): string
    {
        if ($porcentaje >= Elongacion::LIMITE_CAMBIO) {
            return 'CRITICO';
        }

        if ($porcentaje >= Elongacion::LIMITE_COMPRAR) {
            return 'ATENCION';
        }

        return 'NORMAL';
    }

    private function calcularHorasParo(Paro $paro): int
    {
        if (!$paro->fecha_inicio || !$paro->fecha_fin) {
            return 0;
        }

        return (Carbon::parse($paro->fecha_inicio)->startOfDay()
            ->diffInDays(Carbon::parse($paro->fecha_fin)->startOfDay()) + 1) * 24;
    }

    private function logoPath(): ?string
    {
        foreach ([
            public_path('images/logo.png'),
            public_path('images/logoo.png'),
            public_path('images/icono-maquina.png'),
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
