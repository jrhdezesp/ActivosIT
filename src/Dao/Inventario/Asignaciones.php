<?php

namespace Dao\Inventario;

use Dao\Table;

/**
 * Acceso a asignaciones y devoluciones de equipos.
 *
 * Variables de filtro:
 * - $search: texto para responsable, departamento, condicion, serie o articulo.
 * - $estado: ACTIVA o DEVUELTO.
 * - $departamento: departamento de destino.
 * - $orderBy: campo permitido para ordenar.
 * - $orderDescending: true para orden descendente.
 * - $page: pagina basada en cero.
 * - $itemsPerPage: cantidad de registros por pagina.
 *
 * Variables de apoyo:
 * - $equipoId: clave del equipo.
 * - $excludeId: asignacion que se excluye al validar.
 * - $includeId: equipo que se incluye aunque no este disponible.
 * - $excludeAsignacionId: asignacion que se conserva al editar.
 *
 * Claves de $data:
 * - id_equipo, persona_responsable, departamento_destino,
 *   id_usuario_entrega, fecha_asignacion, condicion_entrega,
 *   accesorios_entregados, observaciones.
 */
class Asignaciones extends Table
{
    // Estados que indican si una asignacion sigue activa o fue devuelta.
    public const ESTADOS = ['ACTIVA', 'DEVUELTO'];

    // Columnas permitidas para ordenar las asignaciones.
    private const ORDER_FIELDS = [
        'fecha_asignacion' => 'a.fecha_asignacion',
        'fecha_devolucion' => 'a.fecha_devolucion',
        'persona_responsable' => 'a.persona_responsable',
        'estado_asignacion' => 'a.estado_asignacion',
        'numero_serie_equipo' => 'eq.numero_serie_equipo',
        'id_asignacion' => 'a.id_asignacion',
    ];

    // Datos de la asignacion y nombres relacionados que usa el listado.
    private const SELECT_FIELDS = 'SELECT a.*,'
        . ' eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,'
        . ' eq.estado_operativo, eq.estado_registro AS equipo_estado,'
        . ' eq.id_articulo, ar.nombre_articulo, ar.codigo_interno_articulo,'
        . ' ue.nombre_usuario AS entrega_nombre, ue.user_usuario AS entrega_usuario,'
        . ' ur.nombre_usuario AS recibe_nombre, ur.user_usuario AS recibe_usuario';

    // Tablas relacionadas que completan los datos de cada asignacion.
    private const FROM_JOINS = ' FROM asignaciones_devoluciones a'
        . ' LEFT JOIN equipos_individuales eq ON eq.id_equipo = a.id_equipo'
        . ' LEFT JOIN articulos ar ON ar.id_articulo = eq.id_articulo'
        . ' JOIN usuarios ue ON ue.id_usuario = a.id_usuario_entrega'
        . ' LEFT JOIN usuarios ur ON ur.id_usuario = a.id_usuario_recibe';

