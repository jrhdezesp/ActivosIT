<?php

namespace Controllers\Reportes;

use Controllers\PrivateController;
use Dao\Reportes\Reportes as DaoReportes;
use Utilities\ReportCharts;
use Utilities\ReportExport;
use Views\Renderer;

/**
 * Presenta un resumen general de inventario, equipos y actividad reciente.
 */
class Reportes extends PrivateController
{
    // Forma de distinguir los movimientos en los resumenes y graficas.
    private const TIPOS_MOVIMIENTO = [
        'ENTRADA' => [
            'label' => 'Entrada',
            'class' => 'badge--success',
            'icon' => 'fa-circle-arrow-down',
            'sign' => '+',
        ],
        'SALIDA' => [
            'label' => 'Salida',
            'class' => 'badge--danger',
            'icon' => 'fa-circle-arrow-up',
            'sign' => '-',
        ],
        'AJUSTE_POSITIVO' => [
            'label' => 'Ajuste +',
            'class' => 'badge--info',
            'icon' => 'fa-plus',
            'sign' => '+',
        ],
        'AJUSTE_NEGATIVO' => [
            'label' => 'Ajuste -',
            'class' => 'badge--warning',
            'icon' => 'fa-minus',
            'sign' => '-',
        ],
    ];

    /**
     * Reune los resumenes, presenta las graficas o entrega un reporte exportado.
     */
    public function run(): void
    {
        if (ReportExport::getRequestedFormat() !== '') {
            $this->requireAction('Acciones.Reportes.Exportar');
        }
        $resumen = DaoReportes::getResumenGeneral();
        $stockBajo = DaoReportes::getStockBajo(6);
        $movimientos = DaoReportes::getMovimientosRecientes(6);
        $serieMes = DaoReportes::getMovimientosPorMes(6);
        $distribucion = DaoReportes::getDistribucionTipos();
        $estadosEquipo = $this->buildEstadosEquipo($resumen);
        $charts = $this->buildCharts($estadosEquipo, $serieMes, $distribucion);

        $exportFormat = ReportExport::getRequestedFormat();
        if ($exportFormat !== '') {
            $this->export($exportFormat, $resumen, $stockBajo, $movimientos, $serieMes, $distribucion);
            return;
        }

        ReportCharts::loadAssets();

        Renderer::render('reportes/reportes', [
            'Kpis' => $this->buildKpis($resumen),
            'QuickReports' => $this->buildQuickReports($resumen),
            'Ops' => $this->buildOps($resumen),
            'EstadosEquipo' => $estadosEquipo,
            'StockBajo' => $this->mapStockBajo($stockBajo),
            'HasStockBajo' => count($stockBajo) > 0,
            'MovimientosRecientes' => $this->mapMovimientos($movimientos),
            'HasMovimientos' => count($movimientos) > 0,
            'ChartsJson' => ReportCharts::toJson($charts),
            'ChartEstadosAlt' => $this->buildEstadosAlt($estadosEquipo),
            'ChartSerieAlt' => $this->buildSerieAlt($serieMes),
            'ChartTiposAlt' => $this->buildTiposAlt($distribucion),
            'HasDistribucion' => $this->hasDistribucion($distribucion),
            'SeriePeriodoLabel' => $serieMes['labels'][0] . ' - ' . $serieMes['labels'][count($serieMes['labels']) - 1],
            'SerieTotalEntradas' => intval($serieMes['total_entradas']),
            'SerieTotalSalidas' => intval($serieMes['total_salidas']),
            'SerieEntradasLabel' => intval($serieMes['total_entradas']) === 1 ? 'entrada' : 'entradas',
            'SerieSalidasLabel' => intval($serieMes['total_salidas']) === 1 ? 'salida' : 'salidas',
            'ReportDate' => date('d/m/Y'),
            'KardexUrl' => 'index.php?page=Reportes_Kardex',
            'CustodiosUrl' => 'index.php?page=Reportes_EquiposCustodio',
            'ArticulosUrl' => 'index.php?page=Articulos_Articulos',
            'ExportUrl' => 'index.php?page=Reportes_Reportes&export=csv',
            'ExportXlsxUrl' => 'index.php?page=Reportes_Reportes&export=xlsx',
            'ExportPdfUrl' => 'index.php?page=Reportes_Reportes&export=pdf',
        ]);
    }

