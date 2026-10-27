<?php

namespace Dao\Articulos;

use Dao\Table;

class Articulos extends Table
{
    // Columnas permitidas para ordenar los articulos.
    private const ORDER_FIELDS = [
        'codigo_interno_articulo',
        'nombre_articulo',
        'existencia_actual',
        'existencia_minima',
        'fecha_creacion',
        'id_articulo',
    ];

    public static function getArticulos(
        string $search = '',
        string $status = '',
        int $clasificacionId = 0,
        string $orderBy = 'nombre_articulo',
        bool $orderDescending = false,
        int $page = 0,
        int $itemsPerPage = 10
    ): array {
        if (!in_array($status, ['', '0', '1'], true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        if (!in_array($orderBy, self::ORDER_FIELDS, true)) {
            $orderBy = 'nombre_articulo';
        }

        // Condiciones de busqueda y valores que se envian a la consulta.
        $conditions = [];
        $params = [];
        $search = trim($search);

        if ($search !== '') {
            $conditions[] = '(a.codigo_interno_articulo LIKE :searchCode'
                . ' OR a.nombre_articulo LIKE :searchName'
                . ' OR a.descripcion_articulo LIKE :searchDescription'
                . ' OR a.marca_articulo LIKE :searchBrand'
                . ' OR a.modelo_articulo LIKE :searchModel)';
            $searchValue = '%' . $search . '%';
            $params['searchCode'] = $searchValue;
            $params['searchName'] = $searchValue;
            $params['searchDescription'] = $searchValue;
            $params['searchBrand'] = $searchValue;
            $params['searchModel'] = $searchValue;
        }

        if ($status !== '') {
            $conditions[] = 'a.estado_registro = :status';
            $params['status'] = intval($status);
        }

        if ($clasificacionId > 0) {
            $conditions[] = 'a.id_clasificacion = :clasificacionId';
            $params['clasificacionId'] = $clasificacionId;
        }

        $where = count($conditions) > 0 ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $from = ' FROM articulos a'
            . ' LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion'
            . ' LEFT JOIN unidades_medida u ON u.id_unidad_medida = a.id_unidad_medida';

        $countRow = self::obtenerUnRegistro('SELECT COUNT(*) AS total' . $from . $where, $params);
        $total = intval($countRow['total'] ?? 0);

        $itemsPerPage = max(1, min(100, $itemsPerPage));
        $pages = $total > 0 ? intval(ceil($total / $itemsPerPage)) : 1;
        $page = max(0, $page);
        if ($page > $pages - 1) {
            $page = $pages - 1;
        }
        $offset = $page * $itemsPerPage;

        $sql = 'SELECT a.*, COALESCE(c.nombre_clasificacion, "Sin clasificación") AS nombre_clasificacion, c.estado_clasif,'
            . ' COALESCE(u.nombre_unidad, "Unidad") AS nombre_unidad, u.simbolo_medida, u.estado_medida'
            . $from . $where
            . ' ORDER BY a.' . $orderBy . ($orderDescending ? ' DESC' : ' ASC')
            . ' LIMIT ' . $offset . ', ' . $itemsPerPage;

        return [
            'articulos' => self::obtenerRegistros($sql, $params),
            'total' => $total,
            'page' => $page,
            'itemsPerPage' => $itemsPerPage,
            'pages' => $pages,
        ];
    }

    public static function getArticuloById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT a.*, COALESCE(c.nombre_clasificacion, "Sin clasificación") AS nombre_clasificacion, c.estado_clasif,'
            . ' COALESCE(u.nombre_unidad, "Unidad") AS nombre_unidad, u.simbolo_medida, u.estado_medida'
            . ' FROM articulos a'
            . ' LEFT JOIN clasificaciones c ON c.id_clasificacion = a.id_clasificacion'
            . ' LEFT JOIN unidades_medida u ON u.id_unidad_medida = a.id_unidad_medida'
            . ' WHERE a.id_articulo = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getSummary(): array
    {
        $row = self::obtenerUnRegistro(
            "SELECT COUNT(*) AS total,"
            . " COALESCE(SUM(CASE WHEN tipo_control = 'CANTIDAD' THEN 1 ELSE 0 END), 0) AS cantidad,"
            . " COALESCE(SUM(CASE WHEN tipo_control = 'SERIE' THEN 1 ELSE 0 END), 0) AS serie,"
            . " COALESCE(SUM(CASE WHEN tipo_control = 'CANTIDAD'"
            . " AND existencia_actual <= COALESCE(existencia_minima, 0) THEN 1 ELSE 0 END), 0) AS bajo"
            . " FROM articulos WHERE estado_registro = 1",
            []
        );

        return [
            'total' => intval($row['total'] ?? 0),
            'cantidad' => intval($row['cantidad'] ?? 0),
            'serie' => intval($row['serie'] ?? 0),
            'bajo' => intval($row['bajo'] ?? 0),
        ];
    }

    public static function codeExists(string $code, int $excludeId = 0): bool
    {
        $sql = 'SELECT COUNT(*) AS total FROM articulos WHERE codigo_interno_articulo = :code';
        $params = ['code' => $code];

        if ($excludeId > 0) {
            $sql .= ' AND id_articulo <> :excludeId';
            $params['excludeId'] = $excludeId;
        }

        $row = self::obtenerUnRegistro($sql, $params);

        return intval($row['total'] ?? 0) > 0;
    }

    public static function getClasificacionesOptions(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = 'SELECT id_clasificacion AS value, nombre_clasificacion AS label,'
            . ' estado_clasif AS status FROM clasificaciones';
        // Filtros del selector y sus valores asociados.
        $conditions = [];
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $conditions[] = '(estado_clasif = 1 OR id_clasificacion = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $conditions[] = 'estado_clasif = 1';
            }
        }

        if (count($conditions) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY nombre_clasificacion ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getUnidadesOptions(bool $onlyActive = true, int $includeId = 0): array
    {
        $sql = 'SELECT id_unidad_medida AS value, nombre_unidad AS label,'
            . ' simbolo_medida AS symbol, estado_medida AS status FROM unidades_medida';
        // Filtros del selector y sus valores asociados.
        $conditions = [];
        $params = [];

        if ($onlyActive) {
            if ($includeId > 0) {
                $conditions[] = '(estado_medida = 1 OR id_unidad_medida = :includeId)';
                $params['includeId'] = $includeId;
            } else {
                $conditions[] = 'estado_medida = 1';
            }
        }

        if (count($conditions) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY nombre_unidad ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getClasificacionById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_clasificacion, nombre_clasificacion, estado_clasif'
            . ' FROM clasificaciones WHERE id_clasificacion = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function getUnidadById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $row = self::obtenerUnRegistro(
            'SELECT id_unidad_medida, nombre_unidad, simbolo_medida, estado_medida'
            . ' FROM unidades_medida WHERE id_unidad_medida = :id LIMIT 1',
            ['id' => $id]
        );

        return $row ?: null;
    }

    public static function insertArticulo(array $data): bool
    {
        return self::executeNonQuery(
            'INSERT INTO articulos ('
            . 'codigo_interno_articulo, nombre_articulo, descripcion_articulo,'
            . ' marca_articulo, modelo_articulo, observaciones_articulo,'
            . ' id_clasificacion, id_unidad_medida, tipo_control,'
            . ' existencia_minima, existencia_actual, estado_registro, fecha_creacion'
            . ') VALUES ('
            . ':codigo, :nombre, :descripcion, :marca, :modelo, :observaciones, :clasificacion, :unidad, :control,'
            . ' :existenciaMinima, :existenciaActual, :estado, NOW()'
            . ')',
            [
                'codigo' => $data['codigo_interno_articulo'],
                'nombre' => $data['nombre_articulo'],
                'descripcion' => $data['descripcion_articulo'],
                'marca' => $data['marca_articulo'],
                'modelo' => $data['modelo_articulo'],
                'observaciones' => $data['observaciones_articulo'],
                'clasificacion' => intval($data['id_clasificacion']),
                'unidad' => intval($data['id_unidad_medida']),
                'control' => $data['tipo_control'],
                'existenciaMinima' => intval($data['existencia_minima']),
                'existenciaActual' => intval($data['existencia_actual']),
                'estado' => intval($data['estado_registro']),
            ]
        );
    }

    public static function updateArticulo(int $id, array $data): bool
    {
        return self::executeNonQuery(
            'UPDATE articulos SET'
            . ' codigo_interno_articulo = :codigo, nombre_articulo = :nombre,'
            . ' descripcion_articulo = :descripcion, id_clasificacion = :clasificacion,'
            . ' marca_articulo = :marca, modelo_articulo = :modelo,'
            . ' observaciones_articulo = :observaciones,'
            . ' id_unidad_medida = :unidad, tipo_control = :control,'
            . ' existencia_minima = :existenciaMinima,'
            . ' estado_registro = :estado, fecha_modificacion = NOW()'
            . ' WHERE id_articulo = :id',
            [
                'codigo' => $data['codigo_interno_articulo'],
                'nombre' => $data['nombre_articulo'],
                'descripcion' => $data['descripcion_articulo'],
                'marca' => $data['marca_articulo'],
                'modelo' => $data['modelo_articulo'],
                'observaciones' => $data['observaciones_articulo'],
                'clasificacion' => intval($data['id_clasificacion']),
                'unidad' => intval($data['id_unidad_medida']),
                'control' => $data['tipo_control'],
                'existenciaMinima' => intval($data['existencia_minima']),
                'estado' => intval($data['estado_registro']),
                'id' => $id,
            ]
        );
    }

    public static function deactivateArticulo(int $id): bool
    {
        return self::executeNonQuery(
            'UPDATE articulos SET estado_registro = 0, fecha_modificacion = NOW()'
            . ' WHERE id_articulo = :id',
            ['id' => $id]
        );
    }

    public static function deleteArticulo(int $id): bool
    {
        return self::executeNonQuery(
            'DELETE FROM articulos WHERE id_articulo = :id',
            ['id' => $id]
        );
    }
}
