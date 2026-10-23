<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Mantenimientos\Mantenimientos as DaoMantenimientos;
use Utilities\Paging;
use Views\Renderer;

class Mantenimientos extends PrivateController
{
    private const ORDER_FIELDS = [
        'fecha_recepcion',
        'fecha_finalizacion',
        'costo_mantenimiento',
        'estado_mantenimiento',
        'numero_serie_equipo',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    private const ESTADOS = [
        'EN_PROCESO' => [
            'label' => 'En proceso',
            'class' => 'badge--warning',
            'icon' => 'fa-screwdriver-wrench',
        ],
        'COMPLETADO' => [
            'label' => 'Completado',
            'class' => 'badge--success',
            'icon' => 'fa-circle-check',
        ],
        'CANCELADO' => [
            'label' => 'Cancelado',
            'class' => 'badge--danger',
            'icon' => 'fa-ban',
        ],
    ];

    private $search = '';
    private $estado = '';
    private $tipoId = 0;
    private $orderBy = 'fecha_recepcion';
    private $orderDescending = true;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    public function run(): void
    {
        $this->getParams();

        $result = DaoMantenimientos::getMantenimientos(
            $this->search,
            $this->estado,
            $this->tipoId,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('mantenimientos/mantenimientos', $this->buildViewData($result));
    }

    private function getParams(): void
    {
        $this->search = trim(strval($_GET['q'] ?? ''));
        if ($this->textLength($this->search) > 100) {
            $this->search = $this->substring($this->search, 0, 100);
        }

        $this->estado = strtoupper(strval($_GET['estado'] ?? ''));
        if (!in_array($this->estado, array_keys(self::ESTADOS), true)) {
            $this->estado = '';
        }

        $this->tipoId = max(0, intval($_GET['tipo'] ?? 0));

        $this->orderBy = strval($_GET['orderBy'] ?? 'fecha_recepcion');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'fecha_recepcion';
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
        $mantenimientos = [];
        foreach ($result['mantenimientos'] as $row) {
            $mantenimientos[] = $this->mapMantenimiento($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoMantenimientos::getSummary();
        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));
        $hasFilters = $this->hasFilters();
        $canManage = $this->isFeatureAutorized('Controllers\\Inventario\\Mantenimiento');

        $equipoSort = $this->buildSortData('numero_serie_equipo');
        $fechaSort = $this->buildSortData('fecha_recepcion');
        $estadoSort = $this->buildSortData('estado_mantenimiento');
        $costoSort = $this->buildSortData('costo_mantenimiento');

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Inventario_Mantenimientos',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '–' . $to . ' de ' . $total
                . ($total === 1 ? ' mantenimiento' : ' mantenimientos')
            : 'Sin mantenimientos para mostrar';

        return [
            'Mantenimientos' => $mantenimientos,
            'HasMantenimientos' => count($mantenimientos) > 0,
            'TotalMantenimientos' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Estado' => $this->estado,
            'EstadosFiltro' => $this->decorateEstadoFiltro(),
            'TipoId' => $this->tipoId,
            'TiposFiltro' => $this->decorateTipoFiltro(),
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay mantenimientos registrados',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos mantenimientos que coincidan con la búsqueda o los filtros seleccionados.'
                : ($canManage
                    ? 'Registra el primer mantenimiento para llevar el control del servicio técnico de los equipos.'
                    : 'Todavía no se han registrado mantenimientos en el sistema.'),
            'CanManage' => $canManage,
            'SearchAction' => 'index.php',
            'ListRoute' => 'Inventario_Mantenimientos',
            'ClearUrl' => 'index.php?page=Inventario_Mantenimientos&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryEnProceso' => intval($summary['en_proceso']),
            'SummaryCompletados' => intval($summary['completados']),
            'SummaryCancelados' => intval($summary['cancelados']),
            'SummaryCosto' => $this->formatMoney(floatval($summary['costo_total'])),
            'SortEquipoUrl' => $equipoSort['url'],
            'SortEquipoActive' => $equipoSort['active'],
            'SortEquipoIcon' => $equipoSort['icon'],
            'SortFechaUrl' => $fechaSort['url'],
            'SortFechaActive' => $fechaSort['active'],
            'SortFechaIcon' => $fechaSort['icon'],
            'SortEstadoUrl' => $estadoSort['url'],
            'SortEstadoActive' => $estadoSort['active'],
            'SortEstadoIcon' => $estadoSort['icon'],
            'SortCostoUrl' => $costoSort['url'],
            'SortCostoActive' => $costoSort['active'],
            'SortCostoIcon' => $costoSort['icon'],
            'FlashHas' => $flash !== null,
            'FlashType' => $flash['type'] ?? '',
            'FlashIcon' => $flash['icon'] ?? '',
            'FlashTitle' => $flash['title'] ?? '',
            'FlashMessage' => $flash['message'] ?? '',
            'CreateUrl' => 'index.php?page=Inventario_Mantenimiento&mode=INS',
        ];
    }

    private function mapMantenimiento(array $row): array
    {
        $id = intval($row['id_mantenimiento'] ?? 0);
        $estado = strval($row['estado_mantenimiento'] ?? '');
        $estadoData = self::ESTADOS[$estado] ?? [
            'label' => 'Sin definir',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
        ];

        $serie = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));
        $articulo = trim(strval($row['nombre_articulo'] ?? ''));
        $codigoArticulo = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $tipo = trim(strval($row['nombre_tipo'] ?? ''));
        $tecnico = trim(strval($row['tecnico_responsable'] ?? ''));

        $equipoLabel = $serie !== '' ? $serie : 'Equipo #' . $id;
        if ($patrimonial !== '') {
            $equipoLabel .= ' · ' . $patrimonial;
        }

        $equipoSubtext = $patrimonial;
        if ($articulo !== '') {
            $equipoSubtext .= ($equipoSubtext !== '' ? ' · ' : '') . $articulo;
        }
        if ($codigoArticulo !== '') {
            $equipoSubtext .= ' (' . $codigoArticulo . ')';
        }

        $recepcion = strtotime(strval($row['fecha_recepcion'] ?? ''));
        $recepcionDate = $recepcion !== false ? date('d/m/Y', $recepcion) : 'Sin fecha';
        $recepcionTime = $recepcion !== false ? date('H:i', $recepcion) : '';

        $finalizacion = strtotime(strval($row['fecha_finalizacion'] ?? ''));
        $finalizacionLabel = '';
        if ($finalizacion !== false) {
            $finalizacionLabel = 'Cierre: ' . date('d/m/Y H:i', $finalizacion);
        }

        $duracionLabel = $this->duracionLabel($recepcion, $finalizacion, $estado);
        $canEdit = $estado === 'EN_PROCESO';

        return [
            'id' => $id,
            'equipoLabel' => $equipoLabel,
            'equipoSubtext' => $equipoSubtext !== '' ? $equipoSubtext : 'Sin artículo asociado',
            'tipo' => $tipo !== '' ? $tipo : 'Tipo no disponible',
            'tecnico' => $tecnico !== '' ? $tecnico : 'Sin técnico asignado',
            'fechaRecepcionDate' => $recepcionDate,
            'fechaRecepcionTime' => $recepcionTime,
            'fechaFinalizacionLabel' => $finalizacionLabel,
            'duracionLabel' => $duracionLabel,
            'estadoLabel' => $estadoData['label'],
            'estadoClass' => $estadoData['class'],
            'estadoIcon' => $estadoData['icon'],
            'costoFmt' => $this->formatMoney(floatval($row['costo_mantenimiento'] ?? 0)),
            'canEdit' => $canEdit,
            'canCancel' => $canEdit,
            'rowActionIcon' => $canEdit ? 'fa-pen' : 'fa-eye',
            'rowActionTitle' => $canEdit ? 'Editar mantenimiento' : 'Ver mantenimiento',
            'editUrl' => 'index.php?page=Inventario_Mantenimiento&mode=UPD&id=' . $id,
            'cancelUrl' => 'index.php?page=Inventario_Mantenimiento&mode=DEL&id=' . $id,
        ];
    }

