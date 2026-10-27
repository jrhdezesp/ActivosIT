<?php

namespace Controllers;

use Utilities\Context;
use Utilities\Security;
use Views\Renderer;

/**
 * Presenta una respuesta clara cuando una solicitud no puede completarse.
 */
class Error extends PublicController
{
    /** Selecciona el mensaje apropiado y presenta la pagina de error. */
    public function run(): void
    {
        $errorCode = intval(Context::getContextByKey("ERROR_CODE"));
        if ($errorCode < 400 || $errorCode > 599) {
            $errorCode = 404;
        }

        $errorContent = $this->getErrorContent($errorCode);

        http_response_code($errorCode);
        header("X-Robots-Tag: noindex, nofollow");
        Renderer::render(
            "error",
            [
                "CLIENT_ERROR_CODE" => $errorCode,
                "CLIENT_ERROR_TITLE" => $errorContent["title"],
                "CLIENT_ERROR_MSG" => $errorContent["message"],
                "CLIENT_ERROR_ICON" => $errorContent["icon"],
                "login" => Security::getUser()
            ]
        );
    }

    private function getErrorContent(int $errorCode): array
    {
        $errors = [
            400 => [
                "title" => "Solicitud no válida",
                "message" => "La solicitud contiene datos que no pudimos procesar. Revise la información e intente de nuevo.",
                "icon" => "fa-circle-exclamation"
            ],
            401 => [
                "title" => "Sesión requerida",
                "message" => "Debe iniciar sesión para acceder a este recurso.",
                "icon" => "fa-user-lock"
            ],
            403 => [
                "title" => "Acceso denegado",
                "message" => "No tiene permisos para acceder a este recurso.",
                "icon" => "fa-lock"
            ],
            404 => [
                "title" => "Página no encontrada",
                "message" => "La página que busca no existe o cambió de ubicación. Verifique la dirección o vuelva al inicio.",
                "icon" => "fa-map-location-dot"
            ],
            405 => [
                "title" => "Método no permitido",
                "message" => "La acción solicitada no está permitida en esta página.",
                "icon" => "fa-ban"
            ],
            408 => [
                "title" => "Tiempo de espera agotado",
                "message" => "La solicitud tardó demasiado. Intente de nuevo.",
                "icon" => "fa-clock"
            ],
            413 => [
                "title" => "Solicitud demasiado grande",
                "message" => "El contenido enviado supera el tamaño permitido.",
                "icon" => "fa-file-circle-exclamation"
            ],
            414 => [
                "title" => "Dirección demasiado larga",
                "message" => "La dirección solicitada es demasiado larga.",
                "icon" => "fa-link-slash"
            ],
            429 => [
                "title" => "Demasiadas solicitudes",
                "message" => "Se realizaron demasiadas solicitudes. Espere un momento e intente de nuevo.",
                "icon" => "fa-gauge-high"
            ],
            500 => [
                "title" => "Error interno del servidor",
                "message" => "Ocurrió un problema inesperado. Intente de nuevo en unos minutos.",
                "icon" => "fa-triangle-exclamation"
            ],
            501 => [
                "title" => "Función no disponible",
                "message" => "La función solicitada no está implementada.",
                "icon" => "fa-code"
            ],
            502 => [
                "title" => "Respuesta no válida",
                "message" => "El servidor recibió una respuesta no válida. Intente de nuevo.",
                "icon" => "fa-plug-circle-xmark"
            ],
            503 => [
                "title" => "Servicio no disponible",
                "message" => "El servicio no está disponible en este momento. Intente más tarde.",
                "icon" => "fa-server"
            ],
            504 => [
                "title" => "Tiempo de espera del servidor",
                "message" => "El servidor tardó demasiado en responder. Intente de nuevo.",
                "icon" => "fa-clock-rotate-left"
            ]
        ];

        if (isset($errors[$errorCode])) {
            return $errors[$errorCode];
        }

        if ($errorCode < 500) {
            return [
                "title" => "Solicitud no completada",
                "message" => "No pudimos completar la solicitud. Revise la información e intente de nuevo.",
                "icon" => "fa-circle-exclamation"
            ];
        }

        return [
            "title" => "Error del servidor",
            "message" => "Ocurrió un problema inesperado. Intente de nuevo en unos minutos.",
            "icon" => "fa-triangle-exclamation"
        ];
    }
}
