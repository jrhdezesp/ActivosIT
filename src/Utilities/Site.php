<?php

namespace Utilities;

class Site
{
    public static function configure()
    {
        $donenv = new \Utilities\DotEnv(__DIR__ . "/../../parameters.env");
        \Utilities\Context::setArrayToContext($donenv->load());

        $requiredKeys = [
            "DB_PROVIDER",
            "DB_SERVER",
            "DB_PORT",
            "DB_DATABASE",
            "DB_USER",
            "TIMEZONE",
            "PWD_HASH"
        ];
        $missingKeys = array();
        foreach ($requiredKeys as $key) {
            if (trim((string) \Utilities\Context::getContextByKey($key)) === "") {
                $missingKeys[] = $key;
            }
        }
        if (count($missingKeys) > 0) {
            throw new \RuntimeException(
                "Faltan variables obligatorias en parameters.env: " . implode(", ", $missingKeys)
            );
        }
        if (\Utilities\Context::getContextByKey("PWD_HASH") === "ChangeThisSecretAsap") {
            throw new \RuntimeException(
                "PWD_HASH conserva el valor de ejemplo. Defina una cadena aleatoria y privada."
            );
        }

        date_default_timezone_set(\Utilities\Context::getContextByKey("TIMEZONE"));
        \Utilities\Context::setContext('CURRENT_YEAR', date("Y"));
    }
    public static function getPageRequest()
    {
        $pageRequest = Context::getContextByKey("PUBLIC_DEFAULT_CONTROLLER");
        if (\Utilities\Security::isLogged()) {
            $pageRequest = Context::getContextByKey("PRIVATE_DEFAULT_CONTROLLER");
        }
        if (isset($_GET["page"]) && is_string($_GET["page"])) {
            $pageRequest = str_replace(array("_", "-", "."), "\\", $_GET["page"]);
        }
        // Solo se expone al contexto el parametro redirto. No se deben volcar
        // todos los parametros GET porque podrian sobrescribir configuracion
        // como DEVELOPMENT, DB_* o PWD_HASH.
        $redirto = $_GET["redirto"] ?? "";
        Context::setContext("redirto", is_string($redirto) ? $redirto : "");
        Context::setContext("request_uri", $_SERVER["REQUEST_URI"] ?? "");
        $pageRequest = "Controllers\\" . $pageRequest;

        // Solo se permite instanciar controladores registrados.
        if (
            !class_exists($pageRequest)
            || !is_subclass_of($pageRequest, \Controllers\IController::class)
        ) {
            Context::setContext("ERROR_CODE", 404);
            return "Controllers\\Error";
        }

        return $pageRequest;
    }
    public static function redirectTo($url)
    {
        if (!self::isLocalUrl($url)) {
            $url = "index.php";
        }
        if (Context::getContextByKey("USE_URLREWRITE") == "1") {
            header("Location:" . \Views\Renderer::rewriteUrl($url));
        } else {
            header("Location:" . $url);
        }

        die();
    }
    public static function redirectToWithMsg($url, $msg)
    {
        if (!self::isLocalUrl($url)) {
            $url = "index.php";
        }
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        echo '<script>alert(' . json_encode((string) $msg, $jsonFlags) . ');';
        echo ' window.location.assign(' . json_encode((string) $url, $jsonFlags) . ');</script>';
        die();
    }
    /**
     * Redireccion a un destino externo de confianza.
     * No usar con URLs provenientes directamente del usuario.
     */
    public static function redirectToExternal($url)
    {
        $url = trim((string) $url);
        if (!preg_match('#^https?://#i', $url) || preg_match('/[\r\n]/', $url)) {
            self::redirectTo("index.php");
        }
        header("Location:" . $url);
        die();
    }
    /**
     * Evita redirecciones abiertas: solo acepta rutas internas.
     */
    public static function isLocalUrl($url): bool
    {
        $url = trim((string) $url);
        if ($url === "" || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }
        // Esquema (http:, javascript:, data:, etc.)
        if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $url)) {
            return false;
        }
        // Barras invertidas: algunos navegadores las interpretan como barras
        // normales y permitirian una redireccion a otro dominio.
        if (strpos($url, "\\") !== false) {
            return false;
        }
        // URLs protocol-relative (//dominio.com)
        if (substr($url, 0, 2) === "//") {
            return false;
        }

        return true;
    }
    public static function addLink($href)
    {
        $tmpLinks = \Utilities\Context::getContextByKey("SiteLinks");
        if ($tmpLinks === "") {
            $tmpLinks = array($href);
        } else {
            $tmpLinks[] = $href;
        }
        \Utilities\Context::setContext("SiteLinks", $tmpLinks);
    }
    public static function addBeginScript($src)
    {
        $tmpSrcs = \Utilities\Context::getContextByKey("BeginScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        \Utilities\Context::setContext("BeginScripts", $tmpSrcs);
    }
    public static function addEndScript($src)
    {
        $tmpSrcs = \Utilities\Context::getContextByKey("EndScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        \Utilities\Context::setContext("EndScripts", $tmpSrcs);
    }
    public static function logError($ex, $errorCode)
    {
        error_log($ex);
        \Utilities\Context::setContext("ERROR_CODE", $errorCode);
        \Utilities\Context::setContext("ERROR_MSG", (string) $ex);
    }
}
