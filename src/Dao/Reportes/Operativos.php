<?php

namespace Dao\Reportes;

use Dao\Table;

/**
 * Acceso a los reportes operativos configurables.
 *
 * Variables:
 * - $type: clave del informe dentro de REPORTS.
 * - $filters: filtros comunes; q, desde, hasta, articulo, usuario,
 *   categoria, ubicacion, departamento, estado, page, itemsPerPage y paginate.
 * - $report: definicion del informe seleccionado.
 * - $where: condiciones SQL construidas con filtros.
 * - $params: valores asociados a las condiciones.
 *
 * Claves de una definicion de REPORTS:
 * - title, description, columns, select, from, where, search,
 *   group, union, date, article, user, category, location, department y status.
 */
class Operativos extends Table
{
    // Definicion de cada informe: campos, origen, filtros y formato visible.
    private const REPORTS = [
        'inventario' => [
            'title' => 'Inventario general de artículos',
            'description' => 'Existencias y datos principales de los artículos activos.',
            'columns' => ['Código', 'Artículo', 'Clasificación', 'Unidad', 'Control', 'Existencia', 'Mínimo'],
            'select' => 'a.codigo_interno_articulo, a.nombre_articulo, c.nombre_clasificacion, u.nombre_unidad, a.tipo_control, a.existencia_actual, a.existencia_minima',
            'from' => 'articulos a LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion LEFT JOIN unidades_medida u ON u.id_unidad_medida = a.id_unidad_medida',
            'where' => 'a.estado_registro = 1',
            'search' => ['a.codigo_interno_articulo', 'a.nombre_articulo', 'c.nombre_clasificacion'],
            'article' => 'a.id_articulo',
            'category' => 'a.id_clasificacion',
        ],
        'existencias' => [
            'title' => 'Existencias por artículo y clasificación',
            'description' => 'Saldo actual agrupable por artículo y clasificación.',
            'columns' => ['Código', 'Artículo', 'Clasificación', 'Existencia', 'Mínimo', 'Unidad'],
            'select' => 'a.codigo_interno_articulo, a.nombre_articulo, c.nombre_clasificacion, a.existencia_actual, a.existencia_minima, u.nombre_unidad',
            'from' => 'articulos a LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion LEFT JOIN unidades_medida u ON u.id_unidad_medida = a.id_unidad_medida',
            'where' => 'a.estado_registro = 1',
            'search' => ['a.codigo_interno_articulo', 'a.nombre_articulo', 'c.nombre_clasificacion'],
            'article' => 'a.id_articulo',
            'category' => 'a.id_clasificacion',
        ],
        'stock_bajo' => [
            'title' => 'Artículos bajo el mínimo',
            'description' => 'Artículos controlados por cantidad que requieren reposición.',
            'columns' => ['Código', 'Artículo', 'Clasificación', 'Existencia', 'Mínimo', 'Faltante'],
            'select' => 'a.codigo_interno_articulo, a.nombre_articulo, c.nombre_clasificacion, a.existencia_actual, a.existencia_minima, (a.existencia_minima - a.existencia_actual) AS faltante',
            'from' => 'articulos a LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion',
            'where' => "a.estado_registro = 1 AND a.tipo_control = 'CANTIDAD' AND a.existencia_actual <= COALESCE(a.existencia_minima, 0)",
            'search' => ['a.codigo_interno_articulo', 'a.nombre_articulo', 'c.nombre_clasificacion'],
            'article' => 'a.id_articulo',
            'category' => 'a.id_clasificacion',
        ],
        'entradas' => [
            'title' => 'Entradas de inventario por período',
            'description' => 'Documentos y unidades recibidas dentro del período seleccionado.',
            'columns' => ['Documento', 'Fecha', 'Tipo', 'Proveedor', 'Estado', 'Unidades', 'Usuario'],
            'select' => 'de.correlativo, de.fecha_movimiento, td.nombre_tipo, de.proveedor_origen, de.estado_doc, COALESCE(SUM(det.cantidad), 0) AS unidades, us.nombre_usuario',
            'from' => 'documentos_entrada de JOIN tipos_documentos td ON td.id_tipo_doc = de.id_tipo_doc JOIN usuarios us ON us.id_usuario = de.id_usuario LEFT JOIN detalles_entrada det ON det.id_doc_entrada = de.id_doc_entrada',
            'where' => '1 = 1',
            'group' => 'de.id_doc_entrada, de.correlativo, de.fecha_movimiento, td.nombre_tipo, de.proveedor_origen, de.estado_doc, us.nombre_usuario',
            'search' => ['de.correlativo', 'de.proveedor_origen', 'td.nombre_tipo', 'us.nombre_usuario'],
            'date' => 'de.fecha_movimiento',
            'user' => 'de.id_usuario',
            'status' => 'de.estado_doc',
        ],
        'salidas' => [
            'title' => 'Salidas de inventario por período',
            'description' => 'Documentos y unidades entregadas dentro del período seleccionado.',
            'columns' => ['Documento', 'Fecha', 'Tipo', 'Destino', 'Estado', 'Unidades', 'Usuario'],
            'select' => 'ds.correlativo, ds.fecha_movimiento, td.nombre_tipo, ds.destino_salida, ds.estado_doc, COALESCE(SUM(det.cantidad), 0) AS unidades, us.nombre_usuario',
            'from' => 'documentos_salida ds JOIN tipos_documentos td ON td.id_tipo_doc = ds.id_tipo_doc JOIN usuarios us ON us.id_usuario = ds.id_usuario LEFT JOIN detalles_salida det ON det.id_doc_salida = ds.id_doc_salida',
            'where' => '1 = 1',
            'group' => 'ds.id_doc_salida, ds.correlativo, ds.fecha_movimiento, td.nombre_tipo, ds.destino_salida, ds.estado_doc, us.nombre_usuario',
            'search' => ['ds.correlativo', 'ds.destino_salida', 'ds.autorizacion', 'td.nombre_tipo', 'us.nombre_usuario'],
            'date' => 'ds.fecha_movimiento',
            'user' => 'us.id_usuario',
            'status' => 'ds.estado_doc',
        ],
        'movimientos_articulo' => [
            'title' => 'Movimientos históricos de un artículo',
            'description' => 'Historial del Kardex filtrable por artículo y período.',
            'columns' => ['Fecha', 'Artículo', 'Tipo', 'Cantidad', 'Anterior', 'Nueva', 'Documento', 'Usuario'],
            'select' => 'm.fecha_movimiento, a.nombre_articulo, m.tipo_movimiento, m.cantidad, m.existencia_anterior, m.existencia_nueva, COALESCE(de.correlativo, ds.correlativo), us.nombre_usuario',
            'from' => "movimientos_inventario m JOIN articulos a ON a.id_articulo = m.id_articulo JOIN usuarios us ON us.id_usuario = m.id_usuario LEFT JOIN documentos_entrada de ON m.origen_referencia = 'documentos_entrada' AND de.id_doc_entrada = m.id_doc_referencia LEFT JOIN documentos_salida ds ON m.origen_referencia = 'documentos_salida' AND ds.id_doc_salida = m.id_doc_referencia",
            'where' => '1 = 1',
            'search' => ['a.codigo_interno_articulo', 'a.nombre_articulo', 'm.observacion', 'us.nombre_usuario'],
            'date' => 'm.fecha_movimiento',
            'article' => 'm.id_articulo',
            'category' => 'a.id_clasificacion',
            'user' => 'm.id_usuario',
        ],
        'equipos_asignados' => [
            'title' => 'Equipos asignados por empleado o departamento',
            'description' => 'Asignaciones activas con responsable y departamento.',
            'columns' => ['Serie', 'Código patrimonial', 'Artículo', 'Responsable', 'Departamento', 'Fecha', 'Condición'],
            'select' => 'eq.numero_serie_equipo, eq.codigo_patrimonial_equipo, ar.nombre_articulo, ad.persona_responsable, ad.departamento_destino, ad.fecha_asignacion, ad.condicion_entrega',
            'from' => "asignaciones_devoluciones ad JOIN equipos_individuales eq ON eq.id_equipo = ad.id_equipo JOIN articulos ar ON ar.id_articulo = eq.id_articulo",
            'where' => "ad.estado_asignacion = 'ACTIVA'",
            'search' => ['eq.numero_serie_equipo', 'eq.codigo_patrimonial_equipo', 'ar.nombre_articulo', 'ad.persona_responsable', 'ad.departamento_destino'],
            'article' => 'eq.id_articulo',
            'category' => 'ar.id_clasificacion',
            'location' => 'eq.id_ubicacion',
            'department' => 'ad.departamento_destino',
            'status' => 'eq.estado_operativo',
        ],
        'equipos_estado' => [
            'title' => 'Equipos por estado',
            'description' => 'Equipos disponibles, asignados, en mantenimiento o fuera de servicio.',
            'columns' => ['Serie', 'Código patrimonial', 'Artículo', 'Estado', 'Estado fisico', 'Ubicación', 'Observaciones'],
            'select' => 'eq.numero_serie_equipo, eq.codigo_patrimonial_equipo, ar.nombre_articulo, eq.estado_operativo, eq.estado_fisico, ub.nombre_ubicacion, eq.observaciones_equipo',
            'from' => 'equipos_individuales eq JOIN articulos ar ON ar.id_articulo = eq.id_articulo LEFT JOIN ubicaciones ub ON ub.id_ubicacion = eq.id_ubicacion',
            'where' => 'eq.estado_registro = 1',
            'search' => ['eq.numero_serie_equipo', 'eq.codigo_patrimonial_equipo', 'ar.nombre_articulo', 'ub.nombre_ubicacion'],
            'article' => 'eq.id_articulo',
            'category' => 'ar.id_clasificacion',
            'location' => 'eq.id_ubicacion',
            'status' => 'eq.estado_operativo',
        ],
        'asignaciones' => [
            'title' => 'Historial de asignaciones y devoluciones',
            'description' => 'Trazabilidad completa de entregas y devoluciones de equipos.',
            'columns' => ['Serie', 'Artículo', 'Responsable', 'Departamento', 'Asignación', 'Devolución', 'Estado'],
            'select' => 'eq.numero_serie_equipo, ar.nombre_articulo, ad.persona_responsable, ad.departamento_destino, ad.fecha_asignacion, ad.fecha_devolucion, ad.estado_asignacion',
            'from' => 'asignaciones_devoluciones ad JOIN equipos_individuales eq ON eq.id_equipo = ad.id_equipo JOIN articulos ar ON ar.id_articulo = eq.id_articulo',
            'where' => '1 = 1',
            'search' => ['eq.numero_serie_equipo', 'ar.nombre_articulo', 'ad.persona_responsable', 'ad.departamento_destino'],
            'date' => 'ad.fecha_asignacion',
            'article' => 'eq.id_articulo',
            'category' => 'ar.id_clasificacion',
            'location' => 'eq.id_ubicacion',
            'department' => 'ad.departamento_destino',
            'status' => 'ad.estado_asignacion',
        ],
        'mantenimientos' => [
            'title' => 'Historial de mantenimientos por equipo',
            'description' => 'Servicios, diagnósticos, resultados, costos y estados por equipo.',
            'columns' => ['Serie', 'Artículo', 'Tipo', 'Recepción', 'Finalización', 'Técnico', 'Estado', 'Costo'],
            'select' => 'eq.numero_serie_equipo, ar.nombre_articulo, tm.nombre_tipo, ma.fecha_recepcion, ma.fecha_finalizacion, ma.tecnico_responsable, ma.estado_mantenimiento, ma.costo_mantenimiento',
            'from' => 'mantenimientos ma JOIN equipos_individuales eq ON eq.id_equipo = ma.id_equipo JOIN articulos ar ON ar.id_articulo = eq.id_articulo JOIN tipos_mantenimientos tm ON tm.id_tipo_mantenimiento = ma.id_tipo_mantenimiento',
            'where' => '1 = 1',
            'search' => ['eq.numero_serie_equipo', 'ar.nombre_articulo', 'ma.tecnico_responsable', 'ma.diagnostico_inicial', 'ma.resultado_mantenimiento'],
            'date' => 'ma.fecha_recepcion',
            'article' => 'eq.id_articulo',
            'category' => 'ar.id_clasificacion',
            'location' => 'eq.id_ubicacion',
            'status' => 'ma.estado_mantenimiento',
        ],
        'articulos_responsable' => [
            'title' => 'Artículos recibidos o entregados por responsable',
            'description' => 'Artículos asociados a asignaciones activas o históricas por responsable.',
            'columns' => ['Artículo', 'Serie', 'Responsable', 'Departamento', 'Fecha', 'Estado'],
            'select' => 'ar.nombre_articulo, eq.numero_serie_equipo, ad.persona_responsable, ad.departamento_destino, ad.fecha_asignacion, ad.estado_asignacion',
            'from' => 'asignaciones_devoluciones ad JOIN equipos_individuales eq ON eq.id_equipo = ad.id_equipo JOIN articulos ar ON ar.id_articulo = eq.id_articulo',
            'where' => '1 = 1',
            'search' => ['ar.codigo_interno_articulo', 'ar.nombre_articulo', 'ad.persona_responsable', 'ad.departamento_destino'],
            'date' => 'ad.fecha_asignacion',
            'article' => 'eq.id_articulo',
            'category' => 'ar.id_clasificacion',
            'location' => 'eq.id_ubicacion',
            'department' => 'ad.departamento_destino',
            'status' => 'ad.estado_asignacion',
        ],
        'ajustes_bajas' => [
            'title' => 'Ajustes de inventario y bajas autorizadas',
            'description' => 'Movimientos de ajuste y salidas con motivo registrado.',
            'columns' => ['Fecha', 'Artículo', 'Tipo', 'Cantidad', 'Motivo', 'Usuario', 'Observación'],
            'select' => 'm.fecha_movimiento, a.nombre_articulo, m.tipo_movimiento, m.cantidad, mo.nombre_motivo, us.nombre_usuario, m.observacion',
            'from' => 'movimientos_inventario m JOIN articulos a ON a.id_articulo = m.id_articulo JOIN usuarios us ON us.id_usuario = m.id_usuario LEFT JOIN motivos_ajustes_anulaciones mo ON mo.id_motivo = m.id_motivo',
            'where' => "m.tipo_movimiento IN ('AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO') OR m.id_motivo IS NOT NULL",
            'search' => ['a.codigo_interno_articulo', 'a.nombre_articulo', 'mo.nombre_motivo', 'us.nombre_usuario', 'm.observacion'],
            'date' => 'm.fecha_movimiento',
            'article' => 'm.id_articulo',
            'category' => 'a.id_clasificacion',
            'user' => 'm.id_usuario',
        ],
        'movimientos_usuario' => [
            'title' => 'Movimientos por usuario y fecha',
            'description' => 'Entradas, salidas y ajustes registrados por cada usuario.',
            'columns' => ['Fecha', 'Usuario', 'Artículo', 'Tipo', 'Cantidad', 'Documento', 'Observación'],
            'select' => 'm.fecha_movimiento, us.nombre_usuario, a.nombre_articulo, m.tipo_movimiento, m.cantidad, COALESCE(de.correlativo, ds.correlativo), m.observacion',
            'from' => "movimientos_inventario m JOIN usuarios us ON us.id_usuario = m.id_usuario JOIN articulos a ON a.id_articulo = m.id_articulo LEFT JOIN documentos_entrada de ON m.origen_referencia = 'documentos_entrada' AND de.id_doc_entrada = m.id_doc_referencia LEFT JOIN documentos_salida ds ON m.origen_referencia = 'documentos_salida' AND ds.id_doc_salida = m.id_doc_referencia",
            'where' => '1 = 1',
            'search' => ['us.nombre_usuario', 'us.user_usuario', 'a.nombre_articulo', 'm.observacion'],
            'date' => 'm.fecha_movimiento',
            'article' => 'm.id_articulo',
            'category' => 'a.id_clasificacion',
            'user' => 'm.id_usuario',
        ],
        'documentos' => [
            'title' => 'Historial de documentos de entrada y salida',
            'description' => 'Historial unificado de documentos de inventario.',
            'columns' => ['Tipo', 'Documento', 'Fecha', 'Estado', 'Usuario', 'Referencia', 'Observaciones'],
            'select' => "'ENTRADA' AS tipo_documento, de.correlativo, de.fecha_movimiento, de.estado_doc, us.nombre_usuario, de.proveedor_origen, de.observaciones",
            'from' => 'documentos_entrada de JOIN usuarios us ON us.id_usuario = de.id_usuario',
            'where' => '1 = 1',
            'union' => "SELECT 'SALIDA' AS tipo_documento, ds.correlativo, ds.fecha_movimiento, ds.estado_doc, us.nombre_usuario, ds.destino_salida, ds.observaciones FROM documentos_salida ds JOIN usuarios us ON us.id_usuario = ds.id_usuario",
            'search' => ['correlativo', 'estado_doc', 'nombre_usuario'],
            'date' => 'fecha_movimiento',
            'user' => 'de.id_usuario',
            'status' => 'estado_doc',
        ],
    ];

