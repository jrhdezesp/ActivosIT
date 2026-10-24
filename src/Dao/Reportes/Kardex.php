<?php

namespace Dao\Reportes;

use Dao\Table;

class Kardex extends Table
{
    public const TIPOS = ['ENTRADA', 'SALIDA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'];

    private const ORDER_FIELDS = [
        'fecha_movimiento' => 'm.fecha_movimiento',
        'tipo_movimiento' => 'm.tipo_movimiento',
        'cantidad' => 'm.cantidad',
        'nombre_articulo' => 'a.nombre_articulo',
        'existencia_nueva' => 'm.existencia_nueva',
        'id_movimiento' => 'm.id_movimiento',
    ];

    private const SELECT_FIELDS = 'SELECT m.id_movimiento, m.id_articulo,'
        . ' m.tipo_movimiento, m.cantidad, m.existencia_anterior,'
        . ' m.existencia_nueva, m.fecha_movimiento, m.observacion,'
        . ' m.origen_referencia, m.id_doc_referencia,'
        . ' a.codigo_interno_articulo, a.nombre_articulo, a.tipo_control,'
        . ' u.nombre_usuario AS usuario_nombre, u.user_usuario AS usuario_cuenta,'
        . ' mo.nombre_motivo,'
        . ' COALESCE(de.correlativo, ds.correlativo) AS documento_correlativo';

    private const FROM_JOINS = ' FROM movimientos_inventario m'
        . ' JOIN articulos a ON a.id_articulo = m.id_articulo'
        . ' JOIN usuarios u ON u.id_usuario = m.id_usuario'
        . ' LEFT JOIN motivos_ajustes_anulaciones mo ON mo.id_motivo = m.id_motivo'
        . " LEFT JOIN documentos_entrada de ON m.origen_referencia = 'documentos_entrada'"
        . ' AND de.id_doc_entrada = m.id_doc_referencia'
        . " LEFT JOIN documentos_salida ds ON m.origen_referencia = 'documentos_salida'"
        . ' AND ds.id_doc_salida = m.id_doc_referencia';

    public static function getMovimientos(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = '',
        string $orderBy = 'fecha_movimiento',
        bool $orderDescending = true,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            throw new \InvalidArgumentException('El tipo de movimiento solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_movimiento';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);

        $countRow = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total' . self::FROM_JOINS . $where,
            $params
        );
        $total = intval($countRow['total'] ?? 0);

        $itemsPerPage = max(1, min(100, $itemsPerPage));
        $pages = $total > 0 ? intval(ceil($total / $itemsPerPage)) : 1;
        $page = max(0, $page);
        if ($page > $pages - 1) {
            $page = $pages - 1;
        }
        $offset = $page * $itemsPerPage;

