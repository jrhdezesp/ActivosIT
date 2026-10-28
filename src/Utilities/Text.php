<?php

namespace Utilities;

/**
 * Utilidades de texto compartidas por los controladores.
 */
class Text
{
    /**
     * Longitud de un texto en caracteres (no en bytes).
     *
     * Usa mb_strlen cuando la extension esta disponible y cae a strlen en
     * entornos minimos, igual que hacian los controladores por separado.
     */
    public static function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
