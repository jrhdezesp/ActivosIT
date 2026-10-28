<?php
namespace Controllers\Sec;

use Controllers\PublicController;
use Dao\Security\LoginAttempts as LoginAttemptsDao;
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
    private $motivo = "";

    /**
     * Revisa los datos de acceso y abre la sesion cuando son validos.
     */
    public function run() :void
    {
        $this->csrf_token = CSRF::token('login');
        $this->motivo = strval($_GET['motivo'] ?? '');
        if ($this->motivo === 'sesion_reemplazada' && !$this->isPostBack()) {
            $this->generalError = "Tu sesión se cerró porque se inició sesión en otro dispositivo.";
        }
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
            $ip = Bitacora::getClientIp();

            if (LoginAttemptsDao::isBlocked($this->txtEmail, $ip)) {
                $this->generalError = "Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.";
                $this->hasError = true;
                Bitacora::registrar(
                    'LOGIN',
                    'Seguridad',
                    Bitacora::RESULTADO_DENEGADO,
                    null,
                    null,
                    ['email' => $this->txtEmail, 'motivo' => 'Bloqueo temporal por intentos']
                );
            }

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
                        $this->registerFailure();
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
                        $this->registerFailure();
                    }
                    if (! $this->hasError) {
                        LoginAttemptsDao::clear($this->txtEmail, $ip);
                        DaoSecurity::updateUltimoAcceso($dbUser["usercod"]);
                        // Si la cuenta tenia una sesion abierta, este inicio
                        // de sesion la reemplaza y se deja rastro en auditoria.
                        $teniaSesionActiva = DaoSecurity::getSesionToken($dbUser["usercod"]) !== null;
                        Security::login(
                            $dbUser["usercod"],
                            $dbUser["username"],
                            $dbUser["useremail"]
                        );
                        if ($teniaSesionActiva) {
                            Bitacora::registrar(
                                'SESION_REEMPLAZADA',
                                'Seguridad',
                                Bitacora::RESULTADO_EXITO,
                                null,
                                null,
                                [
                                    'email' => $dbUser["useremail"],
                                    'motivo' => 'El inicio de sesion revoco la sesion anterior',
                                    'ip_nueva' => $ip,
                                ],
                                intval($dbUser["usercod"])
                            );
                        }
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
                    $this->hasError = true;
                    $this->generalError = "¡Credenciales son incorrectas!";
                    $this->registerFailure();
                }
            }
        }
        $dataView = get_object_vars($this);
        Renderer::render("security/login", $dataView);
    }

    /**
     * Registra un intento fallido y muestra el aviso de bloqueo si aplica.
     *
     * @return void
     */
    private function registerFailure(): void
    {
        $status = LoginAttemptsDao::registerFailure(
            $this->txtEmail,
            Bitacora::getClientIp()
        );

        if (!empty($status['bloqueado_hasta'])) {
            $this->generalError = "Demasiados intentos fallidos. Intenta de nuevo en 15 minutos.";
        }
    }
}
