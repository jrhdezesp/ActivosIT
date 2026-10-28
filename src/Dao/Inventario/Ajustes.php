<?php

namespace Dao\Inventario;

use Dao\Table;

/**
 * Acceso a datos para los ajustes manuales de inventario.
 *
 * Un ajuste modifica la existencia de un articulo controlado por cantidad y
 * deja el movimiento correspondiente en el Kardex (AJUSTE_POSITIVO o
 * AJUSTE_NEGATIVO), conservando el saldo anterior y el resultante.
 */
class Ajustes extends Table
{
    /** Articulos controlados por cantidad y activos. */
    public static function getArticulosOptions(): array
    {
        return self::obtenerRegistros(
            "SELECT id_articulo AS value,
                    CONCAT(codigo_interno_articulo, ' - ', nombre_articulo) AS label,
                    existencia_actual AS stock
             FROM articulos
             WHERE estado_registro = 1 AND tipo_control = 'CANTIDAD'
             ORDER BY nombre_articulo;",
            []
        );
    }

    /** Motivos configurados para ajustes de entrada o salida. */
    public static function getMotivosOptions(): array
    {
        return self::obtenerRegistros(
            "SELECT id_motivo AS value,
                    nombre_motivo AS label,
                    motivo_aplica_a AS aplica
             FROM motivos_ajustes_anulaciones
             WHERE estado_motivo = 1
               AND motivo_aplica_a IN ('AJUSTE_ENTRADA', 'AJUSTE_SALIDA')
             ORDER BY nombre_motivo;",
            []
        );
    }

    /** Existencia actual de un articulo. */
    public static function getExistencia(int $articuloId): ?int
    {
        $row = self::obtenerUnRegistro(
            "SELECT existencia_actual FROM articulos
             WHERE id_articulo = :id AND estado_registro = 1 LIMIT 1;",
            ["id" => $articuloId]
        );
        if (!is_array($row)) {
            return null;
        }
        return intval($row["existencia_actual"]);
    }

    /**
     * Registra el ajuste y actualiza la existencia en una sola transaccion.
     *
     * @param int $articuloId Articulo ajustado.
     * @param string $tipo POSITIVO o NEGATIVO.
     * @param int $cantidad Unidades del ajuste (mayor que cero).
     * @param int $motivoId Motivo configurado.
     * @param string $observacion Justificacion del ajuste.
     * @param int $usuarioId Usuario que registra.
     * @return array{saldoAnterior:int, saldoNuevo:int, articulo:string}
     */
    public static function registrarAjuste(
        int $articuloId,
        string $tipo,
        int $cantidad,
        int $motivoId,
        string $observacion,
        int $usuarioId
    ): array {
        self::beginTransaction();
        try {
            $articulo = self::obtenerUnRegistro(
                "SELECT id_articulo, nombre_articulo, existencia_actual
                 FROM articulos
                 WHERE id_articulo = :id AND estado_registro = 1
                 FOR UPDATE;",
                ["id" => $articuloId]
            );
            if (!is_array($articulo)) {
                throw new \RuntimeException('El articulo no existe o esta inactivo.');
            }

            $anterior = intval($articulo["existencia_actual"]);
            if ($tipo === 'NEGATIVO' && $cantidad > $anterior) {
                throw new \RuntimeException(
                    'El ajuste negativo supera la existencia disponible (' . $anterior . ').'
                );
            }

            $nuevo = $tipo === 'POSITIVO' ? $anterior + $cantidad : $anterior - $cantidad;
            $tipoMovimiento = $tipo === 'POSITIVO' ? 'AJUSTE_POSITIVO' : 'AJUSTE_NEGATIVO';

            self::executeNonQuery(
                "UPDATE articulos
                 SET existencia_actual = :nueva, fecha_modificacion = NOW()
                 WHERE id_articulo = :id;",
                ["nueva" => $nuevo, "id" => $articuloId]
            );

            self::executeNonQuery(
                "INSERT INTO movimientos_inventario
                    (id_articulo, id_motivo, tipo_movimiento, cantidad,
                     existencia_anterior, existencia_nueva,
                     fecha_movimiento, id_usuario, observacion)
                 VALUES
                    (:articulo, :motivo, :tipo, :cantidad,
                     :anterior, :nueva,
                     NOW(), :usuario, :observacion);",
                [
                    "articulo" => $articuloId,
                    "motivo" => $motivoId,
                    "tipo" => $tipoMovimiento,
                    "cantidad" => $cantidad,
                    "anterior" => $anterior,
                    "nueva" => $nuevo,
                    "usuario" => $usuarioId,
                    "observacion" => $observacion,
                ]
            );

            self::commit();

            return [
                "saldoAnterior" => $anterior,
                "saldoNuevo" => $nuevo,
                "articulo" => strval($articulo["nombre_articulo"]),
            ];
        } catch (\Throwable $ex) {
            self::rollback();
            throw $ex;
        }
    }

    /** Ultimos ajustes registrados, para contexto en la pantalla. */
    public static function getUltimosAjustes(int $limite = 5): array
    {
        $limite = max(1, min(20, $limite));
        return self::obtenerRegistros(
            "SELECT m.fecha_movimiento, a.codigo_interno_articulo, a.nombre_articulo,
                    m.tipo_movimiento, m.cantidad, m.existencia_anterior,
                    m.existencia_nueva, us.nombre_usuario
             FROM movimientos_inventario m
             JOIN articulos a ON a.id_articulo = m.id_articulo
             LEFT JOIN usuarios us ON us.id_usuario = m.id_usuario
             WHERE m.tipo_movimiento IN ('AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO')
             ORDER BY m.id_movimiento DESC
             LIMIT " . $limite . ";",
            []
        );
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
