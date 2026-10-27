<?php

namespace Utilities;

// Valida y normaliza fechas y horas recibidas desde formularios.
class Fechas
{
    // Devuelve una fecha valida en formato AAAA-MM-DD o null si no es valida.
    public static function normalizarFecha(string $fechaEntrada): ?string
    {
        $fecha = trim($fechaEntrada);
        if ($fecha === '') {
            return null;
        }

        $fechaValidada = \DateTime::createFromFormat('Y-m-d', $fecha);
        if ($fechaValidada === false || $fechaValidada->format('Y-m-d') !== $fecha) {
            return null;
        }

        return $fecha;
    }

    // Devuelve una fecha valida en formato completo o null si no es valida.
    public static function normalizarFechaHora(string $fechaHoraEntrada): ?string
    {
        $fechaHora = trim($fechaHoraEntrada);
        if ($fechaHora === '') {
            return null;
        }

        $fechaHora = str_replace('T', ' ', $fechaHora);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $fechaHora)) {
            $fechaHora .= ':00';
        }

        $fechaValidada = \DateTime::createFromFormat('Y-m-d H:i:s', $fechaHora);
        if ($fechaValidada === false || $fechaValidada->format('Y-m-d H:i:s') !== $fechaHora) {
            return null;
        }

        return $fechaHora;
    }

    // Convierte una fecha y hora al formato de entrada del formulario.
    public static function paraCampoFechaHora(string $fechaHoraEntrada): string
    {
        $marcaTiempo = strtotime($fechaHoraEntrada);

        return $marcaTiempo !== false ? date('Y-m-d\TH:i', $marcaTiempo) : '';
    }

    // Presenta una fecha y hora en el formato visible del sistema.
    public static function paraMostrarFechaHora(string $fechaHoraEntrada): string
    {
        $marcaTiempo = strtotime($fechaHoraEntrada);

        return $marcaTiempo !== false ? date('d/m/Y H:i', $marcaTiempo) : '';
    }

    private function __construct()
    {
    }
}
