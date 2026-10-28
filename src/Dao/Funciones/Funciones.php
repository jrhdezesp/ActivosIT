<?php

namespace Dao\Funciones;

use Dao\Table;

/**
 * Acceso a datos de permisos y funciones del sistema.
 *
 * Variables de filtro:
 * - $partialName: texto para buscar por codigo o descripcion.
 * - $status: estado del permiso.
 * - $orderBy: campo permitido para ordenar.
 * - $orderDescending: true para orden descendente.
 * - $page: pagina basada en cero.
 * - $itemsPerPage: cantidad de registros por pagina.
 *
 * Variables de permiso:
 * - $fncod: codigo o nombre del permiso.
 * - $fndsc: descripcion del permiso.
 * - $fnest: estado; ACT o INA.
 * - $fntyp: tipo; CTR, MNU, MNT o RPT.
 */
class Funciones extends Table
{
    /**
     * Devuelve la lista paginada de permisos.
     *
     * @param string $partialName Texto de busqueda.
     * @param string $status Estado del permiso.
     * @param string $orderBy Campo de orden permitido.
     * @param bool $orderDescending true para orden descendente.
     * @param int $page Pagina basada en cero.
     * @param int $itemsPerPage Registros por pagina.
     * @return array{funciones: array, total: int, page: int, itemsPerPage: int}
     */
    public static function getFunciones(
        string $partialName = '',
        string $status = '',
        string $orderBy = '',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        $sqlstr = "SELECT
            p.id_permiso,
            p.nombre_permiso,
            p.descripcion_permiso,
            p.modulo_permiso,
            p.nombre_permiso AS fncod,
            p.descripcion_permiso AS fndsc,
            CASE WHEN p.estado_permiso = 1 THEN 'ACT' ELSE 'INA' END AS fnest,
            CASE WHEN p.estado_permiso = 1 THEN 'Activo' ELSE 'Inactivo' END AS fnestDsc,
            p.tipo_permiso AS fntyp,
            CASE p.tipo_permiso
                WHEN 'CTR' THEN 'Controlador'
                WHEN 'MNU' THEN 'Menú'
                WHEN 'MNT' THEN 'Mantenimiento'
                WHEN 'RPT' THEN 'Reporte'
                WHEN 'ACC' THEN 'Accion'
                ELSE p.tipo_permiso
            END AS fntypDsc
        FROM permisos p";

        $sqlstrCount = 'SELECT COUNT(*) AS count FROM permisos p';
        // Filtros de la lista y valores asociados a cada condicion.
        $conditions = [];
        $params = [];

        $partialName = trim($partialName);
        if ($partialName !== '') {
            $conditions[] = '(p.nombre_permiso LIKE :partialNameCod OR p.descripcion_permiso LIKE :partialNameDesc)';
            $params['partialNameCod'] = '%' . $partialName . '%';
            $params['partialNameDesc'] = '%' . $partialName . '%';
        }

        if (!in_array($status, ['ACT', 'INA', '', '1', '0'], true)) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }

        if ($status !== '') {
            $conditions[] = 'p.estado_permiso = :status';
            $params['status'] = in_array($status, ['ACT', '1'], true) ? 1 : 0;
        }

        if (count($conditions) > 0) {
            $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            $sqlstr .= $whereClause;
            $sqlstrCount .= $whereClause;
        }

        // Relacion entre las opciones de orden recibidas y sus campos.
        $orderMap = [
            'fncod' => 'p.nombre_permiso',
            'fndsc' => 'p.descripcion_permiso',
            'fntyp' => 'p.tipo_permiso',
        ];

        if (!in_array($orderBy, array_keys($orderMap), true) && $orderBy !== '') {
            throw new \Exception('Error Processing Request OrderBy has invalid value');
        }

        if ($orderBy !== '') {
            $sqlstr .= ' ORDER BY ' . $orderMap[$orderBy];
            if ($orderDescending) {
                $sqlstr .= ' DESC';
            }
        } else {
            $sqlstr .= ' ORDER BY p.tipo_permiso ASC, p.nombre_permiso ASC';
        }

