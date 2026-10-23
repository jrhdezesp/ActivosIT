<?php

namespace Controllers\Sec;

use Controllers\PublicController;
use \Utilities\Validators;
use Exception;

class Register extends PublicController
{
    private $txtEmail = "";
    private $txtPswd = "";
    private $errorEmail ="";
    private $errorPswd = "";
    private $hasErrors = false;
    private $generalError = "";
    private $csrf_token = "";
    public function run() :void
    {
        $this->csrf_token = \Utilities\CSRF::token('register');
        if ($this->isPostBack() && !\Utilities\CSRF::validate('register', $_POST['csrf_token'] ?? '')) {
            $this->txtEmail = strval($_POST['txtEmail'] ?? '');
            $this->generalError = "El formulario expiró o no es válido. Intente de nuevo.";
            \Views\Renderer::render("security/sigin", get_object_vars($this));
            return;
        }

        if ($this->isPostBack()) {
            $this->txtEmail = $_POST["txtEmail"] ?? "";
            $this->txtPswd = $_POST["txtPswd"] ?? "";
            if (!(Validators::IsValidEmail($this->txtEmail))) {
                $this->errorEmail = "El correo no tiene el formato adecuado";
                $this->hasErrors = true;
            }
            if (!Validators::IsValidPassword($this->txtPswd)) {
                $this->errorPswd = "La contraseña debe tener al menos 8 caracteres una mayúscula, un número y un caracter especial.";
                $this->hasErrors = true;
            }

            if (!$this->hasErrors) {
                try{
                    if (\Dao\Security\Security::newUsuario($this->txtEmail, $this->txtPswd)) {
                        $nuevoUsuario = \Dao\Security\Security::getUsuarioByEmail($this->txtEmail);
                        $nuevoUsuarioId = is_array($nuevoUsuario)
                            ? intval($nuevoUsuario["usercod"] ?? 0)
                            : 0;
                        \Utilities\Bitacora::registrar(
                            'REGISTRO',
                            'Seguridad',
                            \Utilities\Bitacora::RESULTADO_EXITO,
                            null,
                            null,
                            [
                                'email' => $this->txtEmail,
                                'nombre' => is_array($nuevoUsuario)
                                    ? strval($nuevoUsuario["username"] ?? '')
                                    : '',
                            ],
                            $nuevoUsuarioId > 0 ? $nuevoUsuarioId : null
                        );
                        \Utilities\Site::redirectToWithMsg("index.php?page=sec_login", "¡Usuario Registrado Satisfactoriamente!");
                    }
                } catch (\Exception $ex){
                    error_log((string) $ex);
                    \Utilities\Bitacora::registrar(
                        'REGISTRO',
                        'Seguridad',
                        \Utilities\Bitacora::RESULTADO_DENEGADO,
                        null,
                        null,
                        [
                            'email' => $this->txtEmail,
                            'motivo' => 'No se pudo crear la cuenta',
                        ]
                    );
                    $this->generalError = "No se pudo crear la cuenta. Verifique si el correo ya está registrado.";
                }
            }
        }
        $viewData = get_object_vars($this);
        \Views\Renderer::render("security/sigin", $viewData);
    }
}