    public static function getReportTypes(): array
    {
        // Tipos de informe listos para mostrarse en el selector.
        $types = [];
        foreach (self::REPORTS as $key => $report) {
            $types[] = [
                'value' => $key,
                'label' => $report['title'],
                'description' => $report['description'],
            ];
        }

        return $types;
    }

    /**
     * Opciones de articulos activos para los filtros del reporte.
     *
     * @return array
     */
    public static function getArticulosOptions(): array
    {
        return self::obtenerRegistros(
            'SELECT id_articulo AS value,'
            . " CONCAT(codigo_interno_articulo, ' - ', nombre_articulo) AS label"
            . ' FROM articulos WHERE estado_registro = 1'
            . ' ORDER BY nombre_articulo ASC',
            []
        );
    }

    /**
     * Opciones de usuarios activos para los filtros del reporte.
     *
     * @return array
     */
    public static function getUsuariosOptions(): array
    {
        return self::obtenerRegistros(
            'SELECT id_usuario AS value,'
            . " CONCAT(nombre_usuario, ' (', user_usuario, ')') AS label"
            . ' FROM usuarios WHERE estado_usuario = 1'
            . ' ORDER BY nombre_usuario ASC',
            []
        );
    }

    /**
     * Opciones de clasificaciones activas.
     *
     * @return array
     */
    public static function getCategoriasOptions(): array
    {
        return self::obtenerRegistros(
            'SELECT id_clasificacion AS value, nombre_clasificacion AS label'
            . ' FROM clasificaciones WHERE estado_clasif = 1'
            . ' ORDER BY nombre_clasificacion ASC',
            []
        );
    }

