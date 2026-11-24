<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class SimpleSpreadsheetService
{
    /**
     * Netralkan formula injection (CWE-1236).
     */
    public static function sanitizeCell(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $chars = ['=', '+', '-', '@', "\t", "\r"];
        if (in_array($value[0], $chars, true)) {
            return "'".$value;
        }

        return $value;
    }

    /**
     * Konversi nilai akhir numerik ke huruf mutu.
     */
    public static function getGradeLetter(float $score): string
    {
        return match (true) {
            $score >= 85 => 'A',
            $score >= 80 => 'AB',
            $score >= 70 => 'B',
            $score >= 65 => 'BC',
            $score >= 55 => 'C',
            $score >= 40 => 'D',
            default => 'E',
        };
    }

    /**
     * Stream unduhan berkas CSV (UTF-8 BOM).
     */
    public static function streamCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, array_map([self::class, 'sanitizeCell'], $headers));

            foreach ($rows as $row) {
                fputcsv($handle, array_map([self::class, 'sanitizeCell'], $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Stream unduhan berkas XLSX berbasis OpenXML SpreadsheetML.
     */
    public static function streamXlsx(array $headers, array $rows, string $filename): StreamedResponse
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        $allData = array_merge([$headers], $rows);
        $rowIndex = 1;
        foreach ($allData as $row) {
            $sheetXml .= '<row r="'.$rowIndex.'">';
            $colIndex = 0;
            foreach ($row as $cellVal) {
                $cellVal = self::sanitizeCell((string) $cellVal);
                $colLetter = self::columnLetter($colIndex + 1);
                $escaped = htmlspecialchars($cellVal, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $sheetXml .= '<c r="'.$colLetter.$rowIndex.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
                $colIndex++;
            }
            $sheetXml .= '</row>';
            $rowIndex++;
        }

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return response()->streamDownload(function () use ($tempFile) {
            $stream = fopen($tempFile, 'rb');
            fpassthru($stream);
            fclose($stream);
            @unlink($tempFile);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Membaca berkas CSV atau XLSX menjadi array baris.
     */
    public static function readSpreadsheet(string $filePath, ?string $originalExtension = null): array
    {
        $ext = strtolower($originalExtension ?: pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return self::readXlsx($filePath);
        }

        return self::readCsv($filePath);
    }

    /**
     * Membaca berkas CSV.
     */
    public static function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (! $handle) {
            return [];
        }

        $rows = [];
        $first = true;
        while (($row = fgetcsv($handle)) !== false) {
            if ($first && ! empty($row)) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                $first = false;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Membaca berkas XLSX OpenXML.
     */
    public static function readXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [];
        }

        // Shared strings bila ada
        $sharedStrings = [];
        $ssIndex = $zip->locateName('xl/sharedStrings.xml');
        if ($ssIndex !== false) {
            $ssXml = $zip->getFromIndex($ssIndex);
            $xmlObj = simplexml_load_string($ssXml);
            if ($xmlObj && isset($xmlObj->si)) {
                foreach ($xmlObj->si as $val) {
                    $sharedStrings[] = (string) ($val->t ?? ($val->r ? $val->r->t : ''));
                }
            }
        }

        // Sheet 1
        $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if (! $sheetContent) {
            return [];
        }

        $xml = simplexml_load_string($sheetContent);
        if (! $xml || ! isset($xml->sheetData->row)) {
            return [];
        }

        $result = [];
        foreach ($xml->sheetData->row as $row) {
            $rowValues = [];
            foreach ($row->c as $cell) {
                $type = (string) ($cell['t'] ?? '');
                $val = '';

                if ($type === 's') {
                    $idx = (int) $cell->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) ($cell->is->t ?? '');
                } else {
                    $val = (string) ($cell->v ?? '');
                }

                $rowValues[] = $val;
            }
            $result[] = $rowValues;
        }

        return $result;
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo).$letter;
            $index = (int) (($index - $modulo) / 26);
        }

        return $letter;
    }
}

