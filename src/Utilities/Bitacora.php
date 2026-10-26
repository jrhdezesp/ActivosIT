<?php

namespace Utilities;

use Dao\Auditoria\Bitacora as DaoBitacora;

/**
 * API central para registrar eventos en la bitacora de auditoria.
 *
 * El registro es tolerante a fallos: cualquier error al escribir la
 * bitacora se registra en el log del servidor y nunca interrumpe la
 * operacion de negocio que se estaba ejecutando.
 */
class Bitacora
{
    public const RESULTADO_EXITO = 'EXITO';
    public const RESULTADO_DENEGADO = 'DENEGADO';
    public const RESULTADO_ERROR = 'ERROR';

    public static function registrar(
        string $tipoOperacion,
        string $moduloAfectado,
        string $resultado = self::RESULTADO_EXITO,
        ?int $registroRelacionadoId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?int $usuarioId = null
    ): bool {
        try {
            if ($usuarioId === null) {
                $usuarioId = intval(Security::getUserId());
            }
            if ($usuarioId <= 0) {
                $usuarioId = null;
            }

            return DaoBitacora::insertEvento(
                $usuarioId,
                $tipoOperacion,
                $moduloAfectado,
                $registroRelacionadoId,
                self::getClientIp(),
                $datosAnteriores,
                $datosNuevos,
                $resultado
            );
        } catch (\Throwable $ex) {
            error_log(
                'No se pudo registrar el evento de auditoria '
                . $tipoOperacion . ' / ' . $moduloAfectado . ': ' . $ex->getMessage()
            );
            return false;
        }
    }

    /**
     * Obtiene la direccion IP del cliente. Solo se usa REMOTE_ADDR como
     * fuente primaria porque el cliente puede falsificar cabeceras como
     * X-Forwarded-For. Se normaliza ::1 a 127.0.0.1 para consistencia.
     */
    public static function getClientIp(): ?string
    {
        $ip = trim(strval($_SERVER['REMOTE_ADDR'] ?? ''));

        // Eliminar puerto si viene incluido (ej: 127.0.0.1:8080)
        if (strpos($ip, ':') !== false && strpos($ip, '::') === false) {
            $parts = explode(':', $ip);
            $ip = $parts[0];
        }

        // Normalizar IPv6 loopback a IPv4
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }
}
