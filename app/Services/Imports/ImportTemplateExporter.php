<?php

namespace App\Services\Imports;

use App\Support\SimpleSpreadsheetExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportTemplateExporter
{
    public function __construct(private readonly SimpleSpreadsheetExporter $spreadsheetExporter)
    {
    }

    public function streamWeaponTemplate(): StreamedResponse
    {
        return $this->spreadsheetExporter->streamTwoSheet(
            filename: 'formato-carga-armas.xlsx',
            dataSheetName: 'Datos',
            dataHeaders: WeaponImportProcessor::templateHeaders(),
            instructionSheetName: 'Instructivo',
            instructionHeaders: self::instructionHeaders(),
            instructionRows: WeaponImportProcessor::templateInstructions(),
            dataColumnWidths: [18, 18, 16, 12, 12, 14, 16, 32],
            instructionColumnWidths: [32, 12, 14, 72],
        );
    }

    public function streamClientTemplate(): StreamedResponse
    {
        return $this->spreadsheetExporter->streamTwoSheet(
            filename: 'formato-carga-clientes.xlsx',
            dataSheetName: 'Datos',
            dataHeaders: ClientImportProcessor::templateHeaders(),
            instructionSheetName: 'Instructivo',
            instructionHeaders: self::instructionHeaders(),
            instructionRows: ClientImportProcessor::templateInstructions(),
            dataColumnWidths: [18, 32, 28, 36, 18],
            instructionColumnWidths: [32, 12, 14, 72],
        );
    }

    /**
     * @return array<int, string>
     */
    private static function instructionHeaders(): array
    {
        return ['Columna', 'Obligatorio', 'Formato', 'Descripción'];
    }
}