        $itemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : 10;
        $page = $page >= 0 ? $page : 0;
        $numeroDeRegistros = self::obtenerUnRegistro($sqlstrCount, $params)['count'] ?? 0;
        $pagesCount = $numeroDeRegistros > 0 ? (int) ceil($numeroDeRegistros / $itemsPerPage) : 1;
        if ($page > $pagesCount - 1) {
            $page = $pagesCount - 1;
        }
        $offset = max(0, $page * $itemsPerPage);
        $sqlstr .= ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        $registros = self::obtenerRegistros($sqlstr, $params);

        return [
            'funciones' => $registros,
            'total' => $numeroDeRegistros,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    public static function getFuncionById(string $fncod)
    {
        $sqlstr = "SELECT
            p.id_permiso,
            p.nombre_permiso,
            p.descripcion_permiso,
            p.modulo_permiso,
            p.nombre_permiso AS fncod,
            p.descripcion_permiso AS fndsc,
            CASE WHEN p.estado_permiso = 1 THEN 'ACT' ELSE 'INA' END AS fnest,
            p.tipo_permiso AS fntyp
        FROM permisos p
        WHERE p.nombre_permiso = :fncod OR p.id_permiso = :fncodInt
        LIMIT 1";

        return self::obtenerUnRegistro($sqlstr, [
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
        ]);
    }

    public static function insertFuncion(
        string $fncod,
        string $fndsc,
        string $fnest,
        string $fntyp
    ): int {
        $tipo = in_array($fntyp, ['CTR', 'MNU', 'MNT', 'RPT', 'ACC'], true) ? $fntyp : 'CTR';
        $estado = in_array($fnest, ['ACT', '1'], true) ? 1 : 0;
        $moduloPorTipo = ['CTR' => 'Controladores', 'MNU' => 'Menús', 'MNT' => 'Mantenimientos', 'RPT' => 'Reportes', 'ACC' => 'Acciones'];

        $sqlstr = 'INSERT INTO permisos (nombre_permiso, descripcion_permiso, modulo_permiso, tipo_permiso, estado_permiso)
            VALUES (:fncod, :fndsc, :modulo, :tipo, :estado)
            ON DUPLICATE KEY UPDATE
                descripcion_permiso = VALUES(descripcion_permiso),
                tipo_permiso = VALUES(tipo_permiso),
                estado_permiso = VALUES(estado_permiso)';

        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fndsc' => $fndsc,
            'modulo' => $moduloPorTipo[$tipo],
            'tipo' => $tipo,
            'estado' => $estado,
        ]);
    }

    public static function updateFuncion(
        string $fncod,
        string $fndsc,
        string $fnest,
        string $fntyp
    ): int {
        $tipo = in_array($fntyp, ['CTR', 'MNU', 'MNT', 'RPT', 'ACC'], true) ? $fntyp : 'CTR';
        $estado = in_array($fnest, ['ACT', '1'], true) ? 1 : 0;

        // modulo_permiso es informativo/agrupador: no se sobrescribe al editar
        // el permiso, de lo contrario el tipo (CTR/MNU) se perderia.
        $sqlstr = 'UPDATE permisos
            SET descripcion_permiso = :fndsc,
                tipo_permiso = :tipo,
                estado_permiso = :estado
            WHERE nombre_permiso = :fncod OR id_permiso = :fncodInt';

        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
            'fndsc' => $fndsc,
            'tipo' => $tipo,
            'estado' => $estado,
        ]);
    }

    public static function deleteFuncion(string $fncod): int
    {
        // Borrado lógico: el permiso se conserva para no romper el historial de
        // roles_permisos y para poder reactivarlo desde el formulario.
        $sqlstr = 'UPDATE permisos SET estado_permiso = 0 WHERE nombre_permiso = :fncod OR id_permiso = :fncodInt';
        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
        ]);
    }

    /**
     * Indica si ya existe un permiso con ese nombre.
     *
     * @param string $nombre Nombre del permiso.
     * @return bool
     */
    public static function nombreExists(string $nombre): bool
    {
        $row = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM permisos WHERE nombre_permiso = :nombre',
            ['nombre' => $nombre]
        );
        return intval($row['total'] ?? 0) > 0;
    }
}
