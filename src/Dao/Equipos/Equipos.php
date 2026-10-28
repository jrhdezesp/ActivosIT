<?php

namespace Dao\Equipos;

use Dao\Table;

/**
 * Acceso a datos de equipos individuales.
 *
 * Variables de filtro:
 * - $search: texto para serie, codigo patrimonial, proveedor, articulo o codigo.
 * - $status: estado logico; vacio, '1' o '0'.
 * - $estadoOperativo: estado operativo permitido.
 * - $articuloId: filtro por articulo; 0 para todos.
 * - $orderBy: campo permitido para ordenar.
 * - $orderDescending: true para orden descendente.
 * - $page: pagina basada en cero.
 * - $itemsPerPage: cantidad de registros por pagina.
 *
 * Variables de apoyo:
 * - $equipoId: clave primaria del equipo.
 * - $serie: numero de serie.
 * - $codigo: codigo patrimonial.
 * - $excludeId: identificador que se excluye al validar duplicados.
 * - $onlyActive: limita los selectores a registros activos.
 * - $includeId: incluye un identificador aunque este inactivo.
 *
 * Claves de $data para insertar o actualizar:
 * - id_articulo, numero_serie_equipo, codigo_patrimonial_equipo,
 *   fecha_adquisicion, id_motivo, proveedor_origen, estado_operativo,
 *   estado_fisico, id_ubicacion, garantia_vencimiento_equipo,
 *   observaciones_equipo, estado_registro.
 */
