<?php

use Utilities\Context;
use Utilities\Site;

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    exit('Falta la carpeta vendor. Ejecute "composer install" antes de iniciar la aplicación.');
}
require $autoloadPath;

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
session_name('ACTIVOSITSESSID');
$sessionPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
if ($sessionPath === '.' || $sessionPath === '') {
    $sessionPath = '/';
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionPath,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; "
    . "img-src 'self' data:; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com "
    . "https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; "
    . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "font-src 'self' data: https://fonts.gstatic.com "
    . "https://cdnjs.cloudflare.com https://ka-p.fontawesome.com; "
    . "connect-src 'self' https://ka-p.fontawesome.com"
);

try {
    Site::configure();
    $pageRequest = Site::getPageRequest();
    $instance = new $pageRequest();
    $instance->run();
    die();
} catch (\Controllers\PrivateNoAuthException $ex) {
    $instance = new \Controllers\NoAuth();
    $instance->run();
    die();
} catch (\Controllers\PrivateNoLoggedException $ex) {
    $redirTo = urlencode(Context::getContextByKey("request_uri"));
    Site::redirectTo("index.php?page=Sec_Login&redirto=" . $redirTo);
    die();
} catch (Exception $ex) {
    Site::logError($ex, 500);
    $instance = new \Controllers\Error();
    $instance->run();
    die();
} catch (Error $ex) {
    Site::logError($ex, 500);
    $instance = new \Controllers\Error();
    $instance->run();
    die();
}
