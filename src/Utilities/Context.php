<?php

namespace Utilities;

// Comparte valores de configuracion y solicitud entre componentes.
class Context
{
    static $_context = array();

    // Devuelve todos los valores guardados durante la solicitud.
    public static function getContext()
    {
        return self::$_context;
    }
    // Guarda un valor en memoria y, si se indica, tambien en la sesion.
    public static function setContext($key, $value, $saveToSession = false)
    {
        self::$_context[$key] = $value;
        if ($saveToSession) {
            $_SESSION[$key] = $value;
        }
    }
    // Busca primero el valor de la solicitud y luego el de la sesion.
    public static function getContextByKey($key)
    {
        $value = "";
        if (isset(self::$_context[$key])) {
            $value = self::$_context[$key];
        } else {
            if (isset($_SESSION[$key])) {
                $value = $_SESSION[$key];
            }
        }
        return $value;
    }
    // Guarda varios valores usando las mismas reglas de almacenamiento.
    public static function setArrayToContext(array $contextValues, $saveToSession = false)
    {
        foreach ($contextValues as $name => $value) {
            self::$_context[$name] = $value;
            if ($saveToSession) {
                $_SESSION[$name] = $value;
            }
        }
    }
    // Elimina un valor tanto de la solicitud como de la sesion.
    public static function removeContextByKey($key)
    {
        if (isset(self::$_context[$key])) {
            unset(self::$_context[$key]);
        }
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    private function __construct()
    {
    }
}
