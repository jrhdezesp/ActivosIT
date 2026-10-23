<?php

namespace Controllers\Usuarios;

use Controllers\PrivateController;
use Dao\Usuarios\Usuarios as UsuariosDao;
use Utilities\CSRF;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

class Usuario extends PrivateController
{
    private $viewData = [];
    private $mode = 'DSP';
    private $modeDescriptions = [
        'DSP' => 'Detalle de %s %s',
        'INS' => 'Nuevo Usuario',
        'UPD' => 'Editar %s %s',
        'DEL' => 'Eliminar %s %s',
    ];
    private $readonly = '';
    private $showCommitBtn = true;
    private $usuario = [
        'usercod' => 0,
        'useremail' => '',
        'username' => '',
        'userpswd' => '',
        'userest' => 'ACT',
        'usertipo' => 'PBL',
    ];
    private $rolesUsuario = [];

    public function run(): void
    {
        try {
            $this->getData();
            $this->loadRolesData();
            if ($this->isPostBack()) {
                CSRF::requireValid('usuario', $_POST['token'] ?? '');
                $formAction = strval($_POST['form_action'] ?? 'usuario');
                if ($formAction === 'roles_usuario') {
                    $this->handleRolesUsuarioPost();
                } elseif ($this->validateData()) {
                    $this->handlePostAction();
                }
            }
            $this->setViewData();
            Renderer::render('usuarios/usuario', $this->viewData);
        } catch (\Exception $ex) {
            Site::redirectToWithMsg(
                'index.php?page=Usuarios_Usuarios',
                $ex->getMessage()
            );
        }
    }

    private function getData()
    {
        $this->mode = $_GET['mode'] ?? 'NOF';
        if (isset($this->modeDescriptions[$this->mode])) {
            $this->readonly = $this->mode === 'DEL' ? 'readonly' : '';
            $this->showCommitBtn = $this->mode !== 'DSP';
            if ($this->mode !== 'INS') {
                $this->usuario = UsuariosDao::getUsuarioById(intval($_GET['usercod'] ?? 0));
                if (!$this->usuario) {
                    throw new \Exception('No se encontró el Usuario', 1);
                }
                $this->usuario['userpswd'] = '';
            }
        } else {
            throw new \Exception('Formulario cargado en modalidad invalida', 1);
        }
    }

    private function validateData()
    {
        $errors = [];
        $this->usuario['usercod'] = $this->mode === 'INS'
            ? 0
            : intval($_GET['usercod'] ?? 0);
        $this->usuario['useremail'] = strval($_POST['useremail'] ?? '');
        $this->usuario['username'] = strval($_POST['username'] ?? '');
        $this->usuario['userpswd'] = strval($_POST['userpswd'] ?? '');
        $this->usuario['userest'] = strval($_POST['userest'] ?? '');
        $this->usuario['usertipo'] = strval($_POST['usertipo'] ?? '');

        if (Validators::IsEmpty($this->usuario['useremail'])) {
            $errors['useremail_error'] = 'El correo del usuario es requerido';
        }

        if (!Validators::IsValidEmail($this->usuario['useremail'])) {
            $errors['useremail_error'] = 'El correo del usuario no es válido';
        }

        if (Validators::IsEmpty($this->usuario['username'])) {
            $errors['username_error'] = 'El nombre del usuario es requerido';
        }

        if ($this->mode === 'INS' && Validators::IsEmpty($this->usuario['userpswd'])) {
            $errors['userpswd_error'] = 'La contraseña del usuario es requerida';
        }

        if (
            !Validators::IsEmpty($this->usuario['userpswd'])
            && !Validators::IsValidPassword($this->usuario['userpswd'])
        ) {
            $errors['userpswd_error'] = 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un carácter especial';
        }

        if (!in_array($this->usuario['userest'], ['ACT', 'INA'])) {
            $errors['userest_error'] = 'El estado del usuario es invalido';
        }

        if (!in_array($this->usuario['usertipo'], ['PBL', 'ADM', 'AUD', 'TEC'])) {
            $errors['usertipo_error'] = 'El tipo de usuario es invalido';
        }

        if (count($errors) > 0) {
            foreach ($errors as $key => $value) {
                $this->usuario[$key] = $value;
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
        $result = UsuariosDao::insertUsuario(
            $this->usuario['useremail'],
            $this->usuario['username'],
            $this->usuario['userpswd'],
            $this->usuario['userest'],
            $this->usuario['usertipo']
        );
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Usuarios_Usuarios',
                'Usuario creado exitosamente'
            );
        }
    }