    private function buildKpis(array $resumen): array
    {
        return [
            [
                'label' => 'Artículos activos',
                'value' => intval($resumen['articulos']),
                'hint' => intval($resumen['existencias']) . ' unidades en existencia',
                'icon' => 'fa-boxes-stacked',
                'tone' => 'primary',
            ],
            [
                'label' => 'Equipos registrados',
                'value' => intval($resumen['equipos']),
                'hint' => intval($resumen['equipos_disponibles']) . ' disponibles',
                'icon' => 'fa-laptop',
                'tone' => 'info',
            ],
            [
                'label' => 'Movimientos',
                'value' => intval($resumen['movimientos']),
                'hint' => intval($resumen['movimientos_mes']) . ' registrados este mes',
                'icon' => 'fa-right-left',
                'tone' => 'neutral',
            ],
            [
                'label' => 'Existencias bajo mínimo',
                'value' => intval($resumen['stock_bajo']),
                'hint' => intval($resumen['stock_bajo']) > 0
                    ? 'Requieren reposición'
                    : 'Sin alertas de stock',
                'icon' => 'fa-triangle-exclamation',
                'tone' => intval($resumen['stock_bajo']) > 0 ? 'danger' : 'success',
            ],
        ];
    }

    private function buildQuickReports(array $resumen): array
    {
        $equiposEnCustodia = intval($resumen['asignaciones_activas']);

        return [
            [
                'title' => 'Kardex de movimientos',
                'description' => 'Consulta entradas, salidas y ajustes con sus existencias antes y después de cada movimiento.',
                'icon' => 'fa-table-list',
                'metric' => intval($resumen['movimientos']),
                'metricLabel' => 'movimientos registrados',
                'hint' => 'Este mes: ' . intval($resumen['movimientos_mes']),
                'url' => 'index.php?page=Reportes_Kardex',
            ],
            [
                'title' => 'Equipos por custodio',
                'description' => 'Identifica qué equipos tiene cada responsable y en qué departamento se encuentran.',
                'icon' => 'fa-map-location-dot',
                'metric' => intval($resumen['custodios']),
                'metricLabel' => intval($resumen['custodios']) === 1
                    ? 'custodio activo'
                    : 'custodios activos',
                'hint' => $equiposEnCustodia === 1
                    ? '1 equipo en custodia'
                    : $equiposEnCustodia . ' equipos en custodia',
                'url' => 'index.php?page=Reportes_EquiposCustodio',
            ],
        ];
    }

    private function buildOps(array $resumen): array
    {
        return [
            [
                'label' => 'Asignaciones activas',
                'value' => intval($resumen['asignaciones_activas']),
                'icon' => 'fa-people-arrows',
            ],
            [
                'label' => 'Mantenimientos en proceso',
                'value' => intval($resumen['mantenimientos_proceso']),
                'icon' => 'fa-screwdriver-wrench',
            ],
            [
                'label' => 'Equipos disponibles',
                'value' => intval($resumen['equipos_disponibles']),
                'icon' => 'fa-circle-check',
            ],
            [
                'label' => 'Equipos en revisión',
                'value' => intval($resumen['equipos_revision']),
                'icon' => 'fa-magnifying-glass-chart',
            ],
        ];
    }

    private function buildEstadosEquipo(array $resumen): array
    {
        $total = intval($resumen['equipos']);
        $estados = [
            [
                'label' => 'Disponibles',
                'value' => intval($resumen['equipos_disponibles']),
                'tone' => 'success',
            ],
            [
                'label' => 'Reservados',
                'value' => intval($resumen['equipos_reservados']),
                'tone' => 'warning',
            ],
            [
                'label' => 'Asignados',
                'value' => intval($resumen['equipos_asignados']),
                'tone' => 'info',
            ],
            [
                'label' => 'En mantenimiento',
                'value' => intval($resumen['equipos_mantenimiento']),
                'tone' => 'warning',
            ],
            [
                'label' => 'En revisión',
                'value' => intval($resumen['equipos_revision']),
                'tone' => 'neutral',
            ],
            [
                'label' => 'De baja',
                'value' => intval($resumen['equipos_baja']),
                'tone' => 'danger',
            ],
        ];

        foreach ($estados as &$estado) {
            $estado['percent'] = $total > 0
                ? intval(round(($estado['value'] / $total) * 100))
                : 0;
            $estado['percentLabel'] = $estado['percent'] . '%';
        }
        unset($estado);

        return $estados;
    }

