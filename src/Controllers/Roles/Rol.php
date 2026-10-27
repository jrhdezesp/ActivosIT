<?php

namespace Controllers\Roles;

use Controllers\PrivateController;
use Dao\Funciones\Funciones as FuncionesDao;
use Dao\Roles\Roles as RolesDao;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * Atiende la creacion, consulta y actualizacion de un rol y sus funciones.
 */
class Rol extends PrivateController
{
    private $viewData = [];
    private $mode = 'DSP';
    private $modeDescriptions = [
        'DSP' => 'Detalle de %s %s',
        'INS' => 'Nuevo Rol',
        'UPD' => 'Editar %s %s',
        'DEL' => 'Eliminar %s %s',
    ];
    private $readonly = '';
    private $codeReadonly = '';
    private $showCommitBtn = true;
    private $rol = [
        'rolescod' => '',
        'rolesdsc' => '',
        'rolesest' => 'ACT',
    ];
    // Funciones que pueden asignarse al rol seleccionado.
    private $funcionesRol = [];

    /**
     * Carga el rol, procesa sus datos o funciones y muestra el formulario.
     */
    public function run(): void
    {
        try {
            $this->getData();
            $this->loadFuncionesData();
            if ($this->isPostBack()) {
                CSRF::requireValid('rol', $_POST['token'] ?? '');
                $formAction = strval($_POST['form_action'] ?? 'rol');
                if ($formAction === 'funciones_rol') {
                    $this->handleFuncionesRolPost();
                } elseif ($this->validateData()) {
                    $this->handlePostAction();
                }
            }
            $this->setViewData();
            Renderer::render('roles/rol', $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Roles_Roles',
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
                $this->rol = RolesDao::getRolById(strval($_GET['rolescod'] ?? ''));
                if (!$this->rol) {
                    throw new \Exception('No se encontró el Rol', 1);
                }
            }
        } else {
            throw new \Exception('Formulario cargado en modalidad invalida', 1);
        }
    }

    /**
     * Revisa los datos principales antes de guardar el rol.
     */
    private function validateData()
    {
        $errors = [];
        $this->rol['rolescod'] = strval($_POST['rolescod'] ?? '');
        $this->rol['rolesdsc'] = strval($_POST['rolesdsc'] ?? '');
        $this->rol['rolesest'] = strval($_POST['rolesest'] ?? '');

        // El codigo es la llave primaria: en UPD/DEL se toma siempre del GET
        // para que no pueda cambiarse manipulando el formulario.
        if ($this->mode !== 'INS') {
            $this->rol['rolescod'] = strval($_GET['rolescod'] ?? '');
        }

        if (Validators::IsEmpty($this->rol['rolescod'])) {
            $errors['rolescod_error'] = 'El código del rol es requerido';
        }

        if (Validators::IsEmpty($this->rol['rolesdsc'])) {
            $errors['rolesdsc_error'] = 'La descripción del rol es requerida';
        }

        if (!in_array($this->rol['rolesest'], ['ACT', 'INA'])) {
            $errors['rolesest_error'] = 'El estado del rol es invalido';
        }

        if (count($errors) > 0) {
            foreach ($errors as $key => $value) {
                $this->rol[$key] = $value;
            }

            return false;
        }

        return true;
    }

