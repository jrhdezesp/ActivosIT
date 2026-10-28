<?php

namespace Controllers\Auditoria;

use Controllers\PrivateController;
use Dao\Auditoria\Bitacora as DaoBitacora;
use Utilities\Fechas;
use Utilities\Paging;
use Utilities\ReportExport;
use Utilities\Site;
use Views\Renderer;

/**
 * Permite consultar, filtrar y exportar el historial de actividad.
 */
class Auditoria extends PrivateController
{
    private const ORDER_FIELDS = [
        'fecha_hora',
        'tipo_operacion',
        'modulo_afectado',
        'resultado',
    ];

    private const ITEMS_PER_PAGE_OPTIONS = [1, 5, 10, 20, 50, 100];

    // Nombres e iconos que ayudan a reconocer los tipos de actividad.
    private const OPERACIONES = [
        'LOGIN' => [
            'label' => 'Inicio de sesión',
            'icon' => 'fa-right-to-bracket',
        ],
        'LOGOUT' => [
            'label' => 'Cierre de sesión',
            'icon' => 'fa-right-from-bracket',
        ],
        'SESION_REEMPLAZADA' => [
            'label' => 'Sesión reemplazada',
            'icon' => 'fa-arrows-rotate',
        ],
        'REGISTRO' => [
            'label' => 'Registro de cuenta',
            'icon' => 'fa-user-plus',
        ],
    ];

    // Presentacion de los resultados registrados en el historial.
    private const RESULTADOS = [
        'EXITO' => [
            'label' => 'Exitoso',
            'class' => 'badge--success',
            'icon' => 'fa-circle-check',
        ],
        'DENEGADO' => [
            'label' => 'Denegado',
            'class' => 'badge--danger',
            'icon' => 'fa-ban',
        ],
        'ERROR' => [
            'label' => 'Error',
            'class' => 'badge--warning',
            'icon' => 'fa-triangle-exclamation',
        ],
    ];

    private $search = '';
    private $modulo = '';
    private $tipo = '';
    private $resultado = '';
    private $desde = '';
    private $hasta = '';
    private $orderBy = 'fecha_hora';
    private $orderDescending = true;
    private $pageNumber = 1;
    private $itemsPerPage = 10;

    /**
     * Muestra un evento, el listado filtrado o el archivo solicitado.
     */
    public function run(): void
    {
        $this->getParams();

        if ($this->isCsvExport()) {
            $this->requireAction('Acciones.Auditoria.Exportar');
            $this->exportCsv();
            return;
        }

        $eventoId = max(0, intval($_GET['id'] ?? 0));
        if ($eventoId > 0) {
            $this->renderEvento($eventoId);
            return;
        }

        $result = DaoBitacora::getEventos(
            $this->search,
            $this->modulo,
            $this->tipo,
            $this->resultado,
            $this->desde,
            $this->hasta,
            $this->orderBy,
            $this->orderDescending,
            $this->pageNumber - 1,
            $this->itemsPerPage
        );

        $this->pageNumber = max(1, intval($result['page']) + 1);
        $this->itemsPerPage = intval($result['itemsPerPage']);

        Renderer::render('auditoria/auditoria', $this->buildListViewData($result));
    }

