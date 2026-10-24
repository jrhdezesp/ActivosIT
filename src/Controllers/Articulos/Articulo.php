<?php

namespace Controllers\Articulos;

use Controllers\PrivateController;
use Dao\Articulos\Articulos as DaoArticulos;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Site;
use Views\Renderer;

class Articulo extends PrivateController
{
    private $mode = 'INS';
    private $id = 0;
    private $item = [];
    private $record = [];
    private $originalItem = [];
    private $errors = [];
    private $clasificaciones = [];
    private $unidades = [];

    public function run(): void
    {
        try {
            $this->getParams();
            $this->loadOptions();

            if ($this->isPostBack()) {
                CSRF::requireValid('articulo', $_POST['token'] ?? '');

                if ($this->mode === 'DEL') {
                    $this->handleDeactivate();
                } elseif ($this->validateData()) {
                    $this->handleSave();
                }
            }

            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Articulos_Articulos&msg=error',
                $ex->getMessage()
            );
        }
    }

    private function getParams(): void
    {
        $this->mode = strtoupper(strval($_GET['mode'] ?? 'INS'));
        if (!in_array($this->mode, ['INS', 'UPD', 'DEL'], true)) {
            throw new \InvalidArgumentException('La modalidad del formulario no es válida.');
        }

        $this->id = intval($_GET['id'] ?? 0);
        $this->initializeItem();

        if ($this->mode !== 'INS') {
            if ($this->id <= 0) {
                throw new \InvalidArgumentException('El artículo solicitado no es válido.');
            }

            $row = DaoArticulos::getArticuloById($this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró el artículo solicitado.');
            }

            $this->record = $row;
            $this->item = $this->pickItem($row);
            $this->originalItem = $this->item;
        }

        if ($this->isPostBack() && $this->mode !== 'DEL') {
            $fields = [
                'codigo_interno_articulo',
                'nombre_articulo',
                'descripcion_articulo',
                'marca_articulo',
                'modelo_articulo',
                'observaciones_articulo',
                'id_clasificacion',
                'id_unidad_medida',
                'tipo_control',
                'existencia_minima',
                'existencia_actual',
                'estado_registro',
            ];

            foreach ($fields as $field) {
                if (array_key_exists($field, $_POST)) {
                    $this->item[$field] = trim(strval($_POST[$field]));
                }
            }

            // La existencia no se edita directamente: es un saldo derivado de
            // los movimientos de inventario (Kardex). Solo se captura al crear
            // el artículo como existencia inicial.
            if ($this->mode === 'UPD') {
                $this->item['existencia_actual'] = strval($this->originalItem['existencia_actual'] ?? '0');
            }
        }
    }

    private function initializeItem(): void
    {
        $this->item = [
            'codigo_interno_articulo' => '',
            'nombre_articulo' => '',
            'descripcion_articulo' => '',
            'marca_articulo' => '',
            'modelo_articulo' => '',
            'observaciones_articulo' => '',
            'id_clasificacion' => '0',
            'id_unidad_medida' => '0',
            'tipo_control' => 'CANTIDAD',
            'existencia_minima' => '0',
            'existencia_actual' => '0',
            'estado_registro' => '1',
        ];
    }

    private function pickItem(array $row): array
    {
        return [
            'codigo_interno_articulo' => strval($row['codigo_interno_articulo'] ?? ''),
            'nombre_articulo' => strval($row['nombre_articulo'] ?? ''),
            'descripcion_articulo' => strval($row['descripcion_articulo'] ?? ''),
            'marca_articulo' => strval($row['marca_articulo'] ?? ''),
            'modelo_articulo' => strval($row['modelo_articulo'] ?? ''),
            'observaciones_articulo' => strval($row['observaciones_articulo'] ?? ''),
            'id_clasificacion' => strval($row['id_clasificacion'] ?? '0'),
            'id_unidad_medida' => strval($row['id_unidad_medida'] ?? '0'),
            'tipo_control' => strval($row['tipo_control'] ?? ''),
            'existencia_minima' => $row['existencia_minima'] === null
                ? '0'
                : strval($row['existencia_minima']),
            'existencia_actual' => strval($row['existencia_actual'] ?? '0'),
            'estado_registro' => strval(intval($row['estado_registro'] ?? 0)),
        ];
    }

    private function loadOptions(): void
    {
        $includeClasificacion = $this->mode !== 'INS'
            ? intval($this->originalItem['id_clasificacion'] ?? 0)
            : 0;
        $includeUnidad = $this->mode !== 'INS'
            ? intval($this->originalItem['id_unidad_medida'] ?? 0)
            : 0;

        $this->clasificaciones = DaoArticulos::getClasificacionesOptions(true, $includeClasificacion);
        $this->unidades = DaoArticulos::getUnidadesOptions(true, $includeUnidad);
    }

    private function validateData(): bool
    {
        $this->errors = [];

        $code = trim(strval($this->item['codigo_interno_articulo'] ?? ''));
        $this->item['codigo_interno_articulo'] = $code;
        if ($code === '') {
            $this->errors['codigo_interno_articulo'] = 'Este campo es requerido.';
        } elseif ($this->textLength($code) > 255) {
            $this->errors['codigo_interno_articulo'] = 'El código no puede superar los 255 caracteres.';
        } else {
            $excludeId = $this->mode === 'UPD' ? $this->id : 0;
            if (DaoArticulos::codeExists($code, $excludeId)) {
                $this->errors['codigo_interno_articulo'] = 'Ya existe un artículo con ese código.';
            }
        }

        $name = trim(strval($this->item['nombre_articulo'] ?? ''));
        $this->item['nombre_articulo'] = $name;
        if ($name === '') {
            $this->errors['nombre_articulo'] = 'Este campo es requerido.';
        } elseif ($this->textLength($name) > 255) {
            $this->errors['nombre_articulo'] = 'El nombre no puede superar los 255 caracteres.';
        }

        $description = trim(strval($this->item['descripcion_articulo'] ?? ''));
        $this->item['descripcion_articulo'] = $description;
        if ($this->textLength($description) > 65535) {
            $this->errors['descripcion_articulo'] = 'La descripción supera el máximo permitido.';
        }

        foreach ([
            'marca_articulo' => 100,
            'modelo_articulo' => 150,
            'observaciones_articulo' => 65535,
        ] as $field => $maxLength) {
            $this->item[$field] = trim(strval($this->item[$field] ?? ''));
            if ($this->textLength($this->item[$field]) > $maxLength) {
                $this->errors[$field] = 'El contenido supera el máximo permitido.';
            }
        }

        $clasificacionId = intval($this->item['id_clasificacion'] ?? 0);
        $this->item['id_clasificacion'] = strval($clasificacionId);
        $clasificacion = DaoArticulos::getClasificacionById($clasificacionId);
        if ($clasificacionId <= 0 || !$clasificacion) {
            $this->errors['id_clasificacion'] = 'Selecciona una clasificación válida.';
        } elseif (
            intval($clasificacion['estado_clasif'] ?? 0) !== 1
            && !$this->isCurrentClasificacion($clasificacionId)
        ) {
            $this->errors['id_clasificacion'] = 'La clasificación seleccionada está inactiva.';
        }

        $unidadId = intval($this->item['id_unidad_medida'] ?? 0);
        $this->item['id_unidad_medida'] = strval($unidadId);
        $unidad = DaoArticulos::getUnidadById($unidadId);
        if ($unidadId <= 0 || !$unidad) {
            $this->errors['id_unidad_medida'] = 'Selecciona una unidad de medida válida.';
        } elseif (
            intval($unidad['estado_medida'] ?? 0) !== 1
            && !$this->isCurrentUnidad($unidadId)
        ) {
            $this->errors['id_unidad_medida'] = 'La unidad de medida seleccionada está inactiva.';
        }

        $control = strval($this->item['tipo_control'] ?? '');
        $this->item['tipo_control'] = $control;
        if (!in_array($control, ['CANTIDAD', 'SERIE'], true)) {
            $this->errors['tipo_control'] = 'Selecciona un tipo de control válido.';
        }

        $minimum = trim(strval($this->item['existencia_minima'] ?? ''));
        if ($minimum === '') {
            $minimum = '0';
        }
        if (!preg_match('/^\d{1,10}$/', $minimum) || intval($minimum) > 2147483647) {
            $this->errors['existencia_minima'] = 'Ingresa un número entero mayor o igual a 0.';
        } else {
            $this->item['existencia_minima'] = strval(intval($minimum));
        }

        $actual = trim(strval($this->item['existencia_actual'] ?? ''));
        if ($this->mode === 'INS') {
            if ($actual === '') {
                $this->errors['existencia_actual'] = 'Este campo es requerido.';
            } elseif (!preg_match('/^\d{1,10}$/', $actual) || intval($actual) > 2147483647) {
                $this->errors['existencia_actual'] = 'Ingresa un número entero mayor o igual a 0.';
            } else {
                $this->item['existencia_actual'] = strval(intval($actual));
            }
        } else {
            $this->item['existencia_actual'] = strval(intval($this->originalItem['existencia_actual'] ?? 0));
        }

        $estado = strval($this->item['estado_registro'] ?? '');
        if (!in_array($estado, ['0', '1'], true)) {
            $this->errors['estado_registro'] = 'Selecciona un estado válido.';
        }

        return count($this->errors) === 0;
    }

    private function handleSave(): void
    {
        $description = trim(strval($this->item['descripcion_articulo'] ?? ''));
        $data = [
            'codigo_interno_articulo' => strval($this->item['codigo_interno_articulo']),
            'nombre_articulo' => strval($this->item['nombre_articulo']),
            'descripcion_articulo' => $description === '' ? null : $description,
            'marca_articulo' => $this->nullIfEmpty(strval($this->item['marca_articulo'])),
            'modelo_articulo' => $this->nullIfEmpty(strval($this->item['modelo_articulo'])),
            'observaciones_articulo' => $this->nullIfEmpty(strval($this->item['observaciones_articulo'])),
            'id_clasificacion' => intval($this->item['id_clasificacion']),
            'id_unidad_medida' => intval($this->item['id_unidad_medida']),
            'tipo_control' => strval($this->item['tipo_control']),
            'existencia_minima' => intval($this->item['existencia_minima']),
            'existencia_actual' => intval($this->item['existencia_actual']),
            'estado_registro' => intval($this->item['estado_registro']),
        ];

        try {
            if ($this->mode === 'INS') {
                if (!DaoArticulos::insertArticulo($data)) {
                    throw new \RuntimeException('No se pudo crear el artículo.');
                }
                Bitacora::registrar(
                    'CREAR_ARTICULO',
                    'Artículos',
                    Bitacora::RESULTADO_EXITO,
                    null,
                    null,
                    $data
                );
                Site::redirectTo('index.php?page=Articulos_Articulos&msg=created');
            }

            if ($this->mode === 'UPD') {
                if (!DaoArticulos::updateArticulo($this->id, $data)) {
                    throw new \RuntimeException('No se pudo actualizar el artículo.');
                }
                Bitacora::registrar(
                    'ACTUALIZAR_ARTICULO',
                    'Artículos',
                    Bitacora::RESULTADO_EXITO,
                    $this->id,
                    $this->originalItem,
                    $data
                );
                Site::redirectTo('index.php?page=Articulos_Articulos&msg=updated');
            }
        } catch (\PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $this->errors['codigo_interno_articulo'] = 'Ya existe un artículo con ese código.';
                return;
            }
            throw $ex;
        }
    }

    private function handleDeactivate(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('El artículo solicitado no es válido.');
        }

        if (!DaoArticulos::getArticuloById($this->id)) {
            throw new \RuntimeException('No se encontró el artículo solicitado.');
        }

        if (!DaoArticulos::deactivateArticulo($this->id)) {
            throw new \RuntimeException('No se pudo desactivar el artículo.');
        }

        Bitacora::registrar(
            'DESACTIVAR_ARTICULO',
            'Artículos',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            $this->record,
            ['estado_registro' => 0]
        );

        Site::redirectTo('index.php?page=Articulos_Articulos&msg=deactivated');
    }

    private function renderForm(): void
    {
        $isDelete = $this->mode === 'DEL';
        $selectedClasificacion = strval($this->item['id_clasificacion'] ?? '0');
        $selectedUnidad = strval($this->item['id_unidad_medida'] ?? '0');

        $formTitle = 'Nuevo artículo';
        $formDescription = 'Registra un artículo y define cómo se controlará su existencia.';
        $submitLabel = 'Crear artículo';
        $submitIcon = 'fa-plus';

        if ($this->mode === 'UPD') {
            $formTitle = 'Editar artículo';
            $formDescription = 'Actualiza los datos, la clasificación y el control de existencia del artículo.';
            $submitLabel = 'Guardar cambios';
            $submitIcon = 'fa-floppy-disk';
        } elseif ($isDelete) {
            $formTitle = 'Desactivar artículo';
            $formDescription = 'El artículo se conservará en el historial, pero dejará de estar disponible para nuevas operaciones.';
            $submitLabel = 'Desactivar artículo';
            $submitIcon = 'fa-ban';
        }

        $formAction = 'index.php?' . http_build_query([
            'page' => 'Articulos_Articulo',
            'mode' => $this->mode,
            'id' => $this->id,
        ]);

        if ($this->mode === 'INS') {
            $formAction = 'index.php?' . http_build_query([
                'page' => 'Articulos_Articulo',
                'mode' => $this->mode,
            ]);
        }

        $recordName = trim(
            strval($this->item['codigo_interno_articulo'] ?? '') . ' · '
            . strval($this->item['nombre_articulo'] ?? '')
        );

        Renderer::render('articulos/articulo', [
            'FormEyebrow' => 'Activos / Catálogo de artículos',
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => $formAction,
            'FormCancelUrl' => 'index.php?page=Articulos_Articulos',
            'FormBackLabel' => 'Volver al catálogo',
            'FormSubmitLabel' => $submitLabel,
            'FormSubmitIcon' => $submitIcon,
            'FormDanger' => $isDelete,
            'FormRecordName' => $recordName,
            'FormMeta' => $this->buildFormMeta(),
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0
                ? 'Revisa los campos marcados en rojo.'
                : '',
            'FormToken' => CSRF::token('articulo'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'FormRequired' => $isDelete ? '' : 'required',
            'FormReadonly' => $isDelete ? 'readonly' : '',
            'FormDisabled' => $isDelete ? 'disabled' : '',
            'FormCode' => strval($this->item['codigo_interno_articulo'] ?? ''),
            'FormName' => strval($this->item['nombre_articulo'] ?? ''),
            'FormArticleDescription' => strval($this->item['descripcion_articulo'] ?? ''),
            'FormBrand' => strval($this->item['marca_articulo'] ?? ''),
            'FormModel' => strval($this->item['modelo_articulo'] ?? ''),
            'FormArticleObservations' => strval($this->item['observaciones_articulo'] ?? ''),
            'FormClasificacion' => $selectedClasificacion,
            'FormUnidad' => $selectedUnidad,
            'FormTipoControl' => strval($this->item['tipo_control'] ?? ''),
            'FormExistenciaMinima' => strval($this->item['existencia_minima'] ?? '0'),
            'FormExistenciaActual' => strval($this->item['existencia_actual'] ?? '0'),
            'FormExistenciaActualReadonly' => $this->mode === 'UPD' ? 'readonly' : '',
            'FormEstado' => strval($this->item['estado_registro'] ?? '1'),
            'FormCodeError' => strval($this->errors['codigo_interno_articulo'] ?? ''),
            'FormNameError' => strval($this->errors['nombre_articulo'] ?? ''),
            'FormDescriptionError' => strval($this->errors['descripcion_articulo'] ?? ''),
            'FormBrandError' => strval($this->errors['marca_articulo'] ?? ''),
            'FormModelError' => strval($this->errors['modelo_articulo'] ?? ''),
            'FormArticleObservationsError' => strval($this->errors['observaciones_articulo'] ?? ''),
            'FormClasificacionError' => strval($this->errors['id_clasificacion'] ?? ''),
            'FormUnidadError' => strval($this->errors['id_unidad_medida'] ?? ''),
            'FormTipoControlError' => strval($this->errors['tipo_control'] ?? ''),
            'FormExistenciaMinimaError' => strval($this->errors['existencia_minima'] ?? ''),
            'FormExistenciaActualError' => strval($this->errors['existencia_actual'] ?? ''),
            'FormEstadoError' => strval($this->errors['estado_registro'] ?? ''),
            'ClasificacionPlaceholderSelected' => intval($selectedClasificacion) <= 0 ? 'selected' : '',
            'UnidadPlaceholderSelected' => intval($selectedUnidad) <= 0 ? 'selected' : '',
            'ClasificacionesForm' => $this->decorateClasificacionOptions(
                $this->clasificaciones,
                $selectedClasificacion
            ),
            'UnidadesForm' => $this->decorateUnidadOptions(
                $this->unidades,
                $selectedUnidad
            ),
            'TipoControlOptions' => $this->decorateSimpleOptions(
                [
                    ['value' => 'CANTIDAD', 'label' => 'Por cantidad'],
                    ['value' => 'SERIE', 'label' => 'Por serie'],
                ],
                strval($this->item['tipo_control'] ?? '')
            ),
            'EstadoOptions' => $this->decorateSimpleOptions(
                [
                    ['value' => '1', 'label' => 'Activo'],
                    ['value' => '0', 'label' => 'Inactivo'],
                ],
                strval($this->item['estado_registro'] ?? '1')
            ),
        ]);
    }

    private function decorateClasificacionOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactiva)';
            }

            $value = strval($row['value'] ?? '');
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateUnidadOptions(array $rows, string $selected): array
    {
        $options = [];

        foreach ($rows as $row) {
            $label = strval($row['label'] ?? '');
            $symbol = trim(strval($row['symbol'] ?? ''));
            if ($symbol !== '') {
                $label .= ' (' . $symbol . ')';
            }
            if (intval($row['status'] ?? 1) !== 1) {
                $label .= ' (inactiva)';
            }

            $value = strval($row['value'] ?? '');
            $options[] = [
                'value' => $value,
                'label' => $label,
                'selected' => $value === $selected ? 'selected' : '',
            ];
        }

        return $options;
    }

    private function decorateSimpleOptions(array $options, string $selected): array
    {
        foreach ($options as &$option) {
            $option['selected'] = strval($option['value']) === $selected ? 'selected' : '';
        }
        unset($option);

        return $options;
    }

    private function isCurrentClasificacion(int $id): bool
    {
        return $this->mode === 'UPD'
            && intval($this->originalItem['id_clasificacion'] ?? 0) === $id;
    }

    private function isCurrentUnidad(int $id): bool
    {
        return $this->mode === 'UPD'
            && intval($this->originalItem['id_unidad_medida'] ?? 0) === $id;
    }

    private function buildFormMeta(): string
    {
        if ($this->mode === 'INS') {
            return '';
        }

        $created = $this->formatDate(strval($this->record['fecha_creacion'] ?? ''));
        $updated = $this->formatDate(strval($this->record['fecha_modificacion'] ?? ''));

        $meta = 'Creado: ' . ($created !== '' ? $created : '—');
        if ($updated !== '') {
            $meta .= ' · Última modificación: ' . $updated;
        }

        return $meta;
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $value;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
