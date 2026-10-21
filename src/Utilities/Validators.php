<?php

namespace Utilities;

class Validators {

    static public function IsEmpty($valor)
    {
        return trim((string) $valor) === "";
    }

    static public function IsValidEmail($valor)
    {
        return filter_var((string) $valor, FILTER_VALIDATE_EMAIL) !== false;
    }

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