        $sql = self::SELECT_FIELDS . self::FROM_JOINS . $where
            . ' ORDER BY ' . self::ORDER_FIELDS[$orderBy]
            . ($orderDescending ? ' DESC' : ' ASC')
            . ', m.id_movimiento DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'movimientos' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    /**
     * Movimientos filtrados sin paginacion para la exportacion del reporte.
     * Mantiene el mismo orden que el listado visible.
     */
    public static function getMovimientosForExport(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = '',
        string $orderBy = 'fecha_movimiento',
        bool $orderDescending = true
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            throw new \InvalidArgumentException('El tipo de movimiento solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_movimiento';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);

        $sql = self::SELECT_FIELDS . self::FROM_JOINS . $where
            . ' ORDER BY ' . self::ORDER_FIELDS[$orderBy]
            . ($orderDescending ? ' DESC' : ' ASC')
            . ', m.id_movimiento DESC';

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Resumen calculado con los mismos filtros del listado.
     */
    public static function getResumen(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = ''
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            $tipo = '';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);

        $row = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total,'
            . " COALESCE(SUM(CASE WHEN m.tipo_movimiento IN ('ENTRADA', 'AJUSTE_POSITIVO')"
            . ' THEN m.cantidad ELSE 0 END), 0) AS unidades_entrada,'
            . " COALESCE(SUM(CASE WHEN m.tipo_movimiento IN ('SALIDA', 'AJUSTE_NEGATIVO')"
            . ' THEN m.cantidad ELSE 0 END), 0) AS unidades_salida,'
            . " COALESCE(SUM(m.tipo_movimiento IN ('AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO')), 0) AS ajustes,"
            . ' COUNT(DISTINCT m.id_articulo) AS articulos'
            . self::FROM_JOINS . $where,
            $params
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'unidades_entrada' => intval($row['unidades_entrada'] ?? 0),
            'unidades_salida' => intval($row['unidades_salida'] ?? 0),
            'ajustes' => intval($row['ajustes'] ?? 0),
            'articulos' => intval($row['articulos'] ?? 0),
        ];
    }

    public static function getArticulosOptions(): array
    {
        return self::obtenerRegistros(
            'SELECT id_articulo AS value, codigo_interno_articulo, nombre_articulo'
            . ' FROM articulos'
            . ' ORDER BY nombre_articulo ASC, codigo_interno_articulo ASC',
            []
        );
    }

    /**
     * Nombre legible de un articulo para los metadatos de la exportacion.
     */
    public static function getArticuloLabel(int $articuloId): string
    {
        if ($articuloId <= 0) {
            return '';
        }

        $row = self::obtenerUnRegistro(
            'SELECT codigo_interno_articulo, nombre_articulo'
            . ' FROM articulos WHERE id_articulo = :articulo',
            ['articulo' => $articuloId]
        );

        if (!is_array($row)) {
            return '';
        }

        $codigo = trim(strval($row['codigo_interno_articulo'] ?? ''));
        $nombre = trim(strval($row['nombre_articulo'] ?? ''));

        if ($codigo !== '' && $nombre !== '') {
            return $codigo . ' · ' . $nombre;
        }

        return $nombre !== '' ? $nombre : $codigo;
    }

    private static function buildFilters(
        string $search,
        string $tipo,
        int $articuloId,
        string $desde,
        string $hasta
    ): array {
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(a.codigo_interno_articulo LIKE :searchCodigo'
                . ' OR a.nombre_articulo LIKE :searchArticulo'
                . ' OR m.observacion LIKE :searchObservacion'
                . ' OR mo.nombre_motivo LIKE :searchMotivo'
                . ' OR u.nombre_usuario LIKE :searchUsuario'
                . ' OR u.user_usuario LIKE :searchCuenta'
                . ' OR de.correlativo LIKE :searchEntrada'
                . ' OR ds.correlativo LIKE :searchSalida)';
            $searchValue = '%' . $search . '%';
            $params['searchCodigo'] = $searchValue;
            $params['searchArticulo'] = $searchValue;
            $params['searchObservacion'] = $searchValue;
            $params['searchMotivo'] = $searchValue;
            $params['searchUsuario'] = $searchValue;
            $params['searchCuenta'] = $searchValue;
            $params['searchEntrada'] = $searchValue;
            $params['searchSalida'] = $searchValue;
        }

        if ($tipo !== '') {
            $conditions[] = 'm.tipo_movimiento = :tipo';
            $params['tipo'] = $tipo;
        }

        if ($articuloId > 0) {
            $conditions[] = 'm.id_articulo = :articulo';
            $params['articulo'] = $articuloId;
        }

        if ($desde !== '') {
            $conditions[] = 'm.fecha_movimiento >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        if ($hasta !== '') {
            $conditions[] = 'm.fecha_movimiento <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }

    /**
     * Serie temporal de entradas y salidas con los mismos filtros del listado.
     * La granularidad se decide segun el rango: dia para periodos cortos y
     * mes para periodos largos. Rellena los periodos sin datos con ceros.
     */
    public static function getSerieMovimientos(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = ''
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            $tipo = '';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);

        $rango = self::obtenerUnRegistro(
            'SELECT MIN(m.fecha_movimiento) AS min_fecha,'
            . ' MAX(m.fecha_movimiento) AS max_fecha'
            . self::FROM_JOINS . $where,
            $params
        );

        $minFecha = strval($rango['min_fecha'] ?? '');
        $maxFecha = strval($rango['max_fecha'] ?? '');

        if ($minFecha === '' || $maxFecha === '') {
            return [
                'labels' => [],
                'entradas' => [],
                'salidas' => [],
                'movimientos' => [],
                'granularidad' => 'dia',
                'desde_label' => '',
                'hasta_label' => '',
                'total_entradas' => 0,
                'total_salidas' => 0,
                'total_movimientos' => 0,
            ];
        }

        $inicio = new \DateTimeImmutable($desde !== '' ? $desde : substr($minFecha, 0, 10));
        $fin = new \DateTimeImmutable($hasta !== '' ? $hasta : substr($maxFecha, 0, 10));
        if ($inicio > $fin) {
            $temporal = $inicio;
            $inicio = $fin;
            $fin = $temporal;
        }

        $dias = intval($inicio->diff($fin)->days);
        $granularidad = $dias > 62 ? 'mes' : 'dia';
        $formatoSql = $granularidad === 'mes' ? '%Y-%m' : '%Y-%m-%d';

        $rows = self::obtenerRegistros(
            "SELECT DATE_FORMAT(m.fecha_movimiento, '" . $formatoSql . "') AS periodo,"
            . " COALESCE(SUM(CASE WHEN m.tipo_movimiento IN ('ENTRADA', 'AJUSTE_POSITIVO')"
            . ' THEN m.cantidad ELSE 0 END), 0) AS entradas,'
            . " COALESCE(SUM(CASE WHEN m.tipo_movimiento IN ('SALIDA', 'AJUSTE_NEGATIVO')"
            . ' THEN m.cantidad ELSE 0 END), 0) AS salidas,'
            . ' COUNT(*) AS movimientos'
            . self::FROM_JOINS . $where
            . ' GROUP BY periodo'
            . ' ORDER BY periodo ASC',
            $params
        );

        $porPeriodo = [];
        foreach ($rows as $row) {
            $porPeriodo[strval($row['periodo'])] = $row;
        }

        $mesesCortos = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $labels = [];
        $entradas = [];
        $salidas = [];
        $movimientos = [];
        $cursor = $granularidad === 'mes'
            ? $inicio->modify('first day of this month')
            : $inicio->setTime(0, 0);

        $finIteracion = $granularidad === 'mes'
            ? $fin->modify('first day of this month')
            : $fin->setTime(0, 0);

        while ($cursor <= $finIteracion) {
            $clave = $cursor->format($granularidad === 'mes' ? 'Y-m' : 'Y-m-d');
            $fila = $porPeriodo[$clave] ?? null;

            $labels[] = $granularidad === 'mes'
                ? $mesesCortos[intval($cursor->format('n'))] . ' ' . $cursor->format('y')
                : $cursor->format('d') . ' ' . $mesesCortos[intval($cursor->format('n'))];

            $entradas[] = intval($fila['entradas'] ?? 0);
            $salidas[] = intval($fila['salidas'] ?? 0);
            $movimientos[] = intval($fila['movimientos'] ?? 0);

            $cursor = $granularidad === 'mes'
                ? $cursor->modify('+1 month')
                : $cursor->modify('+1 day');
        }

        return [
            'labels' => $labels,
            'entradas' => $entradas,
            'salidas' => $salidas,
            'movimientos' => $movimientos,
            'granularidad' => $granularidad,
            'desde_label' => $inicio->format('d/m/Y'),
            'hasta_label' => $fin->format('d/m/Y'),
            'total_entradas' => array_sum($entradas),
            'total_salidas' => array_sum($salidas),
            'total_movimientos' => array_sum($movimientos),
        ];
    }

    /**
     * Conteo de movimientos por tipo con los filtros del listado. Se usa en el
     * grafico de distribucion y respeta los filtros activos.
     */
    public static function getDistribucionTipos(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = ''
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            $tipo = '';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);

        $rows = self::obtenerRegistros(
            'SELECT m.tipo_movimiento, COUNT(*) AS movimientos,'
            . ' COALESCE(SUM(m.cantidad), 0) AS unidades'
            . self::FROM_JOINS . $where
            . ' GROUP BY m.tipo_movimiento',
            $params
        );

        $distribucion = [];
        foreach ($rows as $row) {
            $distribucion[strval($row['tipo_movimiento'])] = [
                'movimientos' => intval($row['movimientos'] ?? 0),
                'unidades' => intval($row['unidades'] ?? 0),
            ];
        }

        $resultado = [];
        foreach (self::TIPOS as $tipoMovimiento) {
            $resultado[$tipoMovimiento] = $distribucion[$tipoMovimiento]
                ?? ['movimientos' => 0, 'unidades' => 0];
        }

        return $resultado;
    }

    /**
     * Articulos con mas movimientos dentro del filtro actual. Se usa en el
     * grafico de barras del kardex.
     */
    public static function getTopArticulos(
        string $search = '',
        string $tipo = '',
        int $articuloId = 0,
        string $desde = '',
        string $hasta = '',
        int $limit = 6
    ): array {
        if ($tipo !== '' && !in_array($tipo, self::TIPOS, true)) {
            $tipo = '';
        }

        [$where, $params] = self::buildFilters($search, $tipo, $articuloId, $desde, $hasta);
        $limit = max(1, min(20, $limit));

        return self::obtenerRegistros(
            'SELECT a.id_articulo, a.nombre_articulo, a.codigo_interno_articulo,'
            . ' COUNT(*) AS movimientos, COALESCE(SUM(m.cantidad), 0) AS unidades'
            . self::FROM_JOINS . $where
            . ' GROUP BY a.id_articulo, a.nombre_articulo, a.codigo_interno_articulo'
            . ' ORDER BY movimientos DESC, a.nombre_articulo ASC'
            . ' LIMIT ' . $limit,
            $params
        );
    }
}
