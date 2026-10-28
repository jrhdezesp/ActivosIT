<?php

namespace Controllers\Reportes;

use Controllers\PrivateController;
use Dao\Reportes\Operativos as DaoOperativos;
use Utilities\Fechas;
use Utilities\Paging;
use Utilities\ReportExport;
use Views\Renderer;

/**
 * Presenta informes operativos que se pueden consultar y descargar.
 */
class Operativos extends PrivateController
{
    private string $type = 'inventario';
    private string $search = '';
    private string $desde = '';
    private string $hasta = '';
    private string $departamento = '';
    private string $estado = '';
    private int $articuloId = 0;
    private int $usuarioId = 0;
    private int $categoriaId = 0;
    private int $ubicacionId = 0;
    private int $pageNumber = 1;
    private int $itemsPerPage = 10;
    private array $articulosFilter = [];
    private array $usuariosFilter = [];
    private array $categoriasFilter = [];
    private array $ubicacionesFilter = [];
    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    /**
     * Reune los filtros, obtiene el informe y muestra o descarga sus resultados.
     */
    public function run(): void
    {
        $this->readParams();
        $this->loadFilterOptions();
        $filters = [
            'q' => $this->search,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'departamento' => $this->departamento,
            'estado' => $this->estado,
            'articulo' => $this->articuloId,
            'usuario' => $this->usuarioId,
            'categoria' => $this->categoriaId,
            'ubicacion' => $this->ubicacionId,
            'page' => $this->pageNumber,
            'itemsPerPage' => $this->itemsPerPage,
            'paginate' => false,
        ];

        $format = strtolower(trim(strval($_GET['export'] ?? '')));
        if (in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $this->requireAction('Acciones.Reportes.Exportar');
        }
        if (!in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $filters['paginate'] = true;
        }
        $report = DaoOperativos::getReport($this->type, $filters);
        $total = intval($report['total']);
        $pages = max(1, intval(ceil($total / $this->itemsPerPage)));
        if ($this->pageNumber > $pages) {
            $this->pageNumber = $pages;
            $filters['page'] = $pages;
            $report = DaoOperativos::getReport($this->type, $filters);
        }
        if (in_array($format, ['csv', 'xlsx', 'pdf'], true)) {
            $this->export($format, $report);
            return;
        }

        $types = DaoOperativos::getReportTypes();
        foreach ($types as &$item) {
            $item['selected'] = strval($item['value']) === $this->type ? 'selected' : '';
        }
        unset($item);

        Renderer::render('reportes/operativos', [
            'ReportTypes' => $types,
            'ReportTitle' => $report['title'],
            'ReportDescription' => $report['description'],
            'Columns' => $report['columns'],
            'Rows' => $this->buildRows($report['rows']),
            'HasRows' => count($report['rows']) > 0,
            'ResultCount' => count($report['rows']),
            'ResultsLabel' => $this->buildResultsLabel($total),
            'ItemsPerPage' => $this->itemsPerPage,
            'PageNumber' => $this->pageNumber,
            'Pagination' => $this->buildPagination($total),
            'HasFilters' => $this->hasFilters(),
            'Search' => $this->search,
            'Desde' => $this->desde,
            'Hasta' => $this->hasta,
            'Departamento' => $this->departamento,
            'Estado' => $this->estado,
            'ArticuloId' => $this->articuloId,
            'UsuarioId' => $this->usuarioId,
            'CategoriaId' => $this->categoriaId,
            'UbicacionId' => $this->ubicacionId,
            'ArticulosFilter' => $this->articulosFilter,
            'UsuariosFilter' => $this->usuariosFilter,
            'CategoriasFilter' => $this->categoriasFilter,
            'UbicacionesFilter' => $this->ubicacionesFilter,
            'ListRoute' => 'Reportes_Operativos',
            'SearchAction' => 'index.php',
            'ReportDate' => date('d/m/Y H:i'),
            'ExportCsvUrl' => $this->buildUrl('csv'),
            'ExportXlsxUrl' => $this->buildUrl('xlsx'),
            'ExportPdfUrl' => $this->buildUrl('pdf'),
            'ClearUrl' => 'index.php?page=Reportes_Operativos',
            'CentroUrl' => 'index.php?page=Reportes_Reportes',
            'KardexUrl' => 'index.php?page=Reportes_Kardex',
        ]);
    }

    private function readParams(): void
    {
        $this->type = trim(strval($_GET['tipo'] ?? 'inventario'));
        $this->search = trim(strval($_GET['q'] ?? ''));
        $this->desde = Fechas::normalizarFecha(strval($_GET['desde'] ?? '')) ?? '';
        $this->hasta = Fechas::normalizarFecha(strval($_GET['hasta'] ?? '')) ?? '';
        $this->departamento = trim(strval($_GET['departamento'] ?? ''));
        $this->estado = trim(strval($_GET['estado'] ?? ''));
        $this->articuloId = max(0, intval($_GET['articulo'] ?? 0));
        $this->usuarioId = max(0, intval($_GET['usuario'] ?? 0));
        $this->categoriaId = max(0, intval($_GET['categoria'] ?? 0));
        $this->ubicacionId = max(0, intval($_GET['ubicacion'] ?? 0));
        $this->pageNumber = max(1, intval($_GET['pageNum'] ?? 1));
        $itemsPerPage = intval($_GET['itemsPerPage'] ?? 10);
        $this->itemsPerPage = in_array($itemsPerPage, self::ITEMS_PER_PAGE_OPTIONS, true)
            ? $itemsPerPage
            : 10;

        if ($this->textLength($this->search) > 100) {
            $this->search = $this->substring($this->search, 0, 100);
        }
        if ($this->textLength($this->departamento) > 150) {
            $this->departamento = $this->substring($this->departamento, 0, 150);
        }
        if ($this->textLength($this->estado) > 40) {
            $this->estado = $this->substring($this->estado, 0, 40);
        }
        if ($this->desde !== '' && $this->hasta !== '' && $this->desde > $this->hasta) {
            [$this->desde, $this->hasta] = [$this->hasta, $this->desde];
        }
    }

