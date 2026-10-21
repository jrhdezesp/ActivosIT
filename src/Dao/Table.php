<?php

namespace Dao;

abstract class Table
{
    private static $_conn = null;
    protected static function getConn()
    {
        if (self::$_conn == null) {
            self::$_conn = Dao::getConn();
        }
        return self::$_conn;
    }
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
    protected static function getBindType($value)
    {
        $valueType = gettype($value);

        return self::$_bindMapping[$valueType];
    }

    protected static function obtenerRegistros($sqlstr, $params, &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key=>&$value) {
            $query->bindParam(":".$key, $value, self::getBindType($value));
        }
        $query->execute();
        $query->setFetchMode(\PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }

    protected static function obtenerUnRegistro($sqlstr, $params, &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key => &$value) {
            $query->bindParam(":" . $key, $value, self::getBindType($value));
        }
        $query->execute();
        $query->setFetchMode(\PDO::FETCH_ASSOC);

        return $query->fetch();
    }

    protected static function executeNonQuery($sqlstr, $params,  &$conn = null)
    {
        $pConn = null;
        if ($conn != null) {
            $pConn = $conn;
        } else {
            $pConn = self::getConn();
        }
        $query = $pConn->prepare($sqlstr);
        foreach ($params as $key => &$value) {
            $query->bindParam(":" . $key, $value, self::getBindType($value));
        }
        return $query->execute();
    }

    protected static function _getStructFrom($structure, $data)
    {
        if (is_array($data) && is_array($structure)) {
            $newData = $structure;
            foreach ($data as $itemKey => $itemVal) {
                if (isset($newData[$itemKey])) {
                    $newData[$itemKey] = $itemVal;
                }
            }
            return $newData;
        } else {
            return array();
        }
    }
}
