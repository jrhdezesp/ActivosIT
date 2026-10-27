<?php

namespace Controllers;

/**
 * Muestra la pagina publica inicial del sistema.
 */
class Index extends PublicController
{

    /** Presenta la pagina de inicio. */
    public function run() :void
    {
        $viewData = array();
        \Views\Renderer::render("index", $viewData);
    }
}
