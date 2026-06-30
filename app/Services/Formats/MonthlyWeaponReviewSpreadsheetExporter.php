<?php

namespace App\Services\Formats;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyWeaponReviewSpreadsheetExporter
{
    public const FORMAT_CODE = 'FO-OP-03';

    public const FORMAT_NAME = 'Revista mensual de armamento';

    public const TEMPLATE_PATH = 'resources/templates/Revista_mensual_armamento.xlsx';

    public const DATA_START_ROW = 7;

    public const DATA_END_ROW = 26;

    public const ROWS_PER_PAGE = 20;

    private const PERIOD_CELL = 'Q2';

    private const PAGE_CELL = 'Q4';

    /**
     * @var list<string>
     */
    private const DATA_COLUMNS = [
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
    ];

    public static function downloadFilename(): string
    {
        return self::FORMAT_CODE.' '.self::FORMAT_NAME.'.xlsx';
    }

    /**
     * @param  Collection<int, Collection<int, array<int, string>>>|null  $pages
     */
    public function stream(string $filename, ?Collection $pages = null, ?Carbon $period = null): StreamedResponse
    {
        $period ??= now();
        $pages ??= collect([collect()]);
        $pages = $pages->isEmpty() ? collect([collect()]) : $pages;
        $totalPages = $pages->count();

        $spreadsheet = $this->loadTemplate();
        $templateSheet = $spreadsheet->getSheet(0);

        for ($index = 1; $index < $totalPages; $index++) {
            $clonedSheet = clone $templateSheet;
            $clonedSheet->setTitle($this->pageSheetTitle($index + 1));
            $spreadsheet->addSheet($clonedSheet);
        }

        foreach ($pages->values() as $pageIndex => $rows) {
            $sheet = $spreadsheet->getSheet($pageIndex);
            if ($pageIndex > 0) {
                $sheet->setTitle($this->pageSheetTitle($pageIndex + 1));
            }

            $this->fillSheet($sheet, $rows, $pageIndex + 1, $totalPages, $period);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'monthly-review-');
            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($temporaryPath);

            $handle = fopen($temporaryPath, 'rb');
            fpassthru($handle);
            fclose($handle);
            @unlink($temporaryPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, Weapon>  $weapons
     */
    public function pagesFromWeapons(Collection $weapons, MonthlyWeaponReviewRowMapper $mapper, ?Carbon $period = null): Collection
    {
        $period ??= now();

        if ($weapons->isEmpty()) {
            return collect([collect()]);
        }

        return $weapons
            ->values()
            ->chunk(self::ROWS_PER_PAGE)
            ->map(function (Collection $chunk, int $pageIndex) use ($mapper, $period) {
                return $chunk
                    ->values()
                    ->map(fn ($weapon, int $index) => $mapper->map(
                        $weapon,
                        ($pageIndex * self::ROWS_PER_PAGE) + $index + 1,
                        $period,
                    ));
            });
    }

    private function loadTemplate(): Spreadsheet
    {
        $path = base_path(self::TEMPLATE_PATH);
        if (! is_file($path)) {
            throw new RuntimeException('Plantilla de revista mensual no encontrada.');
        }

        return IOFactory::load($path);
    }

    /**
     * @param  Collection<int, array<int, string>>  $rows
     */
    private function fillSheet(Worksheet $sheet, Collection $rows, int $pageNumber, int $totalPages, Carbon $period): void
    {
        if ($sheet->getProtection()->getSheet()) {
            $sheet->getProtection()->setSheet(false);
        }

        $sheet->setCellValue(self::PERIOD_CELL, $this->periodLabel($period));
        $sheet->setCellValue(self::PAGE_CELL, 'Pagina: '.$pageNumber.' de '.$totalPages);

        for ($row = self::DATA_START_ROW; $row <= self::DATA_END_ROW; $row++) {
            $itemNumber = ($row - self::DATA_START_ROW + 1) + (($pageNumber - 1) * self::ROWS_PER_PAGE);

            foreach (self::DATA_COLUMNS as $column) {
                $sheet->setCellValue($column.$row, $column === 'A' ? $itemNumber : '');
            }
        }

        foreach ($rows->values() as $offset => $values) {
            $row = self::DATA_START_ROW + $offset;
            if ($row > self::DATA_END_ROW) {
                break;
            }

            foreach ($values as $columnIndex => $value) {
                $column = self::DATA_COLUMNS[$columnIndex] ?? null;
                if ($column === null) {
                    continue;
                }

                $sheet->setCellValue($column.$row, $value);
            }
        }
    }

    private function periodLabel(Carbon $period): string
    {
        $months = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
            5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
            9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        $month = $months[(int) $period->format('n')] ?? strtoupper($period->format('F'));

        return $month.' DEL '.$period->format('Y');
    }

    private function pageSheetTitle(int $pageNumber): string
    {
        return $pageNumber === 1 ? 'FO-OP-03' : 'FO-OP-03 ('.$pageNumber.')';
    }
}