    private function duracionLabel($recepcion, $finalizacion, string $estado): string
    {
        if ($recepcion === false) {
            return '';
        }

        if ($finalizacion !== false) {
            $segundos = max(0, $finalizacion - $recepcion);
            $horas = $segundos / 3600;

            if ($horas < 24) {
                return max(1, intval(round($horas))) . ' h de servicio';
            }

            return number_format($horas / 24, 1, '.', ',') . ' días de servicio';
        }

        return $estado === 'EN_PROCESO' ? 'En curso' : 'Sin cierre registrado';
    }

    private function decorateEstadoFiltro(): array
    {
        $options = [];
        foreach (self::ESTADOS as $value => $data) {
            $options[] = [
                'value' => $value,
                'label' => $data['label'],
                'selected' => $value === $this->estado ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateTipoFiltro(): array
    {
        $options = [];
        foreach (DaoMantenimientos::getTiposMantenimiento(false) as $row) {
            $value = intval($row['value'] ?? 0);
            $options[] = [
                'value' => $value,
                'label' => strval($row['label'] ?? ''),
                'selected' => $value === $this->tipoId ? 'selected' : '',
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
        $params = ['page' => 'Inventario_Mantenimientos'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->estado !== '') {
            $params['estado'] = $this->estado;
        }
        if ($this->tipoId > 0) {
            $params['tipo'] = $this->tipoId;
        }

        return $params;
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->estado !== ''
            || $this->tipoId > 0;
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Mantenimiento registrado',
                'message' => 'El equipo quedó registrado en el taller y su estado operativo se actualizó.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Mantenimiento actualizado',
                'message' => 'Los cambios del mantenimiento se guardaron correctamente.',
            ],
            'completed' => [
                'type' => 'success',
                'icon' => 'fa-screwdriver-wrench',
                'title' => 'Mantenimiento completado',
                'message' => 'El trabajo quedó registrado y el equipo fue liberado del taller.',
            ],
            'cancelled' => [
                'type' => 'success',
                'icon' => 'fa-ban',
                'title' => 'Mantenimiento cancelado',
                'message' => 'El registro se conserva como historial y el equipo fue liberado del taller.',
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

    private function formatMoney(float $value): string
    {
        return number_format($value, 2, '.', ',');
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
