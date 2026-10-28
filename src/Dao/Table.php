<?php

namespace Dao;

/**
 * Utilidades comunes de acceso a datos para los DAOs.
 *
 * Variables:
 * - $_conn: conexion PDO compartida.
 * - $_bindMapping: equivalencia entre tipos PHP y tipos PDO.
 */
abstract class Table
{
    // Conexion reutilizada por las operaciones comunes del DAO.
    private static $_conn = null;
    /**
     * Obtiene la conexion compartida del DAO.
     *
     * @return \PDO
     */
    protected static function getConn()
    {
        if (self::$_conn == null) {
            self::$_conn = Dao::getConn();
        }
        return self::$_conn;
    }
    // Relacion entre el tipo recibido y el tipo usado al enlazar parametros.
    private static $_bindMapping = array(
        "boolean" => \PDO::PARAM_BOOL,
        "integer" => \PDO::PARAM_INT,
        "double"  => \PDO::PARAM_STR,
        "string" => \PDO::PARAM_STR,
        "array" => \PDO::PARAM_STR,
        "object" => \PDO::PARAM_STR,
        "resource" => \PDO::PARAM_STR,
        "NULL" => \PDO::PARAM_NULL,
        "unknown type" => \PDO::PARAM_STR
    );
    /**
     * Devuelve el tipo PDO correspondiente al valor recibido.
     *
     * @param mixed $value Valor que se enlazara a la consulta.
     * @return int Tipo PDO.
     */
    protected static function getBindType($value)
    {
        $valueType = gettype($value);

        return self::$_bindMapping[$valueType];
    }

    /**
     * Ejecuta una consulta y devuelve todos los registros.
     *
     * @param string $sqlstr Consulta SQL con marcadores nombrados.
     * @param array $params Valores asociados a los marcadores.
     * @param \PDO|null $conn Conexion opcional; si se omite, usa la compartida.
     * @return array Registros encontrados.
     */
    protected static function obtenerRegistros($sqlstr, $params, &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key => $value) {
            $query->bindValue(":" . $key, $value, self::getBindType($value));
        }
        $query->execute();
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }

    /**
     * Ejecuta una consulta y devuelve el primer registro.
     *
     * @param string $sqlstr Consulta SQL con marcadores nombrados.
     * @param array $params Valores asociados a los marcadores.
     * @param \PDO|null $conn Conexion opcional; si se omite, usa la compartida.
     * @return array|null Primer registro o null si no hay resultados.
     */
    protected static function obtenerUnRegistro($sqlstr, $params, &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key => $value) {
            $query->bindValue(":" . $key, $value, self::getBindType($value));
        }
        $query->execute();
        $query->setFetchMode(\PDO::FETCH_ASSOC);

        return $query->fetch();
    }

    /**
     * Ejecuta una consulta de escritura.
     *
     * @param string $sqlstr Consulta SQL con marcadores nombrados.
     * @param array $params Valores asociados a los marcadores.
     * @param \PDO|null $conn Conexion opcional; si se omite, usa la compartida.
     * @return bool Resultado de la ejecucion.
     */
    protected static function executeNonQuery($sqlstr, $params,  &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key => $value) {
            $query->bindValue(":" . $key, $value, self::getBindType($value));
        }
        return $query->execute();
    }
}