    private function handleUpdate()
    {
        $result = UsuariosDao::updateUsuario(
            $this->usuario['usercod'],
            $this->usuario['useremail'],
            $this->usuario['username'],
            $this->usuario['userpswd'],
            $this->usuario['userest'],
            $this->usuario['usertipo']
        );
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Usuarios_Usuarios',
                'Usuario actualizado exitosamente'
            );
        }
    }

    private function handleDelete()
    {
        if (intval($this->usuario['usercod']) === intval(\Utilities\Security::getUserId())) {
            throw new \Exception('No puede desactivar su propia cuenta.', 1);
        }
        $result = UsuariosDao::deleteUsuario($this->usuario['usercod']);
        if ($result > 0) {
            Site::redirectToWithMsg(
                'index.php?page=Usuarios_Usuarios',
                'Usuario desactivado exitosamente'
            );
        }
    }

    private function loadRolesData(): void
    {
        if ($this->mode !== 'INS') {
            $this->rolesUsuario = UsuariosDao::getRolesByUsuario($this->usuario['usercod']);
            foreach ($this->rolesUsuario as &$rol) {
                $stateKey = 'roleuserest_'.strtolower($rol['roleuserest']);
                $rol[$stateKey] = 'selected';
            }
            unset($rol);
        }
    }

    private function handleRolesUsuarioPost(): void
    {
        if ($this->mode === 'INS' || $this->mode === 'DEL') {
            throw new \Exception('No se puede editar roles en este modo.', 1);
        }

        $rolescod = strval($_POST['rolescod'] ?? '');
        $roleuserest = strval($_POST['roleuserest'] ?? '');

        if (Validators::IsEmpty($rolescod)) {
            throw new \Exception('Debe seleccionar un rol valido.', 1);
        }

        if (!in_array($roleuserest, ['ACT', 'INA'])) {
            throw new \Exception('El estado del rol para el usuario es invalido.', 1);
        }

        UsuariosDao::upsertUsuarioRol(
            intval($this->usuario['usercod']),
            $rolescod,
            $roleuserest
        );

        Site::redirectToWithMsg(
            sprintf('index.php?page=Usuarios_Usuario&mode=%s&usercod=%d', $this->mode, $this->usuario['usercod']),
            'Rol del usuario actualizado exitosamente'
        );
    }

    private function setViewData(): void
    {
        $this->viewData['mode'] = $this->mode;
        $this->viewData['usercod'] = $this->usuario['usercod'];
        $this->viewData['usuario_xss_token'] = CSRF::token('usuario');
        $this->viewData['FormTitle'] = sprintf(
            $this->modeDescriptions[$this->mode],
            $this->usuario['usercod'],
            $this->usuario['username']
        );
        $this->viewData['showCommitBtn'] = $this->showCommitBtn;
        $this->viewData['readonly'] = $this->readonly;

        $userestKey = 'userest_'.strtolower($this->usuario['userest']);
        $usertipoKey = 'usertipo_'.strtolower($this->usuario['usertipo']);
        $this->usuario[$userestKey] = 'selected';
        $this->usuario[$usertipoKey] = 'selected';

        $this->viewData['usuario'] = $this->usuario;
        $this->viewData['rolesUsuario'] = $this->rolesUsuario;
        $this->viewData['showRolesUsuarioManager'] = $this->mode !== 'INS' && $this->mode !== 'DEL';
    }
}
