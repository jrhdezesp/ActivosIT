<?php

namespace Controllers\Equipos;

use Controllers\PrivateController;
use Dao\Catalogos\Catalogos as DaoCatalogos;
use Dao\Equipos\Equipos as DaoEquipos;
use Utilities\Paging;
use Views\Renderer;

/**
 * Presenta los equipos registrados y permite localizar grupos de equipos.
 */
class Equipos extends PrivateController
{
    private const ORDER_FIELDS = [
        'numero_serie_equipo',
        'codigo_patrimonial_equipo',
        'nombre_articulo',
        'estado_operativo',
        'fecha_adquisicion',
        'garantia_vencimiento_equipo',
        'id_equipo',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [1, 5, 10, 20, 50, 100];

    // Textos e indicadores visuales para cada situacion del equipo.
    private const ESTADOS = [
        'DISPONIBLE' => [
            'label' => 'Disponible',
            'class' => 'badge--success',
            'icon' => 'fa-circle-check',
        ],
        'RESERVADO' => [
            'label' => 'Reservado',
            'class' => 'badge--warning',
            'icon' => 'fa-bookmark',
        ],
        'ASIGNADO' => [
            'label' => 'Asignado',
            'class' => 'badge--info',
            'icon' => 'fa-user-check',
        ],
        'EN_MANTENIMIENTO' => [
            'label' => 'En mantenimiento',
            'class' => 'badge--warning',
            'icon' => 'fa-screwdriver-wrench',
        ],
        'EN_REVISION' => [
            'label' => 'En revisión',
            'class' => 'badge--outline',
            'icon' => 'fa-magnifying-glass',
        ],
        'DE_BAJA' => [
            'label' => 'De baja',
            'class' => 'badge--danger',
            'icon' => 'fa-ban',
        ],
    ];

    private const ESTADOS_FISICOS = [
        'BUENO' => ['label' => 'Bueno', 'class' => 'badge--success'],
        'REGULAR' => ['label' => 'Regular', 'class' => 'badge--warning'],
        'MALO' => ['label' => 'Malo', 'class' => 'badge--danger'],
    ];

    private $search = '';
    private $status = '';
    private $estadoOperativo = '';
    private $estadoFisico = '';
    private $articuloId = 0;
    private $orderBy = 'numero_serie_equipo';
    private $orderDescending = false;
    private $pageNumber = 1;
    private $itemsPerPage = 10;
    private $estadosFisicosLabels = [];

    /**
     * Busca los equipos solicitados y presenta el listado correspondiente.
     */
    public function run(): void
    {
        $this->getParams();
        $this->loadEstadoFisicoLabels();

        $result = DaoEquipos::getEquipos(
            $this->search,
            $this->status,
            $this->estadoOperativo,
            $this->articuloId,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage,
            $this->estadoFisico
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('equipos/equipos', $this->buildViewData($result));
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

        $this->estadoOperativo = strtoupper(strval($_GET['operativo'] ?? ''));
        if (!in_array($this->estadoOperativo, array_keys(self::ESTADOS), true)) {
            $this->estadoOperativo = '';
        }

        $this->estadoFisico = trim(strval($_GET['fisico'] ?? ''));
        if ($this->textLength($this->estadoFisico) > 100) {
            $this->estadoFisico = $this->substring($this->estadoFisico, 0, 100);
        }

        $this->articuloId = max(0, intval($_GET['articulo'] ?? 0));

        $this->orderBy = strval($_GET['orderBy'] ?? 'numero_serie_equipo');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'numero_serie_equipo';
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
        $canManage = $this->isFeatureAutorized('Controllers\\Equipos\\Equipo');
        $equipos = [];
        foreach ($result['equipos'] as $row) {
            $equipos[] = $this->mapEquipo($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $pages = intval($result['pages']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoEquipos::getSummary();
        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));
        $hasFilters = $this->hasFilters();

        $articuloFilter = $this->decorateArticuloOptions(
            DaoEquipos::getArticulosOptions(false),
            $this->articuloId
        );

        $serialSort = $this->buildSortData('numero_serie_equipo');
        $articleSort = $this->buildSortData('nombre_articulo');
        $dateSort = $this->buildSortData('fecha_adquisicion');

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Equipos_Equipos',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '–' . $to . ' de ' . $total
                . ($total === 1 ? ' equipo' : ' equipos')
            : 'Sin equipos para mostrar';

        return [
            'Equipos' => $equipos,
            'HasEquipos' => count($equipos) > 0,
            'TotalEquipos' => $total,
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
            'EstadoOperativo' => $this->estadoOperativo,
            'EstadosFiltro' => $this->decorateEstadoOptions(),
            'EstadoFisico' => $this->estadoFisico,
            'EstadosFisicosFiltro' => $this->decorateEstadoFisicoFiltro(),
            'ArticuloId' => $this->articuloId,
            'ArticulosFiltro' => $articuloFilter,
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay equipos registrados',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos equipos que coincidan con la búsqueda o los filtros seleccionados.'
                : 'Registra el primer equipo serializado para comenzar a controlar su ubicación y estado operativo.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Equipos_Equipos',
            'ClearUrl' => 'index.php?page=Equipos_Equipos&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryDisponibles' => intval($summary['disponibles']),
            'SummaryAsignados' => intval($summary['asignados']),
            'SummaryServicio' => intval($summary['servicio']),
            'SortSerialUrl' => $serialSort['url'],
            'SortSerialActive' => $serialSort['active'],
            'SortSerialIcon' => $serialSort['icon'],
            'SortArticleUrl' => $articleSort['url'],
            'SortArticleActive' => $articleSort['active'],
            'SortArticleIcon' => $articleSort['icon'],
            'SortDateUrl' => $dateSort['url'],
            'SortDateActive' => $dateSort['active'],
            'SortDateIcon' => $dateSort['icon'],
            'FlashHas' => $flash !== null,
            'FlashType' => $flash['type'] ?? '',
            'FlashIcon' => $flash['icon'] ?? '',
            'FlashTitle' => $flash['title'] ?? '',
            'FlashMessage' => $flash['message'] ?? '',
            'CanManage' => $canManage,
            'CreateUrl' => 'index.php?page=Equipos_Equipo&mode=INS',
        ];
    }

    private function loadEstadoFisicoLabels(): void
    {
        $this->estadosFisicosLabels = [];
        foreach (DaoCatalogos::getItems('estados_fisicos', '', '1') as $row) {
            $name = trim(strval($row['nombre_estado_fisico'] ?? ''));
            if ($name !== '') {
                $this->estadosFisicosLabels[$name] = $name;
            }
        }
    }

    private function mapEquipo(array $row): array
    {
        $id = intval($row['id_equipo'] ?? 0);
        $isActive = intval($row['estado_registro'] ?? 0) === 1;
        $estado = strval($row['estado_operativo'] ?? '');
        $estadoData = self::ESTADOS[$estado] ?? [
            'label' => 'Sin definir',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
        ];
        $fisico = strval($row['estado_fisico'] ?? '');
        $fisicoData = self::ESTADOS_FISICOS[$fisico] ?? [
            'label' => $this->estadosFisicosLabels[$fisico] ?? ($fisico !== '' ? $fisico : 'Sin definir'),
            'class' => 'badge--outline',
        ];

        $serial = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));
        $article = trim(strval($row['nombre_articulo'] ?? ''));
        $articleCode = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $location = trim(strval($row['nombre_ubicacion'] ?? ''));
        $motivo = trim(strval($row['nombre_motivo'] ?? ''));

        $acquisition = $this->formatDate(strval($row['fecha_adquisicion'] ?? ''));
        $warranty = $this->formatDate(strval($row['garantia_vencimiento_equipo'] ?? ''));

        $warrantyBadge = '';
        $warrantyBadgeClass = '';
        $warrantyDate = trim(strval($row['garantia_vencimiento_equipo'] ?? ''));
        if ($warrantyDate !== '') {
            $warrantyTime = strtotime($warrantyDate);
            if ($warrantyTime !== false) {
                $daysLeft = intval(floor(($warrantyTime - strtotime(date('Y-m-d'))) / 86400));
                if ($daysLeft < 0) {
                    $warrantyBadge = 'Vencida';
                    $warrantyBadgeClass = 'badge--danger';
                } elseif ($daysLeft <= 30) {
                    $warrantyBadge = 'Por vencer';
                    $warrantyBadgeClass = 'badge--warning';
                }
            }
        }

        return [
            'id' => $id,
            'serial' => $serial !== '' ? $serial : 'Sin número de serie',
            'patrimonial' => $patrimonial !== '' ? $patrimonial : 'Sin código patrimonial',
            'article' => $article !== '' ? $article : 'Artículo no disponible',
            'articleCode' => $articleCode !== '' ? $articleCode : 'Sin código interno',
            'location' => $location !== '' ? $location : 'Sin ubicación',
            'estadoLabel' => $estadoData['label'],
            'estadoClass' => $estadoData['class'],
            'estadoIcon' => $estadoData['icon'],
            'estadoFisicoLabel' => $fisicoData['label'],
            'estadoFisicoClass' => $fisicoData['class'],
            'motivoLabel' => $motivo,
            'acquisitionLabel' => $acquisition !== '' ? $acquisition : 'Sin fecha de adquisición',
            'warrantyLabel' => $warranty !== '' ? 'Garantía: ' . $warranty : 'Sin garantía registrada',
            'warrantyBadge' => $warrantyBadge,
            'warrantyBadgeClass' => $warrantyBadgeClass,
            'isInactive' => !$isActive,
            'editUrl' => 'index.php?page=Equipos_Equipo&mode=UPD&id=' . $id,
            'deactivateUrl' => 'index.php?page=Equipos_Equipo&mode=DEL&id=' . $id,
        ];
    }

    private function decorateEstadoOptions(): array
    {
        $options = [];
        foreach (self::ESTADOS as $value => $data) {
            $options[] = [
                'value' => $value,
                'label' => $data['label'],
                'selected' => $value === $this->estadoOperativo ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateEstadoFisicoFiltro(): array
    {
        $values = [];
        foreach (self::ESTADOS_FISICOS as $value => $data) {
            $values[$value] = $data['label'];
        }
        foreach ($this->estadosFisicosLabels as $value => $label) {
            if (!isset($values[$value])) {
                $values[$value] = $label;
            }
        }
        if ($this->estadoFisico !== '' && !isset($values[$this->estadoFisico])) {
            $values[$this->estadoFisico] = $this->estadoFisico;
        }

        $options = [];
        foreach ($values as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $this->estadoFisico ? 'selected' : '',
            ];
        }

        return $options;
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
        $params = ['page' => 'Equipos_Equipos'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->status !== '') {
            $params['status'] = $this->status;
        }
        if ($this->estadoOperativo !== '') {
            $params['operativo'] = $this->estadoOperativo;
        }
        if ($this->estadoFisico !== '') {
            $params['fisico'] = $this->estadoFisico;
        }
        if ($this->articuloId > 0) {
            $params['articulo'] = $this->articuloId;
        }

        return $params;
    }

    private function decorateArticuloOptions(array $rows, int $selectedId): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            $code = trim(strval($row['code'] ?? ''));
            if ($code !== '') {
                $label .= ' (' . $code . ')';
            }
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactivo)';
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
            || $this->estadoOperativo !== ''
            || $this->estadoFisico !== ''
            || $this->articuloId > 0;
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('d/m/Y', $timestamp) : $value;
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Equipo registrado',
                'message' => 'El equipo se guardó correctamente en el inventario.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Equipo actualizado',
                'message' => 'Los cambios se guardaron correctamente.',
            ],
            'deactivated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Equipo desactivado',
                'message' => 'El equipo quedó inactivo y se conserva en el historial.',
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
