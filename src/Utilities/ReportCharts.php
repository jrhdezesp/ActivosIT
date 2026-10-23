<?php

namespace Utilities;

/**
 * Utilidades de los graficos de reportes: serializacion segura de la
 * configuracion y carga de los activos de Chart.js.
 */
class ReportCharts
{
    /**
     * Serializa la configuracion de los graficos para incrustarla en un
     * bloque <script type="application/json">. Los flags HEX evitan que el
     * contenido pueda cerrar la etiqueta script.
     */
    public static function toJson(array $charts): string
    {
        $json = json_encode(
            ['charts' => array_values($charts)],
            JSON_HEX_TAG
                | JSON_HEX_AMP
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
        );

        return $json === false ? '{"charts":[]}' : $json;
    }

    /**
     * Encola Chart.js local y el inicializador de graficos en el layout
     * privado, solo para las vistas que los usan.
     */
    public static function loadAssets(): void
    {
        Site::addEndScript('public/js/vendor/chart.umd.min.js?v=4.4.4');
        Site::addEndScript('public/js/report-charts.js?v=20261023-04');
    }
}