    /**
     * Opciones de ubicaciones activas.
     *
     * @return array
     */
    public static function getUbicacionesOptions(): array
    {
        return self::obtenerRegistros(
            'SELECT id_ubicacion AS value, nombre_ubicacion AS label'
            . ' FROM ubicaciones WHERE estado_ubi = 1'
            . ' ORDER BY nombre_ubicacion ASC',
            []
        );
    }

    /**
     * Devuelve el resultado del informe solicitado.
     *
     * @param string $type Clave del informe.
     * @param array $filters Filtros documentados en la clase.
     * @return array{title:string, description:string, columns:array, rows:array, total:int, page:int, itemsPerPage:int, pages:int}
     */
    public static function getReport(string $type, array $filters): array
    {
        $type = self::normalizeType($type);
        $report = self::REPORTS[$type];
        [$where, $params] = self::buildFilters($report, $filters);
        $sql = 'SELECT ' . $report['select'] . ' FROM ' . $report['from']
            . ' WHERE (' . $where . ')';

        if (isset($report['group'])) {
            $sql .= ' GROUP BY ' . $report['group'];
        }
        if (isset($report['union'])) {
            $unionWhere = str_replace(
                ['correlativo', 'estado_doc', 'nombre_usuario', 'fecha_movimiento', 'de.id_usuario'],
                ['ds.correlativo', 'ds.estado_doc', 'us.nombre_usuario', 'ds.fecha_movimiento', 'ds.id_usuario'],
                $where
            );
            // Parametros separados para la consulta de la segunda parte del informe.
            $unionParams = [];
            foreach ($params as $name => $value) {
                $unionName = 'union_' . $name;
                $unionWhere = str_replace(':' . $name, ':' . $unionName, $unionWhere);
                $unionParams[$unionName] = $value;
            }
            $params = array_merge($params, $unionParams);
            $sql .= ' UNION ALL ' . $report['union'] . ' WHERE (' . $unionWhere . ')';
        }
        $countRow = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM (' . $sql . ') AS report_rows',
            $params
        );
        $total = intval($countRow['total'] ?? 0);

