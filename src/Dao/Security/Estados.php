<?php

namespace Dao\Security;

/**
 * Reune los estados que puede tener una cuenta.
 */
final class Estados extends \Utilities\Enum
{
    const ACTIVO  = "ACT";
    const INACTIVO = "INA";
    const BLOQUEADO = "BLQ";
    const SUSPENDIDO = "SUS";
}
