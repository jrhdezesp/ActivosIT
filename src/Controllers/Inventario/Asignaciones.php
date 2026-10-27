<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Asignaciones as DaoAsignaciones;
use Utilities\Paging;
use Views\Renderer;

/**
 * Presenta el historial de equipos asignados y sus devoluciones.
 */
class Asignaciones extends PrivateController
{
    private const ORDER_FIELDS = [
        'fecha_asignacion',
        'fecha_devolucion',
        'persona_responsable',
        'estado_asignacion',
        'numero_serie_equipo',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    private const ESTADOS = [
        'ACTIVA' => [
            'label' => 'Activa',
            'class' => 'badge--success',
            'icon' => 'fa-user-check',
        ],
        'DEVUELTO' => [
            'label' => 'Devuelto',
            'class' => 'badge--outline',
            'icon' => 'fa-rotate-left',
        ],
    ];

    private $search = '';
    private $estado = '';
    private $departamento = '';
    private $orderBy = 'fecha_asignacion';
    private $orderDescending = true;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    /** Obtiene y muestra las asignaciones que coinciden con la consulta. */
    public function run(): void
    {
        $this->getParams();

        $result = DaoAsignaciones::getAsignaciones(
            $this->search,
            $this->estado,
            $this->departamento,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('inventario/asignaciones', $this->buildViewData($result));
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

        $this->departamento = trim(strval($_GET['departamento'] ?? ''));
        if ($this->textLength($this->departamento) > 150) {
            $this->departamento = $this->substring($this->departamento, 0, 150);
        }

        $this->orderBy = strval($_GET['orderBy'] ?? 'fecha_asignacion');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'fecha_asignacion';
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
        $asignaciones = [];
        foreach ($result['asignaciones'] as $row) {
            $asignaciones[] = $this->mapAsignacion($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoAsignaciones::getSummary();
        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));
        $hasFilters = $this->hasFilters();
        $canManage = $this->isFeatureAutorized('Controllers\\Inventario\\Asignacion');

        $equipoSort = $this->buildSortData('numero_serie_equipo');
        $personaSort = $this->buildSortData('persona_responsable');
        $fechaSort = $this->buildSortData('fecha_asignacion');
        $devolucionSort = $this->buildSortData('fecha_devolucion');
        $estadoSort = $this->buildSortData('estado_asignacion');

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Inventario_Asignaciones',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '–' . $to . ' de ' . $total
                . ($total === 1 ? ' asignación' : ' asignaciones')
            : 'Sin asignaciones para mostrar';

        return [
            'Asignaciones' => $asignaciones,
            'HasAsignaciones' => count($asignaciones) > 0,
            'TotalAsignaciones' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Estado' => $this->estado,
            'EstadosFiltro' => $this->decorateEstadoFiltro(),
            'Departamento' => $this->departamento,
            'DepartamentosFiltro' => $this->decorateDepartamentoFiltro(),
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay asignaciones registradas',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos asignaciones que coincidan con la búsqueda o los filtros seleccionados.'
                : ($canManage
                    ? 'Registra la primera asignación para llevar el control de los equipos entregados a cada responsable.'
                    : 'Todavía no se han registrado asignaciones en el sistema.'),
            'CanManage' => $canManage,
            'SearchAction' => 'index.php',
            'ListRoute' => 'Inventario_Asignaciones',
            'ClearUrl' => 'index.php?page=Inventario_Asignaciones&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryActivas' => intval($summary['activas']),
            'SummaryDevueltas' => intval($summary['devueltas']),
            'SummaryEquiposActivos' => intval($summary['equipos_activos']),
            'SortEquipoUrl' => $equipoSort['url'],
            'SortEquipoActive' => $equipoSort['active'],
            'SortEquipoIcon' => $equipoSort['icon'],
            'SortPersonaUrl' => $personaSort['url'],
            'SortPersonaActive' => $personaSort['active'],
            'SortPersonaIcon' => $personaSort['icon'],
            'SortFechaUrl' => $fechaSort['url'],
            'SortFechaActive' => $fechaSort['active'],
            'SortFechaIcon' => $fechaSort['icon'],
            'SortDevolucionUrl' => $devolucionSort['url'],
            'SortDevolucionActive' => $devolucionSort['active'],
            'SortDevolucionIcon' => $devolucionSort['icon'],
            'SortEstadoUrl' => $estadoSort['url'],
            'SortEstadoActive' => $estadoSort['active'],
            'SortEstadoIcon' => $estadoSort['icon'],
            'FlashHas' => $flash !== null,
            'FlashType' => $flash['type'] ?? '',
            'FlashIcon' => $flash['icon'] ?? '',
            'FlashTitle' => $flash['title'] ?? '',
            'FlashMessage' => $flash['message'] ?? '',
            'CreateUrl' => 'index.php?page=Inventario_Asignacion&mode=INS',
        ];
    }

    private function mapAsignacion(array $row): array
    {
        $id = intval($row['id_asignacion'] ?? 0);
        $estado = strval($row['estado_asignacion'] ?? '');
        $estadoData = self::ESTADOS[$estado] ?? [
            'label' => 'Sin definir',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
        ];

        $serie = trim(strval($row['numero_serie_equipo'] ?? ''));
        $patrimonial = trim(strval($row['codigo_patrimonial_equipo'] ?? ''));
        $articulo = trim(strval($row['nombre_articulo'] ?? ''));
        $codigoArticulo = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $persona = trim(strval($row['persona_responsable'] ?? ''));
        $departamento = trim(strval($row['departamento_destino'] ?? ''));
        $entrega = trim(strval($row['entrega_nombre'] ?? ''));
        $recibe = trim(strval($row['recibe_nombre'] ?? ''));

        $equipoLabel = $serie !== '' ? $serie : 'Equipo #' . intval($row['id_equipo'] ?? 0);
        if ($patrimonial !== '') {
            $equipoLabel .= ' · ' . $patrimonial;
        }

        $equipoSubtext = $articulo;
        if ($codigoArticulo !== '') {
            $equipoSubtext .= ($equipoSubtext !== '' ? ' (' . $codigoArticulo . ')' : $codigoArticulo);
        }

        $asignacionTs = strtotime(strval($row['fecha_asignacion'] ?? ''));
        $asignacionDate = $asignacionTs !== false ? date('d/m/Y', $asignacionTs) : 'Sin fecha';
        $asignacionTime = $asignacionTs !== false ? date('H:i', $asignacionTs) : '';

        $devolucionTs = strtotime(strval($row['fecha_devolucion'] ?? ''));
        $devolucionDate = $devolucionTs !== false ? date('d/m/Y', $devolucionTs) : '';
        $devolucionTime = $devolucionTs !== false ? date('H:i', $devolucionTs) : '';

        $isActiva = $estado === 'ACTIVA';

        return [
            'id' => $id,
            'equipoLabel' => $equipoLabel,
            'equipoSubtext' => $equipoSubtext !== '' ? $equipoSubtext : 'Sin artículo asociado',
            'persona' => $persona !== '' ? $persona : 'Sin responsable',
            'departamento' => $departamento !== '' ? $departamento : 'Sin departamento',
            'fechaAsignacionDate' => $asignacionDate,
            'fechaAsignacionTime' => $asignacionTime,
            'entregaNombre' => $entrega !== '' ? 'Entrega: ' . $entrega : '',
            'fechaDevolucionDate' => $devolucionDate,
            'fechaDevolucionTime' => $devolucionTime,
            'recibeNombre' => $recibe !== '' ? 'Recibe: ' . $recibe : '',
            'devolucionLabel' => $isActiva ? 'En custodia' : '',
            'estadoLabel' => $estadoData['label'],
            'estadoClass' => $estadoData['class'],
            'estadoIcon' => $estadoData['icon'],
            'canEdit' => $isActiva,
            'canReturn' => $isActiva,
            'rowActionIcon' => $isActiva ? 'fa-pen' : 'fa-eye',
            'rowActionTitle' => $isActiva ? 'Editar asignación' : 'Ver asignación',
            'editUrl' => 'index.php?page=Inventario_Asignacion&mode=UPD&id=' . $id,
            'returnUrl' => 'index.php?page=Inventario_Asignacion&mode=DEV&id=' . $id,
        ];
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

    private function decorateDepartamentoFiltro(): array
    {
        $departamentos = [];
        foreach (DaoAsignaciones::getDepartamentos() as $row) {
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
        $params = ['page' => 'Inventario_Asignaciones'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->estado !== '') {
            $params['estado'] = $this->estado;
        }
        if ($this->departamento !== '') {
            $params['departamento'] = $this->departamento;
        }

        return $params;
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->estado !== ''
            || $this->departamento !== '';
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Asignación registrada',
                'message' => 'El equipo quedó asignado al responsable y su estado operativo se actualizó.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Asignación actualizada',
                'message' => 'Los datos del responsable y del equipo se guardaron correctamente.',
            ],
            'returned' => [
                'type' => 'success',
                'icon' => 'fa-rotate-left',
                'title' => 'Devolución registrada',
                'message' => 'La asignación quedó cerrada y el equipo fue liberado para una nueva entrega.',
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
