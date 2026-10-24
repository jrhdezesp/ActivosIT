<?php

namespace Utilities;

/**
 * Generador de libros XLSX sin dependencias externas.
 *
 * Cada seccion del reporte se escribe en una hoja independiente. El archivo
 * resultante es un paquete OOXML valido compatible con Excel, LibreOffice y
 * Google Sheets. Los valores se escriben como texto o numero, nunca como
 * formulas, para evitar la ejecucion de contenido al abrir el archivo.
 */
class XlsxExport
{
    private const STYLE_DEFAULT = 0;
    private const STYLE_TITLE = 1;
    private const STYLE_META = 2;
    private const STYLE_HEADER = 3;
    private const STYLE_TEXT = 4;
    private const STYLE_NUMBER = 5;

    /**
     * Envia un libro XLSX con una o varias secciones.
     */
    public static function streamSections(
        string $filename,
        array $sections,
        array $meta = []
    ): void {
        $temporary = tempnam(sys_get_temp_dir(), 'activosit_xlsx_');
        if ($temporary === false) {
            throw new \RuntimeException('No se pudo crear el archivo temporal del reporte.');
        }

        try {
            self::writeFile($temporary, $sections, $meta);
            self::sendHeaders($filename);
            readfile($temporary);
        } finally {
            @unlink($temporary);
        }
    }

