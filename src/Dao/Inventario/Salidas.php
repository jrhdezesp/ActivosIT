<?php

namespace Dao\Inventario;

use Dao\Table;

/**
 * Acceso a documentos de salida y sus movimientos de Kardex.
 *
 * Variables de filtro:
 * - $search: texto para correlativo, destino, autorizacion, observaciones o tipo.
 * - $estado: BORRADOR, CONFIRMADO o ANULADO.
 * - $tipoDocId: filtro por tipo de documento.
 * - $orderBy: campo permitido para ordenar.
 * - $orderDescending: true para orden descendente.
 * - $page: pagina basada en cero.
 * - $itemsPerPage: cantidad de registros por pagina.
 *
 * Variables de documento:
 * - $docId: clave del documento de salida.
 * - $userId: usuario que confirma o anula.
 *
 * Claves de $data:
 * - id_tipo_doc, fecha_movimiento, id_usuario, id_motivo,
 *   destino_salida, autorizacion, estado_doc, observaciones.
 *
 * Claves de cada detalle:
 * - id_articulo, cantidad, id_equipo.
 */
class Salidas extends Table
{
    // Estados posibles de un documento de salida.
    public const ESTADOS = ['BORRADOR', 'CONFIRMADO', 'ANULADO'];

    // Columnas permitidas para ordenar las salidas.
    private const ORDER_FIELDS = [
        'fecha_movimiento' => 's.fecha_movimiento',
        'correlativo' => 's.correlativo',
        'estado_doc' => 's.estado_doc',
        'fecha_creacion' => 's.fecha_creacion',
        'id_doc_salida' => 's.id_doc_salida',
    ];

    // Datos del encabezado y nombres relacionados que usa el listado.
    private const SELECT_FIELDS = 'SELECT s.*,'
        . ' td.nombre_tipo, td.naturaleza_doc,'
        . ' m.nombre_motivo, m.motivo_aplica_a,'
        . ' u.nombre_usuario AS usuario_nombre, u.user_usuario,';

    // Datos de cada linea y referencias asociadas al documento.
    private const SELECT_DETAILS = 'SELECT d.*,'
        . ' a.nombre_articulo, a.codigo_interno_articulo, a.tipo_control,'
        . ' a.existencia_actual,'
        . ' um.nombre_unidad, um.simbolo_medida,'
        . ' eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,'
        . ' eq.estado_operativo'
        . ' FROM detalles_salida d'
        . ' JOIN articulos a ON a.id_articulo = d.id_articulo'
        . ' LEFT JOIN unidades_medida um ON um.id_unidad_medida = a.id_unidad_medida'
        . ' LEFT JOIN equipos_individuales eq ON eq.id_equipo = d.id_equipo';

    public static function getSalidas(
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
            $conditions[] = '(s.correlativo LIKE :searchCorrelativo'
                . ' OR s.destino_salida LIKE :searchDestino'
                . ' OR s.autorizacion LIKE :searchAutorizacion'
                . ' OR s.observaciones LIKE :searchObservaciones'
                . ' OR td.nombre_tipo LIKE :searchTipo)';
            $searchValue = '%' . $search . '%';
            $params['searchCorrelativo'] = $searchValue;
            $params['searchDestino'] = $searchValue;
            $params['searchAutorizacion'] = $searchValue;
            $params['searchObservaciones'] = $searchValue;
            $params['searchTipo'] = $searchValue;
        }

        if ($estado !== '') {
            $conditions[] = 's.estado_doc = :estado';
            $params['estado'] = $estado;
        }