    private function buildUrl(string $format = ''): string
    {
        $params = [
            'page' => 'Reportes_Operativos',
            'tipo' => $this->type,
            'q' => $this->search,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'departamento' => $this->departamento,
            'estado' => $this->estado,
            'articulo' => $this->articuloId,
            'usuario' => $this->usuarioId,
            'categoria' => $this->categoriaId,
            'ubicacion' => $this->ubicacionId,
            'itemsPerPage' => $this->itemsPerPage,
        ];
        if ($format !== '') {
            $params['export'] = $format;
        }

        return 'index.php?' . http_build_query($params);
    }

    private function buildPagination(int $total): string
    {
        $params = [
            'page' => 'Reportes_Operativos',
            'tipo' => $this->type,
            'q' => $this->search,
            'desde' => $this->desde,
            'hasta' => $this->hasta,
            'departamento' => $this->departamento,
            'estado' => $this->estado,
            'articulo' => $this->articuloId,
            'usuario' => $this->usuarioId,
            'categoria' => $this->categoriaId,
            'ubicacion' => $this->ubicacionId,
            'itemsPerPage' => $this->itemsPerPage,
        ];
        $baseUrl = 'index.php?' . http_build_query($params);
        $extraParams = [];
        foreach ($params as $name => $value) {
            if ($name !== 'page' && $value !== '') {
                $extraParams[] = ['name' => $name, 'value' => strval($value)];
            }
        }

        return Paging::getPagination(
            $total,
            $this->itemsPerPage,
            $this->pageNumber,
            $baseUrl,
            'Reportes_Operativos',
            $extraParams
        );
    }

    private function buildResultsLabel(int $total): string
    {
        if ($total === 0) {
            return 'Sin resultados para mostrar';
        }

        $from = (($this->pageNumber - 1) * $this->itemsPerPage) + 1;
        $to = min($total, $this->pageNumber * $this->itemsPerPage);

        return 'Mostrando ' . $from . '-' . $to . ' de ' . $total
            . ($total === 1 ? ' resultado' : ' resultados');
    }

    private function export(string $format, array $report): void
    {
        $baseName = 'reporte_' . $this->type . '_' . date('Ymd_His');
        $meta = [
            'title' => $report['title'],
            'creator' => 'ActivosIT',
            'Generado: ' . date('d/m/Y H:i'),
            'Filtros: ' . $this->filterLabel(),
        ];

        if ($format === 'xlsx') {
            ReportExport::streamXlsx($baseName . '.xlsx', $report['columns'], $report['rows'], $meta);
            return;
        }
        if ($format === 'pdf') {
            ReportExport::streamPdf($baseName . '.pdf', $report['title'], $report['columns'], $report['rows'], $meta);
            return;
        }

        ReportExport::stream($baseName . '.csv', $report['columns'], $report['rows']);
    }

    private function filterLabel(): string
    {
        $filters = [];
        foreach ([
            'Búsqueda' => $this->search,
            'Desde' => $this->desde,
            'Hasta' => $this->hasta,
            'Departamento' => $this->departamento,
            'Estado' => $this->estado,
            'Articulo' => $this->articuloId > 0 ? $this->optionLabel($this->articulosFilter, $this->articuloId) : '',
            'Usuario' => $this->usuarioId > 0 ? $this->optionLabel($this->usuariosFilter, $this->usuarioId) : '',
            'Categoria' => $this->categoriaId > 0 ? $this->optionLabel($this->categoriasFilter, $this->categoriaId) : '',
            'Ubicacion' => $this->ubicacionId > 0 ? $this->optionLabel($this->ubicacionesFilter, $this->ubicacionId) : '',
        ] as $label => $value) {
            if ($value !== '') {
                $filters[] = $label . ': ' . $value;
            }
        }

        return count($filters) > 0 ? implode(' | ', $filters) : 'Sin filtros';
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->desde !== ''
            || $this->hasta !== ''
            || $this->departamento !== ''
            || $this->estado !== ''
            || $this->articuloId > 0
            || $this->usuarioId > 0
            || $this->categoriaId > 0
            || $this->ubicacionId > 0;
    }

    private function loadFilterOptions(): void
    {
        $this->articulosFilter = $this->markSelected(
            DaoOperativos::getArticulosOptions(),
            $this->articuloId
        );
        $this->usuariosFilter = $this->markSelected(
            DaoOperativos::getUsuariosOptions(),
            $this->usuarioId
        );
        $this->categoriasFilter = $this->markSelected(
            DaoOperativos::getCategoriasOptions(),
            $this->categoriaId
        );
        $this->ubicacionesFilter = $this->markSelected(
            DaoOperativos::getUbicacionesOptions(),
            $this->ubicacionId
        );
    }

    private function markSelected(array $options, int $selectedId): array
    {
        foreach ($options as &$option) {
            $option['selected'] = intval($option['value']) === $selectedId ? 'selected' : '';
        }
        unset($option);

        return $options;
    }

    private function optionLabel(array $options, int $id): string
    {
        foreach ($options as $option) {
            if (intval($option['value']) === $id) {
                return strval($option['label']);
            }
        }

        return strval($id);
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function substring(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length, 'UTF-8')
            : substr($value, $start, $length);
    }

    private function buildRows(array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = ['Cells' => array_values($row)];
        }

        return $items;
    }
}
