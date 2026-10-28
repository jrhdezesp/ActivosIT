<?php

namespace Dao\Security;

use Dao\Table;
use Utilities\Context;

if (version_compare(phpversion(), '7.4.0', '<')) {
    define('PASSWORD_ALGORITHM', 1);
} else {
    define('PASSWORD_ALGORITHM', '2y');
}

/**
 * Acceso a datos de seguridad, cuentas y permisos.
 *
 * Variables:
 * - $email: correo usado para localizar una cuenta.
 * - $userId: clave primaria de la cuenta.
 * - $password: contrasena en texto antes del hash.
 * - $raw_password: contrasena recibida durante la verificacion.
 * - $hash_password: hash almacenado en la base.
 * - $fncod: codigo del permiso.
 * - $fndsc: descripcion del permiso.
 * - $fnest: estado del permiso.
 * - $fntyp: tipo de permiso.
 * - $userCod: clave primaria del usuario.
 * - $rolescod: identificador del rol.
 * - $rolesdsc: descripcion del rol.
 * - $rolesest: estado del rol.
 */
class Security extends Table
{
    /** Busca una cuenta mediante su correo. */
    public static function getUsuarioByEmail($email)
    {
        $sqlstr = "SELECT
            id_usuario,
            user_usuario,
            clave_usuario,
            nombre_usuario,
            estado_usuario,
            ultimo_acceso_usuario,
            id_usuario AS usercod,
            user_usuario AS useremail,
            nombre_usuario AS username,
            clave_usuario AS userpswd,
            CASE WHEN estado_usuario = 1 THEN 'ACT' ELSE 'INA' END AS userest
        FROM `usuarios`
        WHERE `user_usuario` = :useremail
        LIMIT 1;";

        return self::obtenerUnRegistro($sqlstr, ["useremail" => $email]);
    }

    /** Busca una cuenta mediante su identificador. */
    public static function getUsuarioById($userId)
    {
        $sqlstr = "SELECT
            id_usuario,
            user_usuario,
            clave_usuario,
            nombre_usuario,
            estado_usuario,
            ultimo_acceso_usuario,
            id_usuario AS usercod,
            user_usuario AS useremail,
            nombre_usuario AS username,
            clave_usuario AS userpswd,
            CASE WHEN estado_usuario = 1 THEN 'ACT' ELSE 'INA' END AS userest
        FROM `usuarios`
        WHERE `id_usuario` = :userId
        LIMIT 1;";

        return self::obtenerUnRegistro($sqlstr, ["userId" => intval($userId)]);
    }

    /** Guarda la fecha del acceso mas reciente de una cuenta. */
    public static function updateUltimoAcceso($userId): int
    {
        $sqlstr = "UPDATE `usuarios` SET `ultimo_acceso_usuario` = NOW() WHERE `id_usuario` = :userId;";
        return self::executeNonQuery($sqlstr, ["userId" => intval($userId)]);
    }

    /** Cambia la contrasena de una cuenta. */
    public static function updatePassword($userId, $password): int
    {
        $hashedPassword = self::_hashPassword($password);
        $sql = "UPDATE `usuarios` SET `clave_usuario` = :pwd WHERE `id_usuario` = :userId;";
        return self::executeNonQuery($sql, [
            "pwd" => $hashedPassword,
            "userId" => intval($userId),
        ]);
    }

    private static function _saltPassword($password)
    {
        return hash_hmac(
            "sha256",
            $password,
            Context::getContextByKey("PWD_HASH")
        );
    }

    private static function _hashPassword($password)
    {
        return password_hash(self::_saltPassword($password), PASSWORD_ALGORITHM);
    }

    /**
     * Genera el hash de una contrasena con el PWD_HASH configurado.
     *
     * @param string $password Contrasena en texto.
     * @return string Hash listo para guardar.
     */
    public static function hashPassword($password): string
    {
        return self::_hashPassword($password);
    }

    /** Comprueba si la contrasena ingresada corresponde a la cuenta. */
    public static function verifyPassword($raw_password, $hash_password): bool
    {
        if (empty($raw_password) || empty($hash_password)) {
            return false;
        }

        // 1. Verifica con hash salteado con PWD_HASH (estándar de la arquitectura)
        if (password_verify(self::_saltPassword($raw_password), $hash_password)) {
            return true;
        }

        // 2. Soporte para verificación directa por bcrypt estándar
        if (password_verify($raw_password, $hash_password)) {
            return true;
        }

        return false;
    }

