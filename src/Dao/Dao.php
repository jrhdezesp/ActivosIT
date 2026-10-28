<?php
namespace Dao;

use Utilities\Context;

/**
 * Crea y conserva la conexion PDO compartida por los DAOs.
 *
 * Variables:
 * - $_conn: conexion PDO reutilizada durante la solicitud.
 * - $dds: DSN alternativo para una conexion especifica.
 * - $user: usuario alternativo de la base de datos.
 * - $pswd: contrasena alternativa de la base de datos.
 */
class Dao {
    // Conexion compartida con la base de datos.
    private static $_conn = null;

    private function __construct()
    {
      
    }

    private function __clone()
    {

    }

    /**
     * Devuelve la conexion compartida o crea una nueva si no existe.
     *
     * @param string|null $dds DSN opcional.
     * @param string|null $user Usuario opcional.
     * @param string|null $pswd Contrasena opcional.
     * @return \PDO
     */
    public static function getConn($dds = null, $user = null, $pswd = null)
    {
        if (self::$_conn == null) {
            $_dds = sprintf(
                "%s:host=%s;dbname=%s;port=%s;charset=utf8mb4",
                Context::getContextByKey("DB_PROVIDER"),
                Context::getContextByKey("DB_SERVER"),
                Context::getContextByKey("DB_DATABASE"),
                Context::getContextByKey("DB_PORT")
            );
            $_user = Context::getContextByKey("DB_USER");
            $_pswd = Context::getContextByKey("DB_PSWD");
            if ($dds !== null) {
                $_dds = $dds;
            }
            if ($user !== null) {
                $_user = $user;
            }
            if ($pswd !== null) {
                $_pswd = $pswd;
            }
            self::$_conn = new \PDO(
                $_dds,
                $_user,
                $_pswd,
                array(
                  \PDO::ATTR_EMULATE_PREPARES => false,
                  \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                  \PDO::ATTR_PERSISTENT => false
                )
            );
        }
        return self::$_conn;
    }
}
