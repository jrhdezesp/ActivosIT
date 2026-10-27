<?php
namespace Controllers;

use Utilities\Context;
use Utilities\Security;
use Views\Renderer;

/**
 * Informa que la cuenta actual no puede acceder a la seccion solicitada.
 */
class NoAuth extends PublicController
{
    /** Muestra el aviso de acceso no permitido. */
    public function run() :void
    {
        http_response_code(403);
        if (Security::isLogged()){
            if (Context::getContextByKey("PRIVATE_LAYOUT") !== "") {
                Renderer::render(
                    "noauth",
                    array(),
                    Context::getContextByKey("PRIVATE_LAYOUT")
                );
            } else {
                Renderer::render("noauth", array());
            }
        } else {
            Renderer::render("noauth", array());
        }
    }
}
