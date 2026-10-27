<?php

namespace Controllers;

use Utilities\Context;
use Utilities\Nav;
use Utilities\Security;

/**
 * Base comun para paginas publicas y para el contexto de presentacion.
 */
abstract class PublicController implements IController
{
    protected $name = "";

    /** Prepara la navegacion y el formato general de la pagina. */
    public function __construct()
    {
        $this->name = get_class($this);
        Nav::setPublicNavContext();
        if (Security::isLogged()){
            $layoutFile = Context::getContextByKey("PRIVATE_LAYOUT");
            if ($layoutFile !== "") {
                Context::setContext(
                    "layoutFile",
                    $layoutFile
                );
                Nav::setNavContext();
            }
        }
    }

    public function toString() :string
    {
        return $this->name;
    }

    /** Indica si la pagina recibio informacion de un formulario. */
    protected function isPostBack()
    {
        return $_SERVER["REQUEST_METHOD"] == "POST";
    }

}
