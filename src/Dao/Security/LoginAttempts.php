<?php

namespace Dao\Security;

use Dao\Table;

/**
 * Controla los intentos fallidos de inicio de sesion por correo e IP.
 *
 * La tabla intentos_login se crea desde 01_security.sql. El bloqueo es
 * temporal y no modifica el estado de la cuenta.
 */
class LoginAttempts extends Table
{
    public const MAX_ATTEMPTS = 5;
    public const BLOCK_MINUTES = 15;

    /**
     * Indica si el correo y la IP estan bloqueados en este momento.
     *
     * @param string $email Correo ingresado.
     * @param string|null $ip Direccion IP del cliente.
     * @return bool
     */
    public static function isBlocked(string $email, ?string $ip): bool
    {
        $row = self::getStatus($email, $ip);
        if (!$row || empty($row['bloqueado_hasta'])) {
            return false;
        }

        return strtotime(strval($row['bloqueado_hasta'])) > time();
    }

    /**
     * Registra un intento fallido y devuelve el estado actualizado.
     *
     * @param string $email Correo ingresado.
     * @param string|null $ip Direccion IP del cliente.
     * @return array Estado del intento.
     */
    public static function registerFailure(string $email, ?string $ip): array
    {
        $email = self::normalizeEmail($email);
        $ip = self::normalizeIp($ip);
        $now = time();
        $row = self::getStatus($email, $ip);

        if (!$row) {
            self::executeNonQuery(
                'INSERT INTO intentos_login'
                . ' (email, direccion_ip, intentos, primer_intento, ultimo_intento, bloqueado_hasta)'
                . ' VALUES (:email, :ip, 1, NOW(), NOW(), NULL)',
                ['email' => $email, 'ip' => $ip]
            );

            return self::getStatus($email, $ip);
        }

        $attempts = intval($row['intentos']) + 1;
        $hasBlock = !empty($row['bloqueado_hasta'])
            && strtotime(strval($row['bloqueado_hasta'])) > $now;

        if ($hasBlock) {
            return $row;
        }

        // Reinicia el contador cuando termina el bloqueo temporal.
        if (!empty($row['bloqueado_hasta'])
            && strtotime(strval($row['bloqueado_hasta'])) <= $now) {
            $attempts = 1;
        }

        $blockedUntil = null;
        if ($attempts >= self::MAX_ATTEMPTS) {
            $blockedUntil = date('Y-m-d H:i:s', $now + (self::BLOCK_MINUTES * 60));
        }

        self::executeNonQuery(
            'UPDATE intentos_login'
            . ' SET intentos = :attempts, ultimo_intento = NOW(), bloqueado_hasta = :blocked'
            . ' WHERE email = :email AND direccion_ip = :ip',
            [
                'attempts' => $attempts,
                'blocked' => $blockedUntil,
                'email' => $email,
                'ip' => $ip,
            ]
        );

        return self::getStatus($email, $ip);
    }

    /**
     * Limpia el contador cuando el inicio de sesion es correcto.
     *
     * @param string $email Correo ingresado.
     * @param string|null $ip Direccion IP del cliente.
     * @return void
     */
    public static function clear(string $email, ?string $ip): void
    {
        $email = self::normalizeEmail($email);
        $ip = self::normalizeIp($ip);

        self::executeNonQuery(
            'DELETE FROM intentos_login WHERE email = :email AND direccion_ip = :ip',
            ['email' => $email, 'ip' => $ip]
        );
    }

    private static function getStatus(string $email, ?string $ip): array
    {
        $email = self::normalizeEmail($email);
        $ip = self::normalizeIp($ip);
        $row = self::obtenerUnRegistro(
            'SELECT id_intento, intentos, bloqueado_hasta'
            . ' FROM intentos_login WHERE email = :email AND direccion_ip = :ip LIMIT 1',
            ['email' => $email, 'ip' => $ip]
        );

        return is_array($row) ? $row : [];
    }

    private static function normalizeEmail(string $email): string
    {
        return mb_substr(trim(strtolower($email)), 0, 255, 'UTF-8');
    }

    private static function normalizeIp(?string $ip): string
    {
        return mb_substr(trim(strval($ip)), 0, 45, 'UTF-8');
    }
}
