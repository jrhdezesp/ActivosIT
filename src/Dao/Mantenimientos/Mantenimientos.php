<?php

namespace Dao\Mantenimientos;

use Dao\Table;

class Mantenimientos extends Table
{
    public const ESTADOS = ['EN_PROCESO', 'COMPLETADO', 'CANCELADO'];

    private const ORDER_FIELDS = [
        'fecha_recepcion' => 'm.fecha_recepcion',
        'fecha_finalizacion' => 'm.fecha_finalizacion',
        'costo_mantenimiento' => 'm.costo_mantenimiento',
        'estado_mantenimiento' => 'm.estado_mantenimiento',
        'numero_serie_equipo' => 'eq.numero_serie_equipo',
        'id_mantenimiento' => 'm.id_mantenimiento',
    ];

    private const SELECT_FIELDS = 'SELECT m.*,'
        . ' eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,'
        . ' eq.estado_operativo, eq.estado_registro AS equipo_estado,'
        . ' eq.id_articulo, a.nombre_articulo, a.codigo_interno_articulo,'
        . ' a.tipo_control,'
        . ' t.nombre_tipo, t.descripcion_mant, t.estado_mant,'
        . ' u.nombre_usuario AS usuario_nombre, u.user_usuario';

    private const FROM_JOINS = ' FROM mantenimientos m'
        . ' LEFT JOIN equipos_individuales eq ON eq.id_equipo = m.id_equipo'
        . ' LEFT JOIN articulos a ON a.id_articulo = eq.id_articulo'
        . ' JOIN tipos_mantenimientos t ON t.id_tipo_mantenimiento = m.id_tipo_mantenimiento'
        . ' JOIN usuarios u ON u.id_usuario = m.id_usuario_registro';

    public static function getMantenimientos(
        string $search = '',
        string $estado = '',
        int $tipoId = 0,
        string $orderBy = 'fecha_recepcion',
        bool $orderDescending = true,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if ($estado !== '' && !in_array($estado, self::ESTADOS, true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_recepcion';
        }

        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(eq.numero_serie_equipo LIKE :searchSerie'
                . ' OR eq.codigo_patrimonial_equipo LIKE :searchPatrimonial'
                . ' OR a.nombre_articulo LIKE :searchArticulo'
                . ' OR a.codigo_interno_articulo LIKE :searchCodigo'
                . ' OR m.tecnico_responsable LIKE :searchTecnico'
                . ' OR m.diagnostico_inicial LIKE :searchDiagnostico'
                . ' OR m.trabajo_realizado LIKE :searchTrabajo'
                . ' OR t.nombre_tipo LIKE :searchTipo)';
            $searchValue = '%' . $search . '%';
            $params['searchSerie'] = $searchValue;
            $params['searchPatrimonial'] = $searchValue;
            $params['searchArticulo'] = $searchValue;
            $params['searchCodigo'] = $searchValue;
            $params['searchTecnico'] = $searchValue;
            $params['searchDiagnostico'] = $searchValue;
            $params['searchTrabajo'] = $searchValue;
            $params['searchTipo'] = $searchValue;
        }

        if ($estado !== '') {
            $conditions[] = 'm.estado_mantenimiento = :estado';
            $params['estado'] = $estado;
        }

        if ($tipoId > 0) {
            $conditions[] = 'm.id_tipo_mantenimiento = :tipoId';
            $params['tipoId'] = $tipoId;
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
            . ', m.id_mantenimiento DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'mantenimientos' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getMantenimientoById(int $id): ?array
    {
        return self::getMantenimientoByIdLock($id, false);
    }

    public static function getMantenimientoForUpdate(int $id): ?array
    {
        return self::getMantenimientoByIdLock($id, true);
    }

    private static function getMantenimientoByIdLock(int $id, bool $forUpdate): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $lock = $forUpdate ? ' FOR UPDATE' : '';

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS . self::FROM_JOINS
            . ' WHERE m.id_mantenimiento = :id LIMIT 1' . $lock,
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getSummary(): array
    {
        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(estado_mantenimiento = 'EN_PROCESO'), 0) AS en_proceso,"
            . " COALESCE(SUM(estado_mantenimiento = 'COMPLETADO'), 0) AS completados,"
            . " COALESCE(SUM(estado_mantenimiento = 'CANCELADO'), 0) AS cancelados,"
            . " COALESCE(SUM(CASE WHEN estado_mantenimiento <> 'CANCELADO' THEN costo_mantenimiento ELSE 0 END), 0) AS costo_total"
            . " FROM mantenimientos",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'en_proceso' => intval($row['en_proceso'] ?? 0),
            'completados' => intval($row['completados'] ?? 0),
            'cancelados' => intval($row['cancelados'] ?? 0),
            'costo_total' => floatval($row['costo_total'] ?? 0),
        ];
    }

    public static function getTiposMantenimiento(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = 'SELECT id_tipo_mantenimiento AS value, nombre_tipo AS label,'
            . ' descripcion_mant, estado_mant AS status'
            . ' FROM tipos_mantenimientos';
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $sql .= ' WHERE (estado_mant = 1 OR id_tipo_mantenimiento = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $sql .= ' WHERE estado_mant = 1';
            }
        }

