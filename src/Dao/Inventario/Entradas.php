<?php

namespace Dao\Inventario;

use Dao\Table;

class Entradas extends Table
{
    // Estados posibles de un documento de entrada.
    public const ESTADOS = ['BORRADOR', 'CONFIRMADO', 'ANULADO'];

    // Columnas permitidas para ordenar las entradas.
    private const ORDER_FIELDS = [
        'fecha_movimiento' => 'e.fecha_movimiento',
        'correlativo' => 'e.correlativo',
        'estado_doc' => 'e.estado_doc',
        'fecha_creacion' => 'e.fecha_creacion',
        'id_doc_entrada' => 'e.id_doc_entrada',
    ];

    // Datos del encabezado y nombres relacionados que usa el listado.
    private const SELECT_FIELDS = 'SELECT e.*,'
        . ' td.nombre_tipo, td.naturaleza_doc,'
        . ' m.nombre_motivo, m.motivo_aplica_a,'
        . ' u.nombre_usuario AS usuario_nombre, u.user_usuario,';

    // Datos de cada linea y referencias asociadas al documento.
    private const SELECT_DETAILS = 'SELECT d.*,'
        . ' a.nombre_articulo, a.codigo_interno_articulo, a.tipo_control,'
        . ' um.nombre_unidad, um.simbolo_medida,'
        . ' eq.numero_serie_equipo, eq.codigo_patrimonial_equipo'
        . ' FROM detalles_entrada d'
        . ' JOIN articulos a ON a.id_articulo = d.id_articulo'
        . ' LEFT JOIN unidades_medida um ON um.id_unidad_medida = a.id_unidad_medida'
        . ' LEFT JOIN equipos_individuales eq ON eq.id_equipo = d.id_equipo';

