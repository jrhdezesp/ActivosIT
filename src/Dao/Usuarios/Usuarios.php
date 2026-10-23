<?php

namespace Dao\Usuarios;

use Dao\Table;
use Utilities\Context;

class Usuarios extends Table
{
    private static function hashPassword(string $password): string
    {
        $saltedPassword = hash_hmac('sha256', $password, Context::getContextByKey('PWD_HASH'));

        if (version_compare(phpversion(), '7.4.0', '<')) {
            return password_hash($saltedPassword, 1);
        }

        return password_hash($saltedPassword, '2y');
    }

    public static function getUsuarios(
        string $partialName = '',
        string $status = '',
        string $orderBy = '',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        $sqlstr = "SELECT
            u.id_usuario,
            u.user_usuario,
            u.nombre_usuario,
            u.estado_usuario,
            u.ultimo_acceso_usuario,
            u.id_usuario AS usercod,
            u.user_usuario AS useremail,
            u.nombre_usuario AS username,
            u.tipo_usuario AS usertipo,
            CASE WHEN u.estado_usuario = 1 THEN 'ACT' ELSE 'INA' END AS userest,
            CASE WHEN u.estado_usuario = 1 THEN 'Activo' ELSE 'Inactivo' END AS userestDsc
        FROM usuarios u";

        $sqlstrCount = 'SELECT COUNT(*) AS count FROM usuarios u';
        $conditions = [];
        $params = [];

        $partialName = trim($partialName);
        if ($partialName !== '') {
            $conditions[] = '(u.nombre_usuario LIKE :partialNameNombre OR u.user_usuario LIKE :partialNameEmail)';
            $params['partialNameNombre'] = '%' . $partialName . '%';
            $params['partialNameEmail'] = '%' . $partialName . '%';
        }

        if (!in_array($status, ['ACT', 'INA', '', '1', '0'], true)) {
            throw new \Exception('Error Processing Request Status has invalid value');
        }

        if ($status !== '') {
            $conditions[] = 'u.estado_usuario = :status';
            $params['status'] = in_array($status, ['ACT', '1'], true) ? 1 : 0;
        }

        if (count($conditions) > 0) {
            $whereClause = ' WHERE ' . implode(' AND ', $conditions);
            $sqlstr .= $whereClause;
            $sqlstrCount .= $whereClause;
        }

        $orderMap = [
            'usercod' => 'u.id_usuario',
            'username' => 'u.nombre_usuario',
            'useremail' => 'u.user_usuario',
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
            $sqlstr .= ' ORDER BY u.id_usuario ASC';
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
            'usuarios' => $registros,
            'total' => $numeroDeRegistros,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    public static function getUsuarioById(int $usercod)
    {
        $sqlstr = "SELECT
            u.id_usuario,
            u.user_usuario,
            u.nombre_usuario,
            u.estado_usuario,
            u.ultimo_acceso_usuario,
            u.id_usuario AS usercod,
            u.user_usuario AS useremail,
            u.nombre_usuario AS username,
            '' AS userpswd,
            u.tipo_usuario AS usertipo,
            CASE WHEN u.estado_usuario = 1 THEN 'ACT' ELSE 'INA' END AS userest,
            CASE WHEN u.estado_usuario = 1 THEN 'Activo' ELSE 'Inactivo' END AS userestDsc
        FROM usuarios u
        WHERE u.id_usuario = :usercod
        LIMIT 1";

        return self::obtenerUnRegistro($sqlstr, ['usercod' => $usercod]);
    }

    public static function getRolesByUsuario(int $usercod): array
    {
        $sqlstr = "SELECT
            r.id_rol,
            r.nombre_rol,
            r.descripcion_rol,
            r.estado_rol,
            r.nombre_rol AS rolescod,
            r.descripcion_rol AS rolesdsc,
            CASE WHEN r.estado_rol = 1 THEN 'ACT' ELSE 'INA' END AS rolesest,
            CASE WHEN ur.id_usuario IS NOT NULL THEN 'ACT' ELSE 'INA' END AS roleuserest,
            CASE WHEN ur.id_usuario IS NOT NULL THEN 'Activo' ELSE 'Inactivo' END AS roleuserestDsc
        FROM roles r
        LEFT JOIN usuarios_roles ur
            ON r.id_rol = ur.id_rol
            AND ur.id_usuario = :usercod
        WHERE r.estado_rol = 1
        ORDER BY r.nombre_rol";

        return self::obtenerRegistros($sqlstr, ['usercod' => $usercod]);
    }

    private static function normalizeTipo(string $usertipo): string
    {
        return in_array($usertipo, ['PBL', 'ADM', 'AUD', 'TEC'], true) ? $usertipo : 'PBL';
    }

    public static function insertUsuario(
        string $useremail,
        string $username,
        string $userpswd,
        string $userest,
        string $usertipo
    ): int {
        $estado = in_array($userest, ['ACT', '1'], true) ? 1 : 0;
        $hashed = self::hashPassword($userpswd);

        $sqlstr = 'INSERT INTO usuarios (user_usuario, nombre_usuario, clave_usuario, tipo_usuario, estado_usuario, ultimo_acceso_usuario)
            VALUES (:useremail, :username, :clave, :usertipo, :estado, NOW())';

        $params = [
            'useremail' => $useremail,
            'username' => $username,
            'clave' => $hashed,
            'usertipo' => self::normalizeTipo($usertipo),
            'estado' => $estado,
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
    ): int {
        $estado = in_array($userest, ['ACT', '1'], true) ? 1 : 0;

        if ($userpswd !== '') {
            $sqlstr = 'UPDATE usuarios
                SET user_usuario = :useremail,
                    nombre_usuario = :username,
                    clave_usuario = :clave,
                    tipo_usuario = :usertipo,
                    estado_usuario = :estado
                WHERE id_usuario = :usercod';
            $params = [
                'usercod' => $usercod,
                'useremail' => $useremail,
                'username' => $username,
                'clave' => self::hashPassword($userpswd),
                'usertipo' => self::normalizeTipo($usertipo),
                'estado' => $estado,
            ];
        } else {
            $sqlstr = 'UPDATE usuarios
                SET user_usuario = :useremail,
                    nombre_usuario = :username,
                    tipo_usuario = :usertipo,
                    estado_usuario = :estado
                WHERE id_usuario = :usercod';
            $params = [
                'usercod' => $usercod,
                'useremail' => $useremail,
                'username' => $username,
                'usertipo' => self::normalizeTipo($usertipo),
                'estado' => $estado,
            ];
        }

        return self::executeNonQuery($sqlstr, $params);
    }

    public static function upsertUsuarioRol(
        int $usercod,
        string $rolescod,
        string $roleuserest
    ): int {
        if (!in_array($roleuserest, ['ACT', 'INA'], true)) {
            throw new \Exception('Error Processing Request roleuserest has invalid value');
        }

        if ($roleuserest === 'ACT') {
            $sqlstr = "INSERT INTO usuarios_roles (id_usuario, id_rol)
                SELECT :usercod, r.id_rol
                FROM roles r
                WHERE r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt
                LIMIT 1
                ON DUPLICATE KEY UPDATE id_rol = VALUES(id_rol)";
            return self::executeNonQuery($sqlstr, [
                'usercod' => $usercod,
                'rolescod' => $rolescod,
                'rolescodInt' => intval($rolescod),
            ]);
        }

        // Si es INA, eliminamos la asignación del rol
        $sqlstr = "DELETE ur FROM usuarios_roles ur
            INNER JOIN roles r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :usercod
              AND (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt)";
        return self::executeNonQuery($sqlstr, [
            'usercod' => $usercod,
            'rolescod' => $rolescod,
            'rolescodInt' => intval($rolescod),
        ]);
    }

    public static function deleteUsuario(int $usercod): int
    {
        // Borrado lógico: la cuenta queda inactiva y conserva su historial,
        // asignaciones y bitácora. Nunca se elimina físicamente para no romper
        // las llaves foráneas de inventario/mantenimientos.
        $sqlstr = 'UPDATE usuarios SET estado_usuario = 0 WHERE id_usuario = :usercod';
        return self::executeNonQuery($sqlstr, ['usercod' => $usercod]);
    }
}
