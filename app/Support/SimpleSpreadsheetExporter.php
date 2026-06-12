<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SimpleSpreadsheetExporter
{
    /**
     * @param  array<int, string>  $dataHeaders
     * @param  array<int, string>  $instructionHeaders
     * @param  array<int, array<int, string>>  $instructionRows
     * @param  array<int, float>|null  $dataColumnWidths
     * @param  array<int, float>|null  $instructionColumnWidths
     */
    public function streamTwoSheet(
        string $filename,
        string $dataSheetName,
        array $dataHeaders,
        string $instructionSheetName,
        array $instructionHeaders,
        array $instructionRows,
        int $emptyDataRows = 200,
        ?array $dataColumnWidths = null,
        ?array $instructionColumnWidths = null,
    ): StreamedResponse {
        $dataRows = array_fill(0, max(0, $emptyDataRows), array_fill(0, count($dataHeaders), ''));

        return response()->streamDownload(function () use (
            $dataSheetName,
            $dataHeaders,
            $dataRows,
            $instructionSheetName,
            $instructionHeaders,
            $instructionRows,
            $dataColumnWidths,
            $instructionColumnWidths,
        ) {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'spreadsheet-export-');
            $zip = new ZipArchive;
            $zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelsXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml($dataSheetName, $instructionSheetName));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                $this->worksheetXml($dataHeaders, $dataRows, $dataColumnWidths),
            );
            $zip->addFromString(
                'xl/worksheets/sheet2.xml',
                $this->worksheetXml(
                    $instructionHeaders,
                    $instructionRows,
                    $instructionColumnWidths,
                    freezeHeader: true,
                    autoFilter: false,
                ),
            );
            $zip->close();

            $handle = fopen($temporaryPath, 'rb');
            fpassthru($handle);
            fclose($handle);
            @unlink($temporaryPath);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(string $dataSheetName, string $instructionSheetName): string
    {
        $dataSheetName = $this->escapeXml($dataSheetName);
        $instructionSheetName = $this->escapeXml($instructionSheetName);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="'.$dataSheetName.'" sheetId="1" r:id="rId1"/>'
            .'<sheet name="'.$instructionSheetName.'" sheetId="2" r:id="rId3"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>
</Relationships>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="2">
        <font>
            <sz val="11"/>
            <name val="Aptos"/>
            <family val="2"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <color rgb="FFFFFFFF"/>
            <name val="Aptos"/>
            <family val="2"/>
        </font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FF162457"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
    </fills>
    <borders count="1">
        <border>
            <left/><right/><top/><bottom/><diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">
            <alignment horizontal="center" vertical="center"/>
        </xf>
    </cellXfs>
</styleSheet>
XML;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>|array{values: array<int, string>, style?: int}>  $rows
     * @param  array<int, float>|null  $columnWidths
     */
    private function worksheetXml(
        array $headers,
        array $rows,
        ?array $columnWidths = null,
        bool $freezeHeader = true,
        bool $autoFilter = true,
    ): string {
        $allRows = array_merge([$headers], $rows);
        $columnWidths = $columnWidths ?? array_fill(0, count($headers), 18.0);
        $lastColumn = $this->columnName(count($headers));
        $sheetRows = [];

        foreach ($allRows as $rowIndex => $row) {
            $cells = [];
            if ($rowIndex === 0) {
                $styleIndex = 1;
            } else {
                $styleIndex = is_array($row) && array_key_exists('style', $row)
                    ? ($row['style'] ?? 0)
                    : 0;
                $row = is_array($row) && array_key_exists('values', $row) ? $row['values'] : $row;
            }
            $style = ' s="'.$styleIndex.'"';

            foreach ($row as $columnIndex => $value) {
                $reference = $this->columnName($columnIndex + 1).($rowIndex + 1);
                $escaped = $this->escapeXml((string) ($value ?? ''));
                $cells[] = '<c r="'.$reference.'" t="inlineStr"'.$style.'><is><t>'.$escaped.'</t></is></c>';
            }

            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        $columnsXml = [];
        foreach ($columnWidths as $index => $width) {
            $column = $index + 1;
            $columnsXml[] = '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>';
        }

        $sheetView = $freezeHeader
            ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';

        $autoFilterXml = $autoFilter
            ? '<autoFilter ref="A1:'.$lastColumn.'1"/>'
            : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .$sheetView
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.implode('', $columnsXml).'</cols>'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData>'
            .$autoFilterXml
            .'</worksheet>';
    }

    private function columnName(int $columnIndex): string
    {
        $name = '';

        while ($columnIndex > 0) {
            $columnIndex--;
            $name = chr(65 + ($columnIndex % 26)).$name;
            $columnIndex = intdiv($columnIndex, 26);
        }

        return $name;
    }

    private function escapeXml(string $value): string
    {
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return htmlspecialchars($clean, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
