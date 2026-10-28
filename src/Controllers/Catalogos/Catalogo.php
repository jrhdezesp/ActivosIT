<?php

namespace Controllers\Catalogos;

use Controllers\PrivateController;
use Dao\Catalogos\Catalogos as DaoCatalogos;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Site;
use Views\Renderer;

/**
 * Atiende el formulario para crear, actualizar o eliminar un dato de catalogo.
 */
class Catalogo extends PrivateController
{
    private $type = '';
    private $definition = [];
    private $mode = 'INS';
    private $id = 0;
    private $item = [];
    private $errors = [];

    /**
     * Prepara el formulario y procesa el cambio solicitado al catalogo.
     */
    public function run(): void
    {
        try {
            $this->getParams();
            $this->requireAction('Acciones.Catalogos.Administrar');
            if ($this->isPostBack()) {
                CSRF::requireValid('catalogo', $_POST['token'] ?? '');
                if ($this->mode === 'DEL') {
                    $this->handleDelete();
                } elseif ($this->validateData()) {
                    $this->handleSave();
                }
            }
            $this->renderForm();
        } catch (\Exception $ex) {
            Site::redirectToWithMsg($this->listUrl() . '&msg=error', $ex->getMessage());
        }
    }

    private function getParams(): void
    {
        $this->type = strval($_GET['tipo'] ?? '');
        $this->definition = DaoCatalogos::getDefinition($this->type);

        $this->mode = strtoupper(strval($_GET['mode'] ?? 'INS'));
        if (!in_array($this->mode, ['INS', 'UPD', 'DEL'], true)) {
            throw new \InvalidArgumentException('La modalidad del formulario no es válida.');
        }

        $this->id = intval($_GET['id'] ?? 0);
        $this->initializeItem();

        if ($this->mode !== 'INS') {
            if ($this->id <= 0) {
                throw new \InvalidArgumentException('El registro solicitado no es válido.');
            }
            $row = DaoCatalogos::getItem($this->type, $this->id);
            if (!$row) {
                throw new \RuntimeException('No se encontró el registro solicitado.');
            }
            $this->item = $this->pickFields($row);
        }

        if ($this->isPostBack() && $this->mode !== 'DEL') {
            foreach ($this->definition['fields'] as $field) {
                if (array_key_exists($field['name'], $_POST)) {
                    $this->item[$field['name']] = trim(strval($_POST[$field['name']]));
                }
            }
        }
    }

    private function initializeItem(): void
    {
        $this->item = [];
        foreach ($this->definition['fields'] as $field) {
            if ($field['type'] === 'select' && !empty($field['options'])) {
                $this->item[$field['name']] = strval($field['options'][0]['value']);
            } else {
                $this->item[$field['name']] = '';
            }
        }
    }

    private function pickFields(array $row): array
    {
        $item = [];
        foreach ($this->definition['fields'] as $field) {
            $item[$field['name']] = strval($row[$field['name']] ?? '');
        }

        return $item;
    }

    /**
     * Revisa los campos requeridos y las opciones disponibles del catalogo.
     */
    private function validateData(): bool
    {
        $this->errors = [];

        foreach ($this->definition['fields'] as $field) {
            $name = $field['name'];
            $value = trim(strval($this->item[$name] ?? ''));
            $this->item[$name] = $value;

            if (!empty($field['required']) && $value === '') {
                $this->errors[$name] = 'Este campo es requerido.';
                continue;
            }

            if (
                !empty($field['maxlength'])
                && $value !== ''
                && $this->textLength($value) > intval($field['maxlength'])
            ) {
                $this->errors[$name] = 'El valor supera el máximo de ' . intval($field['maxlength']) . ' caracteres.';
                continue;
            }

            if ($field['type'] === 'select' && !$this->isAllowedOption($field, $value)) {
                $this->errors[$name] = 'Selecciona una opción válida.';
                continue;
            }

            if ($name === $this->definition['nameField'] && $value !== '') {
                $excludeId = $this->mode === 'UPD' ? $this->id : 0;
                if (DaoCatalogos::nameExists($this->type, $value, $excludeId)) {
                    $this->errors[$name] = 'Ya existe un registro con ese nombre.';
                }
            }
        }

        return count($this->errors) === 0;
    }

    /**
     * Guarda un dato nuevo o actualiza el dato existente.
     */
    private function handleSave(): void
    {
        try {
            if ($this->mode === 'INS') {
                if (!DaoCatalogos::insertItem($this->type, $this->item)) {
                    throw new \RuntimeException('No se pudo crear el registro.');
                }
                Bitacora::registrar(
                    'CREAR_CATALOGO',
                    'Catálogos',
                    Bitacora::RESULTADO_EXITO,
                    null,
                    null,
                    ['tipo' => $this->type, 'item' => $this->item]
                );
                Site::redirectTo($this->listUrl() . '&msg=created');
            }

            if ($this->mode === 'UPD') {
                $before = DaoCatalogos::getItem($this->type, $this->id);
                if (!DaoCatalogos::updateItem($this->type, $this->id, $this->item)) {
                    throw new \RuntimeException('No se pudo actualizar el registro.');
                }
                Bitacora::registrar(
                    'ACTUALIZAR_CATALOGO',
                    'Catálogos',
                    Bitacora::RESULTADO_EXITO,
                    $this->id,
                    ['tipo' => $this->type, 'item' => $before],
                    ['tipo' => $this->type, 'item' => $this->item]
                );
                Site::redirectTo($this->listUrl() . '&msg=updated');
            }
        } catch (\PDOException $ex) {
            if ($ex->getCode() === '23000') {
                $this->errors[$this->definition['nameField']] = 'Ya existe un registro con ese nombre.';
                return;
            }
            throw $ex;
        }
    }

