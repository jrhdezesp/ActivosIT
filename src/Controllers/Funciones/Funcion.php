<?php

namespace Controllers\Funciones;

use Controllers\PrivateController;
use Dao\Funciones\Funciones as FuncionesDao;
use Utilities\CSRF;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class Funcion extends PrivateController
{
    private $viewData = [];
    private $mode = 'DSP';
    private $modeDescriptions = [
        'DSP' => 'Detalle de %s %s',
        'INS' => 'Nueva Función',
        'UPD' => 'Editar %s %s',
        'DEL' => 'Eliminar %s %s',
    ];
    private $readonly = '';
    private $codeReadonly = '';
    private $showCommitBtn = true;
    private $funcion = [
        'fncod' => '',
        'fndsc' => '',
        'fnest' => 'ACT',
        'fntyp' => 'CTR',
    ];

    public function run(): void
    {
        try {
            $this->getData();
            if ($this->isPostBack()) {
                CSRF::requireValid('funcion', $_POST['token'] ?? '');
                if ($this->validateData()) {
                    $this->handlePostAction();
                }
            }
            $this->setViewData();
            Renderer::render('funciones/funcion', $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Funciones_Funciones',
                $ex->getMessage()
            );
        }
    }

    private function getData()
    {
        $this->mode = $_GET['mode'] ?? 'NOF';
        if (isset($this->modeDescriptions[$this->mode])) {
            $this->readonly = $this->mode === 'DEL' ? 'readonly' : '';
            $this->codeReadonly = $this->mode === 'INS' ? '' : 'readonly';
            $this->showCommitBtn = $this->mode !== 'DSP';
            if ($this->mode !== 'INS') {
                $this->funcion = FuncionesDao::getFuncionById(strval($_GET['fncod'] ?? ''));
                if (!$this->funcion) {
                    throw new \Exception('No se encontró la Función', 1);
                }
            }
        } else {
            throw new \Exception('Formulario cargado en modalidad invalida', 1);
        }
    }

    private function validateData()
    {
        $errors = [];
        $this->funcion['fncod'] = strval($_POST['fncod'] ?? '');
        $this->funcion['fndsc'] = strval($_POST['fndsc'] ?? '');
        $this->funcion['fnest'] = strval($_POST['fnest'] ?? '');
        $this->funcion['fntyp'] = strval($_POST['fntyp'] ?? '');

        // El codigo es la llave primaria: en UPD/DEL se toma siempre del GET
        // para que no pueda cambiarse manipulando el formulario.
        if ($this->mode !== 'INS') {
            $this->funcion['fncod'] = strval($_GET['fncod'] ?? '');
        }

        if (Validators::IsEmpty($this->funcion['fncod'])) {
            $errors['fncod_error'] = 'El código de la función es requerido';
        }

        if (Validators::IsEmpty($this->funcion['fndsc'])) {
            $errors['fndsc_error'] = 'La descripción de la función es requerida';
        }

        if (!in_array($this->funcion['fnest'], ['ACT', 'INA'])) {
            $errors['fnest_error'] = 'El estado de la función es invalido';
        }

        if (!in_array($this->funcion['fntyp'], ['CTR', 'MNU', 'MNT', 'RPT'])) {
            $errors['fntyp_error'] = 'El tipo de la función es invalido';
        }

        if (count($errors) > 0) {
            foreach ($errors as $key => $value) {
                $this->funcion[$key] = $value;
            }

            return false;
        }

        return true;
    }

    private function handlePostAction()
    {
        switch ($this->mode) {
            case 'INS':
                $this->handleInsert();
                break;
            case 'UPD':
                $this->handleUpdate();
                break;
            case 'DEL':
                $this->handleDelete();
                break;
            default:
                throw new \Exception('Modo invalido', 1);
                break;
        }
    }

    private function handleInsert()
    {
        $result = FuncionesDao::insertFuncion(
            $this->funcion['fncod'],
            $this->funcion['fndsc'],
            $this->funcion['fnest'],
            $this->funcion['fntyp']
        );
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Funciones_Funciones',
                'Función creada exitosamente'
            );
        }
    }

    private function handleUpdate()
    {
        $result = FuncionesDao::updateFuncion(
            $this->funcion['fncod'],
            $this->funcion['fndsc'],
            $this->funcion['fnest'],
            $this->funcion['fntyp']
        );
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Funciones_Funciones',
                'Función actualizada exitosamente'
            );
        }
    }

    private function handleDelete()
    {
        $result = FuncionesDao::deleteFuncion($this->funcion['fncod']);
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Funciones_Funciones',
                'Permiso desactivado exitosamente'
            );
        }
    }

    private function setViewData(): void
    {
        $this->viewData['mode'] = $this->mode;
        $this->viewData['funcion_xss_token'] = CSRF::token('funcion');
        $this->viewData['FormTitle'] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->funcion['fncod'],
            $this->funcion['fndsc']
        );
        $this->viewData['showCommitBtn'] = $this->showCommitBtn;
        $this->viewData['readonly'] = $this->readonly;
        $this->viewData['codeReadonly'] = $this->codeReadonly;

        $fnestKey = 'fnest_'.strtolower($this->funcion['fnest']);
        $fntypKey = 'fntyp_'.strtolower($this->funcion['fntyp']);
        $this->funcion[$fnestKey] = 'selected';
        $this->funcion[$fntypKey] = 'selected';

        $this->viewData['funcion'] = $this->funcion;
    }
}
