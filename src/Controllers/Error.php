<?php

namespace Controllers;

class Error extends PublicController
{
    public function run(): void
    {
        $error_code = intval(\Utilities\Context::getContextByKey("ERROR_CODE"));
        $error_code = $error_code > 0 ? $error_code : 404;
        $error_msg = "Ocurrió algo inesperado";
        if ($error_code === 404) {
            $error_msg = "No se encuentra el recurso solicitado.";
        } elseif ($error_code >= 500) {
            $error_msg = "Algo inesperado ocurrió en nuestro servicio.";
        }
        http_response_code($error_code);
        \Views\Renderer::render(
            "error",
            [
                "CLIENT_ERROR_CODE" => $error_code,
                "CLIENT_ERROR_MSG" => $error_msg
            ]
        );
    }
}
