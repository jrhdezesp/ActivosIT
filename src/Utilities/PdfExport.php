<?php

namespace Utilities;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Exportacion de reportes a PDF usando Dompdf.
 *
 * El documento se genera en orientacion horizontal para que las tablas con
 * muchas columnas conserven todas sus columnas y se lean correctamente.
 */
class PdfExport
{
    /**
     * Envia un PDF con una o varias secciones.
     */
    public static function streamSections(
        string $filename,
        string $title,
        array $sections,
        array $meta = []
    ): void {
        if (!class_exists(Dompdf::class)) {
            throw new \RuntimeException(
                'Falta la dependencia para generar PDF. Ejecute "composer install".'
            );
        }

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('tempDir', sys_get_temp_dir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(self::buildHtml($title, $sections, $meta), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        if ($font !== null) {
            $canvas = $dompdf->getCanvas();
            $canvas->page_text(
                24,
                $canvas->get_height() - 18,
                'ActivosIT · Página {PAGE_NUM} de {PAGE_COUNT}',
                $font,
                7,
                [0.35, 0.41, 0.5]
            );
        }

        $filename = ReportExport::safeFilename($filename, 'reporte.pdf');
        if (strtolower(substr($filename, -4)) !== '.pdf') {
            $filename .= '.pdf';
        }

        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $dompdf->stream($filename, ['Attachment' => true]);
    }

    private static function buildHtml(string $title, array $sections, array $meta): string
    {
        $html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">'
            . '<title>' . self::escape($title) . '</title>'
            . '<style>' . self::styles() . '</style></head><body>';

        $html .= '<header class="report-header">'
            . '<h1>' . self::escape($title) . '</h1>'
            . '<div class="report-meta">' . self::metaLine($meta) . '</div>'
            . '</header>';

        $sectionCount = 0;
        foreach ($sections as $candidate) {
            if (is_array($candidate)) {
                $sectionCount++;
            }
        }

        foreach ($sections as $section) {
            if (!is_array($section)) {
                continue;
            }

            $sectionTitle = trim(strval($section['title'] ?? ''));
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

            $keepTogether = count($rows) <= 12 && $columns <= 12;
            $html .= '<section class="report-section'
                . ($keepTogether ? ' report-section--keep' : '') . '">';

            $showSectionTitle = $sectionTitle !== ''
                && !($sectionCount === 1 && $sectionTitle === $title);
            if ($showSectionTitle) {
                $html .= '<h2>' . self::escape($sectionTitle) . '</h2>';
            }

            $widths = self::columnWidths($headers, $rows, $columns);

            $html .= '<table class="report-table' . ($columns > 8 ? ' compact' : '') . '">';
            $html .= '<colgroup>';
            foreach ($widths as $width) {
                $html .= '<col style="width:' . $width . '%">';
            }
            $html .= '</colgroup>';

            $html .= '<thead><tr>';
            foreach ($headers as $index => $header) {
                $width = $widths[$index] ?? 0;
                $html .= '<th style="width:' . $width . '%">' . self::cell($header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            if (count($rows) === 0) {
                $html .= '<tr><td class="empty" colspan="' . $columns . '">'
                    . 'Sin datos para los filtros seleccionados.</td></tr>';
            } else {
                foreach ($rows as $row) {
                    $html .= '<tr>';
                    foreach ($row as $value) {
                        $html .= '<td>' . self::cell($value) . '</td>';
                    }
                    $html .= '</tr>';
                }
            }

            $html .= '</tbody></table></section>';
        }

        return $html . '</body></html>';
    }

    private static function styles(): string
    {
        return '@page{size:A4 landscape;margin:14mm 10mm 16mm 10mm;}'
            . 'body{font-family:"DejaVu Sans",sans-serif;font-size:8pt;color:#0f172a;}'
            . '.report-header{border-bottom:3px solid #1e3a8a;padding-bottom:8px;margin-bottom:10px;}'
            . '.report-header h1{font-size:18pt;line-height:1.2;margin:0 0 4px;color:#1e3a8a;}'
            . '.report-meta{font-size:7.5pt;line-height:1.45;color:#475569;}'
            . '.report-section{margin-top:10px;}'
            . '.report-section + .report-section{margin-top:14px;padding-top:10px;'
            . 'border-top:1px solid #cbd5e1;}'
            . '.report-section--keep{page-break-inside:avoid;}'
            . '.report-section h2{font-size:11pt;line-height:1.25;margin:0 0 6px;color:#1e3a8a;'
            . 'page-break-after:avoid;}'
            . '.report-table{width:100%;border-collapse:collapse;table-layout:fixed;}'
            . '.report-table thead{display:table-header-group;}'
            . '.report-table th{background:#1e3a8a;color:#fff;font-size:7.2pt;font-weight:bold;'
            . 'padding:4px 3px;border:1px solid #94a3b8;text-align:left;vertical-align:middle;'
            . 'word-wrap:break-word;}'
            . '.report-table td{border:1px solid #cbd5e1;padding:3px;vertical-align:top;'
            . 'font-size:7.5pt;line-height:1.3;word-wrap:break-word;}'
            . '.report-table tbody tr:nth-child(even) td{background:#f8fafc;}'
            . '.report-table tr{page-break-inside:avoid;}'
            . '.report-table .empty{text-align:center;color:#64748b;padding:12px;}'
            . '.report-table.compact th{font-size:6.6pt;padding:3px 2px;}'
            . '.report-table.compact td{font-size:6.7pt;padding:2px;}';
    }

    /**
     * Calcula anchos proporcionales para la tabla PDF.
     *
     * Se reserva el ancho necesario para la cabecera y se reparte el resto
     * entre el contenido. Los textos muy largos se limitan para que una
     * observacion extensa no deje sin espacio a las demas columnas.
     */
    private static function columnWidths(array $headers, array $rows, int $columns): array
    {
        $available = 785.2; // A4 horizontal menos margenes laterales, en puntos.
        $compact = $columns > 8;
        $headerFactor = $compact ? 4.35 : 5.0;
        $bodyFactor = $compact ? 3.9 : 4.5;
        $tokenFactor = $compact ? 4.2 : 4.9;
        $overhead = 5.0;
        $sample = array_slice($rows, 0, 120);

        $minimums = [];
        $desired = [];

        for ($index = 0; $index < $columns; $index++) {
            $headerToken = self::longestToken(strval($headers[$index] ?? ''));
            $maxContent = 0;
            $maxToken = 0;

            foreach ($sample as $row) {
                $value = strval($row[$index] ?? '');
                $maxContent = max($maxContent, self::length($value));
                $maxToken = max($maxToken, self::longestToken($value));
            }

            $headerWidth = $headerToken * $headerFactor;
            $contentWidth = max(
                min(30, $maxToken) * $tokenFactor,
                min(30, $maxContent) * $bodyFactor
            );

            // Correos, URLs y codigos largos necesitan un poco mas de aire.
            if ($maxToken >= 20) {
                $contentWidth += 6.0;
            }

            $minimums[] = max(12.0, $headerWidth + $overhead);
            $desired[] = max(
                $minimums[$index],
                $headerWidth + $overhead,
                $contentWidth + $overhead
            );
        }

        $minimumTotal = array_sum($minimums);
        $desiredTotal = array_sum($desired);

        if ($minimumTotal >= $available) {
            $widthsPoints = $minimums;
            $factor = $available / max(1.0, $minimumTotal);
            foreach ($widthsPoints as &$width) {
                $width *= $factor;
            }
            unset($width);
        } elseif ($desiredTotal <= $available) {
            $widthsPoints = $desired;
            $factor = $available / max(1.0, $desiredTotal);
            foreach ($widthsPoints as &$width) {
                $width *= $factor;
            }
            unset($width);
        } else {
            $deficit = $desiredTotal - $available;
            $slackTotal = 0.0;
            foreach ($desired as $index => $width) {
                $slackTotal += max(0.0, $width - $minimums[$index]);
            }

            $widthsPoints = [];
            foreach ($desired as $index => $width) {
                $slack = max(0.0, $width - $minimums[$index]);
                $reduction = $slackTotal > 0 ? ($deficit * ($slack / $slackTotal)) : 0.0;
                $widthsPoints[] = max($minimums[$index], $width - $reduction);
            }
        }

        $total = array_sum($widthsPoints);
        if ($total <= 0) {
            return array_fill(0, max(1, $columns), round(100 / max(1, $columns), 2));
        }

        $widths = [];
        $accumulated = 0.0;
        $last = count($widthsPoints) - 1;

        foreach ($widthsPoints as $index => $width) {
            $percentage = $index === $last
                ? round(100 - $accumulated, 2)
                : round(($width / $total) * 100, 2);

            $widths[] = $percentage;
            $accumulated += $percentage;
        }

        return $widths;
    }

    private static function longestToken(string $value): int
    {
        $tokens = preg_split('/\s+/u', trim($value));
        if ($tokens === false) {
            return self::length($value);
        }

        $longest = 0;
        foreach ($tokens as $token) {
            $longest = max($longest, self::length($token));
        }

        return $longest;
    }

    private static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private static function cell($value): string
    {
        if (is_int($value) || is_float($value)) {
            return self::escape(strval($value));
        }

        return nl2br(self::escape(strval($value ?? '')));
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
                $parts[] = self::escape($value);
            }
        }

        if (count($parts) === 0) {
            $parts[] = 'Generado el ' . date('d/m/Y H:i');
        }

        return implode(' &middot; ', $parts);
    }

    private static function escape(string $value): string
    {
        $escaped = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return $escaped !== false ? $escaped : '';
    }

}