class Equipos extends Table
{
    // Estados que describen la situacion operativa de un equipo.
    public const ESTADOS_OPERATIVOS = [
        'DISPONIBLE',
        'RESERVADO',
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

    /**
     * Devuelve la lista paginada de equipos.
     *
     * @param string $search Texto de busqueda.
     * @param string $status Estado logico.
     * @param string $estadoOperativo Estado operativo.
     * @param int $articuloId Articulo; 0 para todos.
     * @param string $orderBy Campo de orden permitido.
     * @param bool $orderDescending true para orden descendente.
     * @param int $page Pagina basada en cero.
     * @param int $itemsPerPage Registros por pagina.
     * @param string $estadoFisico Estado fisico; vacio para todos.
     * @return array{equipos: array, total: int, page: int, itemsPerPage: int, pages: int}
     */
    public static function getEquipos(
        string $search = '',
        string $status = '',
        string $estadoOperativo = '',
        int $articuloId = 0,
        string $orderBy = 'numero_serie_equipo',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10,
        string $estadoFisico = ''
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

        $estadoFisico = trim($estadoFisico);
        if ($estadoFisico !== '') {
            $conditions[] = 'e.estado_fisico = :estadoFisico';
            $params['estadoFisico'] = $estadoFisico;
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
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'RESERVADO' THEN 1 ELSE 0 END), 0) AS reservados,"
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'ASIGNADO' THEN 1 ELSE 0 END), 0) AS asignados,"
            . " COALESCE(SUM(CASE WHEN estado_operativo IN ('EN_MANTENIMIENTO', 'EN_REVISION') THEN 1 ELSE 0 END), 0) AS servicio,"
            . " COALESCE(SUM(CASE WHEN estado_operativo = 'DE_BAJA' THEN 1 ELSE 0 END), 0) AS baja"
            . " FROM equipos_individuales WHERE estado_registro = 1",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'disponibles' => intval($row['disponibles'] ?? 0),
            'reservados' => intval($row['reservados'] ?? 0),
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

    /**
     * Inserta un equipo con las claves documentadas en la clase.
     *
     * @param array $data Datos del equipo.
     * @return bool true si la insercion fue correcta.
     */
    public static function insertEquipo(array $data): bool
    {
        $ok = self::executeNonQuery(
            'INSERT INTO equipos_individuales ('
            . 'id_articulo, numero_serie_equipo, codigo_patrimonial_equipo,'
            . ' fecha_adquisicion, id_motivo, proveedor_origen, estado_operativo,'
            . ' estado_fisico, id_estado_fisico, id_ubicacion, garantia_vencimiento_equipo, observaciones_equipo,'
            . ' estado_registro'
            . ') VALUES ('
            . ':articulo, :serie, :patrimonial, :adquisicion, :motivo, :proveedor,'
            . ' :operativo, :fisico,'
            . ' (SELECT id_estado_fisico FROM estados_fisicos WHERE LOWER(nombre_estado_fisico) = LOWER(:fisicoCat) LIMIT 1),'
            . ' :ubicacion, :garantia, :observaciones, :estado'
            . ')',
            [
                'articulo' => intval($data['id_articulo']),
                'serie' => $data['numero_serie_equipo'],
                'patrimonial' => $data['codigo_patrimonial_equipo'],
                'adquisicion' => $data['fecha_adquisicion'],
                'motivo' => $data['id_motivo'],
                'proveedor' => $data['proveedor_origen'],
                'operativo' => $data['estado_operativo'],
                'fisico' => $data['estado_fisico'],
                'fisicoCat' => $data['estado_fisico'],
                'ubicacion' => intval($data['id_ubicacion']),
                'garantia' => $data['garantia_vencimiento_equipo'],
                'observaciones' => $data['observaciones_equipo'],
                'estado' => intval($data['estado_registro']),
            ]
        );
        if ($ok) {
            self::sincronizarExistenciaSerie(intval($data['id_articulo']));
        }
        return $ok;
    }

    /**
     * Actualiza un equipo existente.
     *
     * @param int $id Clave primaria del equipo.
     * @param array $data Datos del equipo.
     * @return bool true si la actualizacion fue correcta.
     */
    public static function updateEquipo(int $id, array $data): bool
    {
        $anterior = self::getEquipoById($id);
        $ok = self::executeNonQuery(
            'UPDATE equipos_individuales SET'
            . ' id_articulo = :articulo, numero_serie_equipo = :serie,'
            . ' codigo_patrimonial_equipo = :patrimonial, fecha_adquisicion = :adquisicion,'
            . ' id_motivo = :motivo, proveedor_origen = :proveedor,'
            . ' estado_operativo = :operativo, estado_fisico = :fisico,'
            . ' id_estado_fisico = (SELECT id_estado_fisico FROM estados_fisicos WHERE LOWER(nombre_estado_fisico) = LOWER(:fisicoCat) LIMIT 1),'
            . ' id_ubicacion = :ubicacion,'
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
                'fisico' => $data['estado_fisico'],
                'fisicoCat' => $data['estado_fisico'],
                'ubicacion' => intval($data['id_ubicacion']),
                'garantia' => $data['garantia_vencimiento_equipo'],
                'observaciones' => $data['observaciones_equipo'],
                'estado' => intval($data['estado_registro']),
                'id' => $id,
            ]
        );
        if ($ok) {
            if (is_array($anterior)) {
                self::sincronizarExistenciaSerie(intval($anterior['id_articulo']));
            }
            self::sincronizarExistenciaSerie(intval($data['id_articulo']));
        }
        return $ok;
    }

    /**
     * Indica si el equipo esta reservado por una salida en borrador.
     *
     * @param int $equipoId Clave primaria del equipo.
     * @return bool
     */
    public static function tieneReservaActiva(int $equipoId): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM detalles_salida d"
            . " JOIN documentos_salida doc ON doc.id_doc_salida = d.id_doc_salida"
            . " JOIN equipos_individuales eq ON eq.id_equipo = d.id_equipo"
            . " WHERE d.id_equipo = :id AND doc.estado_doc = 'BORRADOR'"
            . " AND eq.estado_operativo = 'RESERVADO'",
            ['id' => $equipoId]
        );

        return intval($row['total'] ?? 0) > 0;
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
        $equipo = self::getEquipoById($id);
        $ok = self::executeNonQuery(
            'UPDATE equipos_individuales SET estado_registro = 0 WHERE id_equipo = :id',
            ['id' => $id]
        );
        if ($ok && is_array($equipo)) {
            self::sincronizarExistenciaSerie(intval($equipo['id_articulo']));
        }
        return $ok;
    }

    /**
     * Recalcula la existencia de un articulo controlado por serie.
     *
     * El saldo de estos articulos es el numero de equipos activos asociados,
     * por lo que se actualiza cada vez que un equipo se crea, cambia de
     * articulo o se desactiva.
     *
     * @param int $idArticulo Articulo a recalcular.
     * @return void
     */
    public static function sincronizarExistenciaSerie(int $idArticulo): void
    {
        if ($idArticulo <= 0) {
            return;
        }

        self::executeNonQuery(
            "UPDATE articulos a SET existencia_actual = (
                SELECT COUNT(*) FROM equipos_individuales e
                WHERE e.id_articulo = a.id_articulo AND e.estado_registro = 1 AND e.estado_operativo <> 'DE_BAJA'
            ) WHERE a.id_articulo = :id AND a.tipo_control = 'SERIE'",
            ['id' => $idArticulo]
        );
    }
}
