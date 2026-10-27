<?php
namespace Controllers;

/**
 * Informa que la cuenta actual no puede acceder a la seccion solicitada.
 */
class NoAuth extends PublicController
{
    /** Muestra el aviso de acceso no permitido. */
    public function run() :void
    {
        http_response_code(403);
        if (\Utilities\Security::isLogged()){
            if (\Utilities\Context::getContextByKey("PRIVATE_LAYOUT") !== "") {
                \Views\Renderer::render(
                    "noauth",
                    array(),
                    \Utilities\Context::getContextByKey("PRIVATE_LAYOUT")
                );
            } else {
                \Views\Renderer::render("noauth", array());
            }
        } else {
            \Views\Renderer::render("noauth", array());
        }
    }
}
