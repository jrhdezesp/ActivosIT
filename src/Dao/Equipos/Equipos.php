<?php

namespace Dao\Equipos;

use Dao\Table;

class Equipos extends Table
{
    // Estados que describen la situacion operativa de un equipo.
    public const ESTADOS_OPERATIVOS = [
        'DISPONIBLE',
        'ASIGNADO',
        'EN_MANTENIMIENTO',
        'EN_REVISION',
        'DE_BAJA',
    ];

    // Columnas permitidas para ordenar los equipos.
    private const ORDER_FIELDS = [
        'numero_serie_equipo' => 'e.numero_serie_equipo',
        'codigo_patrimonial_equipo' => 'e.codigo_patrimonial_equipo',
        'nombre_articulo' => 'a.nombre_articulo',
        'estado_operativo' => 'e.estado_operativo',
        'fecha_adquisicion' => 'e.fecha_adquisicion',
        'garantia_vencimiento_equipo' => 'e.garantia_vencimiento_equipo',
        'id_equipo' => 'e.id_equipo',
    ];

    // Campos que identifican y describen cada equipo en los listados.
    private const SELECT_FIELDS = 'SELECT e.*,'
        . ' a.nombre_articulo, a.codigo_interno_articulo, a.tipo_control,'
        . ' a.estado_registro AS articulo_estado,'
        . ' u.nombre_ubicacion, u.estado_ubi,'
        . ' m.nombre_motivo, m.motivo_aplica_a, m.estado_motivo';

    // Tablas relacionadas que completan los datos de cada equipo.
    private const FROM_JOINS = ' FROM equipos_individuales e'
        . ' LEFT JOIN articulos a ON a.id_articulo = e.id_articulo'
        . ' LEFT JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion'
        . ' LEFT JOIN motivos_ajustes_anulaciones m ON m.id_motivo = e.id_motivo';

