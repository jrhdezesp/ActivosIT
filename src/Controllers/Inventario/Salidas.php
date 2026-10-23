<?php

namespace Controllers\Inventario;

use Controllers\PrivateController;
use Dao\Inventario\Salidas as DaoSalidas;
use Utilities\Paging;
use Views\Renderer;

class Salidas extends PrivateController
{
    private const ORDER_FIELDS = [
        'fecha_movimiento',
        'correlativo',
        'estado_doc',
        'fecha_creacion',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [5, 10, 20, 50, 100];

    private const ESTADOS = [
        'BORRADOR' => [
            'label' => 'Borrador',
            'class' => 'badge--warning',
            'icon' => 'fa-pen-ruler',
        ],
        'CONFIRMADO' => [
            'label' => 'Confirmado',
            'class' => 'badge--success',
            'icon' => 'fa-circle-check',
        ],
        'ANULADO' => [
            'label' => 'Anulado',
            'class' => 'badge--danger',
            'icon' => 'fa-ban',
        ],
    ];

    private $search = '';
    private $estado = '';
    private $tipoDocId = 0;
    private $orderBy = 'fecha_movimiento';
    private $orderDescending = true;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    public function run(): void
    {
        $this->getParams();

        $result = DaoSalidas::getSalidas(
            $this->search,
            $this->estado,
            $this->tipoDocId,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('inventario/salidas', $this->buildViewData($result));
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

        $this->tipoDocId = max(0, intval($_GET['tipo'] ?? 0));

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
        $salidas = [];
        foreach ($result['salidas'] as $row) {
            $salidas[] = $this->mapSalida($row);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoSalidas::getSummary();
        $flash = $this->getFlash(strval($_GET['msg'] ?? ''));
        $hasFilters = $this->hasFilters();

        $fechaSort = $this->buildSortData('fecha_movimiento');
        $documentoSort = $this->buildSortData('correlativo');
        $estadoSort = $this->buildSortData('estado_doc');

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Inventario_Salidas',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '–' . $to . ' de ' . $total
                . ($total === 1 ? ' documento' : ' documentos')
            : 'Sin documentos para mostrar';

        return [
            'Salidas' => $salidas,
            'HasSalidas' => count($salidas) > 0,
            'TotalSalidas' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Estado' => $this->estado,
            'EstadosFiltro' => $this->decorateEstadoFiltro(),
            'TipoDocId' => $this->tipoDocId,
            'TiposFiltro' => $this->decorateTipoFiltro(),
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin resultados' : 'Aún no hay salidas registradas',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos documentos que coincidan con la búsqueda o los filtros seleccionados.'
                : 'Registra la primera salida de inventario para descontar existencias y alimentar el Kardex.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Inventario_Salidas',
            'ClearUrl' => 'index.php?page=Inventario_Salidas&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryBorradores' => intval($summary['borradores']),
            'SummaryConfirmados' => intval($summary['confirmados']),
            'SummaryUnidades' => intval($summary['unidades']),
            'SortFechaUrl' => $fechaSort['url'],
            'SortFechaActive' => $fechaSort['active'],
            'SortFechaIcon' => $fechaSort['icon'],
            'SortDocumentoUrl' => $documentoSort['url'],
            'SortDocumentoActive' => $documentoSort['active'],
            'SortDocumentoIcon' => $documentoSort['icon'],
            'SortEstadoUrl' => $estadoSort['url'],
            'SortEstadoActive' => $estadoSort['active'],
            'SortEstadoIcon' => $estadoSort['icon'],
            'FlashHas' => $flash !== null,
            'FlashType' => $flash['type'] ?? '',
            'FlashIcon' => $flash['icon'] ?? '',
            'FlashTitle' => $flash['title'] ?? '',
            'FlashMessage' => $flash['message'] ?? '',
            'CreateUrl' => 'index.php?page=Inventario_Salida&mode=INS',
        ];
    }

    private function mapSalida(array $row): array
    {
        $id = intval($row['id_doc_salida'] ?? 0);
        $estado = strval($row['estado_doc'] ?? '');
        $estadoData = self::ESTADOS[$estado] ?? [
            'label' => 'Sin definir',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
        ];

        $correlativo = trim(strval($row['correlativo'] ?? ''));
        $tipo = trim(strval($row['nombre_tipo'] ?? ''));
        $destino = trim(strval($row['destino_salida'] ?? ''));
        $autorizacion = trim(strval($row['autorizacion'] ?? ''));
        $motivo = trim(strval($row['nombre_motivo'] ?? ''));
        $usuario = trim(strval($row['usuario_nombre'] ?? ''));

        $lineas = intval($row['lineas'] ?? 0);
        $unidades = intval($row['unidades'] ?? 0);

        if ($destino === '') {
            $destino = 'Sin destino registrado';
        }

        if ($autorizacion !== '') {
            $destinoSubtext = 'Autoriza: ' . $autorizacion;
        } elseif ($motivo !== '') {
            $destinoSubtext = $motivo;
        } elseif ($usuario !== '') {
            $destinoSubtext = 'Registrada por ' . $usuario;
        } else {
            $destinoSubtext = '';
        }

        $isDraft = $estado === 'BORRADOR';
        $timestamp = strtotime(strval($row['fecha_movimiento'] ?? ''));
        $fechaDate = $timestamp !== false ? date('d/m/Y', $timestamp) : 'Sin fecha';
        $fechaTime = $timestamp !== false ? date('H:i', $timestamp) : '';

        return [
            'id' => $id,
            'correlativo' => $correlativo !== '' ? $correlativo : 'Documento #' . $id,
            'tipo' => $tipo !== '' ? $tipo : 'Tipo no disponible',
            'fechaDate' => $fechaDate,
            'fechaTime' => $fechaTime,
            'destino' => $destino,
            'destinoSubtext' => $destinoSubtext,
            'usuario' => $usuario,
            'lineasLabel' => $lineas . ($lineas === 1 ? ' línea' : ' líneas'),
            'unidadesLabel' => $unidades . ($unidades === 1 ? ' unidad' : ' unidades'),
            'estadoLabel' => $estadoData['label'],
            'estadoClass' => $estadoData['class'],
            'estadoIcon' => $estadoData['icon'],
            'canAnnul' => $estado !== 'ANULADO',
            'rowActionIcon' => $isDraft ? 'fa-pen' : 'fa-eye',
            'rowActionTitle' => $isDraft ? 'Editar' : 'Ver documento',
            'editUrl' => 'index.php?page=Inventario_Salida&mode=UPD&id=' . $id,
            'annulUrl' => 'index.php?page=Inventario_Salida&mode=DEL&id=' . $id,
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

    private function decorateTipoFiltro(): array
    {
        $options = [];
        foreach (DaoSalidas::getTiposDocumento(false) as $row) {
            $value = intval($row['value'] ?? 0);
            $options[] = [
                'value' => $value,
                'label' => strval($row['label'] ?? ''),
                'selected' => $value === $this->tipoDocId ? 'selected' : '',
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
        $params = ['page' => 'Inventario_Salidas'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->estado !== '') {
            $params['estado'] = $this->estado;
        }
        if ($this->tipoDocId > 0) {
            $params['tipo'] = $this->tipoDocId;
        }

        return $params;
    }

    private function hasFilters(): bool
    {
        return $this->search !== ''
            || $this->estado !== ''
            || $this->tipoDocId > 0;
    }

    private function getFlash(string $code): ?array
    {
        $flashes = [
            'created' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Borrador guardado',
                'message' => 'La salida quedó guardada como borrador. Confírmala para descontar existencias.',
            ],
            'updated' => [
                'type' => 'success',
                'icon' => 'fa-circle-check',
                'title' => 'Salida actualizada',
                'message' => 'Los cambios del borrador se guardaron correctamente.',
            ],
            'confirmed' => [
                'type' => 'success',
                'icon' => 'fa-box-open',
                'title' => 'Salida confirmada',
                'message' => 'Las existencias se descontaron, los equipos se actualizaron y el movimiento quedó registrado en el Kardex.',
            ],
            'annulled' => [
                'type' => 'success',
                'icon' => 'fa-ban',
                'title' => 'Salida anulada',
                'message' => 'El documento quedó anulado. Si estaba confirmado, las existencias y los equipos se revirtieron.',
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
