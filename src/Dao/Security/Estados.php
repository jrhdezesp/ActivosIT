<?php

namespace Dao\Security;

use Utilities\Enum;

/**
 * Reune los estados que puede tener una cuenta.
 */
final class Estados extends Enum
{
    const ACTIVO  = "ACT";
    const INACTIVO = "INA";
    const BLOQUEADO = "BLQ";
    const SUSPENDIDO = "SUS";
}
