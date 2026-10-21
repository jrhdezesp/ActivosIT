<?php
namespace Controllers;

class NoAuth extends PublicController
{
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
