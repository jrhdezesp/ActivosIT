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
     * Solo se usa REMOTE_ADDR. No se confia en cabeceras como
     * X-Forwarded-For porque el cliente puede falsificarlas.
     */
    public static function getClientIp(): ?string
    {
        $ip = trim(strval($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ip;
    }
}
