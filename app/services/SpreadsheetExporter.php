<?php

declare(strict_types=1);

namespace App\services;

use RuntimeException;
use ZipArchive;

/** Produces a small, dependency-free XLSX workbook for image records. */
final class SpreadsheetExporter
{
    private const COLUMNS = [
        ['key' => 'id', 'label' => 'ID', 'width' => 8, 'numeric' => true],
        ['key' => 'original_name', 'label' => '原始檔名', 'width' => 28, 'numeric' => false],
        ['key' => 'stored_name', 'label' => '儲存檔名', 'width' => 31, 'numeric' => false],
        ['key' => 'created_at', 'label' => '建立時間', 'width' => 22, 'numeric' => false],
        ['key' => 'brightness_before_pct', 'label' => '增亮前亮度 (%)', 'width' => 18, 'numeric' => true],
        ['key' => 'brightness_after_pct', 'label' => '增亮後亮度 (%)', 'width' => 18, 'numeric' => true],
        ['key' => 'contrast_before_pct', 'label' => '增亮前對比 (%)', 'width' => 18, 'numeric' => true],
        ['key' => 'contrast_after_pct', 'label' => '增亮後對比 (%)', 'width' => 18, 'numeric' => true],
        ['key' => 'image_width_px', 'label' => '寬度 (px)', 'width' => 14, 'numeric' => true],
        ['key' => 'image_height_px', 'label' => '高度 (px)', 'width' => 14, 'numeric' => true],
        ['key' => 'original_size_kb', 'label' => '原圖大小 (KB)', 'width' => 18, 'numeric' => true],
        ['key' => 'enhanced_size_kb', 'label' => '增亮圖大小 (KB)', 'width' => 18, 'numeric' => true],
        ['key' => 'processing_ms', 'label' => '處理時間 (ms)', 'width' => 18, 'numeric' => true],
    ];

    /** @param array<int, array<string, mixed>> $rows */
    public function download(array $rows): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP Zip extension is required for XLSX export.');
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'lowlight-xlsx-');
        if ($temporaryPath === false) {
            throw new RuntimeException('Unable to create the XLSX temporary file.');
        }

        try {
            $this->writeWorkbook($temporaryPath, $rows);
            $filename = 'lowlight_records_' . date('Ymd_His') . '.xlsx';

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($temporaryPath));
            header('X-Content-Type-Options: nosniff');
            readfile($temporaryPath);
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
        exit;
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function writeWorkbook(string $path, array $rows): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the XLSX archive.');
        }

        try {
            $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $zip->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $zip->addFromString('docProps/app.xml', $this->appPropertiesXml());
            $zip->addFromString('docProps/core.xml', $this->corePropertiesXml());
            $zip->addFromString('xl/workbook.xml', $this->workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $zip->addFromString('xl/styles.xml', $this->stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows));
        } finally {
            $zip->close();
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function worksheetXml(array $rows): string
    {
        $columnDefinitions = '';
        foreach (self::COLUMNS as $index => $column) {
            $number = $index + 1;
            $columnDefinitions .= sprintf(
                '<col min="%d" max="%d" width="%s" customWidth="1"/>',
                $number,
                $number,
                $column['width']
            );
        }

        $sheetRows = '<row r="1" ht="22" customHeight="1">';
        foreach (self::COLUMNS as $index => $column) {
            $sheetRows .= $this->textCell($this->columnLetter($index + 1) . '1', $column['label'], 1);
        }
        $sheetRows .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 2;
            $sheetRows .= '<row r="' . $excelRow . '">';
            foreach (self::COLUMNS as $columnIndex => $column) {
                $reference = $this->columnLetter($columnIndex + 1) . $excelRow;
                $value = $row[$column['key']] ?? null;
                $sheetRows .= $column['numeric']
                    ? $this->numberCell($reference, $value)
                    : $this->textCell($reference, (string)($value ?? ''));
            }
            $sheetRows .= '</row>';
        }

        $lastRow = max(1, count($rows) + 1);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . '<cols>' . $columnDefinitions . '</cols>'
            . '<sheetData>' . $sheetRows . '</sheetData>'
            . '<autoFilter ref="A1:M' . $lastRow . '"/>'
            . '</worksheet>';
    }

    private function textCell(string $reference, string $value, int $style = 0): string
    {
        $safeValue = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';
        $escaped = htmlspecialchars($safeValue, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';
        return '<c r="' . $reference . '" t="inlineStr"' . $styleAttribute
            . '><is><t xml:space="preserve">' . $escaped . '</t></is></c>';
    }

    private function numberCell(string $reference, mixed $value): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '<c r="' . $reference . '"/>';
        }
        return '<c r="' . $reference . '" t="n"><v>' . (string)$value . '</v></c>';
    }

    private function columnLetter(int $number): string
    {
        $letter = '';
        while ($number > 0) {
            $number--;
            $letter = chr(65 + ($number % 26)) . $letter;
            $number = intdiv($number, 26);
        }
        return $letter;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="增亮紀錄" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1769E0"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><left/><right/><top/><bottom style="thin"><color rgb="FFDCE2EA"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function appPropertiesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Lowlight Demo</Application>'
            . '</Properties>';
    }

    private function corePropertiesXml(): string
    {
        $createdAt = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>低光影像增亮紀錄</dc:title><dc:creator>Lowlight Demo</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $createdAt . '</dcterms:created>'
            . '</cp:coreProperties>';
    }
}