    /** Indica si una funcion esta registrada en el sistema. */
    public static function getFeature($fncod): bool
    {
        $sqlstr = "SELECT id_permiso FROM `permisos` WHERE `nombre_permiso` = :fncod AND `estado_permiso` = 1 LIMIT 1;";
        $featuresList = self::obtenerRegistros($sqlstr, ["fncod" => $fncod]);
        return count($featuresList) > 0;
    }

    /** Registra o actualiza una funcion disponible para asignar. */
    public static function addNewFeature($fncod, $fndsc, $fnest = 'ACT', $fntyp = 'FNC')
    {
        $tipo = in_array($fntyp, ["CTR", "MNU", "MNT", "RPT", "ACC"], true) ? $fntyp : "CTR";
        $estado = in_array($fnest, ["ACT", "1", 1, true], true) ? 1 : 0;
        $moduloPorTipo = ["CTR" => "Controladores", "MNU" => "Menús", "MNT" => "Mantenimientos", "RPT" => "Reportes", "ACC" => "Acciones"];
        $sqlins = "INSERT INTO `permisos` (`nombre_permiso`, `descripcion_permiso`, `modulo_permiso`, `tipo_permiso`, `estado_permiso`)
            VALUES (:fncod, :fndsc, :modulo, :tipo, :estado)
            ON DUPLICATE KEY UPDATE
                `descripcion_permiso` = VALUES(`descripcion_permiso`),
                `tipo_permiso` = VALUES(`tipo_permiso`),
                `estado_permiso` = VALUES(`estado_permiso`);";

        return self::executeNonQuery($sqlins, [
            "fncod" => $fncod,
            "fndsc" => $fndsc,
            "modulo" => $moduloPorTipo[$tipo],
            "tipo" => $tipo,
            "estado" => $estado,
        ]);
    }

    /** Indica si la cuenta puede utilizar una funcion del sistema. */
    public static function getFeatureByUsuario($userCod, $fncod, $fntyp = null): bool
    {
        // 1. Administrador (id_rol = 1 o nombre_rol = 'Administrador') posee acceso global
        $sqlAdmin = "SELECT 1
            FROM `usuarios_roles` ur
            INNER JOIN `roles` r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
              AND r.estado_rol = 1
              AND (r.id_rol = 1 OR r.nombre_rol = 'Administrador')
            LIMIT 1;";

        if (count(self::obtenerRegistros($sqlAdmin, ["id_usuario" => intval($userCod)])) > 0) {
            return true;
        }

        // 2. Comprobar permisos asignados al rol del usuario
        $sqlstr = "SELECT 1
            FROM `permisos` p
            INNER JOIN `roles_permisos` rp ON rp.id_permiso = p.id_permiso
            INNER JOIN `roles` r ON r.id_rol = rp.id_rol AND r.estado_rol = 1
            INNER JOIN `usuarios_roles` ur ON ur.id_rol = r.id_rol
            WHERE p.nombre_permiso = :fncod
              AND p.estado_permiso = 1
              AND ur.id_usuario = :id_usuario
            LIMIT 1;";

        $resultados = self::obtenerRegistros($sqlstr, [
            "id_usuario" => intval($userCod),
            "fncod" => $fncod,
        ]);

        return count($resultados) > 0;
    }

    /** Indica si el rol esta registrado en el sistema. */
    public static function getRol($rolescod): bool
    {
        $sqlstr = "SELECT id_rol FROM `roles` WHERE `nombre_rol` = :rolescod OR `id_rol` = :rolescodInt LIMIT 1;";
        $featuresList = self::obtenerRegistros($sqlstr, [
            "rolescod" => $rolescod,
            "rolescodInt" => intval($rolescod),
        ]);
        return count($featuresList) > 0;
    }

    /** Registra un rol inicial cuando aun no existe. */
    public static function addNewRol($rolescod, $rolesdsc, $rolesest = 'ACT')
    {
        $estado = ($rolesest === 'INA' || $rolesest === '0' || $rolesest === 0) ? 0 : 1;
        $sqlins = "INSERT INTO `roles` (`nombre_rol`, `descripcion_rol`, `estado_rol`)
            VALUES (:rolescod, :rolesdsc, :estado_rol)
            ON DUPLICATE KEY UPDATE `descripcion_rol` = VALUES(`descripcion_rol`);";

        return self::executeNonQuery($sqlins, [
            "rolescod" => $rolescod,
            "rolesdsc" => $rolesdsc,
            "estado_rol" => $estado,
        ]);
    }