    private function getParams(): void
    {
        $this->search = $this->cleanText(strval($_GET['q'] ?? ''), 100);

        $this->modulo = $this->cleanText(strval($_GET['modulo'] ?? ''), 100);

        $this->tipo = strtoupper($this->cleanText(strval($_GET['tipo'] ?? ''), 50));

        $resultado = strtoupper($this->cleanText(strval($_GET['resultado'] ?? ''), 20));
        $this->resultado = in_array($resultado, array_keys(self::RESULTADOS), true)
            ? $resultado
            : '';

        $this->desde = Fechas::normalizarFecha(strval($_GET['desde'] ?? '')) ?? '';
        $this->hasta = Fechas::normalizarFecha(strval($_GET['hasta'] ?? '')) ?? '';

        if ($this->desde !== '' && $this->hasta !== '' && $this->desde > $this->hasta) {
            $temporal = $this->desde;
            $this->desde = $this->hasta;
            $this->hasta = $temporal;
        }

        $this->orderBy = strval($_GET['orderBy'] ?? 'fecha_hora');
        if (!in_array($this->orderBy, self::ORDER_FIELDS, true)) {
            $this->orderBy = 'fecha_hora';
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

    private function buildListViewData(array $result): array
    {
        $eventos = [];
        foreach ($result['eventos'] as $row) {
            $eventos[] = $this->mapEvento($row, true);
        }

        $total = intval($result['total']);
        $itemsPerPage = intval($result['itemsPerPage']);
        $from = $total > 0 ? (($this->pageNumber - 1) * $itemsPerPage) + 1 : 0;
        $to = $total > 0 ? min($total, $this->pageNumber * $itemsPerPage) : 0;

        $summary = DaoBitacora::getResumen(
            $this->search,
            $this->modulo,
            $this->tipo,
            $this->resultado,
            $this->desde,
            $this->hasta
        );

        $hasFilters = $this->hasFilters();

        $pagination = Paging::getPagination(
            $total,
            $itemsPerPage,
            $this->pageNumber,
            $this->buildBaseUrl(),
            'Auditoria_Auditoria',
            $this->buildExtraParams()
        );

        $resultsLabel = $total > 0
            ? 'Mostrando ' . $from . '-' . $to . ' de ' . $total
                . ($total === 1 ? ' evento' : ' eventos')
            : 'Sin eventos para mostrar';

        $fechaSort = $this->buildSortData('fecha_hora');
        $tipoSort = $this->buildSortData('tipo_operacion');
        $moduloSort = $this->buildSortData('modulo_afectado');
        $resultadoSort = $this->buildSortData('resultado');

        return [
            'Eventos' => $eventos,
            'HasEventos' => count($eventos) > 0,
            'TotalEventos' => $total,
            'ResultsLabel' => $resultsLabel,
            'Pagination' => $pagination,
            'PageNumber' => $this->pageNumber,
            'ItemsPerPage' => $itemsPerPage,
            'Search' => $this->search,
            'Modulo' => $this->modulo,
            'Tipo' => $this->tipo,
            'Resultado' => $this->resultado,
            'ModulosFiltro' => $this->decorateModulosFiltro(),
            'TiposFiltro' => $this->decorateTiposFiltro(),
            'ResultadosFiltro' => $this->decorateResultadosFiltro(),
            'Desde' => $this->desde,
            'Hasta' => $this->hasta,
            'HasFilters' => $hasFilters,
            'OrderBy' => $this->orderBy,
            'OrderDescending' => $this->orderDescending ? 1 : 0,
            'EmptyTitle' => $hasFilters ? 'Sin coincidencias' : 'Aún no hay eventos registrados',
            'EmptyDescription' => $hasFilters
                ? 'No encontramos eventos que coincidan con la búsqueda o los filtros seleccionados.'
                : 'La bitácora se llenará automáticamente cuando se registren inicios de sesión, altas de cuenta y demás operaciones auditables.',
            'SearchAction' => 'index.php',
            'ListRoute' => 'Auditoria_Auditoria',
            'ClearUrl' => 'index.php?page=Auditoria_Auditoria&itemsPerPage=' . $itemsPerPage,
            'SummaryTotal' => intval($summary['total']),
            'SummaryExitos' => intval($summary['exitos']),
            'SummaryDenegados' => intval($summary['denegados']),
            'SummaryErrores' => intval($summary['errores']),
            'SummaryTotalLabel' => intval($summary['total']) === 1 ? 'Evento' : 'Eventos',
            'SummaryExitosLabel' => intval($summary['exitos']) === 1 ? 'Exitoso' : 'Exitosos',
            'SummaryDenegadosLabel' => intval($summary['denegados']) === 1 ? 'Denegado' : 'Denegados',
            'SummaryErroresLabel' => intval($summary['errores']) === 1 ? 'Error' : 'Errores',
            'ExportUrl' => $this->buildExportUrl(),
            'SortFechaUrl' => $fechaSort['url'],
            'SortFechaActive' => $fechaSort['active'],
            'SortFechaIcon' => $fechaSort['icon'],
            'SortTipoUrl' => $tipoSort['url'],
            'SortTipoActive' => $tipoSort['active'],
            'SortTipoIcon' => $tipoSort['icon'],
            'SortModuloUrl' => $moduloSort['url'],
            'SortModuloActive' => $moduloSort['active'],
            'SortModuloIcon' => $moduloSort['icon'],
            'SortResultadoUrl' => $resultadoSort['url'],
            'SortResultadoActive' => $resultadoSort['active'],
            'SortResultadoIcon' => $resultadoSort['icon'],
        ];
    }

    private function renderEvento(int $eventoId): void
    {
        $row = DaoBitacora::getEventoById($eventoId);
        if (count($row) === 0) {
            Site::redirectToWithMsg(
                'index.php?page=Auditoria_Auditoria',
                'El evento solicitado no existe o ya no está disponible.'
            );
            return;
        }

        Renderer::render('auditoria/evento', $this->buildEventoViewData($row));
    }

    private function buildEventoViewData(array $row): array
    {
        $evento = $this->mapEvento($row, false);
        $anteriores = $this->decodeJson($row['datos_anteriores'] ?? null);
        $nuevos = $this->decodeJson($row['datos_nuevos'] ?? null);

        $ret = strval($_GET['ret'] ?? '');
        $backUrl = Site::isLocalUrl($ret) && strpos($ret, 'index.php') === 0
            ? $ret
            : 'index.php?page=Auditoria_Auditoria';

        return [
            'EventoId' => $evento['id'],
            'EventoFecha' => $evento['fecha'],
            'EventoHora' => $evento['hora'],
            'EventoTipoRaw' => $evento['tipoRaw'],
            'EventoTipoLabel' => $evento['tipoLabel'],
            'EventoTipoIcon' => $evento['tipoIcon'],
            'EventoModulo' => $evento['modulo'],
            'EventoResultadoLabel' => $evento['resultadoLabel'],
            'EventoResultadoClass' => $evento['resultadoClass'],
            'EventoResultadoIcon' => $evento['resultadoIcon'],
            'EventoUsuario' => $evento['usuario'],
            'EventoCuenta' => $evento['cuenta'],
            'EventoTieneCuenta' => $evento['cuenta'] !== '',
            'EventoIp' => $evento['ip'],
            'EventoTieneIp' => $evento['ip'] !== '—',
            'EventoRegistro' => $evento['registro'],
            'EventoTieneRegistro' => $evento['registro'] !== '—',
            'EventoDescripcion' => 'Evento de '
                . $this->lower($evento['tipoLabel'])
                . ' registrado en el módulo ' . $evento['modulo']
                . ' el ' . $evento['fecha'] . ' a las ' . $evento['hora'] . '.',
            'DatosAnterioresJson' => $anteriores['pretty'],
            'HasDatosAnteriores' => $anteriores['has'],
            'DatosNuevosJson' => $nuevos['pretty'],
            'HasDatosNuevos' => $nuevos['has'],
            'BackUrl' => $backUrl,
            'ListUrl' => 'index.php?page=Auditoria_Auditoria',
        ];
    }

    private function mapEvento(array $row, bool $withDetailUrl): array
    {
        $tipoRaw = strval($row['tipo_operacion'] ?? '');
        $tipoData = self::OPERACIONES[$tipoRaw] ?? [
            'label' => $this->humanize($tipoRaw),
            'icon' => 'fa-circle-info',
        ];

        $resultadoRaw = strtoupper(strval($row['resultado'] ?? ''));
        $resultadoData = self::RESULTADOS[$resultadoRaw] ?? [
            'label' => 'Sin resultado',
            'class' => 'badge--outline',
            'icon' => 'fa-circle-question',
        ];

        $fecha = strtotime(strval($row['fecha_hora'] ?? ''));
        $usuarioId = intval($row['id_usuario'] ?? 0);
        $usuario = trim(strval($row['usuario_nombre'] ?? ''));
        $cuenta = trim(strval($row['usuario_cuenta'] ?? ''));
        if ($usuario === '' && $usuarioId > 0) {
            $usuario = 'Usuario no disponible';
        } elseif ($usuario === '') {
            $usuario = 'Sistema';
        }

        $ip = trim(strval($row['direccion_ip'] ?? ''));
        $registro = intval($row['registro_relacionado_id'] ?? 0);
        $id = intval($row['id_bitacora'] ?? 0);

        $evento = [
            'id' => $id,
            'fecha' => $fecha !== false ? date('d/m/Y', $fecha) : 'Sin fecha',
            'hora' => $fecha !== false ? date('H:i:s', $fecha) : '',
            'tipoRaw' => $tipoRaw,
            'tipoLabel' => $tipoData['label'],
            'tipoIcon' => $tipoData['icon'],
            'modulo' => trim(strval($row['modulo_afectado'] ?? '')) !== ''
                ? trim(strval($row['modulo_afectado']))
                : 'Sistema',
            'resultadoLabel' => $resultadoData['label'],
            'resultadoClass' => $resultadoData['class'],
            'resultadoIcon' => $resultadoData['icon'],
            'usuario' => $usuario,
            'cuenta' => $cuenta,
            'ip' => $ip !== '' ? $ip : '—',
            'registro' => $registro > 0 ? '#' . $registro : '—',
        ];

        if ($withDetailUrl) {
            $evento['detalleUrl'] = 'index.php?' . http_build_query([
                'page' => 'Auditoria_Auditoria',
                'id' => $id,
                'ret' => $this->buildReturnUrl(),
            ]);
        }

        return $evento;
    }

    private function decorateModulosFiltro(): array
    {
        $options = [];
        foreach (DaoBitacora::getModulos() as $row) {
            $value = trim(strval($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $options[] = [
                'value' => $value,
                'label' => $value,
                'selected' => $value === $this->modulo ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateTiposFiltro(): array
    {
        $options = [];
        foreach (DaoBitacora::getTiposOperacion() as $row) {
            $value = strtoupper(trim(strval($row['value'] ?? '')));
            if ($value === '') {
                continue;
            }
            $label = self::OPERACIONES[$value]['label'] ?? $this->humanize($value);
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $this->tipo ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateResultadosFiltro(): array
    {
        $options = [];
        foreach (self::RESULTADOS as $value => $data) {
            $options[] = [
                'value' => $value,
                'label' => $data['label'],
                'selected' => $value === $this->resultado ? 'selected' : '',
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

    private function buildReturnUrl(): string
    {
        $url = $this->buildBaseUrl();
        if ($this->pageNumber > 1) {
            $url .= '&pageNum=' . $this->pageNumber;
        }

        return $url;
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
        $params = ['page' => 'Auditoria_Auditoria'];

        if ($this->search !== '') {
            $params['q'] = $this->search;
        }
        if ($this->modulo !== '') {
            $params['modulo'] = $this->modulo;
        }
        if ($this->tipo !== '') {
            $params['tipo'] = $this->tipo;
        }
        if ($this->resultado !== '') {
            $params['resultado'] = $this->resultado;
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
            || $this->modulo !== ''
            || $this->tipo !== ''
            || $this->resultado !== ''
            || $this->desde !== ''
            || $this->hasta !== '';
    }

    private function isCsvExport(): bool
    {
        return strtolower(trim(strval($_GET['export'] ?? ''))) === 'csv';
    }

    private function buildExportUrl(): string
    {
        $params = $this->filterParams();
        $params['orderBy'] = $this->orderBy;
        $params['orderDescending'] = $this->orderDescending ? 1 : 0;
        $params['export'] = 'csv';

        return 'index.php?' . http_build_query($params);
    }

    /**
     * Exporta todos los eventos que coinciden con los filtros actuales,
     * sin limitarse a la pagina visible.
     */
    private function exportCsv(): void
    {
        $rows = DaoBitacora::getEventosForExport(
            $this->search,
            $this->modulo,
            $this->tipo,
            $this->resultado,
            $this->desde,
            $this->hasta,
            $this->orderBy,
            $this->orderDescending
        );

        $data = [];
        foreach ($rows as $row) {
            $evento = $this->mapEvento($row, false);
            $data[] = [
                $evento['id'],
                $evento['fecha'],
                $evento['hora'],
                $evento['tipoLabel'],
                $evento['modulo'],
                $evento['resultadoLabel'],
                $evento['usuario'],
                $evento['cuenta'],
                $evento['ip'] === '—' ? '' : $evento['ip'],
                $evento['registro'] === '—' ? '' : $evento['registro'],
                strval($row['datos_anteriores'] ?? ''),
                strval($row['datos_nuevos'] ?? ''),
            ];
        }

        ReportExport::stream('bitacora_' . date('Ymd_His') . '.csv', [
            'ID',
            'Fecha',
            'Hora',
            'Operación',
            'Módulo',
            'Resultado',
            'Usuario',
            'Cuenta',
            'Dirección IP',
            'Registro relacionado',
            'Datos anteriores',
            'Datos nuevos',
        ], $data);
    }

    private function decodeJson($value): array
    {
        $raw = $value === null ? '' : strval($value);
        if (trim($raw) === '' || strtolower(trim($raw)) === 'null') {
            return ['has' => false, 'pretty' => ''];
        }

        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
            if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
                $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
            }
            $pretty = json_encode($decoded, $flags);
            if ($pretty !== false) {
                return ['has' => true, 'pretty' => $pretty];
            }
        }

        return ['has' => true, 'pretty' => $raw];
    }

    private function cleanText(string $value, int $maxLength): string
    {
        $value = trim($value);
        if ($this->textLength($value) > $maxLength) {
            $value = $this->substring($value, 0, $maxLength);
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

    private function humanize(string $value): string
    {
        $value = str_replace('_', ' ', $value);
        if ($value === '') {
            return 'Evento';
        }

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($value));
    }

    private function lower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }
}
