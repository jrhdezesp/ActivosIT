<?php

namespace Controllers;

use Views\Renderer;

/**
 * Muestra la pagina publica inicial del sistema.
 */
class Index extends PublicController
{

    /** Presenta la pagina de inicio. */
    public function run() :void
    {
        $viewData = array();
        Renderer::render("index", $viewData);
    }
}
