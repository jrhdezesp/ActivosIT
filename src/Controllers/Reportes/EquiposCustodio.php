<?php

namespace Controllers\Reportes;

use Controllers\PrivateController;
use Dao\Catalogos\Catalogos as DaoCatalogos;
use Dao\Reportes\EquiposCustodio as DaoEquiposCustodio;
use Utilities\Paging;
use Utilities\ReportCharts;
use Utilities\ReportExport;
use Views\Renderer;

/**
 * Presenta los equipos agrupados o filtrados por la persona responsable.
 */
class EquiposCustodio extends PrivateController
{
    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    private const ESTADOS_EQUIPO = [
        'DISPONIBLE' => ['label' => 'Disponible', 'tone' => 'success'],
        'RESERVADO' => ['label' => 'Reservado', 'tone' => 'warning'],
        'ASIGNADO' => ['label' => 'Asignado', 'tone' => 'info'],
        'EN_MANTENIMIENTO' => ['label' => 'En mantenimiento', 'tone' => 'warning'],
        'EN_REVISION' => ['label' => 'En revisión', 'tone' => 'neutral'],
        'DE_BAJA' => ['label' => 'De baja', 'tone' => 'danger'],
    ];

    private $search = '';
    private $departamento = '';
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    /**
     * Busca los equipos por custodio y prepara el resultado para consulta o descarga.
     */
    public function run(): void
    {
        $this->getParams();

        if (ReportExport::getRequestedFormat() !== '') {
            $this->requireAction('Acciones.Reportes.Exportar');
        }
        $exportFormat = ReportExport::getRequestedFormat();
        if ($exportFormat !== '') {
            $this->export($exportFormat);
            return;
        }

        $result = DaoEquiposCustodio::getCustodios(
            $this->search,
            $this->departamento,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        ReportCharts::loadAssets();

        Renderer::render('reportes/equipos_custodio', $this->buildViewData($result));
    }

    private function getParams(): void
    {
        $this->search = trim(strval($_GET['q'] ?? ''));
        if (\Utilities\Text::length($this->search) > 100) {
            $this->search = $this->substring($this->search, 0, 100);
        }

        $this->departamento = trim(strval($_GET['departamento'] ?? ''));
        if (\Utilities\Text::length($this->departamento) > 150) {
            $this->departamento = $this->substring($this->departamento, 0, 150);
        }

        $this->pageNumber = max(1, intval($_GET['pageNum'] ?? 1));

        $itemsPerPage = intval($_GET['itemsPerPage'] ?? 10);
        $this->itemsPerPage = in_array($itemsPerPage, self::ITEMS_PER_PAGE_OPTIONS, true)
            ? $itemsPerPage
            : 10;
    }

    private function buildViewData(array $result): array
    {
        $custodios = $this->mapCustodios($result['custodios']);

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoEquiposCustodio::getSummary($this->search, $this->departamento);
        $hasFilters = $this->hasFilters();
        $porDepartamento = DaoEquiposCustodio::getEquiposPorDepartamento(
            $this->search,
            $this->departamento,
            8
        );
        $porEstado = DaoEquiposCustodio::getEquiposPorEstado(
            $this->search,
            $this->departamento
        );
        $topCustodios = DaoEquiposCustodio::getTopCustodios(
            $this->search,
            $this->departamento,
            6
        );

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Reportes_EquiposCustodio',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '-' . $to . ' de ' . $total
                . ($total === 1 ? ' custodio' : ' custodios')
            : 'Sin custodios para mostrar';

        return [
            'Custodios' => $custodios,
            'HasCustodios' => count($custodios) > 0,
            'TotalCustodios' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Departamento' => $this->departamento,
            'DepartamentosFiltro' => $this->decorateDepartamentoFiltro(),
            'HasFilters' => $hasFilters,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay equipos en custodia',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos custodios que coincidan con la búsqueda o el filtro seleccionado.'
                : 'Cuando registres una asignación activa, el custodio y sus equipos aparecerán en este reporte.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Reportes_EquiposCustodio',
            'ClearUrl' => 'index.php?page=Reportes_EquiposCustodio&itemsPerPage=' . $itemsPerPage,
            'SummaryCustodios' => intval($summary['custodios']),
            'SummaryEquipos' => intval($summary['equipos']),
            'SummaryDepartamentos' => intval($summary['departamentos']),
            'SummaryPrimeraAsignacion' => trim(strval($summary['primera_asignacion'] ?? '')) !== ''
                ? $this->formatDate(strval($summary['primera_asignacion']))
                : '—',
            'SummaryCustodiosLabel' => intval($summary['custodios']) === 1
                ? 'Custodio activo'
                : 'Custodios activos',
            'SummaryEquiposLabel' => intval($summary['equipos']) === 1
                ? 'Equipo en custodia'
                : 'Equipos en custodia',
            'SummaryDepartamentosLabel' => intval($summary['departamentos']) === 1
                ? 'Departamento'
                : 'Departamentos',
            'CentroUrl' => 'index.php?page=Reportes_Reportes',
            'KardexUrl' => 'index.php?page=Reportes_Kardex',
            'ExportUrl' => $this->buildExportUrl('csv'),
            'ExportXlsxUrl' => $this->buildExportUrl('xlsx'),
            'ExportPdfUrl' => $this->buildExportUrl('pdf'),
            'ChartsJson' => ReportCharts::toJson(
                $this->buildCharts($porDepartamento, $porEstado, $topCustodios)
            ),
            'ChartDeptosAlt' => $this->buildDepartamentosAlt($porDepartamento),
            'HasPorDepartamento' => count($porDepartamento) > 0,
            'ChartEstadosAlt' => $this->buildEstadosAlt($porEstado),
            'HasEstados' => $this->hasEstados($porEstado),
            'ChartTopAlt' => $this->buildTopAlt($topCustodios),
            'HasTopCustodios' => count($topCustodios) > 0,
        ];
    }

    private function mapCustodios(array $rows): array
    {
        if (count($rows) === 0) {
            return [];
        }

        $equipos = DaoEquiposCustodio::getEquiposPorCustodios($rows);
        $agrupados = [];

        foreach ($equipos as $equipo) {
            $key = $this->custodioKey(
                strval($equipo['persona_responsable'] ?? ''),
                strval($equipo['departamento_destino'] ?? '')
            );
            $agrupados[$key][] = $this->mapEquipo($equipo);
        }

        $items = [];
        foreach ($rows as $row) {
            $persona = trim(strval($row['persona_responsable'] ?? ''));
            $departamento = trim(strval($row['departamento_destino'] ?? ''));
            $key = $this->custodioKey($persona, $departamento);
            $equiposCustodio = $agrupados[$key] ?? [];

            $items[] = [
                'persona' => $persona !== '' ? $persona : 'Sin responsable',
                'departamento' => $departamento !== '' ? $departamento : 'Sin departamento',
                'iniciales' => $this->initials($persona),
                'equiposCount' => intval($row['equipos'] ?? count($equiposCustodio)),
                'primeraFecha' => $this->formatDate(strval($row['primera_asignacion'] ?? '')),
                'ultimaFecha' => $this->formatDate(strval($row['ultima_asignacion'] ?? '')),
                'equipos' => $equiposCustodio,
                'hasEquipos' => count($equiposCustodio) > 0,
            ];
        }

        return $items;
    }

    private function mapEquipo(array $row): array
    {
        $id = intval($row['id_equipo'] ?? 0);
        $serie = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonio = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));
        $articulo = trim(strval($row['nombre_articulo'] ?? ''));
        $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $condicion = trim(strval($row['condicion_entrega'] ?? ''));
        $fecha = $this->formatDate(strval($row['fecha_asignacion'] ?? ''));

