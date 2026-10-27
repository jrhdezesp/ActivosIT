<?php
namespace Controllers\Sec;

use Controllers\PublicController;
use Utilities\Bitacora;
use Utilities\Security;
use Utilities\Site;

/**
 * Cierra la sesion actual y regresa a la pagina de inicio.
 */
class Logout extends PublicController
{
    /** Registra la salida de la cuenta y finaliza su sesion. */
    public function run():void
    {
        $user = Security::getUser();
        if (is_array($user)) {
            $usuarioId = intval($user["userId"] ?? 0);
            Bitacora::registrar(
                'LOGOUT',
                'Seguridad',
                Bitacora::RESULTADO_EXITO,
                null,
                null,
                [
                    'email' => strval($user["userEmail"] ?? ''),
                    'nombre' => strval($user["userName"] ?? ''),
                ],
                $usuarioId > 0 ? $usuarioId : null
            );
        }
        Security::logout();
        Site::redirectTo("index.php");
    }
}
