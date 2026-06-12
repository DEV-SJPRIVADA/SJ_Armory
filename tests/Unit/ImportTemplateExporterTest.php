<?php

namespace Tests\Unit;

use App\Services\Imports\ClientImportProcessor;
use App\Services\Imports\ImportTemplateExporter;
use App\Services\Imports\WeaponImportProcessor;
use App\Support\SimpleSpreadsheetExporter;
use Tests\TestCase;
use ZipArchive;

class ImportTemplateExporterTest extends TestCase
{
    public function test_weapon_template_contains_expected_sheets_and_headers(): void
    {
        $exporter = new ImportTemplateExporter(new SimpleSpreadsheetExporter);
        $response = $exporter->streamWeaponTemplate();

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $this->assertNotFalse($binary);
        $this->assertStringStartsWith('PK', $binary);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'weapon-template-test-');
        file_put_contents($temporaryPath, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryPath) === true);

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $instructionXml = $zip->getFromName('xl/worksheets/sheet2.xml');
        $zip->close();
        @unlink($temporaryPath);

        $this->assertIsString($workbookXml);
        $this->assertStringContainsString('name="Datos"', $workbookXml);
        $this->assertStringContainsString('name="Instructivo"', $workbookXml);

        $this->assertIsString($sheetXml);
        foreach (WeaponImportProcessor::templateHeaders() as $header) {
            $this->assertStringContainsString($header, $sheetXml);
        }

        $this->assertIsString($instructionXml);
        $this->assertStringContainsString('Columna', $instructionXml);
        $this->assertStringContainsString('Clave principal del lote', $instructionXml);
    }

    public function test_client_template_contains_expected_sheets_and_headers(): void
    {
        $exporter = new ImportTemplateExporter(new SimpleSpreadsheetExporter);
        $response = $exporter->streamClientTemplate();

        ob_start();
        $response->sendContent();
        $binary = ob_get_clean();

        $this->assertNotFalse($binary);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'client-template-test-');
        file_put_contents($temporaryPath, $binary);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($temporaryPath) === true);

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($temporaryPath);

        $this->assertIsString($sheetXml);
        foreach (ClientImportProcessor::templateHeaders() as $header) {
            $this->assertStringContainsString($header, $sheetXml);
        }
    }
}
