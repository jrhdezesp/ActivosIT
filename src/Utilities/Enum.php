<?php

namespace Utilities;

use ReflectionClass;

// Provee operaciones comunes para clases que definen grupos de constantes.
abstract class Enum
{
    const NONE = null;
    private function __construct()
    {
        throw new NotSupportedException(); // 
    }
    private function __clone()
    {
        throw new NotSupportedException();
    }
    // Devuelve las constantes declaradas por la clase concreta.
    final public static function toArray()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
    // Devuelve las constantes en pares de codigo y valor para formularios.
    final public static function toFormatedArray()
    {
        $unFormated = (new ReflectionClass(static::class))->getConstants();
        $formated = array();
        foreach ($unFormated as $key => $value) {
            $formated[] = array("code" => $key, "value" => $value);
        }
        return $formated;
    }
    // Indica si un valor pertenece al grupo de constantes.
    final public static function isValid($value)
    {
        return in_array($value, static::toArray());
    }
}