    private function mapStockBajo(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $actual = intval($row['existencia_actual'] ?? 0);
            $minima = intval($row['existencia_minima'] ?? 0);
            $faltante = max(0, $minima - $actual);
            $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
            $nombre = trim(strval($row['nombre_articulo'] ?? ''));

            $unidad = trim(strval($row['nombre_unidad'] ?? ''));
            $simbolo = trim(strval($row['simbolo_medida'] ?? ''));
            if ($simbolo !== '') {
                $unidad .= ' (' . $simbolo . ')';
            }

            $items[] = [
                'nombre' => $nombre !== '' ? $nombre : 'Artículo sin nombre',
                'codigo' => $codigo !== '' ? $codigo : 'Sin código',
                'stock' => $actual . ' / ' . $minima,
                'unidad' => $unidad !== '' ? $unidad : 'Unidades',
                'clasificacion' => trim(strval($row['nombre_clasificacion'] ?? '')),
                'estadoLabel' => $actual === 0 ? 'Sin existencias' : 'Bajo mínimo',
                'estadoClass' => $actual === 0 ? 'badge--danger' : 'badge--warning',
                'faltanteLabel' => $faltante > 0
                    ? 'Faltan ' . $faltante . ' para el mínimo'
                    : 'En el mínimo establecido',
            ];
        }