    public static function getEntradas(
        string $search = '',
        string $estado = '',
        int $tipoDocId = 0,
        string $orderBy = 'fecha_movimiento',
        bool $orderDescending = true,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if ($estado !== '' && !in_array($estado, self::ESTADOS, true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        if (!isset(self::ORDER_FIELDS[$orderBy])) {
            $orderBy = 'fecha_movimiento';
        }

        // Condiciones del listado y valores que se envian a la consulta.
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(e.correlativo LIKE :searchCorrelativo'
                . ' OR e.proveedor_origen LIKE :searchProveedor'
                . ' OR e.observaciones LIKE :searchObservaciones'
                . ' OR td.nombre_tipo LIKE :searchTipo)';
            $searchValue = '%' . $search . '%';
            $params['searchCorrelativo'] = $searchValue;
            $params['searchProveedor'] = $searchValue;
            $params['searchObservaciones'] = $searchValue;
            $params['searchTipo'] = $searchValue;
        }

        if ($estado !== '') {
            $conditions[] = 'e.estado_doc = :estado';
            $params['estado'] = $estado;
        }

        if ($tipoDocId > 0) {
            $conditions[] = 'e.id_tipo_doc = :tipoDoc';
            $params['tipoDoc'] = $tipoDocId;
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $countRow = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM documentos_entrada e'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = e.id_tipo_doc'
            . $where,
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

        $sql = self::SELECT_FIELDS
            . ' (SELECT COUNT(*) FROM detalles_entrada d'
            . ' WHERE d.id_doc_entrada = e.id_doc_entrada) AS lineas,'
            . ' (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalles_entrada d'
            . ' WHERE d.id_doc_entrada = e.id_doc_entrada) AS unidades'
            . ' FROM documentos_entrada e'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = e.id_tipo_doc'
            . ' LEFT JOIN motivos_ajustes_anulaciones m ON m.id_motivo = e.id_motivo'
            . ' JOIN usuarios u ON u.id_usuario = e.id_usuario'
            . $where
            . ' ORDER BY ' . self::ORDER_FIELDS[$orderBy]
            . ($orderDescending ? ' DESC' : ' ASC')
            . ', e.id_doc_entrada DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'entradas' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getEntradaById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS
            . ' (SELECT COUNT(*) FROM detalles_entrada d'
            . ' WHERE d.id_doc_entrada = e.id_doc_entrada) AS lineas,'
            . ' (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalles_entrada d'
            . ' WHERE d.id_doc_entrada = e.id_doc_entrada) AS unidades'
            . ' FROM documentos_entrada e'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = e.id_tipo_doc'
            . ' LEFT JOIN motivos_ajustes_anulaciones m ON m.id_motivo = e.id_motivo'
            . ' JOIN usuarios u ON u.id_usuario = e.id_usuario'
            . ' WHERE e.id_doc_entrada = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getDetalles(int $docId): array
    {
        if ($docId <= 0) {
            return [];
        }

        return self::obtenerRegistros(
            self::SELECT_DETAILS
            . ' WHERE d.id_doc_entrada = :id ORDER BY d.id_detalle_ent ASC',
            ['id' => $docId]
        );
    }

    public static function getSummary(): array
    {
        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(estado_doc = 'BORRADOR'), 0) AS borradores,"
            . " COALESCE(SUM(estado_doc = 'CONFIRMADO'), 0) AS confirmados,"
            . " COALESCE(SUM(estado_doc = 'ANULADO'), 0) AS anulados,"
            . " COALESCE((SELECT SUM(d.cantidad) FROM detalles_entrada d"
            . " JOIN documentos_entrada x ON x.id_doc_entrada = d.id_doc_entrada"
            . " WHERE x.estado_doc = 'CONFIRMADO'), 0) AS unidades"
            . " FROM documentos_entrada",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'borradores' => intval($row['borradores'] ?? 0),
            'confirmados' => intval($row['confirmados'] ?? 0),
            'anulados' => intval($row['anulados'] ?? 0),
            'unidades' => intval($row['unidades'] ?? 0),
        ];
    }

    public static function getTiposDocumento(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = "SELECT id_tipo_doc AS value, nombre_tipo AS label,"
            . " naturaleza_doc, estado_doc AS status"
            . " FROM tipos_documentos WHERE naturaleza_doc = 'ENTRADA'";
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $sql .= ' AND (estado_doc = 1 OR id_tipo_doc = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $sql .= ' AND estado_doc = 1';
            }
        }

        $sql .= ' ORDER BY nombre_tipo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getMotivosEntrada(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = "SELECT id_motivo AS value, nombre_motivo AS label,"
            . " motivo_aplica_a, estado_motivo AS status"
            . " FROM motivos_ajustes_anulaciones WHERE motivo_aplica_a = 'AJUSTE_ENTRADA'";
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $sql .= ' AND (estado_motivo = 1 OR id_motivo = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $sql .= ' AND estado_motivo = 1';
            }
        }

        $sql .= ' ORDER BY nombre_motivo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Articulos disponibles para una linea. Se incluyen los articulos que ya
     * forman parte del documento aunque esten inactivos, para no perder la
     * seleccion al corregir un borrador.
     */
    public static function getArticulosOptions(array $includeIds = []): array
    {
        $sql = 'SELECT id_articulo AS value, nombre_articulo AS label,'
            . ' codigo_interno_articulo AS code, tipo_control,'
            . ' estado_registro AS status'
            . ' FROM articulos WHERE (estado_registro = 1';
        $params = [];

        $includeIds = array_values(array_unique(array_map('intval', $includeIds)));
        $includeIds = array_filter($includeIds, function ($id) {
            return $id > 0;
        });

        if (count($includeIds) > 0) {
            // Valores de cada linea que se agregan en una sola operacion.
            $placeholders = [];
            foreach ($includeIds as $index => $id) {
                $key = 'include' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $sql .= ' OR id_articulo IN (' . implode(', ', $placeholders) . ')';
        }

        $sql .= ') ORDER BY nombre_articulo ASC';

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Equipos que aun no forman parte de una entrada confirmada. Un equipo
     * anulado vuelve a estar disponible para una nueva recepcion.
     */
    public static function getEquiposDisponibles(): array
    {
        return self::obtenerRegistros(
            "SELECT eq.id_equipo AS value, eq.id_articulo,"
            . " eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,"
            . " eq.estado_operativo"
            . " FROM equipos_individuales eq"
            . " WHERE eq.estado_registro = 1"
            . " AND NOT EXISTS ("
            . "   SELECT 1 FROM detalles_entrada d"
            . "   JOIN documentos_entrada doc ON doc.id_doc_entrada = d.id_doc_entrada"
            . "   WHERE d.id_equipo = eq.id_equipo AND doc.estado_doc = 'CONFIRMADO'"
            . " )"
            . " ORDER BY eq.numero_serie_equipo ASC",
            []
        );
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

    public static function getEquipoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_equipo, id_articulo, numero_serie_equipo,'
            . ' codigo_patrimonial_equipo, estado_registro'
            . ' FROM equipos_individuales WHERE id_equipo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getTipoDocumentoById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_tipo_doc, nombre_tipo, naturaleza_doc, estado_doc'
            . ' FROM tipos_documentos WHERE id_tipo_doc = :id LIMIT 1',
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

    public static function equipoUsadoEnConfirmado(int $equipoId): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM detalles_entrada d"
            . " JOIN documentos_entrada doc ON doc.id_doc_entrada = d.id_doc_entrada"
            . " WHERE d.id_equipo = :id AND doc.estado_doc = 'CONFIRMADO'",
            ['id' => $equipoId]
        );

        return intval($row['total'] ?? 0) > 0;
    }

    public static function insertDocumento(array $data): int
    {
        $tempCorrelativo = 'TMP-' . bin2hex(random_bytes(8));

        self::executeNonQuery(
            'INSERT INTO documentos_entrada ('
            . 'correlativo, id_tipo_doc, fecha_movimiento, id_usuario, id_motivo,'
            . ' proveedor_origen, estado_doc, observaciones'
            . ') VALUES ('
            . ':correlativo, :tipo, :fecha, :usuario, :motivo,'
            . ' :proveedor, :estado, :observaciones'
            . ')',
            [
                'correlativo' => $tempCorrelativo,
                'tipo' => intval($data['id_tipo_doc']),
                'fecha' => $data['fecha_movimiento'],
                'usuario' => intval($data['id_usuario']),
                'motivo' => $data['id_motivo'],
                'proveedor' => $data['proveedor_origen'],
                'estado' => $data['estado_doc'],
                'observaciones' => $data['observaciones'],
            ]
        );

        $id = intval(self::getConn()->lastInsertId());
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo registrar el documento de entrada.');
        }

        $year = date('Y', strtotime($data['fecha_movimiento']));
        $correlativo = 'ENT-' . $year . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

        self::executeNonQuery(
            'UPDATE documentos_entrada SET correlativo = :correlativo'
            . ' WHERE id_doc_entrada = :id',
            ['correlativo' => $correlativo, 'id' => $id]
        );

        return $id;
    }

    public static function updateDocumento(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE documentos_entrada SET'
            . ' id_tipo_doc = :tipo, fecha_movimiento = :fecha, id_motivo = :motivo,'
            . ' proveedor_origen = :proveedor, observaciones = :observaciones'
            . ' WHERE id_doc_entrada = :id',
            [
                'tipo' => intval($data['id_tipo_doc']),
                'fecha' => $data['fecha_movimiento'],
                'motivo' => $data['id_motivo'],
                'proveedor' => $data['proveedor_origen'],
                'observaciones' => $data['observaciones'],
                'id' => $id,
            ]
        );
    }

    public static function replaceDetalles(int $docId, array $detalles): void
    {
        self::executeNonQuery(
            'DELETE FROM detalles_entrada WHERE id_doc_entrada = :id',
            ['id' => $docId]
        );

        self::insertDetalles($docId, $detalles);
    }

    public static function insertDetalles(int $docId, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            self::executeNonQuery(
                'INSERT INTO detalles_entrada ('
                . 'id_doc_entrada, id_articulo, cantidad, id_equipo, costo_unitario'
                . ') VALUES ('
                . ':documento, :articulo, :cantidad, :equipo, :costo'
                . ')',
                [
                    'documento' => $docId,
                    'articulo' => intval($detalle['id_articulo']),
                    'cantidad' => intval($detalle['cantidad']),
                    'equipo' => $detalle['id_equipo'],
                    'costo' => $detalle['costo_unitario'],
                ]
            );
        }
    }

    /**
     * Confirma el documento: suma existencias por articulo y registra el
     * movimiento correspondiente en el Kardex. Debe ejecutarse dentro de una
     * transaccion iniciada por el controlador.
     */
    public static function confirmar(int $docId, int $userId): void
    {
        $doc = self::obtenerUnRegistro(
            'SELECT * FROM documentos_entrada WHERE id_doc_entrada = :id FOR UPDATE',
            ['id' => $docId]
        );

        if (!$doc) {
            throw new \RuntimeException('No se encontró el documento de entrada.');
        }

        if (strval($doc['estado_doc']) !== 'BORRADOR') {
            throw new \RuntimeException('Solo se pueden confirmar documentos en borrador.');
        }

        $detalles = self::getDetalles($docId);
        if (count($detalles) === 0) {
            throw new \RuntimeException('El documento no tiene líneas de detalle.');
        }

        // Verificación final de equipos: un equipo solo puede pertenecer a una
        // entrada confirmada, aunque dos borradores lo hayan seleccionado.
        foreach ($detalles as $detalle) {
            $equipoId = intval($detalle['id_equipo'] ?? 0);
            if ($equipoId <= 0) {
                continue;
            }

            self::obtenerUnRegistro(
                'SELECT id_equipo FROM equipos_individuales WHERE id_equipo = :id FOR UPDATE',
                ['id' => $equipoId]
            );

            $usado = self::obtenerUnRegistro(
                'SELECT d.id_detalle_ent FROM detalles_entrada d'
                . ' INNER JOIN documentos_entrada doc ON doc.id_doc_entrada = d.id_doc_entrada'
                . " WHERE d.id_equipo = :equipo AND doc.estado_doc = 'CONFIRMADO'"
                . ' AND d.id_doc_entrada <> :doc LIMIT 1 FOR UPDATE',
                ['equipo' => $equipoId, 'doc' => $docId]
            );

            if ($usado) {
                throw new \RuntimeException(
                    'Uno de los equipos del documento ya fue registrado en otra entrada confirmada.'
                );
            }
        }

        $totales = self::totalesPorArticulo($detalles);

        foreach ($totales as $articuloId => $cantidad) {
            $articulo = self::obtenerUnRegistro(
                'SELECT existencia_actual, nombre_articulo'
                . ' FROM articulos WHERE id_articulo = :id FOR UPDATE',
                ['id' => $articuloId]
            );

            if (!$articulo) {
                throw new \RuntimeException('No se encontró uno de los artículos del documento.');
            }

            $anterior = intval($articulo['existencia_actual']);
            $nueva = $anterior + intval($cantidad);

            self::executeNonQuery(
                'UPDATE articulos SET existencia_actual = :nueva WHERE id_articulo = :id',
                ['nueva' => $nueva, 'id' => $articuloId]
            );

            self::registrarMovimiento(
                $articuloId,
                $doc['id_motivo'] === null ? null : intval($doc['id_motivo']),
                'ENTRADA',
                intval($cantidad),
                $anterior,
                $nueva,
                $docId,
                strval($doc['fecha_movimiento']),
                $userId,
                'Entrada según ' . strval($doc['correlativo'])
            );
        }

        self::executeNonQuery(
            "UPDATE documentos_entrada SET estado_doc = 'CONFIRMADO'"
            . ' WHERE id_doc_entrada = :id',
            ['id' => $docId]
        );
    }

    /**
     * Anula el documento. Si estaba confirmado revierte las existencias y deja
     * el movimiento de ajuste negativo en el Kardex. Debe ejecutarse dentro de
     * una transaccion iniciada por el controlador.
     */
    public static function anular(int $docId, int $userId): void
    {
        $doc = self::obtenerUnRegistro(
            'SELECT * FROM documentos_entrada WHERE id_doc_entrada = :id FOR UPDATE',
            ['id' => $docId]
        );

        if (!$doc) {
            throw new \RuntimeException('No se encontró el documento de entrada.');
        }

        if (strval($doc['estado_doc']) === 'ANULADO') {
            throw new \RuntimeException('El documento ya está anulado.');
        }

        if (strval($doc['estado_doc']) === 'CONFIRMADO') {
            $detalles = self::getDetalles($docId);
            $totales = self::totalesPorArticulo($detalles);

            foreach ($totales as $articuloId => $cantidad) {
                $articulo = self::obtenerUnRegistro(
                    'SELECT existencia_actual, nombre_articulo'
                    . ' FROM articulos WHERE id_articulo = :id FOR UPDATE',
                    ['id' => $articuloId]
                );

                if (!$articulo) {
                    throw new \RuntimeException('No se encontró uno de los artículos del documento.');
                }

                $anterior = intval($articulo['existencia_actual']);
                $cantidad = intval($cantidad);

                if ($anterior < $cantidad) {
                    throw new \RuntimeException(
                        'No hay existencias suficientes de "' . strval($articulo['nombre_articulo'])
                        . '" para anular la entrada (disponible: ' . $anterior
                        . ', requerido: ' . $cantidad . ').'
                    );
                }

                $nueva = $anterior - $cantidad;

                self::executeNonQuery(
                    'UPDATE articulos SET existencia_actual = :nueva WHERE id_articulo = :id',
                    ['nueva' => $nueva, 'id' => $articuloId]
                );

                self::registrarMovimiento(
                    $articuloId,
                    $doc['id_motivo'] === null ? null : intval($doc['id_motivo']),
                    'AJUSTE_NEGATIVO',
                    $cantidad,
                    $anterior,
                    $nueva,
                    $docId,
                    strval($doc['fecha_movimiento']),
                    $userId,
                    'Anulación de ' . strval($doc['correlativo'])
                );
            }
        }

        self::executeNonQuery(
            "UPDATE documentos_entrada SET estado_doc = 'ANULADO'"
            . ' WHERE id_doc_entrada = :id',
            ['id' => $docId]
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

    private static function totalesPorArticulo(array $detalles): array
    {
        // Cantidad acumulada por articulo para aplicar el movimiento al saldo.
        $totales = [];

        foreach ($detalles as $detalle) {
            $articuloId = intval($detalle['id_articulo']);
            $cantidad = intval($detalle['cantidad']);
            if ($articuloId <= 0 || $cantidad <= 0) {
                continue;
            }
            $totales[$articuloId] = ($totales[$articuloId] ?? 0) + $cantidad;
        }

        return $totales;
    }

    private static function registrarMovimiento(
        int $articuloId,
        ?int $motivoId,
        string $tipo,
        int $cantidad,
        int $anterior,
        int $nueva,
        int $docId,
        string $fecha,
        int $userId,
        string $observacion
    ): void {
        self::executeNonQuery(
            'INSERT INTO movimientos_inventario ('
            . 'id_articulo, id_motivo, tipo_movimiento, cantidad,'
            . ' existencia_anterior, existencia_nueva, id_doc_referencia,'
            . ' origen_referencia, fecha_movimiento, id_usuario, observacion'
            . ') VALUES ('
            . ':articulo, :motivo, :tipo, :cantidad,'
            . ' :anterior, :nueva, :documento,'
            . ' :origen, :fecha, :usuario, :observacion'
            . ')',
            [
                'articulo' => $articuloId,
                'motivo' => $motivoId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'anterior' => $anterior,
                'nueva' => $nueva,
                'documento' => $docId,
                'origen' => 'documentos_entrada',
                'fecha' => $fecha,
                'usuario' => $userId,
                'observacion' => $observacion,
            ]
        );
    }
}
