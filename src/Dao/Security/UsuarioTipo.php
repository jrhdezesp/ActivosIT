<?php

namespace Dao\Security;

/**
 * Reune los tipos de cuenta reconocidos por el sistema.
 */
final class UsuarioTipo extends \Utilities\Enum
{
    const PUBLICO  = "PBL";
    const ADMINITRADAOR = "ADM";
    const TECNICO = "TEC";
}
