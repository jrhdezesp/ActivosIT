<?php

namespace Dao\Reportes;

use Dao\Table;

class Reportes extends Table
{
    /**
     * Indicadores generales del inventario. Todas las consultas son de solo
     * lectura y consideran unicamente registros activos.
     */
    public static function getResumenGeneral(): array
    {
        $articulos = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(tipo_control = 'CANTIDAD'"
            . ' AND existencia_actual <= COALESCE(existencia_minima, 0)), 0) AS stock_bajo,'
            . " COALESCE(SUM(existencia_actual), 0) AS existencias"
            . " FROM articulos WHERE estado_registro = 1",
            []
        );

        $equipos = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(estado_operativo = 'DISPONIBLE'), 0) AS disponibles,"
            . " COALESCE(SUM(estado_operativo = 'ASIGNADO'), 0) AS asignados,"
            . " COALESCE(SUM(estado_operativo = 'EN_MANTENIMIENTO'), 0) AS en_mantenimiento,"
            . " COALESCE(SUM(estado_operativo = 'EN_REVISION'), 0) AS en_revision,"
            . " COALESCE(SUM(estado_operativo = 'DE_BAJA'), 0) AS de_baja"
            . " FROM equipos_individuales WHERE estado_registro = 1",
            []
        );

        $movimientos = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(YEAR(fecha_movimiento) = YEAR(CURDATE())"
            . " AND MONTH(fecha_movimiento) = MONTH(CURDATE())), 0) AS del_mes"
            . " FROM movimientos_inventario",
            []
        );

        $asignaciones = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS activas,"
            . " COUNT(DISTINCT persona_responsable, departamento_destino) AS custodios"
            . " FROM asignaciones_devoluciones"
            . " WHERE estado_asignacion = 'ACTIVA'",
            []
        );

        $mantenimientos = self::obtenerUnRegistro(
            "SELECT COALESCE(SUM(estado_mantenimiento = 'EN_PROCESO'), 0) AS en_proceso,"
            . " COALESCE(SUM(estado_mantenimiento = 'COMPLETADO'), 0) AS completados,"
            . " COUNT(*) AS total"
            . " FROM mantenimientos",
            []
        );

        return [
            'articulos' => intval($articulos['total'] ?? 0),
            'stock_bajo' => intval($articulos['stock_bajo'] ?? 0),
            'existencias' => intval($articulos['existencias'] ?? 0),
            'equipos' => intval($equipos['total'] ?? 0),
            'equipos_disponibles' => intval($equipos['disponibles'] ?? 0),
            'equipos_asignados' => intval($equipos['asignados'] ?? 0),
            'equipos_mantenimiento' => intval($equipos['en_mantenimiento'] ?? 0),
            'equipos_revision' => intval($equipos['en_revision'] ?? 0),
            'equipos_baja' => intval($equipos['de_baja'] ?? 0),
            'movimientos' => intval($movimientos['total'] ?? 0),
            'movimientos_mes' => intval($movimientos['del_mes'] ?? 0),
            'asignaciones_activas' => intval($asignaciones['activas'] ?? 0),
            'custodios' => intval($asignaciones['custodios'] ?? 0),
            'mantenimientos' => intval($mantenimientos['total'] ?? 0),
            'mantenimientos_proceso' => intval($mantenimientos['en_proceso'] ?? 0),
            'mantenimientos_completados' => intval($mantenimientos['completados'] ?? 0),
        ];
    }

    /**
     * Articulos por cantidad cuya existencia actual esta en el minimo o por
     * debajo. Usa el mismo criterio que el catalogo de articulos.
     */
    public static function getStockBajo(int $limit = 6): array
    {
        $limit = max(1, min(50, $limit));

        return self::obtenerRegistros(
            "SELECT a.id_articulo, a.codigo_interno_articulo, a.nombre_articulo,"
            . ' a.existencia_actual, a.existencia_minima,'
            . ' u.nombre_unidad, u.simbolo_medida,'
            . ' c.nombre_clasificacion'
            . ' FROM articulos a'
            . ' LEFT JOIN unidades_medida u ON u.id_unidad_medida = a.id_unidad_medida'
            . ' LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion'
            . " WHERE a.estado_registro = 1"
            . " AND a.tipo_control = 'CANTIDAD'"
            . ' AND a.existencia_actual <= COALESCE(a.existencia_minima, 0)'
            . ' ORDER BY (a.existencia_actual - COALESCE(a.existencia_minima, 0)) ASC,'
            . ' a.nombre_articulo ASC'
            . ' LIMIT ' . $limit,
            []
        );
    }

    /**
     * Ultimos movimientos registrados en el kardex para el panel de reportes.
     */
    public static function getMovimientosRecientes(int $limit = 6): array
    {
        $limit = max(1, min(20, $limit));

        return self::obtenerRegistros(
            'SELECT m.id_movimiento, m.tipo_movimiento, m.cantidad,'
            . ' m.existencia_anterior, m.existencia_nueva, m.fecha_movimiento,'
            . ' m.observacion, m.origen_referencia,'
            . ' a.codigo_interno_articulo, a.nombre_articulo, a.tipo_control,'
            . ' u.nombre_usuario AS usuario_nombre,'
            . ' COALESCE(de.correlativo, ds.correlativo) AS documento_correlativo'
            . ' FROM movimientos_inventario m'
            . ' JOIN articulos a ON a.id_articulo = m.id_articulo'
            . ' JOIN usuarios u ON u.id_usuario = m.id_usuario'
            . " LEFT JOIN documentos_entrada de ON m.origen_referencia = 'documentos_entrada'"
            . ' AND de.id_doc_entrada = m.id_doc_referencia'
            . " LEFT JOIN documentos_salida ds ON m.origen_referencia = 'documentos_salida'"
            . ' AND ds.id_doc_salida = m.id_doc_referencia'
            . ' ORDER BY m.fecha_movimiento DESC, m.id_movimiento DESC'
            . ' LIMIT ' . $limit,
            []
        );
    }

    /**
     * Serie de entradas y salidas por mes para el grafico del centro de
     * reportes. Rellena los meses sin movimientos con ceros para que el eje
     * temporal no tenga huecos.
     */
    public static function getMovimientosPorMes(int $meses = 6): array
    {
        $meses = max(1, min(24, $meses));
        $desde = (new \DateTimeImmutable('first day of this month'))
            ->modify('-' . ($meses - 1) . ' months')
            ->format('Y-m-d');

        $rows = self::obtenerRegistros(
            "SELECT DATE_FORMAT(fecha_movimiento, '%Y-%m') AS periodo,"
            . " COALESCE(SUM(CASE WHEN tipo_movimiento IN ('ENTRADA', 'AJUSTE_POSITIVO')"
            . ' THEN cantidad ELSE 0 END), 0) AS entradas,'
            . " COALESCE(SUM(CASE WHEN tipo_movimiento IN ('SALIDA', 'AJUSTE_NEGATIVO')"
            . ' THEN cantidad ELSE 0 END), 0) AS salidas,'
            . ' COUNT(*) AS movimientos'
            . ' FROM movimientos_inventario'
            . ' WHERE fecha_movimiento >= :desde'
            . ' GROUP BY periodo'
            . ' ORDER BY periodo ASC',
            ['desde' => $desde . ' 00:00:00']
        );

        // Resultados consultados, organizados por periodo para completar la serie.
        $porPeriodo = [];
        foreach ($rows as $row) {
            $porPeriodo[strval($row['periodo'])] = $row;
        }

        $mesesCortos = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
            7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        // Valores de cada serie que se entrega al grafico.
        $labels = [];
        $entradas = [];
        $salidas = [];
        $movimientos = [];
        $inicio = new \DateTimeImmutable($desde);

        for ($i = 0; $i < $meses; $i++) {
            $mes = $inicio->modify('+' . $i . ' months');
            $clave = $mes->format('Y-m');
            $fila = $porPeriodo[$clave] ?? null;

            $labels[] = $mesesCortos[intval($mes->format('n'))] . ' ' . $mes->format('y');
            $entradas[] = intval($fila['entradas'] ?? 0);
            $salidas[] = intval($fila['salidas'] ?? 0);
            $movimientos[] = intval($fila['movimientos'] ?? 0);
        }

        return [
            'labels' => $labels,
            'entradas' => $entradas,
            'salidas' => $salidas,
            'movimientos' => $movimientos,
            'total_entradas' => array_sum($entradas),
            'total_salidas' => array_sum($salidas),
            'total_movimientos' => array_sum($movimientos),
        ];
    }

    /**
     * Conteo historico de movimientos por tipo para el grafico del centro
     * de reportes. Incluye los tipos sin movimientos con valor cero.
     */
    public static function getDistribucionTipos(): array
    {
        $rows = self::obtenerRegistros(
            'SELECT tipo_movimiento, COUNT(*) AS movimientos'
            . ' FROM movimientos_inventario'
            . ' GROUP BY tipo_movimiento',
            []
        );

        // Cantidad obtenida para cada tipo de movimiento.
        $conteo = [];
        foreach ($rows as $row) {
            $conteo[strval($row['tipo_movimiento'] ?? '')] = intval($row['movimientos'] ?? 0);
        }

        $distribucion = [];
        foreach (['ENTRADA', 'SALIDA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'] as $tipo) {
            $distribucion[$tipo] = $conteo[$tipo] ?? 0;
        }

        return $distribucion;
    }
}