        $sql .= ' ORDER BY nombre_tipo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Equipos disponibles para un mantenimiento. Se excluyen los equipos que
     * ya tienen otro mantenimiento en proceso y se conserva siempre el equipo
     * seleccionado (includeId) para no perder el valor al corregir el form.
     */
    public static function getEquiposOptions(
        bool $onlyActive = true,
        int $includeId = 0,
        int $excludeMantenimientoId = 0
    ): array {
        $params = [];
        $sql = 'SELECT eq.id_equipo AS value, eq.numero_serie_equipo,'
            . ' eq.codigo_patrimonial_equipo, eq.estado_operativo,'
            . ' eq.estado_registro AS status, eq.id_articulo,'
            . ' a.nombre_articulo, a.codigo_interno_articulo'
            . ' FROM equipos_individuales eq'
            . ' LEFT JOIN articulos a ON a.id_articulo = eq.id_articulo';

        if ($onlyActive) {
            $sql .= ' WHERE (eq.estado_registro = 1 OR eq.id_equipo = :includeId)';
            $params['includeId'] = $includeId;
        } else {
            $sql .= ' WHERE 1 = 1';
        }

        // Excluir equipos dados de baja (no deben entrar a mantenimiento)
        $sql .= " AND (eq.id_equipo = :includeIdBaja OR eq.estado_operativo <> 'DE_BAJA')";
        $params['includeIdBaja'] = $includeId;

        $sql .= ' AND (eq.id_equipo = :includeIdKeep'
            . ' OR NOT EXISTS ('
            . '   SELECT 1 FROM mantenimientos mx'
            . '   WHERE mx.id_equipo = eq.id_equipo'
            . "   AND mx.estado_mantenimiento = 'EN_PROCESO'"
            . '   AND mx.id_mantenimiento <> :excludeMantenimientoId'
            . ' ))';
        $params['includeIdKeep'] = $includeId;
        $params['excludeMantenimientoId'] = $excludeMantenimientoId;

        $sql .= ' ORDER BY eq.numero_serie_equipo ASC, eq.id_equipo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getTipoMantenimientoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_tipo_mantenimiento, nombre_tipo, descripcion_mant, estado_mant'
            . ' FROM tipos_mantenimientos WHERE id_tipo_mantenimiento = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
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
            . ' a.nombre_articulo, a.codigo_interno_articulo'
            . ' FROM equipos_individuales eq'
            . ' LEFT JOIN articulos a ON a.id_articulo = eq.id_articulo'
            . ' WHERE eq.id_equipo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function tieneMantenimientoActivo(int $equipoId, int $excludeId = 0): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM mantenimientos"
            . " WHERE id_equipo = :equipo AND estado_mantenimiento = 'EN_PROCESO'"
            . ' AND id_mantenimiento <> :excludeId',
            ['equipo' => $equipoId, 'excludeId' => $excludeId]
        );

