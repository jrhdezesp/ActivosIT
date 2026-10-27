<?php

namespace Utilities;

use Controllers\IController;
use Views\Renderer;

// Coordina la configuracion general y el flujo de cada solicitud.
class Site
{
    // Carga la configuracion y prepara valores generales del sistema.
    public static function configure()
    {
        $donenv = new DotEnv(__DIR__ . "/../../parameters.env");
        Context::setArrayToContext($donenv->load());

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
            if (trim((string) Context::getContextByKey($key)) === "") {
                $missingKeys[] = $key;
            }
        }
        if (count($missingKeys) > 0) {
            throw new \RuntimeException(
                "Faltan variables obligatorias en parameters.env: " . implode(", ", $missingKeys)
            );
        }
        if (Context::getContextByKey("PWD_HASH") === "ChangeThisSecretAsap") {
            throw new \RuntimeException(
                "PWD_HASH conserva el valor de ejemplo. Defina una cadena aleatoria y privada."
            );
        }

        date_default_timezone_set(Context::getContextByKey("TIMEZONE"));
        Context::setContext('CURRENT_YEAR', date("Y"));
    }
    // Determina que controlador atendera la solicitud actual.
    public static function getPageRequest()
    {
        $hasExplicitPage = isset($_GET["page"]);
        $pageRequest = Context::getContextByKey("PUBLIC_DEFAULT_CONTROLLER");
        if (Security::isLogged()) {
            $pageRequest = Context::getContextByKey("PRIVATE_DEFAULT_CONTROLLER");
            if ($pageRequest === "" || strcasecmp($pageRequest, "Index") === 0) {
                $pageRequest = "Dashboard_Dashboard";
            }
        }
        $pageRequestIsValid = true;
        if (isset($_GET["page"])) {
            if (!is_string($_GET["page"]) || trim($_GET["page"]) === "") {
                $pageRequestIsValid = false;
            } else {
                $pageRequest = str_replace(array("_", "-", "."), "\\", $_GET["page"]);
            }
        }
        // Solo se expone al contexto el parametro redirto. No se deben volcar
        // todos los parametros GET porque podrian sobrescribir configuracion
        // como DEVELOPMENT, DB_* o PWD_HASH.
        $redirto = $_GET["redirto"] ?? "";
        Context::setContext("redirto", is_string($redirto) ? $redirto : "");
        Context::setContext("request_uri", $_SERVER["REQUEST_URI"] ?? "");
        $pageRequest = "Controllers\\"
            . str_replace(array("_", "-", "."), "\\", $pageRequest);

        if (Security::isLogged()) {
            $publicEntryPoints = array(
                "Controllers\\Index",
                "Controllers\\Sec\\Login",
                "Controllers\\Sec\\Register",
                "Controllers\\NoAuth",
            );
            if ($hasExplicitPage && in_array($pageRequest, $publicEntryPoints, true)) {
                self::redirectTo("index.php?page=Dashboard_Dashboard");
            }
        } elseif ($pageRequest !== "Controllers\\Sec\\Login"
            && $pageRequest !== "Controllers\\Sec\\Logout") {
            self::redirectTo("index.php?page=Sec_Login");
        }

        // Solo se permite instanciar controladores concretos y registrados.
        if ($pageRequestIsValid && class_exists($pageRequest)) {
            $reflection = new \ReflectionClass($pageRequest);
            $pageRequestIsValid = $reflection->isInstantiable()
                && $reflection->implementsInterface(IController::class);
        } else {
            $pageRequestIsValid = false;
        }

        if (!$pageRequestIsValid) {
            Context::setContext("ERROR_CODE", 404);
            return "Controllers\\Error";
        }

        return $pageRequest;
    }
    // Redirige a una ruta local y finaliza la solicitud.
    public static function redirectTo($url)
    {
        if (!self::isLocalUrl($url)) {
            $url = "index.php";
        }
        if (Context::getContextByKey("USE_URLREWRITE") == "1") {
            header("Location:" . Renderer::rewriteUrl($url));
        } else {
            header("Location:" . $url);
        }

        die();
    }
    // Muestra un mensaje y luego redirige a una ruta local.
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
    // Agrega una hoja de estilo para que la plantilla la incluya.
    public static function addLink($href)
    {
        $tmpLinks = Context::getContextByKey("SiteLinks");
        if ($tmpLinks === "") {
            $tmpLinks = array($href);
        } else {
            $tmpLinks[] = $href;
        }
        Context::setContext("SiteLinks", $tmpLinks);
    }
    // Agrega un script para cargar al inicio de la pagina.
    public static function addBeginScript($src)
    {
        $tmpSrcs = Context::getContextByKey("BeginScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        Context::setContext("BeginScripts", $tmpSrcs);
    }
    // Agrega un script para cargar al final de la pagina.
    public static function addEndScript($src)
    {
        $tmpSrcs = Context::getContextByKey("EndScripts");
        if ($tmpSrcs === "") {
            $tmpSrcs = array($src);
        } else {
            $tmpSrcs[] = $src;
        }
        Context::setContext("EndScripts", $tmpSrcs);
    }
    // Guarda el codigo de error y registra el detalle en el log del servidor.
    public static function logError($ex, $errorCode)
    {
        error_log($ex);
        Context::setContext("ERROR_CODE", $errorCode);
    }
}