        if ($tipoDocId > 0) {
            $conditions[] = 's.id_tipo_doc = :tipoDoc';
            $params['tipoDoc'] = $tipoDocId;
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $countRow = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM documentos_salida s'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = s.id_tipo_doc'
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
            . ' (SELECT COUNT(*) FROM detalles_salida d'
            . ' WHERE d.id_doc_salida = s.id_doc_salida) AS lineas,'
            . ' (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalles_salida d'
            . ' WHERE d.id_doc_salida = s.id_doc_salida) AS unidades'
            . ' FROM documentos_salida s'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = s.id_tipo_doc'
            . ' LEFT JOIN motivos_ajustes_anulaciones m ON m.id_motivo = s.id_motivo'
            . ' JOIN usuarios u ON u.id_usuario = s.id_usuario'
            . $where
            . ' ORDER BY ' . self::ORDER_FIELDS[$orderBy]
            . ($orderDescending ? ' DESC' : ' ASC')
            . ', s.id_doc_salida DESC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'salidas' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getSalidaById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS
            . ' (SELECT COUNT(*) FROM detalles_salida d'
            . ' WHERE d.id_doc_salida = s.id_doc_salida) AS lineas,'
            . ' (SELECT COALESCE(SUM(d.cantidad), 0) FROM detalles_salida d'
            . ' WHERE d.id_doc_salida = s.id_doc_salida) AS unidades'
            . ' FROM documentos_salida s'
            . ' JOIN tipos_documentos td ON td.id_tipo_doc = s.id_tipo_doc'
            . ' LEFT JOIN motivos_ajustes_anulaciones m ON m.id_motivo = s.id_motivo'
            . ' JOIN usuarios u ON u.id_usuario = s.id_usuario'
            . ' WHERE s.id_doc_salida = :id LIMIT 1',
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
            . ' WHERE d.id_doc_salida = :id ORDER BY d.id_detalle_salida ASC',
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
            . " COALESCE((SELECT SUM(d.cantidad) FROM detalles_salida d"
            . " JOIN documentos_salida x ON x.id_doc_salida = d.id_doc_salida"
            . " WHERE x.estado_doc = 'CONFIRMADO'), 0) AS unidades"
            . " FROM documentos_salida",
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
            . " FROM tipos_documentos WHERE naturaleza_doc = 'SALIDA'";
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

    public static function getMotivosSalida(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = "SELECT id_motivo AS value, nombre_motivo AS label,"
            . " motivo_aplica_a, estado_motivo AS status"
            . " FROM motivos_ajustes_anulaciones"
            . " WHERE motivo_aplica_a IN ('AJUSTE_SALIDA', 'BAJA')";
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
            . ' existencia_actual AS stock, estado_registro AS status'
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
     * Equipos que pueden salir del inventario: estan activos, disponibles y no
     * forman parte de otra salida confirmada. Al editar un borrador se incluye
     * el equipo que ese mismo documento tiene reservado.
     *
     * @param int $includeDocId Documento en edicion; 0 para una salida nueva.
     * @return array
     */
    public static function getEquiposDisponibles(int $includeDocId = 0): array
    {
        $sql = "SELECT eq.id_equipo AS value, eq.id_articulo,"
            . " eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,"
            . " eq.estado_operativo"
            . " FROM equipos_individuales eq"
            . " WHERE eq.estado_registro = 1"
            . " AND NOT EXISTS ("
            . "   SELECT 1 FROM detalles_salida d"
            . "   JOIN documentos_salida doc ON doc.id_doc_salida = d.id_doc_salida"
            . "   WHERE d.id_equipo = eq.id_equipo AND doc.estado_doc = 'CONFIRMADO'"
            . " )";
        $params = [];

        if ($includeDocId > 0) {
            $sql .= " AND (eq.estado_operativo = 'DISPONIBLE'"
                . " OR (eq.estado_operativo = 'RESERVADO' AND EXISTS ("
                . "   SELECT 1 FROM detalles_salida rd"
                . "   JOIN documentos_salida rdoc ON rdoc.id_doc_salida = rd.id_doc_salida"
                . "   WHERE rd.id_equipo = eq.id_equipo"
                . "   AND rdoc.estado_doc = 'BORRADOR'"
                . "   AND rdoc.id_doc_salida = :includeDocId"
                . " )))";
            $params['includeDocId'] = $includeDocId;
        } else {
            $sql .= " AND eq.estado_operativo = 'DISPONIBLE'";
        }

        $sql .= " ORDER BY eq.numero_serie_equipo ASC";

        return self::obtenerRegistros($sql, $params);
    }

    /**
     * Indica si un equipo pertenece a un borrador de salida concreto.
     *
     * @param int $equipoId Equipo consultado.
     * @param int $docId Documento de salida.
     * @return bool
     */
    public static function equipoReservadoEnDocumento(int $equipoId, int $docId): bool
    {
        if ($equipoId <= 0 || $docId <= 0) {
            return false;
        }

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total FROM detalles_salida d"
            . " JOIN documentos_salida doc ON doc.id_doc_salida = d.id_doc_salida"
            . " WHERE d.id_equipo = :equipo"
            . " AND doc.id_doc_salida = :documento"
            . " AND doc.estado_doc = 'BORRADOR'",
            ['equipo' => $equipoId, 'documento' => $docId]
        );

        return intval($row['total'] ?? 0) > 0;
    }

    /**
     * Libera los equipos reservados por un documento en borrador.
     *
     * @param int $docId Documento de salida.
     * @return void
     */
    public static function liberarReservasDocumento(int $docId): void
    {
        if ($docId <= 0) {
            return;
        }

        self::executeNonQuery(
            "UPDATE equipos_individuales eq"
            . " SET eq.estado_operativo = 'DISPONIBLE'"
            . " WHERE eq.estado_operativo = 'RESERVADO'"
            . " AND EXISTS ("
            . "   SELECT 1 FROM detalles_salida d"
            . "   WHERE d.id_doc_salida = :documento AND d.id_equipo = eq.id_equipo"
            . " )",
            ['documento' => $docId]
        );
    }

    /**
     * Marca como reservados los equipos serializados de un borrador.
     *
     * @param array $detalles Lineas con id_equipo.
     * @return void
     */
    public static function reservarEquipos(array $detalles): void
    {
        $equipos = [];
        foreach ($detalles as $detalle) {
            $equipoId = intval($detalle['id_equipo'] ?? 0);
            if ($equipoId > 0) {
                $equipos[$equipoId] = true;
            }
        }

        foreach (array_keys($equipos) as $equipoId) {
            self::executeNonQuery(
                "UPDATE equipos_individuales SET estado_operativo = 'RESERVADO'"
                . " WHERE id_equipo = :id"
                . " AND estado_operativo IN ('DISPONIBLE', 'RESERVADO')",
                ['id' => intval($equipoId)]
            );
        }
    }

    public static function getArticuloById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_articulo, nombre_articulo, codigo_interno_articulo,'
            . ' tipo_control, existencia_actual, estado_registro'
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
            . ' codigo_patrimonial_equipo, estado_operativo, estado_registro'
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

    public static function equipoUsadoEnSalidaConfirmada(int $equipoId, int $excludeDocId = 0): bool
    {
        if ($equipoId <= 0) {
            return false;
        }

        $sql = "SELECT COUNT(*) AS total FROM detalles_salida d"
            . " JOIN documentos_salida doc ON doc.id_doc_salida = d.id_doc_salida"
            . " WHERE d.id_equipo = :id AND doc.estado_doc = 'CONFIRMADO'";
        $params = ['id' => $equipoId];

        if ($excludeDocId > 0) {
            $sql .= ' AND d.id_doc_salida <> :excludeDoc';
            $params['excludeDoc'] = $excludeDocId;
        }

        $row = self::obtenerUnRegistro($sql, $params);

        return intval($row['total'] ?? 0) > 0;
    }

    /**
     * Inserta el encabezado de una salida y genera su correlativo.
     *
     * @param array $data Claves documentadas en la clase.
     * @return int Identificador del documento creado.
     */
    public static function insertDocumento(array $data): int
    {
        $tempCorrelativo = 'TMP-' . bin2hex(random_bytes(8));

        self::executeNonQuery(
            'INSERT INTO documentos_salida ('
            . 'correlativo, id_tipo_doc, fecha_movimiento, id_usuario, id_motivo,'
            . ' destino_salida, autorizacion, estado_doc, observaciones'
            . ') VALUES ('
            . ':correlativo, :tipo, :fecha, :usuario, :motivo,'
            . ' :destino, :autorizacion, :estado, :observaciones'
            . ')',
            [
                'correlativo' => $tempCorrelativo,
                'tipo' => intval($data['id_tipo_doc']),
                'fecha' => $data['fecha_movimiento'],
                'usuario' => intval($data['id_usuario']),
                'motivo' => $data['id_motivo'],
                'destino' => $data['destino_salida'],
                'autorizacion' => $data['autorizacion'],
                'estado' => $data['estado_doc'],
                'observaciones' => $data['observaciones'],
            ]
        );

        $id = intval(self::getConn()->lastInsertId());
        if ($id <= 0) {
            throw new \RuntimeException('No se pudo registrar el documento de salida.');
        }

        $year = date('Y', strtotime($data['fecha_movimiento']));
        $correlativo = 'SAL-' . $year . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);

        self::executeNonQuery(
            'UPDATE documentos_salida SET correlativo = :correlativo'
            . ' WHERE id_doc_salida = :id',
            ['correlativo' => $correlativo, 'id' => $id]
        );

        return $id;
    }

