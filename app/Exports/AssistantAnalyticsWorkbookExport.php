<?php

namespace App\Exports;

use App\Exports\Sheets\AssistantAnalyticsArraySheet;
use App\Exports\Sheets\AssistantAnalyticsDashboardSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssistantAnalyticsWorkbookExport implements WithMultipleSheets
{
    /**
     * @param  array<int, array{
     *     title: string,
     *     headings: array<int, string>,
     *     rows: array<int, array<int, mixed>>,
     *     tone?: string
     * }>  $sheets
     */
    public function __construct(
        private readonly array $sheets,
        private readonly array $dashboard = []
    ) {}

    /**
     * @return array<int, AssistantAnalyticsArraySheet|AssistantAnalyticsDashboardSheet>
     */
    public function sheets(): array
    {
        $sheets = collect($this->sheets)
            ->map(fn (array $sheet): AssistantAnalyticsArraySheet => new AssistantAnalyticsArraySheet(
                (string) $sheet['title'],
                (array) $sheet['headings'],
                (array) $sheet['rows'],
                (string) ($sheet['tone'] ?? 'default')
            ))
            ->values();

        if ($this->dashboard !== []) {
            $sheets->prepend(new AssistantAnalyticsDashboardSheet($this->dashboard));
        }

        return $sheets->values()->all();
    }
}
