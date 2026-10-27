<?php

namespace Dao\Catalogos;

use Dao\Table;

class Catalogos extends Table
{
    // Definiciones de catalogos que se reutilizan durante la solicitud.
    private static $definitions = null;

    public static function getDefinitions(): array
    {
        if (self::$definitions === null) {
            self::$definitions = [
                'clasificaciones' => [
                    'table' => 'clasificaciones',
                    'pk' => 'id_clasificacion',
                    'nameField' => 'nombre_clasificacion',
                    'statusField' => 'estado_clasif',
                    'page' => 'general',
                    'title' => 'Clasificaciones',
                    'description' => 'Familias que agrupan los artículos del inventario.',
                    'icon' => 'fa-layer-group',
                    'tone' => 'blue',
                    'searchFields' => ['nombre_clasificacion', 'descripcion_clasif'],
                    'fields' => [
                        [
                            'name' => 'nombre_clasificacion',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => 'Nombre único de la clasificación.',
                        ],
                        [
                            'name' => 'descripcion_clasif',
                            'label' => 'Descripción',
                            'type' => 'textarea',
                            'required' => false,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => '',
                        ],
                        [
                            'name' => 'estado_clasif',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'ubicaciones' => [
                    'table' => 'ubicaciones',
                    'pk' => 'id_ubicacion',
                    'nameField' => 'nombre_ubicacion',
                    'statusField' => 'estado_ubi',
                    'page' => 'general',
                    'title' => 'Ubicaciones',
                    'description' => 'Espacios físicos donde se resguardan los equipos.',
                    'icon' => 'fa-location-dot',
                    'tone' => 'teal',
                    'searchFields' => ['nombre_ubicacion', 'descripcion_ubi'],
                    'fields' => [
                        [
                            'name' => 'nombre_ubicacion',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 100,
                            'full' => true,
                            'help' => 'Nombre único de la ubicación.',
                        ],
                        [
                            'name' => 'descripcion_ubi',
                            'label' => 'Descripción',
                            'type' => 'textarea',
                            'required' => false,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => '',
                        ],
                        [
                            'name' => 'estado_ubi',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'unidades_medida' => [
                    'table' => 'unidades_medida',
                    'pk' => 'id_unidad_medida',
                    'nameField' => 'nombre_unidad',
                    'statusField' => 'estado_medida',
                    'page' => 'general',
                    'title' => 'Unidades de medida',
                    'description' => 'Formas de cuantificar artículos y servicios.',
                    'icon' => 'fa-ruler-combined',
                    'tone' => 'red',
                    'searchFields' => ['nombre_unidad', 'simbolo_medida'],
                    'fields' => [
                        [
                            'name' => 'nombre_unidad',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 50,
                            'full' => true,
                            'help' => 'Nombre único de la unidad de medida.',
                        ],
                        [
                            'name' => 'simbolo_medida',
                            'label' => 'Símbolo',
                            'type' => 'text',
                            'required' => false,
                            'maxlength' => 10,
                            'full' => false,
                            'help' => 'Ej. UND, CJA, LIC.',
                        ],
                        [
                            'name' => 'estado_medida',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'tipos_documentos' => [
                    'table' => 'tipos_documentos',
                    'pk' => 'id_tipo_doc',
                    'nameField' => 'nombre_tipo',
                    'statusField' => 'estado_doc',
                    'page' => 'operativo',
                    'title' => 'Tipos de documentos',
                    'description' => 'Clasifican los documentos de entrada y salida.',
                    'icon' => 'fa-file-lines',
                    'tone' => 'blue',
                    'searchFields' => ['nombre_tipo', 'naturaleza_doc'],
                    'fields' => [
                        [
                            'name' => 'nombre_tipo',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => 'Nombre único del tipo de documento.',
                        ],
                        [
                            'name' => 'naturaleza_doc',
                            'label' => 'Naturaleza',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => 'ENTRADA', 'label' => 'Entrada'],
                                ['value' => 'SALIDA', 'label' => 'Salida'],
                            ],
                        ],
                        [
                            'name' => 'estado_doc',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'tipos_mantenimientos' => [
                    'table' => 'tipos_mantenimientos',
                    'pk' => 'id_tipo_mantenimiento',
                    'nameField' => 'nombre_tipo',
                    'statusField' => 'estado_mant',
                    'page' => 'operativo',
                    'title' => 'Tipos de mantenimiento',
                    'description' => 'Definen el alcance del trabajo técnico.',
                    'icon' => 'fa-screwdriver-wrench',
                    'tone' => 'amber',
                    'searchFields' => ['nombre_tipo', 'descripcion_mant'],
                    'fields' => [
                        [
                            'name' => 'nombre_tipo',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => 'Nombre único del tipo de mantenimiento.',
                        ],
                        [
                            'name' => 'descripcion_mant',
                            'label' => 'Descripción',
                            'type' => 'textarea',
                            'required' => false,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => '',
                        ],
                        [
                            'name' => 'estado_mant',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
                'motivos_ajustes_anulaciones' => [
                    'table' => 'motivos_ajustes_anulaciones',
                    'pk' => 'id_motivo',
                    'nameField' => 'nombre_motivo',
                    'statusField' => 'estado_motivo',
                    'page' => 'operativo',
                    'title' => 'Motivos de ajustes y anulaciones',
                    'description' => 'Justifican los cambios que afectan el inventario.',
                    'icon' => 'fa-scale-balanced',
                    'tone' => 'red',
                    'searchFields' => ['nombre_motivo', 'motivo_aplica_a'],
                    'fields' => [
                        [
                            'name' => 'nombre_motivo',
                            'label' => 'Nombre',
                            'type' => 'text',
                            'required' => true,
                            'maxlength' => 255,
                            'full' => true,
                            'help' => 'Nombre único del motivo.',
                        ],
                        [
                            'name' => 'motivo_aplica_a',
                            'label' => 'Aplica a',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => 'AJUSTE_ENTRADA', 'label' => 'Ajuste de entrada'],
                                ['value' => 'AJUSTE_SALIDA', 'label' => 'Ajuste de salida'],
                                ['value' => 'ANULACION', 'label' => 'Anulación'],
                                ['value' => 'BAJA', 'label' => 'Baja'],
                            ],
                        ],
                        [
                            'name' => 'estado_motivo',
                            'label' => 'Estado',
                            'type' => 'select',
                            'required' => true,
                            'maxlength' => 0,
                            'full' => false,
                            'help' => '',
                            'options' => [
                                ['value' => '1', 'label' => 'Activo'],
                                ['value' => '0', 'label' => 'Inactivo'],
                            ],
                        ],
                    ],
                ],
            ];
        }

        return self::$definitions;
    }

    public static function getTypesByPage(string $page): array
    {
        $types = [];
        foreach (self::getDefinitions() as $type => $definition) {
            if ($definition['page'] === $page) {
                $types[] = $type;
            }
        }

        return $types;
    }

    public static function getDefinition(string $type): array
    {
        $definitions = self::getDefinitions();
        if (!isset($definitions[$type])) {
            throw new \InvalidArgumentException('El catálogo solicitado no es válido.');
        }

        return $definitions[$type];
    }

    public static function getItems(string $type, string $search = '', string $status = ''): array
    {
        $definition = self::getDefinition($type);
        if (!in_array($status, ['', '0', '1'], true)) {
            throw new \InvalidArgumentException('El estado solicitado no es válido.');
        }

        // Filtros aplicados a los registros del catalogo.
        $conditions = [];
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            // Campos del catalogo incluidos en la busqueda por texto.
            $searchParts = [];
            foreach ($definition['searchFields'] as $index => $field) {
                $key = 'search' . $index;
                $searchParts[] = '`' . $field . '` LIKE :' . $key;
                $params[$key] = '%' . $search . '%';
            }
            if (count($searchParts) > 0) {
                $conditions[] = '(' . implode(' OR ', $searchParts) . ')';
            }
        }

        if ($status !== '') {
            $conditions[] = '`' . $definition['statusField'] . '` = :status';
            $params['status'] = intval($status);
        }

        $sql = 'SELECT * FROM `' . $definition['table'] . '`';
        if (count($conditions) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY `' . $definition['nameField'] . '` ASC';

        return self::obtenerRegistros($sql, $params);
    }

    public static function getItem(string $type, int $id): ?array
    {
        $definition = self::getDefinition($type);
        if ($id <= 0) {
            return null;
        }

        $sql = 'SELECT * FROM `' . $definition['table'] . '` WHERE `' . $definition['pk'] . '` = :id LIMIT 1';
        $row = self::obtenerUnRegistro($sql, ['id' => $id]);

        return $row ?: null;
    }

    public static function nameExists(string $type, string $name, int $excludeId = 0): bool
    {
        $definition = self::getDefinition($type);
        $sql = 'SELECT COUNT(*) AS total FROM `' . $definition['table'] . '` WHERE `' . $definition['nameField'] . '` = :name';
        $params = ['name' => trim($name)];

        if ($excludeId > 0) {
            $sql .= ' AND `' . $definition['pk'] . '` <> :id';
            $params['id'] = $excludeId;
        }

        $row = self::obtenerUnRegistro($sql, $params);

        return intval($row['total'] ?? 0) > 0;
    }

    public static function insertItem(string $type, array $data): bool
    {
        $definition = self::getDefinition($type);
        // Columnas, valores y marcadores que forman el nuevo registro.
        $columns = [];
        $placeholders = [];
        $params = [];

        foreach ($definition['fields'] as $field) {
            $name = $field['name'];
            $columns[] = '`' . $name . '`';
            $placeholders[] = ':' . $name;
            $params[$name] = self::normalizeFieldValue($field, $definition, $data[$name] ?? null);
        }

        $sql = 'INSERT INTO `' . $definition['table'] . '` (' . implode(', ', $columns) . ') '
            . 'VALUES (' . implode(', ', $placeholders) . ')';

        return self::executeNonQuery($sql, $params);
    }

    public static function updateItem(string $type, int $id, array $data): bool
    {
        $definition = self::getDefinition($type);
        if ($id <= 0) {
            throw new \InvalidArgumentException('El registro solicitado no es válido.');
        }

        // Campos que se actualizaran y valores correspondientes.
        $assignments = [];
        $params = ['id' => $id];

        foreach ($definition['fields'] as $field) {
            $name = $field['name'];
            $assignments[] = '`' . $name . '` = :' . $name;
            $params[$name] = self::normalizeFieldValue($field, $definition, $data[$name] ?? null);
        }

        $sql = 'UPDATE `' . $definition['table'] . '` SET ' . implode(', ', $assignments)
            . ' WHERE `' . $definition['pk'] . '` = :id';

        return self::executeNonQuery($sql, $params);
    }

    public static function deleteItem(string $type, int $id): bool
    {
        $definition = self::getDefinition($type);
        if ($id <= 0) {
            throw new \InvalidArgumentException('El registro solicitado no es válido.');
        }

        // Borrado lógico: el catálogo se marca inactivo para no romper las
        // llaves foráneas de artículos, equipos y movimientos que lo usan.
        $sql = 'UPDATE `' . $definition['table'] . '` SET `' . $definition['statusField'] . '` = 0'
            . ' WHERE `' . $definition['pk'] . '` = :id';

        return self::executeNonQuery($sql, ['id' => $id]);
    }

    private static function normalizeFieldValue(array $field, array $definition, $value)
    {
        if ($field['name'] === $definition['statusField']) {
            return intval($value) === 1 ? 1 : 0;
        }

        $value = trim((string) $value);

        if ($value === '' && empty($field['required'])) {
            return null;
        }

        return $value;
    }
}