    public static function updateDocumento(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE documentos_salida SET'
            . ' id_tipo_doc = :tipo, fecha_movimiento = :fecha, id_motivo = :motivo,'
            . ' destino_salida = :destino, autorizacion = :autorizacion,'
            . ' observaciones = :observaciones'
            . ' WHERE id_doc_salida = :id',
            [
                'tipo' => intval($data['id_tipo_doc']),
                'fecha' => $data['fecha_movimiento'],
                'motivo' => $data['id_motivo'],
                'destino' => $data['destino_salida'],
                'autorizacion' => $data['autorizacion'],
                'observaciones' => $data['observaciones'],
                'id' => $id,
            ]
        );
    }

    /**
     * Reemplaza las lineas de detalle de una salida.
     *
     * @param int $docId Documento de salida.
     * @param array $detalles Lineas con las claves documentadas en la clase.
     * @return void
     */
    public static function replaceDetalles(int $docId, array $detalles): void
    {
        self::executeNonQuery(
            'DELETE FROM detalles_salida WHERE id_doc_salida = :id',
            ['id' => $docId]
        );

        self::insertDetalles($docId, $detalles);
    }

    public static function insertDetalles(int $docId, array $detalles): void
    {
        foreach ($detalles as $detalle) {
            self::executeNonQuery(
                'INSERT INTO detalles_salida ('
                . 'id_doc_salida, id_articulo, cantidad, id_equipo'
                . ') VALUES ('
                . ':documento, :articulo, :cantidad, :equipo'
                . ')',
                [
                    'documento' => $docId,
                    'articulo' => intval($detalle['id_articulo']),
                    'cantidad' => intval($detalle['cantidad']),
                    'equipo' => $detalle['id_equipo'],
                ]
            );
        }
    }

    /**
     * Confirma el documento: descuenta existencias por articulo, registra el
     * movimiento de salida en el Kardex y actualiza el estado de los equipos
     * serializados. Debe ejecutarse dentro de una transaccion iniciada por el
     * controlador.
     */
    /**
     * Confirma una salida, descuenta existencias y registra el Kardex.
     *
     * @param int $docId Documento de salida.
     * @param int $userId Usuario que confirma.
     * @return void
     */
    public static function confirmar(int $docId, int $userId): void
    {
        $doc = self::obtenerUnRegistro(
            'SELECT * FROM documentos_salida WHERE id_doc_salida = :id FOR UPDATE',
            ['id' => $docId]
        );

        if (!$doc) {
            throw new \RuntimeException('No se encontró el documento de salida.');
        }

        if (strval($doc['estado_doc']) !== 'BORRADOR') {
            throw new \RuntimeException('Solo se pueden confirmar documentos en borrador.');
        }

        $detalles = self::getDetalles($docId);
        if (count($detalles) === 0) {
            throw new \RuntimeException('El documento no tiene líneas de detalle.');
        }

        $tipo = self::getTipoDocumentoById(intval($doc['id_tipo_doc']));
        $esAsignacion = $tipo !== null
            && stripos(strval($tipo['nombre_tipo'] ?? ''), 'asignaci') !== false;
        $estadoEquipo = $esAsignacion ? 'ASIGNADO' : 'DE_BAJA';

        $motivoEquipo = null;
        if ($estadoEquipo === 'DE_BAJA' && $doc['id_motivo'] !== null) {
            $motivo = self::getMotivoById(intval($doc['id_motivo']));
            if ($motivo !== null && strval($motivo['motivo_aplica_a'] ?? '') === 'BAJA') {
                $motivoEquipo = intval($doc['id_motivo']);
            }
        }

        $totales = self::totalesPorArticulo($detalles);
        // Registros que no pueden procesarse por estado o existencia insuficiente.
        $articulosBloqueados = [];
        $equiposBloqueados = [];

        foreach ($detalles as $detalle) {
            $articuloId = intval($detalle['id_articulo']);
            $cantidad = intval($detalle['cantidad']);
            $equipoId = intval($detalle['id_equipo'] ?? 0);
            $esSerie = strval($detalle['tipo_control'] ?? '') === 'SERIE';

            $articulo = self::obtenerUnRegistro(
                'SELECT id_articulo, nombre_articulo, tipo_control, existencia_actual'
                . ' FROM articulos WHERE id_articulo = :id FOR UPDATE',
                ['id' => $articuloId]
            );

            if (!$articulo) {
                throw new \RuntimeException('No se encontró uno de los artículos del documento.');
            }
            $articulosBloqueados[$articuloId] = $articulo;

            if ($cantidad <= 0) {
                throw new \RuntimeException('Una de las líneas del documento tiene una cantidad inválida.');
            }

            $esSerie = strval($articulo['tipo_control'] ?? '') === 'SERIE';

            if ($esSerie) {
                if ($equipoId <= 0) {
                    throw new \RuntimeException(
                        'El artículo "' . strval($articulo['nombre_articulo'])
                        . '" requiere un equipo serializado.'
                    );
                }
                if ($cantidad !== 1) {
                    throw new \RuntimeException(
                        'Los artículos por serie se registran de uno en uno.'
                    );
                }

                if (isset($equiposBloqueados[$equipoId])) {
                    throw new \RuntimeException('Un equipo está repetido en el documento.');
                }

                $equipo = self::obtenerUnRegistro(
                    'SELECT id_equipo, id_articulo, numero_serie_equipo,'
                    . ' estado_operativo, estado_registro'
                    . ' FROM equipos_individuales WHERE id_equipo = :id FOR UPDATE',
                    ['id' => $equipoId]
                );

                if (!$equipo) {
                    throw new \RuntimeException('No se encontró uno de los equipos del documento.');
                }

                $equipoLabel = trim(strval($equipo['numero_serie_equipo'] ?? ''));
                if ($equipoLabel === '') {
                    $equipoLabel = 'Equipo #' . $equipoId;
                }

                if (intval($equipo['estado_registro'] ?? 0) !== 1) {
                    throw new \RuntimeException('El equipo "' . $equipoLabel . '" está inactivo.');
                }
                if (intval($equipo['id_articulo'] ?? 0) !== $articuloId) {
                    throw new \RuntimeException(
                        'El equipo "' . $equipoLabel . '" no corresponde al artículo de la línea.'
                    );
                }
                $estadoActual = strval($equipo['estado_operativo'] ?? '');
                if ($estadoActual !== 'DISPONIBLE'
                    && !($estadoActual === 'RESERVADO'
                        && self::equipoReservadoEnDocumento($equipoId, $docId))) {
                    throw new \RuntimeException(
                        'El equipo "' . $equipoLabel . '" ya no está disponible para salir.'
                    );
                }
                if (self::equipoUsadoEnSalidaConfirmada($equipoId, $docId)) {
                    throw new \RuntimeException(
                        'El equipo "' . $equipoLabel . '" ya fue registrado en otra salida confirmada.'
                    );
                }

                $equiposBloqueados[$equipoId] = $equipo;
            } elseif ($equipoId > 0) {
                throw new \RuntimeException(
                    'Solo los artículos controlados por serie llevan equipo.'
                );
            }
        }

        foreach ($totales as $articuloId => $cantidad) {
            $articulo = $articulosBloqueados[$articuloId] ?? null;
            if ($articulo === null) {
                throw new \RuntimeException('No se encontró uno de los artículos del documento.');
            }

            $anterior = intval($articulo['existencia_actual']);
            $cantidad = intval($cantidad);

            if ($anterior < $cantidad) {
                throw new \RuntimeException(
                    'No hay existencias suficientes de "' . strval($articulo['nombre_articulo'])
                    . '" para confirmar la salida (disponible: ' . $anterior
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
                'SALIDA',
                $cantidad,
                $anterior,
                $nueva,
                $docId,
                strval($doc['fecha_movimiento']),
                $userId,
                'Salida según ' . strval($doc['correlativo'])
            );
        }

        foreach ($equiposBloqueados as $equipoId => $equipo) {
            self::executeNonQuery(
                'UPDATE equipos_individuales SET estado_operativo = :estado,'
                . ' id_motivo = :motivo WHERE id_equipo = :id',
                [
                    'estado' => $estadoEquipo,
                    'motivo' => $motivoEquipo,
                    'id' => $equipoId,
                ]
            );
        }

        self::executeNonQuery(
            "UPDATE documentos_salida SET estado_doc = 'CONFIRMADO'"
            . ' WHERE id_doc_salida = :id',
            ['id' => $docId]
        );
    }

    /**
     * Anula el documento. Si estaba confirmado devuelve las existencias, deja
     * el ajuste positivo en el Kardex y libera los equipos serializados. Debe
     * ejecutarse dentro de una transaccion iniciada por el controlador.
     */
    /**
     * Anula una salida y revierte sus efectos si estaba confirmada.
     *
     * @param int $docId Documento de salida.
     * @param int $userId Usuario que anula.
     * @return void
     */
    public static function anular(int $docId, int $userId): void
    {
        $doc = self::obtenerUnRegistro(
            'SELECT * FROM documentos_salida WHERE id_doc_salida = :id FOR UPDATE',
            ['id' => $docId]
        );

        if (!$doc) {
            throw new \RuntimeException('No se encontró el documento de salida.');
        }

        if (strval($doc['estado_doc']) === 'ANULADO') {
            throw new \RuntimeException('El documento ya está anulado.');
        }

        if (strval($doc['estado_doc']) === 'CONFIRMADO') {
            $detalles = self::getDetalles($docId);
            $totales = self::totalesPorArticulo($detalles);

            foreach ($totales as $articuloId => $cantidad) {
                $articulo = self::obtenerUnRegistro(
                    'SELECT id_articulo, nombre_articulo, existencia_actual'
                    . ' FROM articulos WHERE id_articulo = :id FOR UPDATE',
                    ['id' => $articuloId]
                );

                if (!$articulo) {
                    throw new \RuntimeException('No se encontró uno de los artículos del documento.');
                }

                $anterior = intval($articulo['existencia_actual']);
                $cantidad = intval($cantidad);
                $nueva = $anterior + $cantidad;

                self::executeNonQuery(
                    'UPDATE articulos SET existencia_actual = :nueva WHERE id_articulo = :id',
                    ['nueva' => $nueva, 'id' => $articuloId]
                );

                self::registrarMovimiento(
                    $articuloId,
                    $doc['id_motivo'] === null ? null : intval($doc['id_motivo']),
                    'AJUSTE_POSITIVO',
                    $cantidad,
                    $anterior,
                    $nueva,
                    $docId,
                    strval($doc['fecha_movimiento']),
                    $userId,
                    'Anulación de ' . strval($doc['correlativo'])
                );
            }

            $equipos = [];
            foreach ($detalles as $detalle) {
                $equipoId = intval($detalle['id_equipo'] ?? 0);
                if ($equipoId <= 0 || isset($equipos[$equipoId])) {
                    continue;
                }
                $equipos[$equipoId] = true;

                self::executeNonQuery(
                    "UPDATE equipos_individuales SET estado_operativo = 'DISPONIBLE',"
                    . ' id_motivo = NULL WHERE id_equipo = :id',
                    ['id' => $equipoId]
                );
            }
        }

        if (strval($doc['estado_doc']) === 'BORRADOR') {
            self::liberarReservasDocumento($docId);
        }

        self::executeNonQuery(
            "UPDATE documentos_salida SET estado_doc = 'ANULADO',"
            . ' id_usuario_anulacion = :usuario, fecha_anulacion = NOW()'
            . ' WHERE id_doc_salida = :id',
            ['usuario' => $userId, 'id' => $docId]
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
                'origen' => 'documentos_salida',
                'fecha' => $fecha,
                'usuario' => $userId,
                'observacion' => $observacion,
            ]
        );
    }
}
