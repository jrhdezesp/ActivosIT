<?php

namespace Utilities;

/**
 * Exportacion de reportes a CSV, Excel y PDF.
 *
 * El CSV se genera con BOM UTF-8 y separador punto y coma para que Excel en
 * espanol lo abra en columnas. Las celdas CSV se sanean para evitar la
 * ejecucion de formulas al abrir el archivo en Excel o LibreOffice.
 */
class ReportExport
{
    /**
     * Devuelve el formato de exportacion solicitado por la peticion.
     *
     * @return string csv, xlsx, pdf o cadena vacia.
     */
    public static function getRequestedFormat(): string
    {
        $format = strtolower(trim(strval($_GET['export'] ?? '')));

        return in_array($format, ['csv', 'xlsx', 'pdf'], true) ? $format : '';
    }

    /**
     * Envia un CSV plano con una sola tabla.
     */
    public static function stream(string $filename, array $headers, array $rows): void
    {
        self::streamSections($filename, [[
            'headers' => $headers,
            'rows' => $rows,
        ]]);
    }

    /**
     * Envia un XLSX plano con una sola tabla.
     */
    public static function streamXlsx(string $filename, array $headers, array $rows, array $meta = []): void
    {
        self::streamXlsxSections($filename, [[
            'headers' => $headers,
            'rows' => $rows,
        ]], $meta);
    }

    /**
     * Envia un XLSX con una hoja por seccion.
     */
    public static function streamXlsxSections(string $filename, array $sections, array $meta = []): void
    {
        XlsxExport::streamSections($filename, $sections, $meta);
    }

    /**
     * Envia un PDF plano con una sola tabla.
     */
    public static function streamPdf(
        string $filename,
        string $title,
        array $headers,
        array $rows,
        array $meta = []
    ): void {
        self::streamPdfSections($filename, $title, [[
            'headers' => $headers,
            'rows' => $rows,
        ]], $meta);
    }

    /**
     * Envia un PDF con una o varias secciones.
     */
    public static function streamPdfSections(
        string $filename,
        string $title,
        array $sections,
        array $meta = []
    ): void {
        PdfExport::streamSections($filename, $title, $sections, $meta);
    }

    /**
     * Limpia un nombre de archivo para usarlo en Content-Disposition.
     */
    public static function safeFilename(string $filename, string $fallback = 'reporte'): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? '';

        return $filename !== '' ? $filename : $fallback;
    }

    /**
     * Envia un CSV con varias secciones separadas por una linea en blanco.
     * Cada seccion puede incluir un titulo, encabezados y filas.
     */
    public static function streamSections(string $filename, array $sections): void
    {
        self::sendHeaders($filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            return;
        }

        fwrite($output, "\xEF\xBB\xBF");

        $first = true;
        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            if (!$first) {
                self::writeRow($output, []);
            }
            $first = false;

            $title = trim(strval($section['title'] ?? ''));
            if ($title !== '') {
                self::writeRow($output, [$title]);
            }

            $headers = $section['headers'] ?? [];
            if (is_array($headers) && count($headers) > 0) {
                self::writeRow($output, $headers);
            }

            $rows = $section['rows'] ?? [];
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                self::writeRow($output, is_array($row) ? $row : [$row]);
            }
        }

        fclose($output);
    }

    /**
     * Escribe una fila usando punto y coma como separador.
     */
    public static function writeRow($output, array $row): void
    {
        $safe = array_map([self::class, 'safeCell'], $row);
        fputcsv($output, $safe, ';', '"', '');
    }

    /**
     * Neutraliza valores que Excel interpretaria como formulas.
     */
    public static function safeCell($value): string
    {
        $value = strval($value ?? '');

        if ($value !== '' && preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'" . $value;
        }

        return $value;
    }

    private static function sendHeaders(string $filename): void
    {
        $filename = self::safeFilename($filename, 'reporte.csv');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}
