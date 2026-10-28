<?php

namespace Dao\Reportes;

use Dao\Table;

/**
 * Acceso al reporte de equipos agrupados por custodio.
 *
 * Variables:
 * - $search: texto para responsable o departamento.
 * - $departamento: filtro por departamento.
 * - $page: pagina basada en cero.
 * - $itemsPerPage: cantidad de registros por pagina.
 * - $custodios: pares persona/departamento de la pagina actual.
 */
class EquiposCustodio extends Table
{
    /**
     * Agrupa las asignaciones vigentes por persona y departamento. El listado
     * se pagina por custodio, no por equipo.
     */
    /**
     * Agrupa las asignaciones vigentes por persona y departamento.
     *
     * @param string $search Texto de busqueda.
     * @param string $departamento Departamento.
     * @param int $page Pagina basada en cero.
     * @param int $itemsPerPage Registros por pagina.
     * @return array{custodios: array, total: int, page: int, itemsPerPage: int, pages: int}
     */
    public static function getCustodios(
        string $search = '',
        string $departamento = '',
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        [$where, $params] = self::buildFilters($search, $departamento);

        $grouped = 'SELECT a.persona_responsable, a.departamento_destino,'
            . ' COUNT(*) AS equipos,'
            . ' MIN(a.fecha_asignacion) AS primera_asignacion,'
            . ' MAX(a.fecha_asignacion) AS ultima_asignacion'
            . ' FROM asignaciones_devoluciones a'
            . $where
            . ' GROUP BY a.persona_responsable, a.departamento_destino';

        $countRow = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM (' . $grouped . ') AS custodios',
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

        $sql = $grouped
            . ' ORDER BY a.persona_responsable ASC, a.departamento_destino ASC'
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'custodios' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    /**
     * Custodios filtrados sin paginacion para la exportacion del reporte.
     */
    public static function getCustodiosForExport(
        string $search = '',
        string $departamento = ''
    ): array {
        [$where, $params] = self::buildFilters($search, $departamento);

        return self::obtenerRegistros(
            'SELECT a.persona_responsable, a.departamento_destino,'
            . ' COUNT(*) AS equipos,'
            . ' MIN(a.fecha_asignacion) AS primera_asignacion,'
            . ' MAX(a.fecha_asignacion) AS ultima_asignacion'
            . ' FROM asignaciones_devoluciones a'
            . $where
            . ' GROUP BY a.persona_responsable, a.departamento_destino'
            . ' ORDER BY a.persona_responsable ASC, a.departamento_destino ASC',
            $params
        );
    }

    /**
     * Equipos vigentes de los custodios que se muestran en la pagina actual.
     * Se consulta en una sola sentencia usando pares persona/departamento.
     */
    public static function getEquiposPorCustodios(array $custodios): array
    {
        if (count($custodios) === 0) {
            return [];
        }

        // Pares de persona y departamento usados para buscar sus equipos.
        $conditions = [];
        $params = [];
        $index = 0;

        foreach ($custodios as $custodio) {
            $personaKey = 'persona' . $index;
            $departamentoKey = 'departamento' . $index;
            $conditions[] = '(a.persona_responsable = :' . $personaKey
                . ' AND a.departamento_destino = :' . $departamentoKey . ')';
            $params[$personaKey] = strval($custodio['persona_responsable'] ?? '');
            $params[$departamentoKey] = strval($custodio['departamento_destino'] ?? '');
            $index++;
        }

        $sql = 'SELECT a.id_asignacion, a.persona_responsable, a.departamento_destino,'
            . ' a.fecha_asignacion, a.condicion_entrega,'
            . ' eq.id_equipo, eq.numero_serie_equipo, eq.codigo_patrimonial_equipo,'
            . ' eq.estado_operativo,'
            . ' ar.nombre_articulo, ar.codigo_interno_articulo,'
            . ' u.nombre_usuario AS entrega_nombre'
            . ' FROM asignaciones_devoluciones a'
            . ' LEFT JOIN equipos_individuales eq ON eq.id_equipo = a.id_equipo'
            . ' LEFT JOIN articulos ar ON ar.id_articulo = eq.id_articulo'
            . ' JOIN usuarios u ON u.id_usuario = a.id_usuario_entrega'
            . " WHERE a.estado_asignacion = 'ACTIVA'"
            . ' AND (' . implode(' OR ', $conditions) . ')'
            . ' ORDER BY a.persona_responsable ASC, a.departamento_destino ASC,'
            . ' a.fecha_asignacion ASC, a.id_asignacion ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getSummary(string $search = '', string $departamento = ''): array
    {
        [$where, $params] = self::buildFilters($search, $departamento);

        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS equipos,"
            . ' COUNT(DISTINCT a.persona_responsable, a.departamento_destino) AS custodios,'
            . ' COUNT(DISTINCT a.departamento_destino) AS departamentos,'
            . ' MIN(a.fecha_asignacion) AS primera_asignacion'
            . ' FROM asignaciones_devoluciones a'
            . $where,
            $params
        );

        return [
            'equipos' => intval($row['equipos'] ?? 0),
            'custodios' => intval($row['custodios'] ?? 0),
            'departamentos' => intval($row['departamentos'] ?? 0),
            'primera_asignacion' => strval($row['primera_asignacion'] ?? ''),
        ];
    }

    public static function getDepartamentos(): array
    {
        return self::obtenerRegistros(
            'SELECT DISTINCT departamento_destino AS departamento'
            . ' FROM asignaciones_devoluciones'
            . " WHERE estado_asignacion = 'ACTIVA'"
            . " AND departamento_destino <> ''"
            . ' ORDER BY departamento_destino ASC',
            []
        );
    }

    /**
     * Equipos en custodia agrupados por departamento para el grafico de barras.
     * Respeta la busqueda y el filtro de departamento del reporte.
     */
    public static function getEquiposPorDepartamento(
        string $search = '',
        string $departamento = '',
        int $limit = 8
    ): array {
        [$where, $params] = self::buildFilters($search, $departamento);

        $limit = max(1, min(20, $limit));

        return self::obtenerRegistros(
            'SELECT a.departamento_destino AS departamento,'
            . ' COUNT(*) AS equipos,'
            . ' COUNT(DISTINCT a.persona_responsable) AS custodios'
            . ' FROM asignaciones_devoluciones a'
            . $where
            . " AND a.departamento_destino <> ''"
            . ' GROUP BY a.departamento_destino'
            . ' ORDER BY equipos DESC, a.departamento_destino ASC'
            . ' LIMIT ' . $limit,
            $params
        );
    }

    /**
     * Equipos en custodia agrupados por estado operativo. Respeta la busqueda
     * y el filtro de departamento del reporte.
     */
    public static function getEquiposPorEstado(
        string $search = '',
        string $departamento = ''
    ): array {
        [$where, $params] = self::buildFilters($search, $departamento);

        return self::obtenerRegistros(
            'SELECT eq.estado_operativo, COUNT(*) AS equipos'
            . ' FROM asignaciones_devoluciones a'
            . ' JOIN equipos_individuales eq ON eq.id_equipo = a.id_equipo'
            . $where
            . ' GROUP BY eq.estado_operativo'
            . ' ORDER BY equipos DESC, eq.estado_operativo ASC',
            $params
        );
    }

    /**
     * Custodios con mas equipos activos dentro del filtro actual.
     */
    public static function getTopCustodios(
        string $search = '',
        string $departamento = '',
        int $limit = 6
    ): array {
        [$where, $params] = self::buildFilters($search, $departamento);
        $limit = max(1, min(20, $limit));

        return self::obtenerRegistros(
            'SELECT a.persona_responsable, a.departamento_destino,'
            . ' COUNT(*) AS equipos'
            . ' FROM asignaciones_devoluciones a'
            . $where
            . ' GROUP BY a.persona_responsable, a.departamento_destino'
            . ' ORDER BY equipos DESC, a.persona_responsable ASC, a.departamento_destino ASC'
            . ' LIMIT ' . $limit,
            $params
        );
    }

    private static function buildFilters(string $search, string $departamento): array
    {
        // Filtros comunes para los resumenes de equipos por responsable.
        $conditions = ["a.estado_asignacion = 'ACTIVA'"];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(a.persona_responsable LIKE :searchPersona'
                . ' OR a.departamento_destino LIKE :searchDepartamento)';
            $searchValue = '%' . $search . '%';
            $params['searchPersona'] = $searchValue;
            $params['searchDepartamento'] = $searchValue;
        }

        $departamento = trim($departamento);
        if ($departamento !== '') {
            $conditions[] = 'a.departamento_destino = :departamento';
            $params['departamento'] = $departamento;
        }

        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }
}
