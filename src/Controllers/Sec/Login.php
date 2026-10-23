<?php
namespace Controllers\Sec;
class Login extends \Controllers\PublicController
{
    private $txtEmail = "";
    private $txtPswd = "";
    private $errorEmail = "";
    private $errorPswd = "";
    private $generalError = "";
    private $hasError = false;
    private $csrf_token = "";

    public function run() :void
    {
        $this->csrf_token = \Utilities\CSRF::token('login');
        if (!\Utilities\Site::isLocalUrl(\Utilities\Context::getContextByKey('redirto'))) {
            \Utilities\Context::setContext('redirto', '');
        }
        if ($this->isPostBack() && !\Utilities\CSRF::validate('login', $_POST['csrf_token'] ?? '')) {
            $this->txtEmail = strval($_POST['txtEmail'] ?? '');
            $this->generalError = "El formulario expiró o no es válido. Intente de nuevo.";
            \Utilities\Bitacora::registrar(
                'LOGIN',
                'Seguridad',
                \Utilities\Bitacora::RESULTADO_DENEGADO,
                null,
                null,
                ['email' => $this->txtEmail, 'motivo' => 'Token CSRF inválido']
            );
            \Views\Renderer::render("security/login", get_object_vars($this));
            return;
        }
        if ($this->isPostBack()) {
            $this->txtEmail = $_POST["txtEmail"] ?? "";
            $this->txtPswd = $_POST["txtPswd"] ?? "";

            if (!\Utilities\Validators::IsValidEmail($this->txtEmail)) {
                $this->errorEmail = "¡Correo no tiene el formato adecuado!";
                $this->hasError = true;
            }
            if (\Utilities\Validators::IsEmpty($this->txtPswd)) {
                $this->errorPswd = "¡Debe ingresar una contraseña!";
                $this->hasError = true;
            }
            if (! $this->hasError) {
                if ($dbUser = \Dao\Security\Security::getUsuarioByEmail($this->txtEmail)) {
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
                        \Utilities\Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            \Utilities\Bitacora::RESULTADO_DENEGADO,
                            null,
                            null,
                            [
                                'email' => $dbUser["useremail"],
                                'motivo' => 'Cuenta inactiva',
                            ],
                            intval($dbUser["usercod"])
                        );
                    }
                    if (! $this->hasError && !\Dao\Security\Security::verifyPassword($this->txtPswd, $dbUser["userpswd"])) {
                        $this->generalError = "¡Credenciales son incorrectas!";
                        $this->hasError = true;
                        error_log(
                            sprintf(
                                "ERROR: %d %s contraseña incorrecta",
                                $dbUser["usercod"],
                                $dbUser["useremail"]
                            )
                        );
                        \Utilities\Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            \Utilities\Bitacora::RESULTADO_DENEGADO,
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
                        \Dao\Security\Security::updateUltimoAcceso($dbUser["usercod"]);
                        \Utilities\Security::login(
                            $dbUser["usercod"],
                            $dbUser["username"],
                            $dbUser["useremail"]
                        );
                        \Utilities\Bitacora::registrar(
                            'LOGIN',
                            'Seguridad',
                            \Utilities\Bitacora::RESULTADO_EXITO,
                            null,
                            null,
                            [
                                'email' => $dbUser["useremail"],
                                'nombre' => $dbUser["username"],
                            ]
                        );
                        if (\Utilities\Context::getContextByKey("redirto") !== "") {
                            \Utilities\Site::redirectTo(
                                \Utilities\Context::getContextByKey("redirto")
                            );
                        } else {
                            \Utilities\Site::redirectTo("index.php?page=Dashboard_Dashboard");
                        }
                    }
                } else {
                    error_log(
                        sprintf(
                            "ERROR: %s trato de ingresar",
                            $this->txtEmail
                        )
                    );
                    \Utilities\Bitacora::registrar(
                        'LOGIN',
                        'Seguridad',
                        \Utilities\Bitacora::RESULTADO_DENEGADO,
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
        \Views\Renderer::render("security/login", $dataView);
    }
}
