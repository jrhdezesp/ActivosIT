<?php

namespace Dao\Security;

use Utilities\Enum;

/**
 * Reune los tipos de cuenta reconocidos por el sistema.
 */
final class UsuarioTipo extends Enum
{
    const PUBLICO  = "PBL";
    const ADMINITRADAOR = "ADM";
    const TECNICO = "TEC";
}
