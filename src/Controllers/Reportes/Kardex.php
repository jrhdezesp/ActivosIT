<?php

namespace Controllers\Reportes;

use Controllers\PrivateController;
use Dao\Reportes\Kardex as DaoKardex;
use Utilities\Paging;
use Utilities\ReportCharts;
use Utilities\ReportExport;
use Views\Renderer;

/**
 * Muestra los cambios de existencias de articulos y permite consultarlos por periodo.
 */
class Kardex extends PrivateController
{
    private const ORDER_FIELDS = [
        'fecha_movimiento',
        'tipo_movimiento',
        'cantidad',
        'nombre_articulo',
        'existencia_nueva',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    private const TIPOS = [
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
            'label' => 'Ajuste positivo',
            'class' => 'badge--info',
            'icon' => 'fa-plus',
            'sign' => '+',
        ],
        'AJUSTE_NEGATIVO' => [
            'label' => 'Ajuste negativo',
            'class' => 'badge--warning',
            'icon' => 'fa-minus',
            'sign' => '-',
        ],
    ];

    private $search = '';
    private $tipo = '';
    private $articuloId = 0;
    private $desde = '';
    private $hasta = '';
    private $orderBy = 'fecha_movimiento';
    private $orderDescending = true;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    /**
     * Presenta los movimientos del articulo elegido y sus opciones de descarga.
     */
    public function run(): void
    {
        $this->getParams();

        $exportFormat = $this->getExportFormat();
        if ($exportFormat !== '') {
            $this->export($exportFormat);
            return;
        }

        $result = DaoKardex::getMovimientos(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        ReportCharts::loadAssets();

        Renderer::render('reportes/kardex', $this->buildViewData($result));
    }

    private function getParams(): void
    {
        $this->search = trim(strval($_GET['q'] ?? ''));
        if ($this->textLength($this->search) > 100) {
            $this->search = $this->substring($this->search, 0, 100);
        }

        $this->tipo = strtoupper(strval($_GET['tipo'] ?? ''));
        if (!in_array($this->tipo, array_keys(self::TIPOS), true)) {
            $this->tipo = '';
        }

        $this->articuloId = max(0, intval($_GET['articulo'] ?? 0));

        $this->desde = $this->normalizeDate(strval($_GET['desde'] ?? ''));
        $this->hasta = $this->normalizeDate(strval($_GET['hasta'] ?? ''));

        if ($this->desde !== '' && $this->hasta !== '' && $this->desde > $this->hasta) {
            $temporal = $this->desde;
            $this->desde = $this->hasta;
            $this->hasta = $temporal;
        }

        $this->orderBy = strval($_GET['orderBy'] ?? 'fecha_movimiento');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'fecha_movimiento';
        }

        $this->orderDescending = in_array(
            strval($_GET['orderDescending'] ?? '1'),
            ['1', 'true', 'on'],
            true
        );

        $this->pageNumber = max(1, intval($_GET['pageNum'] ?? 1));

        $itemsPerPage = intval($_GET['itemsPerPage'] ?? 10);
        $this->itemsPerPage = in_array($itemsPerPage, self::ITEMS_PER_PAGE_OPTIONS, true)
            ? $itemsPerPage
            : 10;
    }

