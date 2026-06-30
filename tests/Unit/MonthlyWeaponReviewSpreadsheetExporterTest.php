<?php

namespace Tests\Unit;

use App\Services\Formats\MonthlyWeaponReviewQueryService;
use App\Services\Formats\MonthlyWeaponReviewRowMapper;
use App\Services\Formats\MonthlyWeaponReviewSpreadsheetExporter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class MonthlyWeaponReviewSpreadsheetExporterTest extends TestCase
{
    public function test_empty_export_uses_official_template_layout(): void
    {
        $exporter = new MonthlyWeaponReviewSpreadsheetExporter;
        $response = $exporter->stream('revista-mensual-armamento-vacio.xlsx');

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'monthly-review-empty-');
        file_put_contents($temporaryPath, $binary);

        $spreadsheet = IOFactory::load($temporaryPath);
        $sheet = $spreadsheet->getSheet(0);
        @unlink($temporaryPath);

        $this->assertSame('FO-OP-03', $sheet->getTitle());
        $this->assertSame('FO-OP-03', $sheet->getCell('Q1')->getValue());
        $this->assertStringContainsString('DEL', (string) $sheet->getCell('Q2')->getValue());
        $this->assertSame('Pagina: 1 de 1', $sheet->getCell('Q4')->getValue());
        $this->assertSame('REVISTA MENSUAL DE ARMAMENTO', $sheet->getCell('D1')->getValue());
        $this->assertSame('DISPOSITIVO', $sheet->getCell('B5')->getValue());
        $this->assertSame('DEPARTAMENTO', $sheet->getCell('F5')->getValue());
        $this->assertSame(1, $sheet->getCell('A7')->getValue());
        $this->assertSame(20, $sheet->getCell('A26')->getValue());
        $this->assertSame('', (string) $sheet->getCell('B7')->getValue());
    }

    public function test_paged_export_splits_more_than_twenty_weapons_into_multiple_sheets(): void
    {
        $exporter = new MonthlyWeaponReviewSpreadsheetExporter;
        $rows = collect(range(1, 25))->map(fn (int $number) => [
            (string) $number,
            'Puesto '.$number,
            '',
            '',
            '',
            'Pistola',
            'SER-'.$number,
            '',
            '9MM',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
        ]);

        $pages = collect([
            $rows->take(MonthlyWeaponReviewQueryService::ROWS_PER_PAGE)->values(),
            $rows->slice(MonthlyWeaponReviewQueryService::ROWS_PER_PAGE)->values(),
        ]);

        $response = $exporter->stream('revista-mensual-armamento.xlsx', $pages);
        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $temporaryPath = tempnam(sys_get_temp_dir(), 'monthly-review-paged-');
        file_put_contents($temporaryPath, $binary);

        $spreadsheet = IOFactory::load($temporaryPath);
        @unlink($temporaryPath);

        $this->assertSame(2, $spreadsheet->getSheetCount());
        $this->assertSame('Pagina: 1 de 2', $spreadsheet->getSheet(0)->getCell('Q4')->getValue());
        $this->assertSame('Pagina: 2 de 2', $spreadsheet->getSheet(1)->getCell('Q4')->getValue());
        $this->assertSame('SER-1', $spreadsheet->getSheet(0)->getCell('G7')->getValue());
        $this->assertSame('SER-21', $spreadsheet->getSheet(1)->getCell('G7')->getValue());
        $this->assertSame(21, $spreadsheet->getSheet(1)->getCell('A7')->getValue());
    }
}
