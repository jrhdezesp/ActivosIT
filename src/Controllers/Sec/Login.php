<?php
namespace Controllers\Sec;

use Controllers\PublicController;
use Dao\Security\Security as DaoSecurity;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * Permite iniciar una sesion con una cuenta activa.
 */
class Login extends PublicController
{
    private $txtEmail = "";
    private $txtPswd = "";
    private $errorEmail = "";
    private $errorPswd = "";
    private $generalError = "";
    private $hasError = false;
    private $csrf_token = "";

    /**
     * Revisa los datos de acceso y abre la sesion cuando son validos.
     */
    public function run() :void
    {
        $this->csrf_token = CSRF::token('login');
        if ($this->isPostBack() && !CSRF::validate('login', $_POST['csrf_token'] ?? '')) {
            $this->txtEmail = strval($_POST['txtEmail'] ?? '');
            $this->generalError = "El formulario expiró o no es válido. Intente de nuevo.";
            Bitacora::registrar(
                'LOGIN',
                'Seguridad',
                Bitacora::RESULTADO_DENEGADO,
                null,
                null,
                ['email' => $this->txtEmail, 'motivo' => 'Token CSRF inválido']
            );
            Renderer::render("security/login", get_object_vars($this));
            return;
        }
        if ($this->isPostBack()) {
            $this->txtEmail = $_POST["txtEmail"] ?? "";
            $this->txtPswd = $_POST["txtPswd"] ?? "";

            if (!Validators::IsValidEmail($this->txtEmail)) {
                $this->errorEmail = "¡Correo no tiene el formato adecuado!";
                $this->hasError = true;
            }
            if (Validators::IsEmpty($this->txtPswd)) {
                $this->errorPswd = "¡Debe ingresar una contraseña!";
                $this->hasError = true;
            }
            if (! $this->hasError) {
                if ($dbUser = DaoSecurity::getUsuarioByEmail($this->txtEmail)) {
                    $isActive = ($dbUser["estado_usuario"] == 1 || $dbUser["userest"] === 'ACT');
                    if (!$isActive) {
                        $this->generalError = "¡Credenciales son incorrectas!";
                        $this->hasError = true;
                        error_log(
                            sprintf(
                                "ERROR: %d %s tiene cuenta inactiva",
                                $dbUser["usercod"],
                                $dbUser["useremail"]
                            )
                        );
                        Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            Bitacora::RESULTADO_DENEGADO,
                            null,
                            null,
                            [
                                'email' => $dbUser["useremail"],
                                'motivo' => 'Cuenta inactiva',
                            ],
                            intval($dbUser["usercod"])
                        );
                    }
                    if (! $this->hasError && !DaoSecurity::verifyPassword($this->txtPswd, $dbUser["userpswd"])) {
                        $this->generalError = "¡Credenciales son incorrectas!";
                        $this->hasError = true;
                        error_log(
                            sprintf(
                                "ERROR: %d %s contraseña incorrecta",
                                $dbUser["usercod"],
                                $dbUser["useremail"]
                            )
                        );
                        Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            Bitacora::RESULTADO_DENEGADO,
                            null,
                            null,
                            [
                                'email' => $dbUser["useremail"],
                                'motivo' => 'Contraseña incorrecta',
                            ],
                            intval($dbUser["usercod"])
                        );
                    }
                    if (! $this->hasError) {
                        DaoSecurity::updateUltimoAcceso($dbUser["usercod"]);
                        Security::login(
                            $dbUser["usercod"],
                            $dbUser["username"],
                            $dbUser["useremail"]
                        );
                        Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            Bitacora::RESULTADO_EXITO,
                            null,
                            null,
                            [
                                'email' => $dbUser["useremail"],
                                'nombre' => $dbUser["username"],
                            ]
                        );
                        Site::redirectTo("index.php?page=Dashboard_Dashboard");
                    }
                } else {
                    error_log(
                        sprintf(
                            "ERROR: %s trato de ingresar",
                            $this->txtEmail
                        )
                    );
                    Bitacora::registrar(
                        'LOGIN',
                        'Seguridad',
                        Bitacora::RESULTADO_DENEGADO,
                        null,
                        null,
                        [
                            'email' => $this->txtEmail,
                            'motivo' => 'Cuenta no encontrada',
                        ]
                    );
                    $this->generalError = "¡Credenciales son incorrectas!";
                }
            }
        }
        $dataView = get_object_vars($this);
        Renderer::render("security/login", $dataView);
    }
}