    private function buildViewData(array $result): array
    {
        $movimientos = [];
        foreach ($result['movimientos'] as $row) {
            $movimientos[] = $this->mapMovimiento($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoKardex::getResumen(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta
        );

        $hasFilters = $this->hasFilters();

        $serie = DaoKardex::getSerieMovimientos(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta
        );
        $distribucion = DaoKardex::getDistribucionTipos(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta
        );
        $topArticulos = DaoKardex::getTopArticulos(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta,
            6
        );
        $charts = $this->buildCharts($serie, $distribucion, $topArticulos);

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Reportes_Kardex',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '-' . $to . ' de ' . $total
                . ($total === 1 ? ' movimiento' : ' movimientos')
            : 'Sin movimientos para mostrar';

        $movimientoSort = $this->buildSortData('fecha_movimiento');
        $articuloSort = $this->buildSortData('nombre_articulo');
        $tipoSort = $this->buildSortData('tipo_movimiento');
        $cantidadSort = $this->buildSortData('cantidad');
        $existenciaSort = $this->buildSortData('existencia_nueva');

        return [
            'Movimientos' => $movimientos,
            'HasMovimientos' => count($movimientos) > 0,
            'TotalMovimientos' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Tipo' => $this->tipo,
            'TiposFiltro' => $this->decorateTiposFiltro(),
            'ArticuloId' => $this->articuloId,
            'ArticulosFiltro' => $this->decorateArticulosFiltro(),
            'Desde' => $this->desde,
            'Hasta' => $this->hasta,
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay movimientos registrados',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos movimientos que coincidan con la búsqueda o los filtros seleccionados.'
                : 'El Kardex se llenará automáticamente cuando se confirmen entradas, salidas o ajustes de inventario.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Reportes_Kardex',
            'ClearUrl' => 'index.php?page=Reportes_Kardex&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryEntradas' => intval($summary['unidades_entrada']),
            'SummarySalidas' => intval($summary['unidades_salida']),
            'SummaryArticulos' => intval($summary['articulos']),
            'SummaryAjustes' => intval($summary['ajustes']),
            'SummaryTotalLabel' => intval($summary['total']) === 1 ? 'Movimiento' : 'Movimientos',
            'SummaryEntradasLabel' => intval($summary['unidades_entrada']) === 1
                ? 'Unidad ingresada'
                : 'Unidades ingresadas',
            'SummarySalidasLabel' => intval($summary['unidades_salida']) === 1
                ? 'Unidad retirada'
                : 'Unidades retiradas',
            'SummaryArticulosLabel' => intval($summary['articulos']) === 1
                ? 'Artículo afectado'
                : 'Artículos afectados',
            'CentroUrl' => 'index.php?page=Reportes_Reportes',
            'CustodiosUrl' => 'index.php?page=Reportes_EquiposCustodio',
            'ChartsJson' => ReportCharts::toJson($charts),
            'ChartSerieAlt' => $this->buildSerieAlt($serie),
            'ChartSerieRango' => $serie['desde_label'] !== ''
                ? $serie['desde_label'] . ' - ' . $serie['hasta_label']
                : 'sin movimientos',
            'ChartSerieUnidad' => $serie['granularidad'] === 'mes' ? 'mes' : 'día',
            'ChartTiposAlt' => $this->buildTiposAlt($distribucion),
            'HasDistribucion' => $this->hasDistribucion($distribucion),
            'ChartTopAlt' => $this->buildTopAlt($topArticulos),
            'HasTopArticulos' => count($topArticulos) > 0,
            'ExportUrl' => $this->buildExportUrl('csv'),
            'ExportXlsxUrl' => $this->buildExportUrl('xlsx'),
            'ExportPdfUrl' => $this->buildExportUrl('pdf'),
            'SortMovimientoUrl' => $movimientoSort['url'],
            'SortMovimientoActive' => $movimientoSort['active'],
            'SortMovimientoIcon' => $movimientoSort['icon'],
            'SortArticuloUrl' => $articuloSort['url'],
            'SortArticuloActive' => $articuloSort['active'],
            'SortArticuloIcon' => $articuloSort['icon'],
            'SortTipoUrl' => $tipoSort['url'],
            'SortTipoActive' => $tipoSort['active'],
            'SortTipoIcon' => $tipoSort['icon'],
            'SortCantidadUrl' => $cantidadSort['url'],
            'SortCantidadActive' => $cantidadSort['active'],
            'SortCantidadIcon' => $cantidadSort['icon'],
            'SortExistenciaUrl' => $existenciaSort['url'],
            'SortExistenciaActive' => $existenciaSort['active'],
            'SortExistenciaIcon' => $existenciaSort['icon'],
        ];
    }

    private function mapMovimiento(array $row): array
    {
        $tipo = strval($row['tipo_movimiento'] ?? '');
        $tipoData = self::TIPOS[$tipo] ?? [
            'label' => 'Movimiento',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
            'sign' => '',
        ];

        $fecha = strtotime(strval($row['fecha_movimiento'] ?? ''));
        $articulo = trim(strval($row['nombre_articulo'] ?? ''));
        $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $observacion = trim(strval($row['observacion'] ?? ''));
        $documento = trim(strval($row['documento_correlativo'] ?? ''));
        $motivo = trim(strval($row['nombre_motivo'] ?? ''));
        $usuario = trim(strval($row['usuario_nombre'] ?? ''));
        $cuenta = trim(strval($row['usuario_cuenta'] ?? ''));
        $origen = trim(strval($row['origen_referencia'] ?? ''));

        $documentoLabel = $documento !== '' ? $documento : 'Sin documento';
        if ($motivo !== '') {
            $documentoLabel .= ' · ' . $motivo;
        } elseif ($documento === '' && $origen !== '') {
            $documentoLabel = str_replace('documentos_', 'Documento de ', $origen);
        }

        return [
            'id' => intval($row['id_movimiento'] ?? 0),
            'fecha' => $fecha !== false ? date('d/m/Y', $fecha) : 'Sin fecha',
            'hora' => $fecha !== false ? date('H:i', $fecha) : '',
            'articulo' => $articulo !== '' ? $articulo : 'Artículo sin nombre',
            'codigo' => $codigo !== '' ? $codigo : 'Sin código',
            'observacion' => $observacion !== '' ? $observacion : 'Sin observación',
            'tipoLabel' => $tipoData['label'],
            'tipoClass' => $tipoData['class'],
            'tipoIcon' => $tipoData['icon'],
            'cantidad' => $tipoData['sign'] . intval($row['cantidad'] ?? 0),
            'cantidadTone' => $tipoData['sign'] === '-' ? 'is-negative' : 'is-positive',
            'existenciaAnterior' => intval($row['existencia_anterior'] ?? 0),
            'existenciaNueva' => intval($row['existencia_nueva'] ?? 0),
            'documento' => $documentoLabel,
            'usuario' => $usuario !== '' ? $usuario : 'Usuario no disponible',
            'cuenta' => $cuenta,
        ];
    }

    private function decorateTiposFiltro(): array
    {
        $options = [];
        foreach (self::TIPOS as $value => $data) {
            $options[] = [
                'value' => $value,
                'label' => $data['label'],
                'selected' => $value === $this->tipo ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateArticulosFiltro(): array
    {
        $options = [];
        foreach (DaoKardex::getArticulosOptions() as $row) {
            $value = intval($row['value'] ?? 0);
            if ($value <= 0) {
                continue;
            }
            $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
            $nombre = trim(strval($row['nombre_articulo'] ?? ''));
            $label = $codigo !== '' ? $codigo . ' · ' . $nombre : $nombre;
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $this->articuloId ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function buildSortData(string $field): array
    {
        $isCurrent = $this->orderBy === $field;
        $descending = $isCurrent ? !$this->orderDescending : true;

        $params = $this->filterParams();
        $params['orderBy'] = $field;
        $params['orderDescending'] = $descending ? 1 : 0;
        $params['itemsPerPage'] = $this->itemsPerPage;

        return [
            'url' => 'index.php?' . http_build_query($params),
            'active' => $isCurrent,
            'icon' => $isCurrent
                ? ($this->orderDescending ? 'fa-sort-down' : 'fa-sort-up')
                : 'fa-sort',
        ];
    }

    private function buildBaseUrl(): string
    {
        $params = $this->filterParams();
        $params['orderBy'] = $this->orderBy;
        $params['orderDescending'] = $this->orderDescending ? 1 : 0;
        $params['itemsPerPage'] = $this->itemsPerPage;

        return 'index.php?' . http_build_query($params);
    }

    private function buildExtraParams(): array
    {
        $params = $this->filterParams();
        $params['orderBy'] = $this->orderBy;
        $params['orderDescending'] = $this->orderDescending ? 1 : 0;

        $extraParams = [];
        foreach ($params as $name => $value) {
            if ($name === 'page') {
                continue;
            }
            $extraParams[] = ['name' => $name, 'value' => strval($value)];
        }

        return $extraParams;
    }

    private function filterParams(): array
    {
        $params = ['page' => 'Reportes_Kardex'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->tipo !== '') {
            $params['tipo'] = $this->tipo;
        }
        if ($this->articuloId > 0) {
            $params['articulo'] = $this->articuloId;
        }
        if ($this->desde !== '') {
            $params['desde'] = $this->desde;
        }
        if ($this->hasta !== '') {
            $params['hasta'] = $this->hasta;
        }

        return $params;
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->tipo !== ''
            || $this->articuloId > 0
            || $this->desde !== ''
            || $this->hasta !== '';
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return '';
        }

        return $value;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function substring(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    }

    /**
     * Graficos del kardex: serie temporal y distribucion por tipo. Ambos
     * respetan los filtros aplicados al listado.
     */
    private function buildCharts(array $serie, array $distribucion, array $topArticulos): array
    {
        $charts = [];

        if (count($serie['labels']) > 0) {
            $charts[] = [
                'id' => 'kardexSerie',
                'type' => 'line',
                'labels' => $serie['labels'],
                'datasets' => [
                    [
                        'label' => 'Entradas',
                        'data' => $serie['entradas'],
                        'color' => 'success',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Salidas',
                        'data' => $serie['salidas'],
                        'color' => 'danger',
                        'fill' => true,
                    ],
                ],
                'unit' => 'unidades',
                'unitSingular' => 'unidad',
            ];
        }

        $colores = [
            'ENTRADA' => 'success',
            'SALIDA' => 'danger',
            'AJUSTE_POSITIVO' => 'info',
            'AJUSTE_NEGATIVO' => 'warning',
        ];

        $labels = [];
        $data = [];
        $colors = [];
        foreach (self::TIPOS as $tipo => $tipoData) {
            $movimientos = intval($distribucion[$tipo]['movimientos'] ?? 0);
            if ($movimientos <= 0) {
                continue;
            }
            $labels[] = $tipoData['label'];
            $data[] = $movimientos;
            $colors[] = $colores[$tipo] ?? 'brand';
        }

        if (count($data) > 0) {
            $charts[] = [
                'id' => 'kardexTipos',
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

        if (count($topArticulos) > 0) {
            $labelsTop = [];
            $dataTop = [];

            foreach ($topArticulos as $fila) {
                $nombre = trim(strval($fila['nombre_articulo'] ?? ''));
                if ($nombre === '') {
                    $nombre = 'Artículo sin nombre';
                }
                $labelsTop[] = $this->truncate($nombre, 30);
                $dataTop[] = intval($fila['movimientos'] ?? 0);
            }

            $charts[] = [
                'id' => 'kardexTopArticulos',
                'type' => 'bar',
                'horizontal' => true,
                'labels' => $labelsTop,
                'datasets' => [[
                    'label' => 'Movimientos',
                    'data' => $dataTop,
                    'color' => 'brand',
                ]],
                'unit' => 'movimientos',
                'unitSingular' => 'movimiento',
                'maxBarThickness' => 22,
                'legend' => false,
            ];
        }

        return $charts;
    }

    private function buildTopAlt(array $topArticulos): string
    {
        if (count($topArticulos) === 0) {
            return 'Sin artículos con movimientos para graficar.';
        }

        $partes = [];
        foreach ($topArticulos as $fila) {
            $nombre = trim(strval($fila['nombre_articulo'] ?? ''));
            if ($nombre === '') {
                $nombre = 'artículo sin nombre';
            }
            $partes[] = $nombre . ': ' . intval($fila['movimientos'] ?? 0);
        }

        return 'Artículos con más movimientos: ' . implode(', ', $partes) . '.';
    }

    private function buildSerieAlt(array $serie): string
    {
        if (count($serie['labels']) === 0) {
            return 'Sin movimientos para graficar con los filtros actuales.';
        }

        $entradas = intval($serie['total_entradas']);
        $salidas = intval($serie['total_salidas']);

        return 'Evolución de movimientos entre ' . $serie['desde_label']
            . ' y ' . $serie['hasta_label'] . ': '
            . $entradas . ' ' . ($entradas === 1 ? 'unidad de entrada' : 'unidades de entrada')
            . ' y ' . $salidas . ' ' . ($salidas === 1 ? 'unidad de salida' : 'unidades de salida')
            . ', agrupadas por '
            . ($serie['granularidad'] === 'mes' ? 'mes' : 'día') . '.';
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
        foreach (self::TIPOS as $tipo => $tipoData) {
            $movimientos = intval($distribucion[$tipo]['movimientos'] ?? 0);
            if ($movimientos > 0) {
                $label = $labels[$tipo] ?? ['movimiento', 'movimientos'];
                $partes[] = $movimientos . ' '
                    . ($movimientos === 1 ? $label[0] : $label[1]);
            }
        }

        if (count($partes) === 0) {
            return 'Sin movimientos para graficar con los filtros actuales.';
        }

        return 'Distribución por tipo de movimiento: ' . implode(', ', $partes) . '.';
    }

    private function hasDistribucion(array $distribucion): bool
    {
        foreach ($distribucion as $fila) {
            if (intval($fila['movimientos'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function getExportFormat(): string
    {
        $format = strtolower(trim(strval($_GET['export'] ?? '')));

        return in_array($format, ['csv', 'xlsx', 'pdf'], true) ? $format : '';
    }

    private function buildExportUrl(string $format = 'csv'): string
    {
        $params = $this->filterParams();
        $params['orderBy'] = $this->orderBy;
        $params['orderDescending'] = $this->orderDescending ? 1 : 0;
        $params['export'] = $format;

        return 'index.php?' . http_build_query($params);
    }

    /**
     * Exporta todos los movimientos que coinciden con los filtros actuales,
     * sin limitarse a la pagina visible.
     */
    private function export(string $format): void
    {
        $rows = DaoKardex::getMovimientosForExport(
            $this->search,
            $this->tipo,
            $this->articuloId,
            $this->desde,
            $this->hasta,
            $this->orderBy,
            $this->orderDescending
        );

        $data = [];
        foreach ($rows as $row) {
            $tipo = strval($row['tipo_movimiento'] ?? '');
            $tipoLabel = self::TIPOS[$tipo]['label'] ?? 'Movimiento';
            $fecha = strtotime(strval($row['fecha_movimiento'] ?? ''));
            $documento = trim(strval($row['documento_correlativo'] ?? ''));
            $origen = trim(strval($row['origen_referencia'] ?? ''));
            if ($documento === '' && $origen !== '') {
                $documento = str_replace('documentos_', 'Documento de ', $origen);
            }

            $data[] = [
                intval($row['id_movimiento'] ?? 0),
                $fecha !== false ? date('d/m/Y', $fecha) : 'Sin fecha',
                $fecha !== false ? date('H:i', $fecha) : '',
                $tipoLabel,
                intval($row['cantidad'] ?? 0),
                intval($row['existencia_anterior'] ?? 0),
                intval($row['existencia_nueva'] ?? 0),
                trim(strval($row['nombre_articulo'] ?? '')),
                trim(strval($row['codigo_interno_articulo'] ?? '')),
                $documento,
                trim(strval($row['nombre_motivo'] ?? '')),
                trim(strval($row['usuario_nombre'] ?? '')),
                trim(strval($row['usuario_cuenta'] ?? '')),
                trim(strval($row['observacion'] ?? '')),
            ];
        }

        $headers = [
            'ID',
            'Fecha',
            'Hora',
            'Tipo',
            'Cantidad',
            'Existencia anterior',
            'Existencia nueva',
            'Artículo',
            'Código',
            'Documento',
            'Motivo',
            'Usuario',
            'Cuenta',
            'Observación',
        ];

        $sections = [[
            'title' => 'Kardex de movimientos',
            'headers' => $headers,
            'rows' => $data,
        ]];

        $baseName = 'kardex_' . date('Ymd_His');
        $meta = [
            'title' => 'Kardex de movimientos',
            'creator' => 'ActivosIT',
            $this->buildFilterSummary(),
            'Generado el ' . date('d/m/Y H:i'),
        ];

        if ($format === 'xlsx') {
            ReportExport::streamXlsxSections($baseName . '.xlsx', $sections, $meta);
            return;
        }

        if ($format === 'pdf') {
            ReportExport::streamPdfSections(
                $baseName . '.pdf',
                'Kardex de movimientos',
                $sections,
                $meta
            );
            return;
        }

        ReportExport::stream($baseName . '.csv', $headers, $data);
    }

    private function buildFilterSummary(): string
    {
        $parts = [];

        if ($this->search !== '') {
            $parts[] = 'Búsqueda: ' . $this->search;
        }

        if ($this->tipo !== '') {
            $parts[] = 'Tipo: ' . (self::TIPOS[$this->tipo]['label'] ?? $this->tipo);
        }

        if ($this->articuloId > 0) {
            $articulo = DaoKardex::getArticuloLabel($this->articuloId);
            $parts[] = 'Artículo: ' . ($articulo !== '' ? $articulo : '#' . $this->articuloId);
        }

        if ($this->desde !== '' || $this->hasta !== '') {
            $parts[] = 'Desde: ' . ($this->desde !== '' ? $this->formatDate($this->desde) : 'sin límite');
            $parts[] = 'Hasta: ' . ($this->hasta !== '' ? $this->formatDate($this->hasta) : 'sin límite');
        }

        if (count($parts) === 0) {
            return 'Sin filtros aplicados';
        }

        return 'Filtros aplicados: ' . implode(' · ', $parts);
    }

    private function formatDate(string $value): string
    {
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            return $value;
        }

        return $date->format('d/m/Y');
    }

    private function truncate(string $value, int $length): string
    {
        if ($this->textLength($value) <= $length) {
            return $value;
        }

        return $this->substring($value, 0, $length - 1) . '…';
    }
}