    public static function getAsignaciones(
        string $search = '',
        string $estado = '',
        string $departamento = '',
        string $orderBy = 'fecha_asignacion',
        bool $orderDescending = true,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if ($estado !== '' && !in_array($estado, self::ESTADOS, true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_asignacion';
        }

        // Condiciones del listado y valores que se envian a la consulta.
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(a.persona_responsable LIKE :searchPersona'
                . ' OR a.departamento_destino LIKE :searchDepartamento'
                . ' OR a.condicion_entrega LIKE :searchCondicion'
                . ' OR a.condicion_devolucion LIKE :searchCondicionDevolucion'
                . ' OR a.observaciones LIKE :searchObservaciones'
                . ' OR eq.numero_serie_equipo LIKE :searchSerie'
                . ' OR eq.codigo_patrimonial_equipo LIKE :searchPatrimonial'
                . ' OR ar.nombre_articulo LIKE :searchArticulo'
                . ' OR ar.codigo_interno_articulo LIKE :searchCodigo'
                . ' OR ue.nombre_usuario LIKE :searchEntrega'
                . ' OR ur.nombre_usuario LIKE :searchRecibe)';
            $searchValue = '%' . $search . '%';
            $params['searchPersona'] = $searchValue;
            $params['searchDepartamento'] = $searchValue;
            $params['searchCondicion'] = $searchValue;
            $params['searchCondicionDevolucion'] = $searchValue;
            $params['searchObservaciones'] = $searchValue;
            $params['searchSerie'] = $searchValue;
            $params['searchPatrimonial'] = $searchValue;
            $params['searchArticulo'] = $searchValue;
            $params['searchCodigo'] = $searchValue;
            $params['searchEntrega'] = $searchValue;
            $params['searchRecibe'] = $searchValue;
        }

        if ($estado !== '') {
            $conditions[] = 'a.estado_asignacion = :estado';
            $params['estado'] = $estado;
        }

        if ($departamento !== '') {
            $conditions[] = 'a.departamento_destino = :departamento';
            $params['departamento'] = $departamento;
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
            . ', a.id_asignacion DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'asignaciones' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getAsignacionById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS . self::FROM_JOINS
            . ' WHERE a.id_asignacion = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getAsignacionForUpdate(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT * FROM asignaciones_devoluciones WHERE id_asignacion = :id FOR UPDATE',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getSummary(): array
    {
        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(estado_asignacion = 'ACTIVA'), 0) AS activas,"
            . " COALESCE(SUM(estado_asignacion = 'DEVUELTO'), 0) AS devueltas,"
            . " COALESCE(COUNT(DISTINCT CASE WHEN estado_asignacion = 'ACTIVA' THEN id_equipo END), 0) AS equipos_activos"
            . " FROM asignaciones_devoluciones",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'activas' => intval($row['activas'] ?? 0),
            'devueltas' => intval($row['devueltas'] ?? 0),
            'equipos_activos' => intval($row['equipos_activos'] ?? 0),
        ];
    }

    public static function getDepartamentos(): array
    {
        return self::obtenerRegistros(
            'SELECT DISTINCT departamento_destino AS departamento'
            . ' FROM asignaciones_devoluciones'
            . ' WHERE departamento_destino <> \'\''
            . ' ORDER BY departamento_destino ASC',
            []
        );
    }

    /**
     * Equipos disponibles para una asignación. Se excluyen los equipos con
     * otra asignación activa o con un mantenimiento en proceso, y se conserva
     * siempre el equipo actual (includeId) para no perder la selección al
     * corregir el formulario o al registrar una devolución.
     */
    public static function getEquiposOptions(
        bool $onlyActive = true,
        int $includeId = 0,
        int $excludeAsignacionId = 0
    ): array {
        $params = [];
        $sql = 'SELECT eq.id_equipo AS value, eq.numero_serie_equipo,'
            . ' eq.codigo_patrimonial_equipo, eq.estado_operativo,'
            . ' eq.estado_registro AS status, eq.id_articulo,'
            . ' ar.nombre_articulo, ar.codigo_interno_articulo'
            . ' FROM equipos_individuales eq'
            . ' LEFT JOIN articulos ar ON ar.id_articulo = eq.id_articulo';

        if ($onlyActive) {
            $sql .= ' WHERE (eq.estado_registro = 1 OR eq.id_equipo = :includeId)';
            $params['includeId'] = $includeId;
        } else {
            $sql .= ' WHERE 1 = 1';
        }

        $sql .= ' AND (eq.id_equipo = :includeIdKeep'
            . ' OR (eq.estado_operativo = \'DISPONIBLE\''
            . ' AND NOT EXISTS ('
            . '   SELECT 1 FROM asignaciones_devoluciones ax'
            . '   WHERE ax.id_equipo = eq.id_equipo'
            . "   AND ax.estado_asignacion = 'ACTIVA'"
            . '   AND ax.id_asignacion <> :excludeAsignacionId'
            . ' )'
            . ' AND NOT EXISTS ('
            . '   SELECT 1 FROM mantenimientos mx'
            . '   WHERE mx.id_equipo = eq.id_equipo'
            . "   AND mx.estado_mantenimiento = 'EN_PROCESO'"
            . ' )))';
        $params['includeIdKeep'] = $includeId;
        $params['excludeAsignacionId'] = $excludeAsignacionId;

        $sql .= ' ORDER BY eq.numero_serie_equipo ASC, eq.id_equipo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getEquipoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT eq.id_equipo, eq.numero_serie_equipo,'
            . ' eq.codigo_patrimonial_equipo, eq.estado_operativo,'
            . ' eq.estado_registro, eq.id_articulo,'
            . ' ar.nombre_articulo, ar.codigo_interno_articulo'
            . ' FROM equipos_individuales eq'
            . ' LEFT JOIN articulos ar ON ar.id_articulo = eq.id_articulo'
            . ' WHERE eq.id_equipo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    /**
     * Bloquea la fila del equipo para validar su disponibilidad dentro de la
     * misma transaccion en la que se registra o mueve una asignacion.
     */
    public static function getEquipoForUpdate(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_equipo, estado_operativo, estado_registro'
            . ' FROM equipos_individuales WHERE id_equipo = :id FOR UPDATE',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function tieneAsignacionActiva(int $equipoId, int $excludeId = 0): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM asignaciones_devoluciones"
            . " WHERE id_equipo = :equipo AND estado_asignacion = 'ACTIVA'"
            . ' AND id_asignacion <> :excludeId',
            ['equipo' => $equipoId, 'excludeId' => $excludeId]
        );

        return intval($row['total'] ?? 0) > 0;
    }

    public static function tieneMantenimientoActivo(int $equipoId): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM mantenimientos"
            . " WHERE id_equipo = :equipo AND estado_mantenimiento = 'EN_PROCESO'",
            ['equipo' => $equipoId]
        );