    /**
     * Elimina el dato elegido cuando ya no esta siendo utilizado.
     */
    private function handleDelete(): void
    {
        if ($this->id <= 0) {
            throw new \InvalidArgumentException('El registro solicitado no es válido.');
        }

        $before = DaoCatalogos::getItem($this->type, $this->id);
        if (!$before) {
            throw new \RuntimeException('No se encontró el registro solicitado.');
        }

        try {
            if (!DaoCatalogos::deleteItem($this->type, $this->id)) {
                throw new \RuntimeException('No se pudo eliminar el registro.');
            }
        } catch (\PDOException $ex) {
            if ($ex->getCode() === '23000') {
                throw new \RuntimeException(
                    'No se puede eliminar el registro porque está en uso por otros datos del sistema.'
                );
            }
            throw $ex;
        }

        Bitacora::registrar(
            'ELIMINAR_CATALOGO',
            'Catálogos',
            Bitacora::RESULTADO_EXITO,
            $this->id,
            ['tipo' => $this->type, 'item' => $before],
            ['tipo' => $this->type, 'eliminado' => true]
        );

        Site::redirectTo($this->listUrl() . '&msg=deleted');
    }

    private function renderForm(): void
    {
        $fields = [];
        foreach ($this->definition['fields'] as $field) {
            $name = $field['name'];
            $value = strval($this->item[$name] ?? '');
            $error = strval($this->errors[$name] ?? '');

            $field['value'] = $value;
            $field['error'] = $error;
            $field['hasError'] = $error !== '';
            $field['isText'] = $field['type'] === 'text';
            $field['isTextarea'] = $field['type'] === 'textarea';
            $field['isSelect'] = $field['type'] === 'select';
            $field['full'] = !empty($field['full']);
            $field['help'] = strval($field['help'] ?? '');
            $field['requiredAttr'] = (!empty($field['required']) && $this->mode !== 'DEL') ? 'required' : '';
            $field['readonlyAttr'] = '';
            if ($this->mode === 'DEL') {
                $field['readonlyAttr'] = $field['type'] === 'select' ? 'disabled' : 'readonly';
            }

            $options = [];
            foreach ($field['options'] ?? [] as $option) {
                $option['selected'] = strval($option['value']) === $value ? 'selected' : '';
                $options[] = $option;
            }
            $field['options'] = $options;
            $fields[] = $field;
        }

        $title = $this->definition['title'];
        $formTitle = 'Nuevo registro';
        $formDescription = 'Completa los datos para agregar un registro al catálogo de ' . strtolower($title) . '.';
        $submitLabel = 'Crear registro';
        $submitIcon = 'fa-plus';

        if ($this->mode === 'UPD') {
            $formTitle = 'Editar registro';
            $formDescription = 'Actualiza los datos del registro seleccionado.';
            $submitLabel = 'Guardar cambios';
            $submitIcon = 'fa-floppy-disk';
        } elseif ($this->mode === 'DEL') {
            $formTitle = 'Eliminar registro';
            $formDescription = 'Confirma la eliminación del registro. Esta acción no se puede deshacer.';
            $submitLabel = 'Eliminar registro';
            $submitIcon = 'fa-trash-can';
        }

        $formAction = 'index.php?page=Catalogos_Catalogo&tipo=' . urlencode($this->type) . '&mode=' . $this->mode;
        if ($this->mode !== 'INS') {
            $formAction .= '&id=' . $this->id;
        }

        Renderer::render('catalogos/catalogo', [
            'CatalogFormEyebrow' => 'Catálogos / ' . $title,
            'CatalogType' => $this->type,
            'CatalogTypeTitle' => $title,
            'FormTitle' => $formTitle,
            'FormDescription' => $formDescription,
            'FormAction' => $formAction,
            'FormCancelUrl' => $this->listUrl(),
            'FormBackLabel' => 'Volver a ' . $title,
            'FormSubmitLabel' => $submitLabel,
            'FormSubmitIcon' => $submitIcon,
            'FormDanger' => $this->mode === 'DEL',
            'FormRecordName' => strval($this->item[$this->definition['nameField']] ?? ''),
            'FormHasErrors' => count($this->errors) > 0,
            'FormErrorSummary' => count($this->errors) > 0 ? 'Revisa los campos marcados en rojo.' : '',
            'FormToken' => CSRF::token('catalogo'),
            'FormMode' => $this->mode,
            'FormId' => $this->id,
            'fields' => $fields,
        ]);
    }

    private function isAllowedOption(array $field, string $value): bool
    {
        foreach ($field['options'] ?? [] as $option) {
            if (strval($option['value']) === $value) {
                return true;
            }
        }

        return false;
    }

    private function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }

    private function listUrl(): string
    {
        $page = strval($this->definition['page'] ?? 'general');

        return 'index.php?page=' . ($page === 'operativo' ? 'Catalogos_CatalogosOperativos' : 'Catalogos_Catalogos');
    }
}
