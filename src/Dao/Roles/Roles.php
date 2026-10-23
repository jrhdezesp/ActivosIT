<?php

namespace Dao\Roles;

use Dao\Table;

class Roles extends Table
{
    public static function getRoles(
        string $partialName = '',
        string $status = '',
        string $orderBy = '',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        $sqlstr = "SELECT
            r.id_rol,
            r.nombre_rol,
            r.descripcion_rol,
            r.estado_rol,
            r.nombre_rol AS rolescod,
            r.descripcion_rol AS rolesdsc,
            CASE WHEN r.estado_rol = 1 THEN 'ACT' ELSE 'INA' END AS rolesest,
            CASE WHEN r.estado_rol = 1 THEN 'Activo' ELSE 'Inactivo' END AS rolesestDsc
        FROM roles r";

        $sqlstrCount = 'SELECT COUNT(*) AS count FROM roles r';
        $conditions = [];
        $params = [];

        $partialName = trim($partialName);
        if ($partialName !== '') {
            $conditions[] = '(r.nombre_rol LIKE :partialNameNombre OR r.descripcion_rol LIKE :partialNameDesc)';
            $params['partialNameNombre'] = '%' . $partialName . '%';
            $params['partialNameDesc'] = '%' . $partialName . '%';
        }

        if (!in_array($status, ['ACT', 'INA', '', '1', '0'], true)) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }

        if ($status !== '') {
            $conditions[] = 'r.estado_rol = :status';
            $params['status'] = in_array($status, ['ACT', '1'], true) ? 1 : 0;
        }

        if (count($conditions) > 0) {
            $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            $sqlstr .= $whereClause;
            $sqlstrCount .= $whereClause;
        }

        $orderMap = [
            'rolescod' => 'r.nombre_rol',
            'rolesdsc' => 'r.descripcion_rol',
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
            $sqlstr .= ' ORDER BY r.id_rol ASC';
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
            'roles' => $registros,
            'total' => $numeroDeRegistros,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    public static function getRolById(string $rolescod)
    {
        $sqlstr = "SELECT
            r.id_rol,
            r.nombre_rol,
            r.descripcion_rol,
            r.estado_rol,
            r.nombre_rol AS rolescod,
            r.descripcion_rol AS rolesdsc,
            CASE WHEN r.estado_rol = 1 THEN 'ACT' ELSE 'INA' END AS rolesest
        FROM roles r
        WHERE r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt
        LIMIT 1";

        return self::obtenerUnRegistro($sqlstr, [
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
        ]);
    }

    public static function getFuncionesByRol(string $rolescod): array
    {
        $sqlstr = "SELECT
            p.id_permiso,
            p.nombre_permiso,
            p.descripcion_permiso,
            p.modulo_permiso,
            p.nombre_permiso AS fncod,
            p.descripcion_permiso AS fndsc,
            CASE WHEN p.estado_permiso = 1 THEN 'ACT' ELSE 'INA' END AS fnest,
            p.tipo_permiso AS fntyp,
            CASE p.tipo_permiso
                WHEN 'CTR' THEN 'Controlador'
                WHEN 'MNU' THEN 'Menú'
                WHEN 'MNT' THEN 'Mantenimiento'
                WHEN 'RPT' THEN 'Reporte'
                ELSE p.tipo_permiso
            END AS fntypDsc,
            CASE WHEN rp.id_rol IS NOT NULL THEN 'ACT' ELSE 'INA' END AS fnrolest,
            CASE WHEN rp.id_rol IS NOT NULL THEN 'Activo' ELSE 'Inactivo' END AS fnrolestDsc
        FROM permisos p
        LEFT JOIN roles_permisos rp
            ON p.id_permiso = rp.id_permiso
            AND rp.id_rol = (SELECT id_rol FROM roles WHERE nombre_rol = :rolescod OR id_rol = :rolescodInt LIMIT 1)
        ORDER BY p.nombre_permiso";

        return self::obtenerRegistros($sqlstr, [
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
        ]);
    }

    public static function insertRol(
        string $rolescod,
        string $rolesdsc,
        string $rolesest
    ): int {
        $estado = in_array($rolesest, ['ACT', '1'], true) ? 1 : 0;
        $sqlstr = 'INSERT INTO roles (nombre_rol, descripcion_rol, estado_rol)
            VALUES (:rolescod, :rolesdsc, :estado)';

        return self::executeNonQuery($sqlstr, [
            'rolescod' => $rolescod,
            'rolesdsc' => $rolesdsc,
            'estado' => $estado,
        ]);
    }

    public static function updateRol(
        string $rolescod,
        string $rolesdsc,
        string $rolesest
    ): int {
        $estado = in_array($rolesest, ['ACT', '1'], true) ? 1 : 0;
        $sqlstr = 'UPDATE roles
            SET descripcion_rol = :rolesdsc,
                estado_rol = :estado
            WHERE nombre_rol = :rolescod OR id_rol = :rolescodInt';

        return self::executeNonQuery($sqlstr, [
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
            'rolesdsc' => $rolesdsc,
            'estado' => $estado,
        ]);
    }

    public static function upsertRolFuncion(
        string $rolescod,
        string $fncod,
        string $fnrolest
    ): int {
        if (!in_array($fnrolest, ['ACT', 'INA'], true)) {
            throw new \Exception('Error Processing Request fnrolest has invalid value');
        }

        if ($fnrolest === 'ACT') {
            $sqlstr = "INSERT INTO roles_permisos (id_rol, id_permiso)
                SELECT r.id_rol, p.id_permiso
                FROM roles r
                CROSS JOIN permisos p
                WHERE (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt)
                  AND (p.nombre_permiso = :fncod OR p.id_permiso = :fncodInt)
                LIMIT 1
                ON DUPLICATE KEY UPDATE id_rol = VALUES(id_rol)";

            return self::executeNonQuery($sqlstr, [
                'rolescod' => $rolescod,
                'rolescodInt' => intval($rolescod),
                'fncod' => $fncod,
                'fncodInt' => intval($fncod),
            ]);
        }

        // Si es INA, eliminamos la relación
        $sqlstr = "DELETE rp FROM roles_permisos rp
            INNER JOIN roles r ON r.id_rol = rp.id_rol
            INNER JOIN permisos p ON p.id_permiso = rp.id_permiso
            WHERE (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt)
              AND (p.nombre_permiso = :fncod OR p.id_permiso = :fncodInt)";

        return self::executeNonQuery($sqlstr, [
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
            'fncod' => $fncod,
            'fncodInt' => intval($fncod),
        ]);
    }

    public static function deleteRol(string $rolescod): int
    {
        // Borrado lógico: el rol se desactiva y conserva su historial de
        // asignaciones. Las cuentas que lo tuvieran dejan de recibir sus
        // permisos porque todas las consultas filtran estado_rol = 1.
        $sqlstr = 'UPDATE roles SET estado_rol = 0 WHERE nombre_rol = :rolescod OR id_rol = :rolescodInt';
        return self::executeNonQuery($sqlstr, [
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
        ]);
    }
}
