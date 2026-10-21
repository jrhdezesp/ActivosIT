<?php

namespace Utilities;

/**
 * Manejo de tokens CSRF por formulario.
 * El token se guarda en la sesion y se compara con hash_equals.
 */
class CSRF
{
    private const SESSION_KEY = '_csrf_tokens';

    public static function token(string $form): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        $form = self::normalize($form);
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        if (
            !isset($_SESSION[self::SESSION_KEY][$form])
            || !is_string($_SESSION[self::SESSION_KEY][$form])
            || $_SESSION[self::SESSION_KEY][$form] === ''
        ) {
            $_SESSION[self::SESSION_KEY][$form] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY][$form];
    }

    public static function validate(string $form, $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        $form = self::normalize($form);
        $expected = $_SESSION[self::SESSION_KEY][$form] ?? '';

        return is_string($token)
            && $token !== ''
            && is_string($expected)
            && $expected !== ''
            && hash_equals($expected, $token);
    }

    public static function requireValid(string $form, $token): void
    {
        if (!self::validate($form, $token)) {
            throw new \RuntimeException(
                'El formulario expiró o no es válido. Recargue la página e intente de nuevo.'
            );
        }
    }

    public static function field(string $form): string
    {
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s" />',
            htmlspecialchars(self::token($form), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    private static function normalize(string $form): string
    {
        $form = preg_replace('/[^a-zA-Z0-9_\-]/', '', $form);

        return $form === '' ? 'default' : $form;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