    /**
     * Dirige la solicitud a la accion de crear, actualizar o desactivar el rol.
     */
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
        $result = RolesDao::insertRol(
            $this->rol['rolescod'],
            $this->rol['rolesdsc'],
            $this->rol['rolesest']
        );
        if ($result > 0) {
            Bitacora::registrar(
                'CREAR_ROL',
                'Seguridad',
                Bitacora::RESULTADO_EXITO,
                null,
                null,
                $this->auditRolData()
            );
            Site::redirectToWithMsg(
                'index.php?page=Roles_Roles',
                'Rol creado exitosamente'
            );
        }
    }

    private function handleUpdate()
    {
        $before = RolesDao::getRolById($this->rol['rolescod']);
        $result = RolesDao::updateRol(
            $this->rol['rolescod'],
            $this->rol['rolesdsc'],
            $this->rol['rolesest']
        );
        if ($result > 0) {
            Bitacora::registrar(
                'ACTUALIZAR_ROL',
                'Seguridad',
                Bitacora::RESULTADO_EXITO,
                null,
                $this->auditRolData($before),
                $this->auditRolData()
            );
            Site::redirectToWithMsg(
                'index.php?page=Roles_Roles',
                'Rol actualizado exitosamente'
            );
        }
    }

    private function handleDelete()
    {
        $before = RolesDao::getRolById($this->rol['rolescod']);
        $result = RolesDao::deleteRol($this->rol['rolescod']);
        if ($result > 0) {
            Bitacora::registrar(
                'DESACTIVAR_ROL',
                'Seguridad',
                Bitacora::RESULTADO_EXITO,
                null,
                $this->auditRolData($before),
                ['rolescod' => $this->rol['rolescod'], 'rolesest' => 'INA']
            );
            Site::redirectToWithMsg(
                'index.php?page=Roles_Roles',
                'Rol desactivado exitosamente'
            );
        }
    }

    private function auditRolData($rol = null): array
    {
        $rol = is_array($rol) ? $rol : $this->rol;

        return [
            'rolescod' => strval($rol['rolescod'] ?? ''),
            'rolesdsc' => strval($rol['rolesdsc'] ?? ''),
            'rolesest' => strval($rol['rolesest'] ?? ''),
        ];
    }

    private function loadFuncionesData(): void
    {
        if ($this->mode !== 'INS') {
            $this->funcionesRol = RolesDao::getFuncionesByRol($this->rol['rolescod']);
            foreach ($this->funcionesRol as &$funcion) {
                $stateKey = 'fnrolest_'.strtolower($funcion['fnrolest']);
                $funcion[$stateKey] = 'selected';
            }
            unset($funcion);
        }
    }

    private function handleFuncionesRolPost(): void
    {
        if ($this->mode === 'INS' || $this->mode === 'DEL') {
            throw new \Exception('No se puede editar funciones en este modo.', 1);
        }

        $fncod = strval($_POST['fncod'] ?? '');
        $fnrolest = strval($_POST['fnrolest'] ?? '');

        if (Validators::IsEmpty($fncod)) {
            throw new \Exception('Debe seleccionar una funcion valida.', 1);
        }

        if (!in_array($fnrolest, ['ACT', 'INA'])) {
            throw new \Exception('El estado de la funcion para el rol es invalido.', 1);
        }

        if (!FuncionesDao::getFuncionById($fncod)) {
            Bitacora::registrar(
                'ACTUALIZAR_FUNCIONES_ROL',
                'Seguridad',
                Bitacora::RESULTADO_ERROR,
                null,
                null,
                [
                    'rolescod' => $this->rol['rolescod'],
                    'fncod' => $fncod,
                    'fnrolest' => $fnrolest,
                    'motivo' => 'Funcion inexistente',
                ]
            );
            throw new \Exception('La funcion seleccionada no existe.', 1);
        }

        RolesDao::upsertRolFuncion(
            $this->rol['rolescod'],
            $fncod,
            $fnrolest
        );

        Bitacora::registrar(
            'ACTUALIZAR_FUNCIONES_ROL',
            'Seguridad',
            Bitacora::RESULTADO_EXITO,
            null,
            null,
            [
                'rolescod' => $this->rol['rolescod'],
                'fncod' => $fncod,
                'fnrolest' => $fnrolest,
            ]
        );

        Site::redirectToWithMsg(
            sprintf('index.php?page=Roles_Rol&mode=%s&rolescod=%s', $this->mode, urlencode($this->rol['rolescod'])),
            'Funcion del rol actualizada exitosamente'
        );
    }

    private function setViewData(): void
    {
        $this->viewData['mode'] = $this->mode;
        $this->viewData['rolescod'] = $this->rol['rolescod'];
        $this->viewData['rol_xss_token'] = CSRF::token('rol');
        $this->viewData['FormTitle'] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->rol['rolescod'],
            $this->rol['rolesdsc']
        );
        $this->viewData['showCommitBtn'] = $this->showCommitBtn;
        $this->viewData['readonly'] = $this->readonly;
        $this->viewData['codeReadonly'] = $this->codeReadonly;

        $rolesestKey = 'rolesest_'.strtolower($this->rol['rolesest']);
        $this->rol[$rolesestKey] = 'selected';

        $this->viewData['rol'] = $this->rol;
        $this->viewData['funcionesRol'] = $this->funcionesRol;
        $this->viewData['showFuncionesRolManager'] = $this->mode !== 'INS' && $this->mode !== 'DEL';
    }
}
