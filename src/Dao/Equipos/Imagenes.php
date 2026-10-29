<?php

namespace Dao\Equipos;

use Dao\Table;

/**
 * Acceso a las imagenes del estado de los equipos.
 *
 * Cada fila pertenece a un equipo y puede estar asociada a un mantenimiento.
 * El archivo fisico se guarda en storage/ y aqui solo se conserva su ruta.
 */
class Imagenes extends Table
{
    private const SELECT_FIELDS = 'SELECT i.*,'
        . ' u.nombre_usuario AS usuario_nombre, u.user_usuario AS usuario_cuenta'
        . ' FROM equipos_imagenes i'
        . ' LEFT JOIN usuarios u ON u.id_usuario = i.id_usuario_registro';

    /** Imagenes de un equipo, de la mas reciente a la mas antigua. */
    public static function getByEquipo(int $idEquipo): array
    {
        if ($idEquipo <= 0) {
            return [];
        }

        return self::obtenerRegistros(
            self::SELECT_FIELDS
            . ' WHERE i.id_equipo = :equipo'
            . ' ORDER BY i.fecha_registro DESC, i.id_imagen DESC',
            ['equipo' => $idEquipo]
        );
    }

    /** Evidencias de un mantenimiento concreto. */
    public static function getByMantenimiento(int $idMantenimiento): array
    {
        if ($idMantenimiento <= 0) {
            return [];
        }

        return self::obtenerRegistros(
            self::SELECT_FIELDS
            . ' WHERE i.id_mantenimiento = :mantenimiento'
            . ' ORDER BY i.fecha_registro DESC, i.id_imagen DESC',
            ['mantenimiento' => $idMantenimiento]
        );
    }

    /** Evidencias de una asignacion o devolucion concreta. */
    public static function getByAsignacion(int $idAsignacion): array
    {
        if ($idAsignacion <= 0) {
            return [];
        }

        return self::obtenerRegistros(
            self::SELECT_FIELDS
            . ' WHERE i.id_asignacion = :asignacion'
            . ' ORDER BY i.fecha_registro DESC, i.id_imagen DESC',
            ['asignacion' => $idAsignacion]
        );
    }

    /** Devuelve una imagen por su identificador. */
    public static function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            self::SELECT_FIELDS . ' WHERE i.id_imagen = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    /** Indica si el equipo ya tiene imagenes registradas. */
    public static function countByEquipo(int $idEquipo): int
    {
        if ($idEquipo <= 0) {
            return 0;
        }

        $row = self::obtenerUnRegistro(
            'SELECT COUNT(*) AS total FROM equipos_imagenes WHERE id_equipo = :equipo',
            ['equipo' => $idEquipo]
        );

        return intval($row['total'] ?? 0);
    }

    /**
     * Inserta varias imagenes en una sola transaccion.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public static function insertMany(array $rows): bool
    {
        if (count($rows) === 0) {
            return true;
        }

        $conn = self::getConn();
        $conn->beginTransaction();

        try {
            $statement = $conn->prepare(
                'INSERT INTO equipos_imagenes ('
                . 'id_equipo, ruta_imagen, nombre_original, tipo_mime,'
                . ' tamano_bytes, descripcion, contexto, id_mantenimiento,'
                . ' id_asignacion, id_usuario_registro'
                . ') VALUES ('
                . ':equipo, :ruta, :nombre, :mime, :tamano, :descripcion,'
                . ' :contexto, :mantenimiento, :asignacion, :usuario'
                . ')'
            );

            foreach ($rows as $row) {
                $statement->bindValue(':equipo', intval($row['id_equipo']), \PDO::PARAM_INT);
                $statement->bindValue(':ruta', strval($row['ruta_imagen']), \PDO::PARAM_STR);
                $statement->bindValue(
                    ':nombre',
                    $row['nombre_original'] !== null ? strval($row['nombre_original']) : null,
                    $row['nombre_original'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL
                );
                $statement->bindValue(':mime', strval($row['tipo_mime']), \PDO::PARAM_STR);
                $statement->bindValue(':tamano', intval($row['tamano_bytes']), \PDO::PARAM_INT);
                $statement->bindValue(
                    ':descripcion',
                    $row['descripcion'] !== null ? strval($row['descripcion']) : null,
                    $row['descripcion'] !== null ? \PDO::PARAM_STR : \PDO::PARAM_NULL
                );
                $statement->bindValue(':contexto', strval($row['contexto']), \PDO::PARAM_STR);
                $statement->bindValue(
                    ':mantenimiento',
                    intval($row['id_mantenimiento'] ?? 0) > 0
                        ? intval($row['id_mantenimiento'])
                        : null,
                    intval($row['id_mantenimiento'] ?? 0) > 0
                        ? \PDO::PARAM_INT
                        : \PDO::PARAM_NULL
                );
                $statement->bindValue(
                    ':asignacion',
                    intval($row['id_asignacion'] ?? 0) > 0
                        ? intval($row['id_asignacion'])
                        : null,
                    intval($row['id_asignacion'] ?? 0) > 0
                        ? \PDO::PARAM_INT
                        : \PDO::PARAM_NULL
                );
                $statement->bindValue(':usuario', intval($row['id_usuario_registro']), \PDO::PARAM_INT);
                $statement->execute();
            }

            $conn->commit();

            return true;
        } catch (\Throwable $ex) {
            $conn->rollBack();
            throw $ex;
        }
    }

    /** Elimina una imagen por su identificador. */
    public static function deleteById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return self::executeNonQuery(
            'DELETE FROM equipos_imagenes WHERE id_imagen = :id',
            ['id' => $id]
        );
    }
}
