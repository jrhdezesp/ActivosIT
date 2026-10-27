<?php

namespace Dao\Auditoria;

use Dao\Table;

class Bitacora extends Table
{
    // Resultados que puede tener una accion registrada.
    public const RESULTADOS = ['EXITO', 'DENEGADO', 'ERROR'];

    // Campos permitidos para ordenar los eventos de la bitacora.
    private const ORDER_FIELDS = [
        'fecha_hora' => 'b.fecha_hora',
        'tipo_operacion' => 'b.tipo_operacion',
        'modulo_afectado' => 'b.modulo_afectado',
        'resultado' => 'b.resultado',
        'id_bitacora' => 'b.id_bitacora',
    ];

    // Datos del evento y nombres relacionados que se muestran en la bitacora.
    private const SELECT_FIELDS = 'SELECT b.id_bitacora, b.id_usuario,'
        . ' b.fecha_hora, b.tipo_operacion, b.modulo_afectado,'
        . ' b.registro_relacionado_id, b.direccion_ip,'
        . ' b.datos_anteriores, b.datos_nuevos, b.resultado,'
        . ' u.nombre_usuario AS usuario_nombre,'
        . ' u.user_usuario AS usuario_cuenta';

    // Tabla de eventos y datos de la cuenta que realizo cada accion.
    private const FROM_JOINS = ' FROM bitacora b'
        . ' LEFT JOIN usuarios u ON u.id_usuario = b.id_usuario';

    public static function getEventos(
        string $search = '',
        string $modulo = '',
        string $tipo = '',
        string $resultado = '',
        string $desde = '',
        string $hasta = '',
        string $orderBy = 'fecha_hora',
        bool $orderDescending = true,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_hora';
        }

        [$where, $params] = self::buildFilters($search, $modulo, $tipo, $resultado, $desde, $hasta);

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
            . ', b.id_bitacora DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'eventos' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    /**
     * Eventos filtrados sin paginacion para la exportacion CSV.
     * Conserva el mismo orden que el listado visible.
     */
    public static function getEventosForExport(
        string $search = '',
        string $modulo = '',
        string $tipo = '',
        string $resultado = '',
        string $desde = '',
        string $hasta = '',
        string $orderBy = 'fecha_hora',
        bool $orderDescending = true
    ): array {
        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_hora';
        }

        [$where, $params] = self::buildFilters($search, $modulo, $tipo, $resultado, $desde, $hasta);