        return intval($row['total'] ?? 0) > 0;
    }

    /**
     * Registra una asignacion y devuelve su identificador.
     *
     * @param array $data Claves documentadas en la clase.
     * @return int Identificador creado.
     */
    public static function insertAsignacion(array $data): int
    {
        self::executeNonQuery(
            'INSERT INTO asignaciones_devoluciones ('
            . 'id_equipo, persona_responsable, departamento_destino, id_departamento,'
            . ' id_usuario_entrega, fecha_asignacion, condicion_entrega, accesorios_entregados,'
            . ' estado_asignacion, observaciones'
            . ') VALUES ('
            . ':equipo, :persona, :departamento,'
            . ' (SELECT id_departamento FROM departamentos WHERE LOWER(nombre_departamento) = LOWER(:departamentoCat) LIMIT 1),'
            . ' :usuarioEntrega, :fechaAsignacion, :condicionEntrega, :accesorios,'
            . " 'ACTIVA', :observaciones"
            . ')',
            [
                'equipo' => intval($data['id_equipo']),
                'persona' => $data['persona_responsable'],
                'departamento' => $data['departamento_destino'],
                'departamentoCat' => $data['departamento_destino'],
                'usuarioEntrega' => intval($data['id_usuario_entrega']),
                'fechaAsignacion' => $data['fecha_asignacion'],
                'condicionEntrega' => $data['condicion_entrega'],
                'accesorios' => $data['accesorios_entregados'],
                'observaciones' => $data['observaciones'],
            ]
        );

        $id = intval(self::getConn()->lastInsertId());
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo registrar la asignación.');
        }

        return $id;
    }

    /**
     * Actualiza una asignación únicamente mientras siga activa.
     */
    /**
     * Actualiza una asignacion activa.
     *
     * @param int $id Clave de la asignacion.
     * @param array $data Claves documentadas en la clase.
     * @return bool true si la actualizacion fue correcta.
     */
    public static function updateAsignacion(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE asignaciones_devoluciones SET'
            . ' id_equipo = :equipo, persona_responsable = :persona,'
            . ' departamento_destino = :departamento,'
            . ' id_departamento = (SELECT id_departamento FROM departamentos WHERE LOWER(nombre_departamento) = LOWER(:departamentoCat) LIMIT 1),'
            . ' fecha_asignacion = :fechaAsignacion,'
            . ' condicion_entrega = :condicionEntrega,'
            . ' accesorios_entregados = :accesorios,'
            . ' observaciones = :observaciones'
            . " WHERE id_asignacion = :id AND estado_asignacion = 'ACTIVA'",
            [
                'equipo' => intval($data['id_equipo']),
                'persona' => $data['persona_responsable'],
                'departamento' => $data['departamento_destino'],
                'departamentoCat' => $data['departamento_destino'],
                'fechaAsignacion' => $data['fecha_asignacion'],
                'condicionEntrega' => $data['condicion_entrega'],
                'accesorios' => $data['accesorios_entregados'],
                'observaciones' => $data['observaciones'],
                'id' => $id,
            ]
        );
    }

    /**
     * Registra la devolucion de una asignacion activa.
     *
     * @param int $id Clave de la asignacion.
     * @param string $fechaDevolucion Fecha de devolucion.
     * @param int $usuarioRecibe Usuario que recibe el equipo.
     * @param string|null $condicionDevolucion Condicion del equipo.
     * @return bool true si la devolucion fue correcta.
     */
    public static function registrarDevolucion(
        int $id,
        string $fechaDevolucion,
        int $usuarioRecibe,
        ?string $condicionDevolucion
    ): bool {
        return self::executeNonQuery(
            "UPDATE asignaciones_devoluciones SET"
            . " estado_asignacion = 'DEVUELTO',"
            . ' fecha_devolucion = :fechaDevolucion,'
            . ' id_usuario_recibe = :usuarioRecibe,'
            . ' condicion_devolucion = :condicionDevolucion'
            . " WHERE id_asignacion = :id AND estado_asignacion = 'ACTIVA'",
            [
                'fechaDevolucion' => $fechaDevolucion,
                'usuarioRecibe' => $usuarioRecibe,
                'condicionDevolucion' => $condicionDevolucion,
                'id' => $id,
            ]
        );
    }

    /**
     * Mantiene coherente el estado operativo del equipo:
     * - Si tiene un mantenimiento en proceso, prevalece EN_MANTENIMIENTO.
     * - Si tiene una asignación activa, pasa a ASIGNADO.
     * - Si ya no tiene vínculos activos, vuelve a DISPONIBLE.
     * - Un equipo de baja nunca cambia automáticamente.
     */
    public static function syncEquipoEstado(int $equipoId): void
    {
        if ($equipoId <= 0) {
            return;
        }

        $equipo = self::obtenerUnRegistro(
            'SELECT id_equipo, estado_operativo, estado_registro'
            . ' FROM equipos_individuales WHERE id_equipo = :id FOR UPDATE',
            ['id' => $equipoId]
        );

        if (!$equipo || intval($equipo['estado_registro'] ?? 0) !== 1) {
            return;
        }

        $actual = strval($equipo['estado_operativo'] ?? '');
        if ($actual === 'DE_BAJA') {
            return;
        }

        $mantenimientos = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM mantenimientos"
            . " WHERE id_equipo = :id AND estado_mantenimiento = 'EN_PROCESO'",
            ['id' => $equipoId]
        );

        $target = '';
        if (intval($mantenimientos['total'] ?? 0) > 0) {
            $target = 'EN_MANTENIMIENTO';
        } else {
            $asignaciones = self::obtenerUnRegistro(
                "SELECT COUNT(*) AS total FROM asignaciones_devoluciones"
                . " WHERE id_equipo = :id AND estado_asignacion = 'ACTIVA'",
                ['id' => $equipoId]
            );

            if (intval($asignaciones['total'] ?? 0) > 0) {
                $target = 'ASIGNADO';
            } elseif (in_array($actual, ['ASIGNADO', 'EN_MANTENIMIENTO'], true)) {
                $target = 'DISPONIBLE';
            }
        }

        if ($target === '' || $target === $actual) {
            return;
        }

        self::executeNonQuery(
            'UPDATE equipos_individuales SET estado_operativo = :estado'
            . ' WHERE id_equipo = :id',
            ['estado' => $target, 'id' => $equipoId]
        );
    }

    public static function beginTransaction(): void
    {
        if (!self::getConn()->inTransaction()) {
            self::getConn()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        if (self::getConn()->inTransaction()) {
            self::getConn()->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::getConn()->inTransaction()) {
            self::getConn()->rollBack();
        }
    }
}
