<?php
namespace Controllers\Sec;
class Logout extends \Controllers\PublicController
{
    public function run():void
    {
        $user = \Utilities\Security::getUser();
        if (is_array($user)) {
            $usuarioId = intval($user["userId"] ?? 0);
            \Utilities\Bitacora::registrar(
                'LOGOUT',
                'Seguridad',
                \Utilities\Bitacora::RESULTADO_EXITO,
                null,
                null,
                [
                    'email' => strval($user["userEmail"] ?? ''),
                    'nombre' => strval($user["userName"] ?? ''),
                ],
                $usuarioId > 0 ? $usuarioId : null
            );
        }
        \Utilities\Security::logout();
        \Utilities\Site::redirectTo("index.php");
    }
}
