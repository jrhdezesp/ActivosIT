<?php

namespace Utilities;

// Normaliza fechas y horas recibidas desde formularios.
class DateHelper
{
    // Devuelve una fecha valida en formato completo o null si no es valida.
    public static function normalizeDateTime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            return null;
        }

        return $value;
    }

    private function __construct()
    {
    }
}
