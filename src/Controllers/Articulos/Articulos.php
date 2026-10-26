<?php

namespace Controllers\Articulos;

use Controllers\PrivateController;
use Dao\Articulos\Articulos as DaoArticulos;
use Utilities\Paging;
use Views\Renderer;

class Articulos extends PrivateController
{
    private const ORDER_FIELDS = [
        'codigo_interno_articulo',
        'nombre_articulo',
        'existencia_actual',
        'existencia_minima',
        'fecha_creacion',
        'id_articulo',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [1, 5, 10, 20, 50, 100];

    private $search = '';
    private $status = '';
    private $clasificacionId = 0;
    private $orderBy = 'nombre_articulo';
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    public function run(): void
    {
        $this->getParams();

        $result = DaoArticulos::getArticulos(
            $this->search,
            $this->status,
            $this->clasificacionId,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('articulos/articulos', $this->buildViewData($result));
    }

    private function getParams(): void
    {
        $this->search = trim(strval($_GET['q'] ?? ''));
        if ($this->textLength($this->search) > 100) {
            $this->search = $this->substring($this->search, 0, 100);
        }

        $this->status = strval($_GET['status'] ?? '');
        if (!in_array($this->status, ['', '0', '1'], true)) {
            $this->status = '';
        }

        $this->clasificacionId = max(0, intval($_GET['clasificacion'] ?? 0));

        $this->orderBy = strval($_GET['orderBy'] ?? 'nombre_articulo');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'nombre_articulo';
        }

        $this->orderDescending = in_array(
            strval($_GET['orderDescending'] ?? '0'),
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
        $canManage = $this->isFeatureAutorized('Controllers\\Articulos\\Articulo');
        $articles = [];
        foreach ($result['articulos'] as $row) {
            $articles[] = $this->mapArticle($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $pages = intval($result['pages']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoArticulos::getSummary();
        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));
        $hasFilters = $this->hasFilters();

        $classificationFilter = $this->decorateClassificationOptions(
            DaoArticulos::getClasificacionesOptions(false),
            $this->clasificacionId
        );

        $codeSort = $this->buildSortData('codigo_interno_articulo');
        $nameSort = $this->buildSortData('nombre_articulo');
        $stockSort = $this->buildSortData('existencia_actual');

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Articulos_Articulos',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '–' . $to . ' de ' . $total
                . ($total === 1 ? ' artículo' : ' artículos')
            : 'Sin artículos para mostrar';

        return [
            'Articulos' => $articles,
            'HasArticulos' => count($articles) > 0,
            'TotalArticulos' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Pages' => $pages,
            'Search' => $this->search,
            'Status' => $this->status,
            'StatusAllSelected' => $this->status === '' ? 'selected' : '',
            'StatusActiveSelected' => $this->status === '1' ? 'selected' : '',
            'StatusInactiveSelected' => $this->status === '0' ? 'selected' : '',
            'ClasificacionId' => $this->clasificacionId,
            'ClasificacionesFiltro' => $classificationFilter,
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay artículos',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos artículos que coincidan con la búsqueda o los filtros seleccionados.'
                : 'Registra el primer artículo para comenzar a controlar las existencias del área de TI.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Articulos_Articulos',
            'ClearUrl' => 'index.php?page=Articulos_Articulos&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryCantidad' => intval($summary['cantidad']),
            'SummarySerie' => intval($summary['serie']),
            'SummaryBajo' => intval($summary['bajo']),
            'SortCodeUrl' => $codeSort['url'],
            'SortCodeActive' => $codeSort['active'],
            'SortCodeIcon' => $codeSort['icon'],
            'SortNameUrl' => $nameSort['url'],
            'SortNameActive' => $nameSort['active'],
            'SortNameIcon' => $nameSort['icon'],
            'SortStockUrl' => $stockSort['url'],
            'SortStockActive' => $stockSort['active'],
            'SortStockIcon' => $stockSort['icon'],
            'FlashHas' => $flash !== null,
            'FlashType' => $flash['type'] ?? '',
            'FlashIcon' => $flash['icon'] ?? '',
            'FlashTitle' => $flash['title'] ?? '',
            'FlashMessage' => $flash['message'] ?? '',
            'CanManage' => $canManage,
            'CreateUrl' => 'index.php?page=Articulos_Articulo&mode=INS',
        ];
    }

    private function mapArticle(array $row): array
    {
        $id = intval($row['id_articulo'] ?? 0);
        $control = strval($row['tipo_control'] ?? '');
        $isActive = intval($row['estado_registro'] ?? 0) === 1;
        $minimum = $row['existencia_minima'] === null ? null : intval($row['existencia_minima']);
        $actual = intval($row['existencia_actual'] ?? 0);
        $isLowStock = $isActive && $control === 'CANTIDAD' && $actual <= ($minimum ?? 0);

        $description = trim(strval($row['descripcion_articulo'] ?? ''));
        if ($description === '') {
            $description = 'Sin descripción';
        }

        $unit = trim(strval($row['nombre_unidad'] ?? ''));
        $symbol = trim(strval($row['simbolo_medida'] ?? ''));
        if ($symbol !== '') {
            $unit .= ' (' . $symbol . ')';
        }

        if ($control === 'CANTIDAD') {
            $controlLabel = 'Por cantidad';
            $controlClass = 'badge--info';
        } elseif ($control === 'SERIE') {
            $controlLabel = 'Por serie';
            $controlClass = 'badge--warning';
        } else {
            $controlLabel = 'Sin definir';
            $controlClass = 'badge--outline';
        }

        return [
            'id' => $id,
            'code' => strval($row['codigo_interno_articulo'] ?? ''),
            'name' => strval($row['nombre_articulo'] ?? ''),
            'description' => $description,
            'classification' => strval($row['nombre_clasificacion'] ?? 'Sin clasificación'),
            'unit' => $unit,
            'controlLabel' => $controlLabel,
            'controlClass' => $controlClass,
            'actual' => $actual,
            'minLabel' => $minimum === null ? 'Sin mínimo' : 'Mín. ' . $minimum,
            'isLow' => $isLowStock,
            'stockClass' => $isLowStock ? 'article-stock--low' : '',
            'statusLabel' => $isActive ? 'Activo' : 'Inactivo',
            'statusClass' => $isActive ? 'badge--success' : 'badge--danger',
            'editUrl' => 'index.php?page=Articulos_Articulo&mode=UPD&id=' . $id,
            'deactivateUrl' => 'index.php?page=Articulos_Articulo&mode=DEL&id=' . $id,
        ];
    }

    private function buildSortData(string $field): array
    {
        $isCurrent = $this->orderBy === $field;
        $descending = $isCurrent ? !$this->orderDescending : false;

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
        $params = ['page' => 'Articulos_Articulos'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->clasificacionId > 0) {
            $params['clasificacion'] = $this->clasificacionId;
        }

        return $params;
    }

    private function decorateClassificationOptions(array $rows, int $selectedId): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactiva)';
            }

            $options[] = [
                'value' => strval($row['value'] ?? ''),
                'label' => $label,
                'selected' => intval($row['value'] ?? 0) === $selectedId ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->status !== ''
            || $this->clasificacionId > 0;
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Artículo creado',
                'message' => 'El artículo se guardó correctamente en el catálogo.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Artículo actualizado',
                'message' => 'Los cambios se guardaron correctamente.',
            ],
            'deactivated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Artículo desactivado',
                'message' => 'El artículo quedó inactivo y se conserva en el historial.',
            ],
            'error' => [
                'type' => 'danger',
                'icon' => 'fa-circle-exclamation',
                'title' => 'No se pudo completar la operación',
                'message' => 'Revisa los datos e intenta de nuevo.',
            ],
        ];

        return $flashes[$code] ?? null;
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
}