    public static function getEquipos(
        string $search = '',
        string $status = '',
        string $estadoOperativo = '',
        int $articuloId = 0,
        string $orderBy = 'numero_serie_equipo',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if (!in_array($status, ['', '0', '1'], true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        if ($estadoOperativo !== '' && !in_array($estadoOperativo, self::ESTADOS_OPERATIVOS, true)) {
            throw new \InvalidArgumentException('El estado operativo solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'numero_serie_equipo';
        }

        // Condiciones de busqueda y valores que se envian a la consulta.
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(e.numero_serie_equipo LIKE :searchSerie'
                . ' OR e.codigo_patrimonial_equipo LIKE :searchPatrimonial'
                . ' OR e.proveedor_origen LIKE :searchProveedor'
                . ' OR a.nombre_articulo LIKE :searchArticulo'
                . ' OR a.codigo_interno_articulo LIKE :searchCode)';
            $searchValue = '%' . $search . '%';
            $params['searchSerie'] = $searchValue;
            $params['searchPatrimonial'] = $searchValue;
            $params['searchProveedor'] = $searchValue;
            $params['searchArticulo'] = $searchValue;
            $params['searchCode'] = $searchValue;
        }

        if ($status !== '') {
            $conditions[] = 'e.estado_registro = :status';
            $params['status'] = intval($status);
        }

        if ($estadoOperativo !== '') {
            $conditions[] = 'e.estado_operativo = :estadoOperativo';
            $params['estadoOperativo'] = $estadoOperativo;
        }

        if ($articuloId > 0) {
            $conditions[] = 'e.id_articulo = :articuloId';
            $params['articuloId'] = $articuloId;
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

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
            . ', e.id_equipo ASC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'equipos' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getEquipoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS . self::FROM_JOINS . ' WHERE e.id_equipo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getSummary(): array
    {
        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'DISPONIBLE' THEN 1 ELSE 0 END), 0) AS disponibles,"
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'ASIGNADO' THEN 1 ELSE 0 END), 0) AS asignados,"
            . " COALESCE(SUM(CASE WHEN estado_operativo IN ('EN_MANTENIMIENTO', 'EN_REVISION') THEN 1 ELSE 0 END), 0) AS servicio,"
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'DE_BAJA' THEN 1 ELSE 0 END), 0) AS baja"
            . " FROM equipos_individuales WHERE estado_registro = 1",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'disponibles' => intval($row['disponibles'] ?? 0),
            'asignados' => intval($row['asignados'] ?? 0),
            'servicio' => intval($row['servicio'] ?? 0),
            'baja' => intval($row['baja'] ?? 0),
        ];
    }

    public static function serieExists(string $serie, int $excludeId = 0): bool
    {
        return self::valueExists('numero_serie_equipo', $serie, $excludeId);
    }

    public static function patrimonialExists(string $codigo, int $excludeId = 0): bool
    {
        return self::valueExists('codigo_patrimonial_equipo', $codigo, $excludeId);
    }

    private static function valueExists(string $column, string $value, int $excludeId = 0): bool
    {
        $allowed = ['numero_serie_equipo', 'codigo_patrimonial_equipo'];
        if (!in_array($column, $allowed, true) || $value === '') {
            return false;
        }

        $sql = 'SELECT COUNT(*) AS total FROM equipos_individuales WHERE ' . $column . ' = :value';
        $params = ['value' => $value];

        if ($excludeId > 0) {
            $sql .= ' AND id_equipo <> :excludeId';
            $params['excludeId'] = $excludeId;
        }

        $row = self::obtenerUnRegistro($sql, $params);

        return intval($row['total'] ?? 0) > 0;
    }

    /**
     * Solo los articulos controlados por serie pueden tener equipos individuales.
     * Se incluye el articulo actual aunque este inactivo para no perder el valor
     * seleccionado al editar un registro historico.
     */
    public static function getArticulosOptions(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = "SELECT id_articulo AS value, nombre_articulo AS label,"
            . " codigo_interno_articulo AS code, estado_registro AS status"
            . " FROM articulos WHERE tipo_control = 'SERIE'";
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $sql .= ' AND (estado_registro = 1 OR id_articulo = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $sql .= ' AND estado_registro = 1';
            }
        }

        $sql .= ' ORDER BY nombre_articulo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getUbicacionesOptions(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = 'SELECT id_ubicacion AS value, nombre_ubicacion AS label,'
            . ' estado_ubi AS status FROM ubicaciones';
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $sql .= ' WHERE (estado_ubi = 1 OR id_ubicacion = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $sql .= ' WHERE estado_ubi = 1';
            }
        }

        $sql .= ' ORDER BY nombre_ubicacion ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getMotivosOptions(int $includeId = 0): array
    {
        $sql = 'SELECT id_motivo AS value, nombre_motivo AS label,'
            . ' motivo_aplica_a AS applies, estado_motivo AS status'
            . ' FROM motivos_ajustes_anulaciones';
        $params = [];

        if ($includeId > 0) {
            $sql .= ' WHERE (estado_motivo = 1 OR id_motivo = :includeId)';
            $params['includeId'] = $includeId;
        } else {
            $sql .= ' WHERE estado_motivo = 1';
        }

        $sql .= ' ORDER BY nombre_motivo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getArticuloById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_articulo, nombre_articulo, codigo_interno_articulo,'
            . ' tipo_control, estado_registro'
            . ' FROM articulos WHERE id_articulo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getUbicacionById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_ubicacion, nombre_ubicacion, estado_ubi'
            . ' FROM ubicaciones WHERE id_ubicacion = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getMotivoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_motivo, nombre_motivo, motivo_aplica_a, estado_motivo'
            . ' FROM motivos_ajustes_anulaciones WHERE id_motivo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function insertEquipo(array $data): bool
    {
        return self::executeNonQuery(
            'INSERT INTO equipos_individuales ('
            . 'id_articulo, numero_serie_equipo, codigo_patrimonial_equipo,'
            . ' fecha_adquisicion, id_motivo, proveedor_origen, estado_operativo,'
            . ' id_ubicacion, garantia_vencimiento_equipo, observaciones_equipo,'
            . ' estado_registro'
            . ') VALUES ('
            . ':articulo, :serie, :patrimonial, :adquisicion, :motivo, :proveedor,'
            . ' :operativo, :ubicacion, :garantia, :observaciones, :estado'
            . ')',
            [
                'articulo' => intval($data['id_articulo']),
                'serie' => $data['numero_serie_equipo'],
                'patrimonial' => $data['codigo_patrimonial_equipo'],
                'adquisicion' => $data['fecha_adquisicion'],
                'motivo' => $data['id_motivo'],
                'proveedor' => $data['proveedor_origen'],
                'operativo' => $data['estado_operativo'],
                'ubicacion' => intval($data['id_ubicacion']),
                'garantia' => $data['garantia_vencimiento_equipo'],
                'observaciones' => $data['observaciones_equipo'],
                'estado' => intval($data['estado_registro']),
            ]
        );
    }

    public static function updateEquipo(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE equipos_individuales SET'
            . ' id_articulo = :articulo, numero_serie_equipo = :serie,'
            . ' codigo_patrimonial_equipo = :patrimonial, fecha_adquisicion = :adquisicion,'
            . ' id_motivo = :motivo, proveedor_origen = :proveedor,'
            . ' estado_operativo = :operativo, id_ubicacion = :ubicacion,'
            . ' garantia_vencimiento_equipo = :garantia,'
            . ' observaciones_equipo = :observaciones, estado_registro = :estado'
            . ' WHERE id_equipo = :id',
            [
                'articulo' => intval($data['id_articulo']),
                'serie' => $data['numero_serie_equipo'],
                'patrimonial' => $data['codigo_patrimonial_equipo'],
                'adquisicion' => $data['fecha_adquisicion'],
                'motivo' => $data['id_motivo'],
                'proveedor' => $data['proveedor_origen'],
                'operativo' => $data['estado_operativo'],
                'ubicacion' => intval($data['id_ubicacion']),
                'garantia' => $data['garantia_vencimiento_equipo'],
                'observaciones' => $data['observaciones_equipo'],
                'estado' => intval($data['estado_registro']),
                'id' => $id,
            ]
        );
    }

    public static function tieneHistorialOperativo(int $equipoId): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        // Verificar asignaciones (activas o devueltas)
        $asignaciones = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM asignaciones_devoluciones WHERE id_equipo = :id',
            ['id' => $equipoId]
        );
        if (intval($asignaciones['total'] ?? 0) > 0) {
            return true;
        }

        // Verificar mantenimientos (cualquier estado)
        $mantenimientos = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM mantenimientos WHERE id_equipo = :id',
            ['id' => $equipoId]
        );
        if (intval($mantenimientos['total'] ?? 0) > 0) {
            return true;
        }

        return false;
    }

    public static function deactivateEquipo(int $id): bool
    {
        return self::executeNonQuery(
            'UPDATE equipos_individuales SET estado_registro = 0 WHERE id_equipo = :id',
            ['id' => $id]
        );
    }
}
