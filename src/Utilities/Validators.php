<?php

namespace Utilities;

// Reune validaciones sencillas para datos ingresados en formularios.
class Validators {

    // Indica si un valor esta vacio despues de quitar espacios.
    static public function IsEmpty($valor)
    {
        return trim((string) $valor) === "";
    }

    // Comprueba que el valor tenga formato de correo electronico.
    static public function IsValidEmail($valor)
    {
        return filter_var((string) $valor, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Comprueba que la contrasena cumpla el formato requerido por el sistema.
    static public function IsValidPassword($valor){
        return preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,32}$/", $valor) && true;
    }

    private function __construct()
    {
        
    }
    private function __clone()
    {
        
    }
}
