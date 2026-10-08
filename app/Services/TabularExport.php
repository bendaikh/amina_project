<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class TabularExport
{
    public function download(string $format, string $filename, array $document): Response
    {
        $format = strtolower($format);
        $safe = preg_replace('/[^A-Za-z0-9_\-]+/', '-', $filename) ?: 'export';

        return match ($format) {
            'pdf', 'print' => $this->pdf($safe, $document, $format === 'print'),
            'xlsx', 'excel' => $this->xlsx($safe, $document),
            default => $this->csv($safe, $document),
        };
    }

    private function csv(string $filename, array $document): Response
    {
        $lines = [];
        $lines[] = $this->csvLine([$document['company']['nom'] ?? '']);
        $this->companyLines($document, function ($cells) use (&$lines) {
            $lines[] = $this->csvLine($cells);
        });
        $lines[] = $this->csvLine([$document['title'] ?? '']);
        $lines[] = $this->csvLine(['Généré le', $document['generated_at'] ?? '']);
        foreach ($document['kpis'] ?? [] as $kpi) {
            $lines[] = $this->csvLine([$kpi['label'], $kpi['value']]);
        }
        foreach ($document['sections'] ?? [] as $section) {
            $lines[] = '';
            $lines[] = $this->csvLine([$section['title']]);
            $lines[] = $this->csvLine($section['headers']);
            foreach ($section['rows'] as $row) {
                $lines[] = $this->csvLine($row);
            }
        }
        $content = "\xEF\xBB\xBF" . implode("\r\n", $lines);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ]);
    }

    private function xlsx(string $filename, array $document): Response
    {
        $rows = [];
        $rows[] = [$document['company']['nom'] ?? ''];
        $this->companyLines($document, function ($cells) use (&$rows) {
            $rows[] = $cells;
        });
        $rows[] = [$document['title'] ?? ''];
        $rows[] = ['Généré le', $document['generated_at'] ?? ''];
        foreach ($document['kpis'] ?? [] as $kpi) {
            $rows[] = [$kpi['label'], $kpi['value']];
        }
        foreach ($document['sections'] ?? [] as $section) {
            $rows[] = [];
            $rows[] = [$section['title']];
            $rows[] = $section['headers'];
            foreach ($section['rows'] as $row) {
                $rows[] = $row;
            }
        }
        $binary = $this->buildXlsx($rows);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
        ]);
    }

    private function pdf(string $filename, array $document, bool $inline): Response
    {
        $pdf = Pdf::loadView('exports.tableau', ['document' => $document])->setPaper('a4', 'landscape');
        $output = $pdf->output();

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($inline ? 'inline' : 'attachment') . '; filename="' . $filename . '.pdf"',
        ]);
    }

    private function companyLines(array $document, callable $push): void
    {
        $company = $document['company'] ?? [];
        $pairs = [
            'ICE' => $company['ice'] ?? null,
            'IF' => $company['if'] ?? null,
            'RC' => $company['rc'] ?? null,
            'Adresse' => $company['adresse'] ?? null,
            'Téléphone' => $company['telephone'] ?? null,
            'Email' => $company['email'] ?? null,
            'Banque' => $company['banque'] ?? null,
            'RIB' => $company['rib'] ?? null,
            'IBAN' => $company['iban'] ?? null,
        ];
        $line = [];
        foreach ($pairs as $label => $value) {
            if ($value) {
                $line[] = $label . ' : ' . $value;
            }
        }
        if ($line) {
            $push($line);
        }
    }

    private function csvLine(array $cells): string
    {
        return implode(';', array_map(function ($cell) {
            $value = $this->cell($cell);
            if (preg_match('/^[=+\-@]/', $value)) {
                $value = "'" . $value;
            }
            return '"' . str_replace('"', '""', $value) . '"';
        }, $cells));
    }

    private function cell(mixed $cell): string
    {
        if (is_float($cell) || is_int($cell)) {
            return is_float($cell) ? number_format($cell, 2, ',', ' ') : (string) $cell;
        }

        return (string) $cell;
    }

    private function buildXlsx(array $rows): string
    {
        $shared = [];
        $index = [];
        $sheetRows = '';
        $r = 1;
        foreach ($rows as $row) {
            $cells = '';
            $c = 1;
            foreach ($row as $value) {
                $ref = $this->col($c) . $r;
                if (is_int($value) || is_float($value)) {
                    $cells .= '<c r="' . $ref . '"><v>' . (0 + $value) . '</v></c>';
                } else {
                    $text = (string) $value;
                    if (!isset($index[$text])) {
                        $index[$text] = count($shared);
                        $shared[] = $text;
                    }
                    $cells .= '<c r="' . $ref . '" t="s"><v>' . $index[$text] . '</v></c>';
                }
                $c++;
            }
            $sheetRows .= '<row r="' . $r . '">' . $cells . '</row>';
            $r++;
        }
        $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared) . '" uniqueCount="' . count($shared) . '">';
        foreach ($shared as $text) {
            $sharedXml .= '<si><t xml:space="preserve">' . $this->xml($text) . '</t></si>';
        }
        $sharedXml .= '</sst>';
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetRows . '</sheetData></worksheet>';
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Rapport" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/></Relationships>';

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->addFromString('xl/sharedStrings.xml', $sharedXml);
        $zip->close();
        $binary = file_get_contents($tmp) ?: '';
        @unlink($tmp);

        return $binary;
    }

    private function col(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
