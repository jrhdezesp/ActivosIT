<?php

namespace Dao\Usuarios;

use Dao\Table;
use Utilities\Context;

class Usuarios extends Table
{
    public static function getUsuarios(
        string $partialName = '',
        string $status = '',
        string $orderBy = '',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ) {
        $sqlstr = "SELECT u.usercod, u.useremail, u.username, u.userest, u.usertipo, case when u.userest = 'ACT' then 'Activo' when u.userest = 'INA' then 'Inactivo' else 'Sin Asignar' end as userestDsc
    FROM usuario u";
        $sqlstrCount = 'SELECT COUNT(*) as count FROM usuario u';
        $conditions = [];
        $params = [];
        if ($partialName != '') {
            $conditions[] = 'u.username LIKE :partialName';
            $params['partialName'] = '%'.$partialName.'%';
        }
        if (!in_array($status, ['ACT', 'INA', ''])) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }
        if ($status != '') {
            $conditions[] = 'u.userest = :status';
            $params['status'] = $status;
        }
        if (count($conditions) > 0) {
            $sqlstr .= ' WHERE '.implode(' AND ', $conditions);
            $sqlstrCount .= ' WHERE '.implode(' AND ', $conditions);
        }
        if (!in_array($orderBy, ['usercod', 'username', 'useremail', ''])) {
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

        return ['usuarios' => $registros, 'total' => $numeroDeRegistros, 'page' => $page, 'itemsPerPage' => $itemsPerPage];
    }

    public static function getUsuarioById(int $usercod)
    {
        $sqlstr = 'SELECT u.usercod, u.useremail, u.username, u.userpswd, u.userest, u.usertipo FROM usuario u WHERE u.usercod = :usercod';
        $params = ['usercod' => $usercod];

        return self::obtenerUnRegistro($sqlstr, $params);
    }

    public static function getRolesByUsuario(int $usercod)
    {
        $sqlstr = "SELECT r.rolescod, r.rolesdsc, r.rolesest,
            COALESCE(ru.roleuserest, 'INA') AS roleuserest,
            CASE
                WHEN COALESCE(ru.roleuserest, 'INA') = 'ACT' THEN 'Activo'
                WHEN COALESCE(ru.roleuserest, 'INA') = 'INA' THEN 'Inactivo'
                ELSE 'Sin Asignar'
            END AS roleuserestDsc
        FROM roles r
        LEFT JOIN roles_usuarios ru
            ON r.rolescod = ru.rolescod
            AND ru.usercod = :usercod
        ORDER BY r.rolescod";
        $params = ['usercod' => $usercod];

        return self::obtenerRegistros($sqlstr, $params);
    }

    private static function hashPassword(string $password): string
    {
        $saltedPassword = hash_hmac('sha256', $password, Context::getContextByKey('PWD_HASH'));

        if (version_compare(phpversion(), '7.4.0', '<')) {
            return password_hash($saltedPassword, 1);
        }

        return password_hash($saltedPassword, '2y');
    }

    public static function insertUsuario(
        string $useremail,
        string $username,
        string $userpswd,
        string $userest,
        string $usertipo
    ) {
        $sqlstr = 'INSERT INTO usuario (useremail, username, userpswd, userfching, userpswdest, userpswdexp, userest, useractcod, userpswdchg, usertipo) VALUES (:useremail, :username, :userpswd, now(), :userpswdest, DATE_ADD(now(), INTERVAL 90 DAY), :userest, :useractcod, now(), :usertipo)';
        $params = [
            'useremail' => $useremail,
            'username' => $username,
            'userpswd' => self::hashPassword($userpswd),
            'userpswdest' => 'ACT',
            'userest' => $userest,
            'useractcod' => hash('sha256', $useremail.time()),
            'usertipo' => $usertipo,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function updateUsuario(
        int $usercod,
        string $useremail,
        string $username,
        string $userpswd,
        string $userest,
        string $usertipo
    ) {
        $sqlstr = 'UPDATE usuario SET useremail = :useremail, username = :username, userest = :userest, usertipo = :usertipo WHERE usercod = :usercod';
        $params = [
            'usercod' => $usercod,
            'useremail' => $useremail,
            'username' => $username,
            'userest' => $userest,
            'usertipo' => $usertipo,
        ];

        if ($userpswd !== '') {
            $sqlstr = 'UPDATE usuario SET useremail = :useremail, username = :username,
                userpswd = :userpswd, userpswdchg = now(),
                userpswdexp = DATE_ADD(now(), INTERVAL 90 DAY),
                userest = :userest, usertipo = :usertipo
                WHERE usercod = :usercod';
            $params['userpswd'] = self::hashPassword($userpswd);
        }

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function upsertUsuarioRol(
        int $usercod,
        string $rolescod,
        string $roleuserest
    ) {
        if (!in_array($roleuserest, ['ACT', 'INA'])) {
            throw new \Exception('Error Processing Request roleuserest has invalid value');
        }

        $sqlstr = 'INSERT INTO roles_usuarios (usercod, rolescod, roleuserest, roleuserfch, roleuserexp)
            VALUES (:usercod, :rolescod, :roleuserest, now(), :roleuserexp)
            ON DUPLICATE KEY UPDATE
                roleuserest = :roleuserest,
                roleuserfch = now(),
                roleuserexp = :roleuserexp';
        $params = [
            'usercod' => $usercod,
            'rolescod' => $rolescod,
            'roleuserest' => $roleuserest,
            'roleuserexp' => $roleuserest === 'INA' ? date('Y-m-d H:i:s') : null,
        ];

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function deleteUsuario(int $usercod)
    {
        $sqlstr = 'DELETE FROM usuario WHERE usercod = :usercod';
        $params = ['usercod' => $usercod];

        return self::executeNonQuery($sqlstr, $params);
    }
}
