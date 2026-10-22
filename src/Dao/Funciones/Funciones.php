<?php

namespace Dao\Funciones;

use Dao\Table;

class Funciones extends Table
{
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
            'ACT' AS fnest,
            'Activo' AS fnestDsc,
            p.modulo_permiso AS fntyp
        FROM permisos p";

        $sqlstrCount = 'SELECT COUNT(*) AS count FROM permisos p';
        $conditions = [];
        $params = [];

        $partialName = trim($partialName);
        if ($partialName !== '') {
            $conditions[] = '(p.nombre_permiso LIKE :partialName OR p.descripcion_permiso LIKE :partialName)';
            $params['partialName'] = '%' . $partialName . '%';
        }

        if (!in_array($status, ['ACT', 'INA', '', '1', '0'], true)) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }

        if (count($conditions) > 0) {
            $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            $sqlstr .= $whereClause;
            $sqlstrCount .= $whereClause;
        }

        $orderMap = [
            'fncod' => 'p.nombre_permiso',
            'fndsc' => 'p.descripcion_permiso',
            'fntyp' => 'p.modulo_permiso',
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
            $sqlstr .= ' ORDER BY p.modulo_permiso ASC, p.nombre_permiso ASC';
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
            'ACT' AS fnest,
            p.modulo_permiso AS fntyp
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
        $modulo = trim($fntyp) !== '' ? $fntyp : 'General';
        $sqlstr = 'INSERT INTO permisos (nombre_permiso, descripcion_permiso, modulo_permiso)
            VALUES (:fncod, :fndsc, :modulo)
            ON DUPLICATE KEY UPDATE
                descripcion_permiso = VALUES(descripcion_permiso),
                modulo_permiso = VALUES(modulo_permiso)';

        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fndsc' => $fndsc,
            'modulo' => $modulo,
        ]);
    }

    public static function updateFuncion(
        string $fncod,
        string $fndsc,
        string $fnest,
        string $fntyp
    ): int {
        $modulo = trim($fntyp) !== '' ? $fntyp : 'General';
        $sqlstr = 'UPDATE permisos
            SET descripcion_permiso = :fndsc,
                modulo_permiso = :modulo
            WHERE nombre_permiso = :fncod OR id_permiso = :fncodInt';

        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
            'fndsc' => $fndsc,
            'modulo' => $modulo,
        ]);
    }

    public static function deleteFuncion(string $fncod): int
    {
        // 1. Eliminar relaciones en roles_permisos
        self::executeNonQuery(
            "DELETE rp FROM roles_permisos rp
             INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
             WHERE p.nombre_permiso = :fncod OR p.id_permiso = :fncodInt",
            ['fncod' => $fncod, 'fncodInt' => intval($fncod)]
        );

        // 2. Eliminar permiso
        $sqlstr = 'DELETE FROM permisos WHERE nombre_permiso = :fncod OR id_permiso = :fncodInt';
        return self::executeNonQuery($sqlstr, [
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
        ]);
    }
}
