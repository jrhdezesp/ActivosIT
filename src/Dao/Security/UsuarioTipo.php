<?php

namespace Dao\Security;

use Utilities\Enum;

/**
 * Reune los tipos de cuenta reconocidos por el sistema.
 */
final class UsuarioTipo extends Enum
{
    // Perfil de cuenta publica.
    const PUBLICO  = "PBL";
    // Perfil con acceso administrativo.
    const ADMINITRADAOR = "ADM";
    // Perfil de soporte tecnico.
    const TECNICO = "TEC";
}