    /** Indica si una cuenta pertenece al rol indicado. */
    public static function isUsuarioInRol($userCod, $rolescod): bool
    {
        $sqlstr = "SELECT 1
            FROM `roles` r
            INNER JOIN `usuarios_roles` ur ON ur.id_rol = r.id_rol
            WHERE r.estado_rol = 1
              AND ur.id_usuario = :id_usuario
              AND (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt)
            LIMIT 1;";

        $resultados = self::obtenerRegistros($sqlstr, [
            "id_usuario" => intval($userCod),
            "rolescod" => $rolescod,
            "rolescodInt" => intval($rolescod),
        ]);
        return count($resultados) > 0;
    }

    /** Devuelve los roles relacionados con una cuenta. */
    public static function getRolesByUsuario($userCod): array
    {
        $sqlstr = "SELECT
            r.id_rol,
            r.nombre_rol,
            r.descripcion_rol,
            r.estado_rol,
            r.nombre_rol AS rolescod,
            r.descripcion_rol AS rolesdsc,
            CASE WHEN r.estado_rol = 1 THEN 'ACT' ELSE 'INA' END AS rolesest
        FROM `roles` r
        INNER JOIN `usuarios_roles` ur ON ur.id_rol = r.id_rol
        WHERE r.estado_rol = 1
          AND ur.id_usuario = :id_usuario;";

        return self::obtenerRegistros($sqlstr, ["id_usuario" => intval($userCod)]);
    }

    /** Quita de una cuenta la relacion con el rol indicado. */
    public static function removeRolFromUser($userCod, $rolescod)
    {
        $sqlstr = "DELETE ur FROM `usuarios_roles` ur
            INNER JOIN `roles` r ON r.id_rol = ur.id_rol
            WHERE ur.id_usuario = :id_usuario
              AND (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt);";

        return self::executeNonQuery($sqlstr, [
            "id_usuario" => intval($userCod),
            "rolescod" => $rolescod,
            "rolescodInt" => intval($rolescod),
        ]);
    }

    /** Quita del rol el acceso a la funcion indicada. */
    public static function removeFeatureFromRol($fncod, $rolescod)
    {
        $sqlstr = "DELETE rp FROM `roles_permisos` rp
            INNER JOIN `permisos` p ON p.id_permiso = rp.id_permiso
            INNER JOIN `roles` r ON r.id_rol = rp.id_rol
            WHERE p.nombre_permiso = :fncod
              AND (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt);";

        return self::executeNonQuery($sqlstr, [
            "fncod" => $fncod,
            "rolescod" => $rolescod,
            "rolescodInt" => intval($rolescod),
        ]);
    }

    /** Devuelve las funciones que aun no se han agregado al rol. */
    public static function getUnAssignedFeatures($rolescod): array
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
        FROM `permisos` p
        WHERE NOT EXISTS (
            SELECT 1 FROM `roles_permisos` rp
            INNER JOIN `roles` r ON r.id_rol = rp.id_rol
            WHERE rp.id_permiso = p.id_permiso
              AND (r.nombre_rol = :rolescod OR r.id_rol = :rolescodInt)
        )
        ORDER BY p.nombre_permiso;";

        return self::obtenerRegistros($sqlstr, [
            "rolescod" => $rolescod,
            "rolescodInt" => intval($rolescod),
        ]);
    }

    /** Devuelve los roles que aun no se han agregado a la cuenta. */
    public static function getUnAssignedRoles($userCod): array
    {
        $sqlstr = "SELECT
            r.id_rol,
            r.nombre_rol,
            r.descripcion_rol,
            r.estado_rol,
            r.nombre_rol AS rolescod,
            r.descripcion_rol AS rolesdsc,
            CASE WHEN r.estado_rol = 1 THEN 'ACT' ELSE 'INA' END AS rolesest
        FROM `roles` r
        WHERE r.estado_rol = 1
          AND NOT EXISTS (
              SELECT 1 FROM `usuarios_roles` ur
              WHERE ur.id_rol = r.id_rol
                AND ur.id_usuario = :id_usuario
          )
        ORDER BY r.nombre_rol;";

        return self::obtenerRegistros($sqlstr, ["id_usuario" => intval($userCod)]);
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
