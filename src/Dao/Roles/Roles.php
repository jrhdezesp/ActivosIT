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
    ) {
        $sqlstr = "SELECT r.rolescod, r.rolesdsc, r.rolesest, case when r.rolesest = 'ACT' then 'Activo' when r.rolesest = 'INA' then 'Inactivo' else 'Sin Asignar' end as rolesestDsc
    FROM roles r";
        $sqlstrCount = 'SELECT COUNT(*) as count FROM roles r';
        $conditions = [];
        $params = [];
        if ($partialName != '') {
            $conditions[] = 'r.rolesdsc LIKE :partialName';
            $params['partialName'] = '%'.$partialName.'%';
        }
        if (!in_array($status, ['ACT', 'INA', ''])) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }
        if ($status != '') {
            $conditions[] = 'r.rolesest = :status';
            $params['status'] = $status;
        }
        if (count($conditions) > 0) {
            $sqlstr .= ' WHERE '.implode(' AND ', $conditions);
            $sqlstrCount .= ' WHERE '.implode(' AND ', $conditions);
        }
        if (!in_array($orderBy, ['rolescod', 'rolesdsc', ''])) {
            throw new \Exception('Error Processing Request OrderBy has invalid value');
        }
        if ($orderBy != '') {
            $sqlstr .= ' ORDER BY '.$orderBy;
            if ($orderDescending) {
                $sqlstr .= ' DESC';
            }
        }
        $itemsPerPage = $itemsPerPage > 0 ? $itemsPerPage : 10;
        $page = $page >= 0 ? $page : 0;
        $numeroDeRegistros = self::obtenerUnRegistro($sqlstrCount, $params)['count'];
        $pagesCount = $numeroDeRegistros > 0 ? ceil($numeroDeRegistros / $itemsPerPage) : 1;
        if ($page > $pagesCount - 1) {
            $page = $pagesCount - 1;
        }
        $offset = $page * $itemsPerPage;
        $sqlstr .= ' LIMIT '.$offset.', '.$itemsPerPage;

        $registros = self::obtenerRegistros($sqlstr, $params);

        return ['roles' => $registros, 'total' => $numeroDeRegistros, 'page' => $page, 'itemsPerPage' => $itemsPerPage];
    }

    public static function getRolById(string $rolescod)
    {
        $sqlstr = 'SELECT r.rolescod, r.rolesdsc, r.rolesest FROM roles r WHERE r.rolescod = :rolescod';
        $params = ['rolescod' => $rolescod];

        return self::obtenerUnRegistro($sqlstr, $params);
    }

    public static function getFuncionesByRol(string $rolescod)
    {
        $sqlstr = "SELECT f.fncod, f.fndsc, f.fnest, f.fntyp,
            COALESCE(fr.fnrolest, 'INA') AS fnrolest,
            CASE
                WHEN COALESCE(fr.fnrolest, 'INA') = 'ACT' THEN 'Activo'
                WHEN COALESCE(fr.fnrolest, 'INA') = 'INA' THEN 'Inactivo'
                ELSE 'Sin Asignar'
            END AS fnrolestDsc
        FROM funciones f
        LEFT JOIN funciones_roles fr
            ON f.fncod = fr.fncod
            AND fr.rolescod = :rolescod
        ORDER BY f.fncod";
        $params = ['rolescod' => $rolescod];

        return self::obtenerRegistros($sqlstr, $params);
    }

    public static function insertRol(
        string $rolescod,
        string $rolesdsc,
        string $rolesest
    ) {
        $sqlstr = 'INSERT INTO roles (rolescod, rolesdsc, rolesest) VALUES (:rolescod, :rolesdsc, :rolesest)';
        $params = [
            'rolescod' => $rolescod,
            'rolesdsc' => $rolesdsc,
            'rolesest' => $rolesest,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function updateRol(
        string $rolescod,
        string $rolesdsc,
        string $rolesest
    ) {
        $sqlstr = 'UPDATE roles SET rolesdsc = :rolesdsc, rolesest = :rolesest WHERE rolescod = :rolescod';
        $params = [
            'rolescod' => $rolescod,
            'rolesdsc' => $rolesdsc,
            'rolesest' => $rolesest,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function upsertRolFuncion(
        string $rolescod,
        string $fncod,
        string $fnrolest
    ) {
        if (!in_array($fnrolest, ['ACT', 'INA'])) {
            throw new \Exception('Error Processing Request fnrolest has invalid value');
        }

        $sqlstr = 'INSERT INTO funciones_roles (rolescod, fncod, fnrolest, fnexp)
            VALUES (:rolescod, :fncod, :fnrolest, :fnexp)
            ON DUPLICATE KEY UPDATE
                fnrolest = :fnrolest,
                fnexp = :fnexp';
        $params = [
            'rolescod' => $rolescod,
            'fncod' => $fncod,
            'fnrolest' => $fnrolest,
            'fnexp' => $fnrolest === 'INA' ? date('Y-m-d H:i:s') : null,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function deleteRol(string $rolescod)
    {
        $sqlstr = 'DELETE FROM roles WHERE rolescod = :rolescod';
        $params = ['rolescod' => $rolescod];

        return self::executeNonQuery($sqlstr, $params);
    }
}