        return intval($row['total'] ?? 0) > 0;
    }

    public static function insertMantenimiento(array $data): int
    {
        self::executeNonQuery(
            'INSERT INTO mantenimientos ('
            . 'id_equipo, id_tipo_mantenimiento, fecha_recepcion,'
            . ' diagnostico_inicial, trabajo_realizado, resultado_mantenimiento,'
            . ' recomendaciones, tecnico_responsable,'
            . ' repuestos_utilizados, costo_mantenimiento, fecha_finalizacion,'
            . ' estado_mantenimiento, id_usuario_registro'
            . ') VALUES ('
            . ':equipo, :tipo, :fechaRecepcion,'
            . ' :diagnostico, :trabajo, :resultado, :recomendaciones, :tecnico,'
            . ' :repuestos, :costo, :fechaFinalizacion,'
            . ' :estado, :usuario'
            . ')',
            [
                'equipo' => intval($data['id_equipo']),
                'tipo' => intval($data['id_tipo_mantenimiento']),
                'fechaRecepcion' => $data['fecha_recepcion'],
                'diagnostico' => $data['diagnostico_inicial'],
                'trabajo' => $data['trabajo_realizado'],
                'resultado' => $data['resultado_mantenimiento'],
                'recomendaciones' => $data['recomendaciones'],
                'tecnico' => $data['tecnico_responsable'],
                'repuestos' => $data['repuestos_utilizados'],
                'costo' => $data['costo_mantenimiento'],
                'fechaFinalizacion' => $data['fecha_finalizacion'],
                'estado' => $data['estado_mantenimiento'],
                'usuario' => intval($data['id_usuario_registro']),
            ]
        );

        $id = intval(self::getConn()->lastInsertId());
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo registrar el mantenimiento.');
        }

        return $id;
    }

    /**
     * Actualiza un mantenimiento únicamente mientras siga en proceso. La
     * condición en el WHERE evita sobrescribir un registro ya cerrado.
     */
    public static function updateMantenimiento(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE mantenimientos SET'
            . ' id_equipo = :equipo, id_tipo_mantenimiento = :tipo,'
            . ' fecha_recepcion = :fechaRecepcion,'
            . ' diagnostico_inicial = :diagnostico,'
            . ' trabajo_realizado = :trabajo,'
            . ' resultado_mantenimiento = :resultado,'
            . ' recomendaciones = :recomendaciones,'
            . ' tecnico_responsable = :tecnico,'
            . ' repuestos_utilizados = :repuestos,'
            . ' costo_mantenimiento = :costo,'
            . ' fecha_finalizacion = :fechaFinalizacion,'
            . ' estado_mantenimiento = :estado'
            . " WHERE id_mantenimiento = :id AND estado_mantenimiento = 'EN_PROCESO'",
            [
                'equipo' => intval($data['id_equipo']),
                'tipo' => intval($data['id_tipo_mantenimiento']),
                'fechaRecepcion' => $data['fecha_recepcion'],
                'diagnostico' => $data['diagnostico_inicial'],
                'trabajo' => $data['trabajo_realizado'],
                'resultado' => $data['resultado_mantenimiento'],
                'recomendaciones' => $data['recomendaciones'],
                'tecnico' => $data['tecnico_responsable'],
                'repuestos' => $data['repuestos_utilizados'],
                'costo' => $data['costo_mantenimiento'],
                'fechaFinalizacion' => $data['fecha_finalizacion'],
                'estado' => $data['estado_mantenimiento'],
                'id' => $id,
            ]
        );
    }

    public static function cancelarMantenimiento(
        int $id,
        string $fechaFinalizacion,
        ?string $motivoCancelacion
    ): bool
    {
        return self::executeNonQuery(
            "UPDATE mantenimientos SET estado_mantenimiento = 'CANCELADO',"
            . ' fecha_finalizacion = :fecha, motivo_cancelacion = :motivo'
            . " WHERE id_mantenimiento = :id AND estado_mantenimiento = 'EN_PROCESO'",
            ['fecha' => $fechaFinalizacion, 'motivo' => $motivoCancelacion, 'id' => $id]
        );
    }

    /**
     * Mantiene coherente el estado operativo del equipo:
     * - Con un mantenimiento en proceso pasa a EN_MANTENIMIENTO.
     * - Al cerrarlo vuelve a ASIGNADO si tiene una asignación activa o a
     *   DISPONIBLE si estaba en el taller.
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

        $activos = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM mantenimientos"
            . " WHERE id_equipo = :id AND estado_mantenimiento = 'EN_PROCESO'",
            ['id' => $equipoId]
        );

        $target = '';
        if (intval($activos['total'] ?? 0) > 0) {
            $target = 'EN_MANTENIMIENTO';
        } else {
            $asignaciones = self::obtenerUnRegistro(
                "SELECT COUNT(*) AS total FROM asignaciones_devoluciones"
                . " WHERE id_equipo = :id AND estado_asignacion = 'ACTIVA'",
                ['id' => $equipoId]
            );

            if (intval($asignaciones['total'] ?? 0) > 0) {
                $target = 'ASIGNADO';
            } elseif ($actual === 'EN_MANTENIMIENTO') {
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