        $sql = self::SELECT_FIELDS . self::FROM_JOINS . $where
            . ' ORDER BY ' . self::ORDER_FIELDS[$orderBy]
            . ($orderDescending ? ' DESC' : ' ASC')
            . ', b.id_bitacora DESC';

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Totales por resultado con los mismos filtros del listado.
     */
    public static function getResumen(
        string $search = '',
        string $modulo = '',
        string $tipo = '',
        string $resultado = '',
        string $desde = '',
        string $hasta = ''
    ): array {
        [$where, $params] = self::buildFilters($search, $modulo, $tipo, $resultado, $desde, $hasta);

        $row = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total,'
            . " COALESCE(SUM(b.resultado = 'EXITO'), 0) AS exitos,"
            . " COALESCE(SUM(b.resultado = 'DENEGADO'), 0) AS denegados,"
            . " COALESCE(SUM(b.resultado = 'ERROR'), 0) AS errores"
            . self::FROM_JOINS . $where,
            $params
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'exitos' => intval($row['exitos'] ?? 0),
            'denegados' => intval($row['denegados'] ?? 0),
            'errores' => intval($row['errores'] ?? 0),
        ];
    }

    public static function getEventoById(int $eventoId): array
    {
        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS . self::FROM_JOINS
            . ' WHERE b.id_bitacora = :eventoId'
            . ' LIMIT 1',
            ['eventoId' => max(0, $eventoId)]
        );

        return is_array($row) ? $row : [];
    }

    public static function getModulos(): array
    {
        return self::obtenerRegistros(
            "SELECT DISTINCT modulo_afectado AS value"
            . " FROM bitacora"
            . " WHERE modulo_afectado IS NOT NULL AND modulo_afectado <> ''"
            . " ORDER BY modulo_afectado ASC",
            []
        );
    }

    public static function getTiposOperacion(): array
    {
        return self::obtenerRegistros(
            "SELECT DISTINCT tipo_operacion AS value"
            . " FROM bitacora"
            . " WHERE tipo_operacion IS NOT NULL AND tipo_operacion <> ''"
            . " ORDER BY tipo_operacion ASC",
            []
        );
    }

    /**
     * Registra un evento de auditoria. No lanza errores de validacion:
     * normaliza los valores para que la operacion principal nunca falle
     * por un detalle del registro de bitacora.
     */
    public static function insertEvento(
        ?int $usuarioId,
        string $tipoOperacion,
        string $moduloAfectado,
        ?int $registroRelacionadoId = null,
        ?string $direccionIp = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        string $resultado = 'EXITO'
    ): bool {
        $tipoOperacion = trim($tipoOperacion);
        if ($tipoOperacion === '') {
            $tipoOperacion = 'EVENTO';
        }
        if (function_exists('mb_substr')) {
            $tipoOperacion = mb_substr($tipoOperacion, 0, 50, 'UTF-8');
        } else {
            $tipoOperacion = substr($tipoOperacion, 0, 50);
        }

        $moduloAfectado = trim($moduloAfectado);
        if ($moduloAfectado === '') {
            $moduloAfectado = 'Sistema';
        }
        if (function_exists('mb_substr')) {
            $moduloAfectado = mb_substr($moduloAfectado, 0, 100, 'UTF-8');
        } else {
            $moduloAfectado = substr($moduloAfectado, 0, 100);
        }

        $resultado = strtoupper(trim($resultado));
        if (!in_array($resultado, self::RESULTADOS, true)) {
            $resultado = 'ERROR';
        }

        $direccionIp = trim(strval($direccionIp ?? ''));
        if ($direccionIp === '' || filter_var($direccionIp, FILTER_VALIDATE_IP) === false) {
            $direccionIp = null;
        }

        $registroRelacionadoId = $registroRelacionadoId !== null && $registroRelacionadoId > 0
            ? $registroRelacionadoId
            : null;

        $usuarioId = $usuarioId !== null && $usuarioId > 0 ? $usuarioId : null;

        return self::executeNonQuery(
            'INSERT INTO bitacora'
            . ' (id_usuario, tipo_operacion, modulo_afectado, registro_relacionado_id,'
            . ' direccion_ip, datos_anteriores, datos_nuevos, resultado)'
            . ' VALUES (:usuario, :tipo, :modulo, :registro, :ip, :anteriores, :nuevos, :resultado)',
            [
                'usuario' => $usuarioId,
                'tipo' => $tipoOperacion,
                'modulo' => $moduloAfectado,
                'registro' => $registroRelacionadoId,
                'ip' => $direccionIp,
                'anteriores' => self::encodeJson($datosAnteriores),
                'nuevos' => self::encodeJson($datosNuevos),
                'resultado' => $resultado,
            ]
        );
    }

    private static function encodeJson(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        $json = json_encode($data, $flags);

        return $json === false ? null : $json;
    }

    private static function buildFilters(
        string $search,
        string $modulo,
        string $tipo,
        string $resultado,
        string $desde,
        string $hasta
    ): array {
        // Filtros del historial y valores asociados a cada condicion.
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(b.tipo_operacion LIKE :searchTipo'
                . ' OR b.modulo_afectado LIKE :searchModulo'
                . ' OR b.direccion_ip LIKE :searchIp'
                . ' OR u.nombre_usuario LIKE :searchUsuario'
                . ' OR u.user_usuario LIKE :searchCuenta'
                . ' OR CAST(b.registro_relacionado_id AS CHAR) LIKE :searchRegistro'
                . ' OR CAST(b.id_bitacora AS CHAR) LIKE :searchEvento)';
            $searchValue = '%' . $search . '%';
            $params['searchTipo'] = $searchValue;
            $params['searchModulo'] = $searchValue;
            $params['searchIp'] = $searchValue;
            $params['searchUsuario'] = $searchValue;
            $params['searchCuenta'] = $searchValue;
            $params['searchRegistro'] = $searchValue;
            $params['searchEvento'] = $searchValue;
        }

        $modulo = trim($modulo);
        if ($modulo !== '') {
            $conditions[] = 'b.modulo_afectado = :modulo';
            $params['modulo'] = $modulo;
        }

        $tipo = trim($tipo);
        if ($tipo !== '') {
            $conditions[] = 'b.tipo_operacion = :tipo';
            $params['tipo'] = $tipo;
        }

        $resultado = strtoupper(trim($resultado));
        if (in_array($resultado, self::RESULTADOS, true)) {
            $conditions[] = 'b.resultado = :resultado';
            $params['resultado'] = $resultado;
        }

        if ($desde !== '') {
            $conditions[] = 'b.fecha_hora >= :desde';
            $params['desde'] = $desde . ' 00:00:00';
        }

        if ($hasta !== '') {
            $conditions[] = 'b.fecha_hora <= :hasta';
            $params['hasta'] = $hasta . ' 23:59:59';
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

        return [$where, $params];
    }
}
