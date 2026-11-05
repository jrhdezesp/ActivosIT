<?php

namespace Utilities;

use Dao\Security\Security as DaoSecurity;

// Administra los datos de acceso y permisos durante la sesion.
class Security {
    // Tiempo maximo de inactividad antes de cerrar la sesion.
    private const SESSION_IDLE_SECONDS = 1500;
    private const SESSION_END_KEY = 'session_end_reason';

    private function __construct()
    {
        
    }
    private function __clone()
    {
        
    }
    // Cierra la sesion de acceso y actualiza los datos de navegacion.
    //
    // $revocarSesionActiva debe ser false cuando la sesion se cierra porque
    // otro dispositivo tomo el control: en ese caso el token vigente le
    // pertenece al dispositivo nuevo y no debe borrarse.
    public static function logout($revocarSesionActiva = true)
    {
        $login = $_SESSION["login"] ?? null;
        if ($revocarSesionActiva && is_array($login)) {
            $userId = intval($login["userId"] ?? 0);
            if ($userId > 0) {
                DaoSecurity::cerrarSesion($userId);
            }
        }
        unset($_SESSION["login"]);
        Nav::invalidateNavData();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            if (!headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => !empty($params['secure']),
                    'httponly' => !empty($params['httponly']),
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]);
            }
        }
    }
    // Guarda los datos basicos de la cuenta autenticada en la sesion.
    //
    // Cada inicio de sesion genera un token nuevo y lo registra en la cuenta.
    // Eso invalida de forma automatica la sesion abierta en otro dispositivo.
    public static function login($userId, $userName, $userEmail)
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION["login"] = array(
            "isLogged" => true,
            "userId" => $userId,
            "userName" => $userName,
            "userEmail" => $userEmail,
            "sessionToken" => $sessionToken,
            "lastActivity" => time()
        );
        unset($_SESSION[self::SESSION_END_KEY]);
        DaoSecurity::registrarSesionUnica(
            $userId,
            hash('sha256', $sessionToken),
            Bitacora::getClientIp()
        );
        Nav::invalidateNavData();
    }
    // Comprueba que la sesion actual siga siendo la vigente de la cuenta.
    //
    // Se ejecuta en cada peticion privada. Si otro dispositivo inicio sesion
    // despues, el token guardado ya no coincide con el de esta sesion.
    public static function assertActiveSession():bool
    {
        $login = $_SESSION["login"] ?? null;
        if (!is_array($login)) {
            return false;
        }

        $lastActivity = intval($login["lastActivity"] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) > self::SESSION_IDLE_SECONDS) {
            $_SESSION[self::SESSION_END_KEY] = 'sesion_expirada';
            return false;
        }

        $sessionToken = strval($login["sessionToken"] ?? "");
        if ($sessionToken === "") {
            $_SESSION[self::SESSION_END_KEY] = 'sesion_reemplazada';
            return false;
        }
        $tokenGuardado = DaoSecurity::getSesionToken(intval($login["userId"] ?? 0));
        if ($tokenGuardado === null) {
            $_SESSION[self::SESSION_END_KEY] = 'sesion_reemplazada';
            return false;
        }
        if (!hash_equals($tokenGuardado, hash('sha256', $sessionToken))) {
            $_SESSION[self::SESSION_END_KEY] = 'sesion_reemplazada';
            return false;
        }

        $_SESSION["login"]["lastActivity"] = time();
        return true;
    }

    /** Motivo por el que se cerro la sesion anterior. */
    public static function getSessionEndReason(): string
    {
        return strval($_SESSION[self::SESSION_END_KEY] ?? 'sesion_reemplazada');
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
        if (Context::getContextByKey("DEVELOPMENT") == "1") {
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
        if (Context::getContextByKey("DEVELOPMENT") == "1") {
            $rolInDb = DaoSecurity::getRol($rol);
            if (!$rolInDb) {
                DaoSecurity::addNewRol($rol, $rol, "ACT");
            }
        }
        return DaoSecurity::isUsuarioInRol($userId, $rol);
    }
}