        return $items;
    }

    private function mapMovimientos(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            $tipo = strval($row['tipo_movimiento'] ?? '');
            $tipoData = self::TIPOS_MOVIMIENTO[$tipo] ?? [
                'label' => 'Movimiento',
                'class' => 'badge--outline',
                'icon' => 'fa-circle-question',
                'sign' => '',
            ];

            $fecha = strtotime(strval($row['fecha_movimiento'] ?? ''));
            $documento = trim(strval($row['documento_correlativo'] ?? ''));
            $observacion = trim(strval($row['observacion'] ?? ''));
            $articulo = trim(strval($row['nombre_articulo'] ?? ''));
            $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
            $usuario = trim(strval($row['usuario_nombre'] ?? ''));

            $items[] = [
                'id' => intval($row['id_movimiento'] ?? 0),
                'fecha' => $fecha !== false ? date('d/m/Y', $fecha) : 'Sin fecha',
                'hora' => $fecha !== false ? date('H:i', $fecha) : '',
                'articulo' => $articulo !== '' ? $articulo : 'Artículo sin nombre',
                'codigo' => $codigo !== '' ? $codigo : 'Sin código',
                'observacion' => $observacion,
                'tipoLabel' => $tipoData['label'],
                'tipoClass' => $tipoData['class'],
                'tipoIcon' => $tipoData['icon'],
                'cantidad' => $tipoData['sign'] . intval($row['cantidad'] ?? 0),
                'cantidadTone' => $tipoData['sign'] === '-' ? 'is-negative' : 'is-positive',
                'existenciaAnterior' => intval($row['existencia_anterior'] ?? 0),
                'existenciaNueva' => intval($row['existencia_nueva'] ?? 0),
                'documento' => $documento !== '' ? $documento : 'Sin documento',
                'usuario' => $usuario !== '' ? $usuario : 'Usuario no disponible',
            ];
        }

        return $items;
    }

    /**
     * Configuracion de los graficos del centro de reportes. La vista solo
     * declara los canvas; el JS lee esta configuracion desde el bloque JSON.
     */
    private function buildCharts(array $estadosEquipo, array $serieMes, array $distribucion): array
    {
        $charts = [
            [
                'id' => 'centroEquiposEstado',
                'type' => 'doughnut',
                'labels' => array_column($estadosEquipo, 'label'),
                'datasets' => [[
                    'label' => 'Equipos',
                    'data' => array_column($estadosEquipo, 'value'),
                    'colors' => ['success', 'info', 'warning', 'neutral', 'danger'],
                ]],
                'unit' => 'equipos',
                'unitSingular' => 'equipo',
            ],
            [
                'id' => 'centroMovimientosMes',
                'type' => 'bar',
                'labels' => $serieMes['labels'],
                'datasets' => [
                    [
                        'label' => 'Entradas',
                        'data' => $serieMes['entradas'],
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Salidas',
                        'data' => $serieMes['salidas'],
                        'color' => 'danger',
                    ],
                ],
                'unit' => 'unidades',
                'unitSingular' => 'unidad',
                'maxBarThickness' => 26,
            ],
        ];

        $colores = [
            'ENTRADA' => 'success',
            'SALIDA' => 'danger',
            'AJUSTE_POSITIVO' => 'info',
            'AJUSTE_NEGATIVO' => 'warning',
        ];

        $labels = [];
        $data = [];
        $colors = [];
        foreach (self::TIPOS_MOVIMIENTO as $tipo => $tipoData) {
            $movimientos = intval($distribucion[$tipo] ?? 0);
            if ($movimientos <= 0) {
                continue;
            }
            $labels[] = $tipoData['label'];
            $data[] = $movimientos;
            $colors[] = $colores[$tipo] ?? 'brand';
        }

        if (count($data) > 0) {
            $charts[] = [
                'id' => 'centroMovimientosTipo',
                'type' => 'doughnut',
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Movimientos',
                    'data' => $data,
                    'colors' => $colors,
                ]],
                'unit' => 'movimientos',
                'unitSingular' => 'movimiento',
            ];
        }

        return $charts;
    }

    private function hasDistribucion(array $distribucion): bool
    {
        foreach ($distribucion as $movimientos) {
            if (intval($movimientos) > 0) {
                return true;
            }
        }

        return false;
    }

    private function buildTiposAlt(array $distribucion): string
    {
        $labels = [
            'ENTRADA' => ['entrada', 'entradas'],
            'SALIDA' => ['salida', 'salidas'],
            'AJUSTE_POSITIVO' => ['ajuste positivo', 'ajustes positivos'],
            'AJUSTE_NEGATIVO' => ['ajuste negativo', 'ajustes negativos'],
        ];

        $partes = [];
        foreach (self::TIPOS_MOVIMIENTO as $tipo => $tipoData) {
            $movimientos = intval($distribucion[$tipo] ?? 0);
            if ($movimientos > 0) {
                $label = $labels[$tipo] ?? ['movimiento', 'movimientos'];
                $partes[] = $movimientos . ' '
                    . ($movimientos === 1 ? $label[0] : $label[1]);
            }
        }

        if (count($partes) === 0) {
            return 'Sin movimientos registrados para graficar.';
        }

        return 'Movimientos por tipo: ' . implode(', ', $partes) . '.';
    }

    /**
     * Exporta un resumen del centro de reportes en secciones.
     */
    private function export(
        string $format,
        array $resumen,
        array $stockBajo,
        array $movimientos,
        array $serieMes,
        array $distribucion
    ): void {
        $kpiRows = [];
        foreach ($this->buildKpis($resumen) as $kpi) {
            $kpiRows[] = [$kpi['label'], $kpi['value'], $kpi['hint']];
        }

        $stockRows = [];
        foreach ($stockBajo as $row) {
            $actual = intval($row['existencia_actual'] ?? 0);
            $minima = intval($row['existencia_minima'] ?? 0);
            $stockRows[] = [
                trim(strval($row['nombre_articulo'] ?? '')),
                trim(strval($row['codigo_interno_articulo'] ?? '')),
                $actual,
                $minima,
                max(0, $minima - $actual),
                trim(strval($row['nombre_unidad'] ?? '')),
                trim(strval($row['nombre_clasificacion'] ?? '')),
            ];
        }

        $movimientoRows = [];
        foreach ($movimientos as $row) {
            $fecha = strtotime(strval($row['fecha_movimiento'] ?? ''));
            $tipo = strval($row['tipo_movimiento'] ?? '');
            $tipoLabel = self::TIPOS_MOVIMIENTO[$tipo]['label'] ?? 'Movimiento';
            $movimientoRows[] = [
                $fecha !== false ? date('d/m/Y', $fecha) : 'Sin fecha',
                $fecha !== false ? date('H:i', $fecha) : '',
                trim(strval($row['nombre_articulo'] ?? '')),
                trim(strval($row['codigo_interno_articulo'] ?? '')),
                $tipoLabel,
                intval($row['cantidad'] ?? 0),
                intval($row['existencia_anterior'] ?? 0),
                intval($row['existencia_nueva'] ?? 0),
                trim(strval($row['documento_correlativo'] ?? '')),
                trim(strval($row['usuario_nombre'] ?? '')),
                trim(strval($row['observacion'] ?? '')),
            ];
        }

        $serieRows = [];
        foreach ($serieMes['labels'] as $index => $label) {
            $serieRows[] = [
                $label,
                intval($serieMes['entradas'][$index] ?? 0),
                intval($serieMes['salidas'][$index] ?? 0),
                intval($serieMes['movimientos'][$index] ?? 0),
            ];
        }

        $tipoRows = [];
        foreach (self::TIPOS_MOVIMIENTO as $tipo => $tipoData) {
            $tipoRows[] = [$tipoData['label'], intval($distribucion[$tipo] ?? 0)];
        }

        $sections = [
            [
                'title' => 'Resumen general',
                'headers' => ['Indicador', 'Valor', 'Detalle'],
                'rows' => $kpiRows,
            ],
            [
                'title' => 'Existencias bajo mínimo',
                'headers' => [
                    'Artículo',
                    'Código',
                    'Existencia actual',
                    'Existencia mínima',
                    'Faltante',
                    'Unidad',
                    'Clasificación',
                ],
                'rows' => $stockRows,
            ],
            [
                'title' => 'Últimos movimientos',
                'headers' => [
                    'Fecha',
                    'Hora',
                    'Artículo',
                    'Código',
                    'Tipo',
                    'Cantidad',
                    'Existencia anterior',
                    'Existencia nueva',
                    'Documento',
                    'Usuario',
                    'Observación',
                ],
                'rows' => $movimientoRows,
            ],
            [
                'title' => 'Movimientos por mes',
                'headers' => ['Periodo', 'Entradas', 'Salidas', 'Movimientos'],
                'rows' => $serieRows,
            ],
            [
                'title' => 'Movimientos por tipo',
                'headers' => ['Tipo', 'Movimientos'],
                'rows' => $tipoRows,
            ],
        ];

        $baseName = 'centro_reportes_' . date('Ymd_His');
        $meta = [
            'title' => 'Centro de reportes',
            'creator' => 'ActivosIT',
            'Resumen general al ' . date('d/m/Y H:i'),
            'Los reportes filtrados están disponibles en Kardex y Equipos por custodio',
        ];

        if ($format === 'xlsx') {
            ReportExport::streamXlsxSections($baseName . '.xlsx', $sections, $meta);
            return;
        }

        if ($format === 'pdf') {
            ReportExport::streamPdfSections(
                $baseName . '.pdf',
                'Centro de reportes',
                $sections,
                $meta
            );
            return;
        }

        ReportExport::streamSections($baseName . '.csv', $sections);
    }

    private function buildEstadosAlt(array $estadosEquipo): string
    {
        $labels = [
            'Disponibles' => ['disponible', 'disponibles'],
            'Asignados' => ['asignado', 'asignados'],
            'En mantenimiento' => ['en mantenimiento', 'en mantenimiento'],
            'En revisión' => ['en revisión', 'en revisión'],
            'De baja' => ['de baja', 'de baja'],
        ];

        $partes = [];
        foreach ($estadosEquipo as $estado) {
            $equipos = intval($estado['value']);
            if ($equipos <= 0) {
                continue;
            }
            $label = $labels[$estado['label']] ?? [
                $this->lower($estado['label']),
                $this->lower($estado['label']),
            ];
            $partes[] = $equipos . ' ' . ($equipos === 1 ? $label[0] : $label[1]);
        }

        if (count($partes) === 0) {
            return 'Sin equipos registrados para graficar.';
        }

        return 'Equipos por estado: ' . implode(', ', $partes) . '.';
    }

    private function buildSerieAlt(array $serieMes): string
    {
        $total = count($serieMes['labels']);
        $entradas = intval($serieMes['total_entradas']);
        $salidas = intval($serieMes['total_salidas']);

        return 'Movimientos por mes: ' . $entradas . ' '
            . ($entradas === 1 ? 'unidad de entrada' : 'unidades de entrada')
            . ' y ' . $salidas . ' '
            . ($salidas === 1 ? 'unidad de salida' : 'unidades de salida')
            . ' en ' . ($total === 1 ? 'el último mes' : 'los últimos ' . $total . ' meses') . '.';
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}
