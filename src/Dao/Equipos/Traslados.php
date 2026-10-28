<?php

namespace Dao\Equipos;

use Dao\Table;

/**
 * Acceso a datos para el traslado de equipos entre ubicaciones.
 *
 * El traslado actualiza la ubicacion actual del equipo y devuelve el nombre
 * de la ubicacion anterior y la nueva para dejarlo en la bitacora.
 */
class Traslados extends Table
{
    /** Equipos activos con su ubicacion actual. */
    public static function getEquiposOptions(): array
    {
        return self::obtenerRegistros(
            "SELECT e.id_equipo AS value,
                    CONCAT(
                        COALESCE(NULLIF(e.codigo_patrimonial_equipo, ''), CONCAT('EQ-', e.id_equipo)),
                        ' - ',
                        COALESCE(NULLIF(e.numero_serie_equipo, ''), 'sin serie')
                    ) AS label,
                    u.nombre_ubicacion AS ubicacion,
                    e.id_ubicacion AS ubicacion_id
             FROM equipos_individuales e
             JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion
             WHERE e.estado_registro = 1 AND e.estado_operativo <> 'DE_BAJA'
             ORDER BY e.id_equipo;",
            []
        );
    }

    /** Ubicaciones activas disponibles. */
    public static function getUbicacionesOptions(): array
    {
        return self::obtenerRegistros(
            "SELECT id_ubicacion AS value, nombre_ubicacion AS label
             FROM ubicaciones
             WHERE estado_ubi = 1
             ORDER BY nombre_ubicacion;",
            []
        );
    }

    /**
     * Cambia la ubicacion del equipo dentro de una transaccion.
     *
     * @param int $equipoId Equipo a trasladar.
     * @param int $ubicacionId Ubicacion destino.
     * @return array{anterior:string, nueva:string, equipo:string}
     */
    public static function trasladar(int $equipoId, int $ubicacionId): array
    {
        self::beginTransaction();
        try {
            $equipo = self::obtenerUnRegistro(
                "SELECT e.id_equipo,
                        COALESCE(NULLIF(e.codigo_patrimonial_equipo, ''), CONCAT('EQ-', e.id_equipo)) AS codigo,
                        e.id_ubicacion,
                        u.nombre_ubicacion
                 FROM equipos_individuales e
                 JOIN ubicaciones u ON u.id_ubicacion = e.id_ubicacion
                 WHERE e.id_equipo = :id AND e.estado_registro = 1
                 FOR UPDATE;",
                ["id" => $equipoId]
            );
            if (!is_array($equipo)) {
                throw new \RuntimeException('El equipo no existe o esta inactivo.');
            }

            $destino = self::obtenerUnRegistro(
                "SELECT id_ubicacion, nombre_ubicacion FROM ubicaciones
                 WHERE id_ubicacion = :id AND estado_ubi = 1 LIMIT 1;",
                ["id" => $ubicacionId]
            );
            if (!is_array($destino)) {
                throw new \RuntimeException('La ubicacion de destino no existe o esta inactiva.');
            }

            if (intval($equipo["id_ubicacion"]) === intval($destino["id_ubicacion"])) {
                throw new \RuntimeException('El equipo ya se encuentra en esa ubicacion.');
            }

            self::executeNonQuery(
                "UPDATE equipos_individuales SET id_ubicacion = :ubi WHERE id_equipo = :id;",
                ["ubi" => $ubicacionId, "id" => $equipoId]
            );

            self::commit();

            return [
                "anterior" => strval($equipo["nombre_ubicacion"]),
                "nueva" => strval($destino["nombre_ubicacion"]),
                "equipo" => strval($equipo["codigo"]),
            ];
        } catch (\Throwable $ex) {
            self::rollback();
            throw $ex;
        }
    }

    public static function beginTransaction(): void
    {
        if (!self::getConn()->inTransaction()) {
            self::getConn()->beginTransaction();
        }
    }

    public static function commit(): void
    {
        if (self::getConn()->inTransaction()) {
            self::getConn()->commit();
        }
    }

    public static function rollback(): void
    {
        if (self::getConn()->inTransaction()) {
            self::getConn()->rollBack();
        }
    }
}