        $sql .= ' ORDER BY 1 DESC';
        if (($filters['paginate'] ?? true) === true) {
            $itemsPerPage = max(1, min(100, intval($filters['itemsPerPage'] ?? 10)));
            $page = max(1, intval($filters['page'] ?? 1));
            $offset = ($page - 1) * $itemsPerPage;
            $sql .= ' LIMIT ' . $offset . ', ' . $itemsPerPage;
        } else {
            $sql .= ' LIMIT 5000';
        }

        $rows = self::obtenerRegistros($sql, $params);

        return [
            'title' => $report['title'],
            'description' => $report['description'],
            'columns' => $report['columns'],
            'rows' => array_map('array_values', $rows),
            'total' => $total,
        ];
    }

    private static function normalizeType(string $type): string
    {
        return isset(self::REPORTS[$type]) ? $type : 'inventario';
    }

    private static function buildFilters(array $report, array $filters): array
    {
        // Filtros del informe y valores que se envian a la consulta.
        $conditions = [$report['where']];
        $params = [];
        $search = trim(strval($filters['q'] ?? ''));

        if ($search !== '' && isset($report['search'])) {
            $parts = [];
            foreach ($report['search'] as $index => $column) {
                $name = 'q' . $index;
                $parts[] = $column . ' LIKE :' . $name;
                $params[$name] = '%' . $search . '%';
            }
            $conditions[] = '(' . implode(' OR ', $parts) . ')';
        }
        if (isset($report['date']) && strval($filters['desde'] ?? '') !== '') {
            $conditions[] = $report['date'] . ' >= :desde';
            $params['desde'] = strval($filters['desde']) . ' 00:00:00';
        }
        if (isset($report['date']) && strval($filters['hasta'] ?? '') !== '') {
            $conditions[] = $report['date'] . ' <= :hasta';
            $params['hasta'] = strval($filters['hasta']) . ' 23:59:59';
        }
        foreach (['article' => 'articulo', 'user' => 'usuario', 'category' => 'categoria', 'location' => 'ubicacion', 'department' => 'departamento', 'status' => 'estado'] as $key => $filter) {
            if (isset($report[$key]) && strval($filters[$filter] ?? '') !== '' && intval($filters[$filter]) > 0 && in_array($key, ['article', 'user', 'category', 'location'], true)) {
                $conditions[] = $report[$key] . ' = :' . $filter;
                $params[$filter] = intval($filters[$filter]);
            } elseif ($key === 'department' && isset($report[$key]) && strval($filters[$filter] ?? '') !== '') {
                $conditions[] = $report[$key] . ' = :' . $filter;
                $params[$filter] = strval($filters[$filter]);
            } elseif ($key === 'status' && isset($report[$key]) && strval($filters[$filter] ?? '') !== '') {
                $conditions[] = $report[$key] . ' = :' . $filter;
                $params[$filter] = strval($filters[$filter]);
            }
        }

        return [implode(' AND ', $conditions), $params];
    }
}
