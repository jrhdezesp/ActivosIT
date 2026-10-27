<?php

namespace Utilities;

use Dao\Security\Security as DaoSecurity;

// Administra los datos de acceso y permisos durante la sesion.
class Security {
    private function __construct()
    {
        
    }
    private function __clone()
    {
        
    }
    // Cierra la sesion de acceso y actualiza los datos de navegacion.
    public static function logout()
    {
        unset($_SESSION["login"]);
        \Utilities\Nav::invalidateNavData();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    // Guarda los datos basicos de la cuenta autenticada en la sesion.
    public static function login($userId, $userName, $userEmail)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION["login"] = array(
            "isLogged" => true,
            "userId" => $userId,
            "userName" => $userName,
            "userEmail" => $userEmail
        );
        \Utilities\Nav::invalidateNavData();
    }
    // Indica si existe una cuenta autenticada en la sesion actual.
    public static function isLogged():bool
    {
        return isset($_SESSION["login"]["isLogged"]) && $_SESSION["login"]["isLogged"] === true;
    }
    // Devuelve los datos de la cuenta autenticada.
    public static function getUser()
    {
        if (isset($_SESSION["login"])) {
            return $_SESSION["login"];
        }
        return false;
    }
    // Devuelve el identificador de la cuenta autenticada.
    public static function getUserId()
    {
        if (isset($_SESSION["login"])) {
            return $_SESSION["login"]["userId"];
        }
        return 0;
    }
    // Consulta si la cuenta puede utilizar una funcion del sistema.
    public static function isAuthorized($userId, $function, $type = 'FNC'):bool
    {
        if (\Utilities\Context::getContextByKey("DEVELOPMENT") == "1") {
            $functionInDb = DaoSecurity::getFeature($function);
            if (!$functionInDb) {
                DaoSecurity::addNewFeature($function, $function, "ACT", $type);
            }
        }
        return DaoSecurity::getFeatureByUsuario($userId, $function, $type);
    }
    // Consulta si la cuenta pertenece al rol indicado.
    public static function isInRol($userId, $rol):bool
    {
        if (\Utilities\Context::getContextByKey("DEVELOPMENT") == "1") {
            $rolInDb = DaoSecurity::getRol($rol);
            if (!$rolInDb) {
                DaoSecurity::addNewRol($rol, $rol, "ACT");
            }
        }
        return DaoSecurity::isUsuarioInRol($userId, $rol);
    }
}