        $serieLabel = $serie !== '' ? $serie : 'Equipo #' . $id;
        if ($patrimonio !== '') {
            $serieLabel .= ' · ' . $patrimonio;
        }

        $articuloLabel = $articulo !== '' ? $articulo : 'Artículo no disponible';
        if ($codigo !== '') {
            $articuloLabel .= ' (' . $codigo . ')';
        }

        $detalle = 'Desde ' . $fecha;
        if ($condicion !== '') {
            $detalle .= ' · ' . $condicion;
        }

        return [
            'id' => $id,
            'serie' => $serieLabel,
            'articulo' => $articuloLabel,
            'detalle' => $detalle,
        ];
    }

    private function decorateDepartamentoFiltro(): array
    {
        $departamentos = [];
        foreach (DaoCatalogos::getItems('departamentos', '', '1') as $row) {
            $value = strval($row['nombre_departamento'] ?? '');
            if ($value !== '') {
                $departamentos[$value] = $value;
            }
        }
        foreach (DaoEquiposCustodio::getDepartamentos() as $row) {
            $value = strval($row['departamento'] ?? '');
            if ($value !== '') {
                $departamentos[$value] = $value;
            }
        }

        if ($this->departamento !== '' && !isset($departamentos[$this->departamento])) {
            $departamentos[$this->departamento] = $this->departamento;
        }

        ksort($departamentos, SORT_NATURAL | SORT_FLAG_CASE);

        $options = [];
        foreach ($departamentos as $value) {
            $options[] = [
                'value' => $value,
                'label' => $value,
                'selected' => $value === $this->departamento ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function buildBaseUrl(): string
    {
        $params = $this->filterParams();
        $params['itemsPerPage'] = $this->itemsPerPage;

        return 'index.php?' . http_build_query($params);
    }

    private function buildExtraParams(): array
    {
        $params = $this->filterParams();

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
        $params = ['page' => 'Reportes_EquiposCustodio'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->departamento !== '') {
            $params['departamento'] = $this->departamento;
        }

        return $params;
    }

    private function hasFilters(): bool
    {
        return $this->search !== '' || $this->departamento !== '';
    }

    private function custodioKey(string $persona, string $departamento): string
    {
        return $persona . "\x1F" . $departamento;
    }

    private function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name);
        if (!is_array($parts)) {
            $parts = [];
        }

        $ignore = ['de', 'del', 'la', 'las', 'los', 'y', 'e'];
        $letters = '';

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || in_array($this->lower($part), $ignore, true)) {
                continue;
            }
            $letters .= $this->upper($this->substring($part, 0, 1));
            if (\Utilities\Text::length($letters) >= 2) {
                break;
            }
        }

        return $letters !== '' ? $letters : $this->upper($this->substring($name, 0, 1));
    }

    private function formatDate(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp !== false ? date('d/m/Y', $timestamp) : 'Sin fecha';
    }

    private function lower(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }

    private function upper(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }


    private function substring(string $value, int $start, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, $start, $length, 'UTF-8');
        }

        return substr($value, $start, $length);
    }

    /**
     * Graficos del reporte: equipos por departamento, por estado operativo y
     * custodios con mas equipos. Todos respetan los filtros del listado.
     */
    private function buildCharts(
        array $porDepartamento,
        array $porEstado,
        array $topCustodios
    ): array {
        $charts = [];

        if (count($porDepartamento) > 0) {
            $labels = [];
            $data = [];

            foreach ($porDepartamento as $fila) {
                $departamento = trim(strval($fila['departamento'] ?? ''));
                $labels[] = $departamento !== '' ? $departamento : 'Sin departamento';
                $data[] = intval($fila['equipos'] ?? 0);
            }

            $charts[] = [
                'id' => 'custodiosDepartamentos',
                'type' => 'bar',
                'horizontal' => true,
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Equipos',
                    'data' => $data,
                    'color' => 'brand',
                ]],
                'unit' => 'equipos',
                'unitSingular' => 'equipo',
                'maxBarThickness' => 24,
                'legend' => false,
            ];
        }

        $conteoEstados = [];
        foreach ($porEstado as $fila) {
            $conteoEstados[strval($fila['estado_operativo'] ?? '')] = intval($fila['equipos'] ?? 0);
        }

        $labelsEstados = [];
        $dataEstados = [];
        $colorsEstados = [];
        foreach (self::ESTADOS_EQUIPO as $estado => $estadoData) {
            $equipos = intval($conteoEstados[$estado] ?? 0);
            if ($equipos <= 0) {
                continue;
            }
            $labelsEstados[] = $estadoData['label'];
            $dataEstados[] = $equipos;
            $colorsEstados[] = $estadoData['tone'];
        }

        if (count($dataEstados) > 0) {
            $charts[] = [
                'id' => 'custodiosEstados',
                'type' => 'doughnut',
                'labels' => $labelsEstados,
                'datasets' => [[
                    'label' => 'Equipos',
                    'data' => $dataEstados,
                    'colors' => $colorsEstados,
                ]],
                'unit' => 'equipos',
                'unitSingular' => 'equipo',
            ];
        }

        if (count($topCustodios) > 0) {
            $labelsTop = [];
            $dataTop = [];

            foreach ($topCustodios as $fila) {
                $persona = trim(strval($fila['persona_responsable'] ?? ''));
                if ($persona === '') {
                    $persona = 'Sin responsable';
                }
                $labelsTop[] = $this->truncate($persona, 26);
                $dataTop[] = intval($fila['equipos'] ?? 0);
            }

            $charts[] = [
                'id' => 'custodiosTop',
                'type' => 'bar',
                'horizontal' => true,
                'labels' => $labelsTop,
                'datasets' => [[
                    'label' => 'Equipos',
                    'data' => $dataTop,
                    'color' => 'info',
                ]],
                'unit' => 'equipos',
                'unitSingular' => 'equipo',
                'maxBarThickness' => 22,
                'legend' => false,
            ];
        }

        return $charts;
    }

    private function buildDepartamentosAlt(array $porDepartamento): string
    {
        if (count($porDepartamento) === 0) {
            return 'Sin equipos en custodia para graficar.';
        }

        $partes = [];
        foreach ($porDepartamento as $fila) {
            $departamento = trim(strval($fila['departamento'] ?? ''));
            $partes[] = ($departamento !== '' ? $departamento : 'sin departamento')
                . ': ' . intval($fila['equipos'] ?? 0);
        }

        return 'Equipos en custodia por departamento: ' . implode(', ', $partes) . '.';
    }

    private function buildEstadosAlt(array $porEstado): string
    {
        $conteo = [];
        foreach ($porEstado as $fila) {
            $conteo[strval($fila['estado_operativo'] ?? '')] = intval($fila['equipos'] ?? 0);
        }

        $labels = [
            'DISPONIBLE' => ['disponible', 'disponibles'],
            'RESERVADO' => ['reservado', 'reservados'],
            'ASIGNADO' => ['asignado', 'asignados'],
            'EN_MANTENIMIENTO' => ['en mantenimiento', 'en mantenimiento'],
            'EN_REVISION' => ['en revisión', 'en revisión'],
            'DE_BAJA' => ['de baja', 'de baja'],
        ];

        $partes = [];
        foreach (self::ESTADOS_EQUIPO as $estado => $estadoData) {
            $equipos = intval($conteo[$estado] ?? 0);
            if ($equipos > 0) {
                $label = $labels[$estado] ?? [
                    $this->lower($estadoData['label']),
                    $this->lower($estadoData['label']),
                ];
                $partes[] = $equipos . ' ' . ($equipos === 1 ? $label[0] : $label[1]);
            }
        }

        if (count($partes) === 0) {
            return 'Sin equipos en custodia para graficar.';
        }

        return 'Equipos en custodia por estado operativo: ' . implode(', ', $partes) . '.';
    }

    private function hasEstados(array $porEstado): bool
    {
        foreach ($porEstado as $fila) {
            if (intval($fila['equipos'] ?? 0) > 0) {
                return true;
            }
        }

        return false;
    }

    private function buildTopAlt(array $topCustodios): string
    {
        if (count($topCustodios) === 0) {
            return 'Sin custodios con equipos para graficar.';
        }

        $partes = [];
        foreach ($topCustodios as $fila) {
            $persona = trim(strval($fila['persona_responsable'] ?? ''));
            if ($persona === '') {
                $persona = 'sin responsable';
            }
            $partes[] = $persona . ': ' . intval($fila['equipos'] ?? 0);
        }

        return 'Custodios con más equipos: ' . implode(', ', $partes) . '.';
    }

    private function buildExportUrl(string $format = 'csv'): string
    {
        $params = $this->filterParams();
        $params['export'] = $format;

        return 'index.php?' . http_build_query($params);
    }

    /**
     * Exporta un renglon por equipo en custodia con los filtros actuales.
     */
    private function export(string $format): void
    {
        $custodios = DaoEquiposCustodio::getCustodiosForExport(
            $this->search,
            $this->departamento
        );
        $equipos = DaoEquiposCustodio::getEquiposPorCustodios($custodios);

        $agrupados = [];
        foreach ($equipos as $equipo) {
            $key = $this->custodioKey(
                strval($equipo['persona_responsable'] ?? ''),
                strval($equipo['departamento_destino'] ?? '')
            );
            $agrupados[$key][] = $equipo;
        }

        $data = [];
        foreach ($custodios as $custodio) {
            $persona = trim(strval($custodio['persona_responsable'] ?? ''));
            $departamento = trim(strval($custodio['departamento_destino'] ?? ''));
            $key = $this->custodioKey($persona, $departamento);
            $equiposCustodio = $agrupados[$key] ?? [];
            $total = intval($custodio['equipos'] ?? count($equiposCustodio));

            if (count($equiposCustodio) === 0) {
                $data[] = [
                    $persona !== '' ? $persona : 'Sin responsable',
                    $departamento !== '' ? $departamento : 'Sin departamento',
                    $total,
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
                continue;
            }

            foreach ($equiposCustodio as $equipo) {
                $estado = strval($equipo['estado_operativo'] ?? '');
                $fecha = strtotime(strval($equipo['fecha_asignacion'] ?? ''));

                $data[] = [
                    $persona !== '' ? $persona : 'Sin responsable',
                    $departamento !== '' ? $departamento : 'Sin departamento',
                    $total,
                    trim(strval($equipo['numero_serie_equipo'] ?? '')),
                    trim(strval($equipo['codigo_patrimonial_equipo'] ?? '')),
                    trim(strval($equipo['nombre_articulo'] ?? '')),
                    trim(strval($equipo['codigo_interno_articulo'] ?? '')),
                    trim(strval($equipo['condicion_entrega'] ?? '')),
                    $fecha !== false ? date('d/m/Y', $fecha) : '',
                    trim(strval($equipo['entrega_nombre'] ?? '')),
                    self::ESTADOS_EQUIPO[$estado]['label'] ?? $estado,
                ];
            }
        }

        $headers = [
            'Custodio',
            'Departamento',
            'Equipos en custodia',
            'Serie',
            'Código patrimonial',
            'Artículo',
            'Código artículo',
            'Condición',
            'Fecha asignación',
            'Entregado por',
            'Estado operativo',
        ];

        $sections = [[
            'title' => 'Equipos por custodio',
            'headers' => $headers,
            'rows' => $data,
        ]];

        $baseName = 'custodios_' . date('Ymd_His');
        $meta = [
            'title' => 'Equipos por custodio',
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
                'Equipos por custodio',
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

        if ($this->departamento !== '') {
            $parts[] = 'Departamento: ' . $this->departamento;
        }

        if (count($parts) === 0) {
            return 'Sin filtros aplicados';
        }

        return 'Filtros aplicados: ' . implode(' · ', $parts);
    }

    private function truncate(string $value, int $length): string
    {
        if (\Utilities\Text::length($value) <= $length) {
            return $value;
        }

        return $this->substring($value, 0, $length - 1) . '…';
    }
}
