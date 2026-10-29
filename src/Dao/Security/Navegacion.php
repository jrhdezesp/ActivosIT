<?php

namespace Dao\Security;

use Dao\Table;

/**
 * Guarda el orden y la visibilidad elegidos para los elementos del menu.
 *
 * Solo se almacenan identificadores que ya existen en nav.config.json; la
 * definicion de rutas, iconos y permisos sigue en el archivo de configuracion.
 */
final class Navegacion extends Table
{
    /**
     * Devuelve las preferencias guardadas, indexadas por identificador de menu.
     *
     * @return array<string, array{orden:int, visible:bool}>
     */
    public static function getPreferencias(): array
    {
        try {
            $rows = self::obtenerRegistros(
                'SELECT nav_id, orden, visible FROM navegacion_preferencias;',
                []
            );
        } catch (\PDOException $ex) {
            // Una instalacion anterior puede no tener aun la tabla: en ese
            // caso el menu conserva el orden definido en nav.config.json.
            if (self::isMissingTable($ex)) {
                return [];
            }
            throw $ex;
        }

        $preferences = [];
        foreach ($rows as $row) {
            $preferences[strval($row['nav_id'])] = [
                'orden' => intval($row['orden']),
                'visible' => intval($row['visible']) === 1,
            ];
        }

        return $preferences;
    }

    /**
     * Guarda en una sola transaccion el orden y la visibilidad recibidos.
     *
     * @param array $preferences Lista de ['nav_id', 'orden', 'visible'].
     * @param int $usuarioId Cuenta que realizo el cambio.
     * @return void
     */
    public static function guardarPreferencias(array $preferences, int $usuarioId): void
    {
        $conn = self::getConn();
        $conn->beginTransaction();
        try {
            foreach ($preferences as $preference) {
                self::executeNonQuery(
                    'INSERT INTO navegacion_preferencias'
                    . ' (nav_id, orden, visible, id_usuario, fecha_modificacion)'
                    . ' VALUES (:nav, :orden, :visible, :usuario, NOW())'
                    . ' ON DUPLICATE KEY UPDATE orden = VALUES(orden),'
                    . ' visible = VALUES(visible), id_usuario = VALUES(id_usuario),'
                    . ' fecha_modificacion = NOW();',
                    [
                        'nav' => strval($preference['nav_id']),
                        'orden' => intval($preference['orden']),
                        'visible' => boolval($preference['visible']),
                        'usuario' => $usuarioId > 0 ? $usuarioId : null,
                    ]
                );
            }
            $conn->commit();
        } catch (\Throwable $ex) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw $ex;
        }
    }

    private static function isMissingTable(\PDOException $ex): bool
    {
        $driverCode = intval($ex->errorInfo[1] ?? 0);

        return strval($ex->getCode()) === '42S02' || $driverCode === 1146;
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }
}