    /**
     * Crea un archivo XLSX en la ruta indicada.
     */
    public static function writeFile(string $path, array $sections, array $meta = []): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new \RuntimeException(
                'Falta la extension ZIP para generar Excel. Habilite "ext-zip" en PHP.'
            );
        }

        $sheets = self::prepareSheets($sections, $meta);

        $zip = new \ZipArchive();
        $opened = $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new \RuntimeException('No se pudo generar el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml(count($sheets)));
        $zip->addFromString('_rels/.rels', self::rootRelationshipsXml());
        $zip->addFromString('docProps/core.xml', self::corePropertiesXml($meta));
        $zip->addFromString('docProps/app.xml', self::appPropertiesXml($sheets));
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationshipsXml(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::stylesXml());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($index + 1) . '.xml',
                self::worksheetXml($sheet, $index === 0)
            );
        }

        if (!$zip->close()) {
            throw new \RuntimeException('No se pudo cerrar el archivo Excel generado.');
        }
    }

    private static function prepareSheets(array $sections, array $meta): array
    {
        $metaLine = self::metaLine($meta);
        $sheets = [];
        $usedNames = [];

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $title = trim(strval($section['title'] ?? ''));
            $headers = array_values(is_array($section['headers'] ?? null) ? $section['headers'] : []);
            $rows = [];

            foreach ((array) ($section['rows'] ?? []) as $row) {
                $rows[] = array_values(is_array($row) ? $row : [$row]);
            }

            $columns = max(1, count($headers));
            foreach ($rows as $row) {
                $columns = max($columns, count($row));
            }

            if (count($headers) === 0) {
                for ($i = 0; $i < $columns; $i++) {
                    $headers[] = 'Columna ' . ($i + 1);
                }
            }

            while (count($headers) < $columns) {
                $headers[] = '';
            }

            foreach ($rows as &$row) {
                while (count($row) < $columns) {
                    $row[] = '';
                }
                $row = array_slice($row, 0, $columns);
            }
            unset($row);

            $baseName = self::sanitizeSheetName($title !== '' ? $title : 'Hoja ' . (count($sheets) + 1));
            $name = $baseName;
            $suffix = 2;
            while (isset($usedNames[self::lower($name)])) {
                $suffixLabel = ' (' . $suffix . ')';
                $name = self::substring($baseName, 0, 31 - self::length($suffixLabel)) . $suffixLabel;
                $suffix++;
            }
            $usedNames[self::lower($name)] = true;

            $sheets[] = [
                'name' => $name,
                'title' => $title !== '' ? $title : 'Reporte',
                'meta' => $metaLine,
                'headers' => $headers,
                'rows' => $rows,
                'columns' => $columns,
            ];
        }

        if (count($sheets) === 0) {
            $sheets[] = [
                'name' => 'Reporte',
                'title' => 'Reporte',
                'meta' => $metaLine,
                'headers' => ['Sin datos'],
                'rows' => [],
                'columns' => 1,
            ];
        }

        return $sheets;
    }

    private static function worksheetXml(array $sheet, bool $selected): string
    {
        $columns = intval($sheet['columns']);
        $lastColumn = self::columnLetter($columns);
        $rows = $sheet['rows'];
        $lastRow = count($rows) > 0 ? 3 + count($rows) : 4;

        $xml = self::xmlDeclaration();
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>';
        $xml .= '<dimension ref="A1:' . $lastColumn . $lastRow . '"/>';
        $xml .= '<sheetViews><sheetView tabSelected="' . ($selected ? '1' : '0') . '" workbookViewId="0">'
            . '<pane ySplit="3" topLeftCell="A4" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A4" sqref="A4"/>'
            . '</sheetView></sheetViews>';
        $xml .= '<sheetFormatPr defaultRowHeight="15"/>';

        $widths = self::columnWidths($sheet);

        $xml .= '<cols>';
        foreach ($widths as $index => $width) {
            $column = $index + 1;
            $xml .= '<col min="' . $column . '" max="' . $column . '" width="' . $width . '" customWidth="1"/>';
        }
        $xml .= '</cols>';

        $xml .= '<sheetData>';
        $xml .= '<row r="1" ht="24" customHeight="1">'
            . self::cellXml('A1', $sheet['title'], self::STYLE_TITLE)
            . '</row>';
        $xml .= '<row r="2" ht="20" customHeight="1">'
            . self::cellXml('A2', $sheet['meta'], self::STYLE_META)
            . '</row>';

        $xml .= '<row r="3" ht="32" customHeight="1">';
        foreach ($sheet['headers'] as $index => $header) {
            $ref = self::columnLetter($index + 1) . '3';
            $xml .= self::cellXml($ref, $header, self::STYLE_HEADER);
        }
        $xml .= '</row>';

        if (count($rows) === 0) {
            $xml .= '<row r="4" ht="24" customHeight="1">';
            $xml .= self::cellXml('A4', 'Sin datos para los filtros seleccionados.', self::STYLE_TEXT);
            for ($i = 1; $i < $columns; $i++) {
                $xml .= self::cellXml(self::columnLetter($i + 1) . '4', '', self::STYLE_TEXT);
            }
            $xml .= '</row>';
        } else {
            foreach ($rows as $rowIndex => $row) {
                $excelRow = $rowIndex + 4;
                $height = self::rowHeight($row, $widths);
                $xml .= '<row r="' . $excelRow . '" ht="' . $height . '" customHeight="1">';
                foreach ($row as $columnIndex => $value) {
                    $ref = self::columnLetter($columnIndex + 1) . $excelRow;
                    $style = is_int($value) || is_float($value)
                        ? self::STYLE_NUMBER
                        : self::STYLE_TEXT;
                    $xml .= self::cellXml($ref, $value, $style);
                }
                $xml .= '</row>';
            }
        }
        $xml .= '</sheetData>';

        $xml .= '<autoFilter ref="A3:' . $lastColumn . $lastRow . '"/>';

        if ($columns > 1) {
            $xml .= '<mergeCells count="2">'
                . '<mergeCell ref="A1:' . $lastColumn . '1"/>'
                . '<mergeCell ref="A2:' . $lastColumn . '2"/>'
                . '</mergeCells>';
        }

        $xml .= '<pageMargins left="0.35" right="0.35" top="0.45" bottom="0.45" header="0.2" footer="0.2"/>';
        $xml .= '<pageSetup paperSize="9" orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        $xml .= '</worksheet>';

        return $xml;
    }

    private static function columnWidths(array $sheet): array
    {
        $widths = [];
        $headers = $sheet['headers'];
        $rows = $sheet['rows'];

        for ($index = 0; $index < intval($sheet['columns']); $index++) {
            $max = self::length(strval($headers[$index] ?? ''));

            foreach ($rows as $row) {
                $max = max($max, self::length(strval($row[$index] ?? '')));
            }

            $widths[] = min(46, max(11, $max + 2));
        }

        return $widths;
    }

    private static function rowHeight(array $row, array $widths): int
    {
        $lines = 1;

        foreach ($row as $index => $value) {
            $text = strval($value ?? '');
            if ($text === '') {
                continue;
            }

            $available = max(8, intval($widths[$index] ?? 12) - 2);
            $parts = preg_split('/\R/u', $text);
            if ($parts === false) {
                $parts = [$text];
            }

            foreach ($parts as $part) {
                $lines = max($lines, intval(ceil(max(1, self::length($part)) / $available)));
            }
        }

        return min(409, max(20, $lines * 13 + 6));
    }

    private static function cellXml(string $reference, $value, int $style): string
    {
        if ($value === null || $value === '') {
            return '<c r="' . $reference . '" s="' . $style . '"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $reference . '" s="' . $style . '"><v>'
                . self::numberValue($value) . '</v></c>';
        }

        $text = self::cleanText(strval($value));
        if (self::length($text) > 32767) {
            $text = self::substring($text, 0, 32767);
        }

        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
            . self::escape($text) . '</t></is></c>';
    }

    private static function numberValue($value): string
    {
        if (is_int($value)) {
            return strval($value);
        }

        $text = rtrim(rtrim(sprintf('%.10F', floatval($value)), '0'), '.');

        return $text !== '' ? $text : '0';
    }

    private static function contentTypesXml(int $sheetCount): string
    {
        $xml = self::xmlDeclaration();
        $xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $xml .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $xml .= '<Default Extension="xml" ContentType="application/xml"/>';
        $xml .= '<Override PartName="/xl/workbook.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $xml .= '<Override PartName="/xl/styles.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $xml .= '<Override PartName="/docProps/core.xml"'
            . ' ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $xml .= '<Override PartName="/docProps/app.xml"'
            . ' ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Override PartName="/xl/worksheets/sheet' . $index . '.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $xml . '</Types>';
    }

    private static function rootRelationshipsXml(): string
    {
        $xml = self::xmlDeclaration();
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $xml .= '<Relationship Id="rId1"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"'
            . ' Target="xl/workbook.xml"/>';
        $xml .= '<Relationship Id="rId2"'
            . ' Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"'
            . ' Target="docProps/core.xml"/>';
        $xml .= '<Relationship Id="rId3"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties"'
            . ' Target="docProps/app.xml"/>';

        return $xml . '</Relationships>';
    }

    private static function workbookXml(array $sheets): string
    {
        $xml = self::xmlDeclaration();
        $xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $xml .= '<bookViews><workbookView activeTab="0"/></bookViews><sheets>';

        foreach ($sheets as $index => $sheet) {
            $xml .= '<sheet name="' . self::escape($sheet['name']) . '" sheetId="' . ($index + 1)
                . '" r:id="rId' . ($index + 1) . '"/>';
        }

        $xml .= '</sheets><calcPr calcId="191029" fullCalcOnLoad="1"/></workbook>';

        return $xml;
    }

    private static function workbookRelationshipsXml(int $sheetCount): string
    {
        $xml = self::xmlDeclaration();
        $xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        for ($index = 1; $index <= $sheetCount; $index++) {
            $xml .= '<Relationship Id="rId' . $index . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . $index . '.xml"/>';
        }

        $xml .= '<Relationship Id="rId' . ($sheetCount + 1) . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';

        return $xml . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        $xml = self::xmlDeclaration();
        $xml .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $xml .= '<numFmts count="0"/>';
        $xml .= '<fonts count="4">'
            . '<font><sz val="10"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF1E293B"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><i/><sz val="9"/><color rgb="FF475569"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>';
        $xml .= '<fills count="4">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>';
        $xml .= '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border><left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/></border>'
            . '</borders>';
        $xml .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
        $xml .= '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"'
            . ' applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"'
            . ' applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1"'
            . ' applyFill="1" applyBorder="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"'
            . ' applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"'
            . ' applyAlignment="1"><alignment horizontal="right" vertical="top"/></xf>'
            . '</cellXfs>';
        $xml .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
        $xml .= '<dxfs count="0"/>';
        $xml .= '<tableStyles count="0" defaultTableStyle="TableStyleMedium9" defaultPivotStyle="PivotStyleLight16"/>';

        return $xml . '</styleSheet>';
    }

    private static function corePropertiesXml(array $meta): string
    {
        $title = self::escape(self::metaValue($meta, 'title', 'Reporte ActivosIT'));
        $creator = self::escape(self::metaValue($meta, 'creator', 'ActivosIT'));
        $created = gmdate('Y-m-d\TH:i:s\Z');

        $xml = self::xmlDeclaration();
        $xml .= '<cp:coreProperties'
            . ' xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"'
            . ' xmlns:dc="http://purl.org/dc/elements/1.1/"'
            . ' xmlns:dcterms="http://purl.org/dc/terms/"'
            . ' xmlns:dcmitype="http://purl.org/dc/dcmitype/"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $xml .= '<dc:title>' . $title . '</dc:title>';
        $xml .= '<dc:creator>' . $creator . '</dc:creator>';
        $xml .= '<cp:lastModifiedBy>' . $creator . '</cp:lastModifiedBy>';
        $xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>';
        $xml .= '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:modified>';

        return $xml . '</cp:coreProperties>';
    }

    private static function appPropertiesXml(array $sheets): string
    {
        $titles = [];
        foreach ($sheets as $sheet) {
            $titles[] = $sheet['name'];
        }

        $xml = self::xmlDeclaration();
        $xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"'
            . ' xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">';
        $xml .= '<Application>ActivosIT</Application><DocSecurity>0</DocSecurity>'
            . '<ScaleCrop>false</ScaleCrop><HeadingPairs><vt:vector size="2" baseType="variant">'
            . '<vt:variant><vt:lpstr>Hojas</vt:lpstr></vt:variant>'
            . '<vt:variant><vt:i4>' . count($titles) . '</vt:i4></vt:variant>'
            . '</vt:vector></HeadingPairs><TitlesOfParts><vt:vector size="' . count($titles)
            . '" baseType="lpstr">';
        foreach ($titles as $title) {
            $xml .= '<vt:lpstr>' . self::escape($title) . '</vt:lpstr>';
        }
        $xml .= '</vt:vector></TitlesOfParts><Company>ActivosIT</Company>'
            . '<LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc>'
            . '<HyperlinksChanged>false</HyperlinksChanged><AppVersion>1.0</AppVersion>';

        return $xml . '</Properties>';
    }

    private static function metaLine(array $meta): string
    {
        $parts = [];
        foreach ($meta as $key => $value) {
            if (is_string($key)) {
                if ($key === 'title' || $key === 'creator') {
                    continue;
                }
                $value = strval($value);
            }
            $value = trim(strval($value));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return count($parts) > 0
            ? implode(' · ', $parts)
            : 'Generado el ' . date('d/m/Y H:i');
    }

    private static function metaValue(array $meta, string $key, string $default): string
    {
        $value = trim(strval($meta[$key] ?? ''));

        return $value !== '' ? $value : $default;
    }

    private static function sendHeaders(string $filename): void
    {
        $filename = ReportExport::safeFilename($filename, 'reporte.xlsx');
        if (strtolower(substr($filename, -5)) !== '.xlsx') {
            $filename .= '.xlsx';
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private static function sanitizeSheetName(string $name): string
    {
        $name = preg_replace('/[\[\]:*?\/\\\\]/u', ' ', $name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $name = trim($name, "'");
        if ($name === '') {
            $name = 'Reporte';
        }

        $name = rtrim(self::substring($name, 0, 31), " '");

        return $name !== '' ? $name : 'Reporte';
    }

    private static function columnLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters !== '' ? $letters : 'A';
    }

    private static function cleanText(string $value): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '';

        return $value;
    }

    private static function escape(string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $escaped !== false ? $escaped : '';
    }

    private static function xmlDeclaration(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    }

    private static function lower(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function substring(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    }
}
