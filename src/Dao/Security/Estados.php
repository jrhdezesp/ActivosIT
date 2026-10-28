<?php

namespace Dao\Security;

use Utilities\Enum;

/**
 * Reune los estados que puede tener una cuenta.
 */
final class Estados extends Enum
{
    // Cuenta habilitada para iniciar sesion.
    const ACTIVO  = "ACT";
    // Cuenta deshabilitada.
    const INACTIVO = "INA";
    // Cuenta bloqueada por seguridad.
    const BLOQUEADO = "BLQ";
    // Cuenta suspendida temporalmente.
    const SUSPENDIDO = "SUS";
}
