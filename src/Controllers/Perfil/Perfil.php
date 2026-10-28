<?php

namespace Controllers\Perfil;

use Controllers\PrivateController;
use Dao\Security\Security as SecurityDao;
use Dao\Usuarios\Usuarios as UsuariosDao;
use Utilities\Bitacora;
use Utilities\CSRF;
use Utilities\Security;
use Utilities\Site;
use Utilities\Validators;
use Views\Renderer;

/**
 * Muestra los datos de la cuenta y los roles activos de la persona conectada.
 */
class Perfil extends PrivateController
{
    // Abreviaturas usadas al presentar fechas en el perfil.
    private const MONTHS = [
        1 => 'ene',
        2 => 'feb',
        3 => 'mar',
        4 => 'abr',
        5 => 'may',
        6 => 'jun',
        7 => 'jul',
        8 => 'ago',
        9 => 'sep',
        10 => 'oct',
        11 => 'nov',
        12 => 'dic',
    ];

    // Mensajes del formulario de cambio de contrasena.
    private $passwordErrors = [];
    private $passwordSuccess = '';

    /**
     * Reune los datos de la cuenta y los prepara para su consulta.
     */
    public function run(): void
    {
        $userId = intval(Security::getUserId());
        $login = Security::getUser();
        $usuario = is_array($login) && !empty($login['userEmail'])
            ? SecurityDao::getUsuarioByEmail($login['userEmail'])
            : false;

        if (!$usuario) {
            Security::logout();
            Site::redirectTo('index.php?page=Sec_Login');
            return;
        }

        if ($this->isPostBack()) {
            $this->handlePasswordChange($usuario);
        }

        $roles = UsuariosDao::getRolesByUsuario($userId);
        $activeRoles = [];
        foreach ($roles as $rol) {
            if (($rol['roleuserest'] ?? '') === 'ACT') {
                $activeRoles[] = [
                    'rolescod' => $rol['rolescod'] ?? '',
                    'rolesdsc' => $rol['rolesdsc'] ?? '',
                ];
            }
        }

        $primaryRole = !empty($activeRoles) ? $activeRoles[0]['rolescod'] : 'Usuario';
        $usuario = $this->buildUserData($usuario, $primaryRole);
        $usuario = array_merge($usuario, $this->buildPasswordData($usuario['userpswdexp'] ?? ''));

        $rolesLabel = count($activeRoles) === 1
            ? '1 rol activo'
            : count($activeRoles) . ' roles activos';

        Renderer::render('perfil/perfil', [
            'PerfilUsuario' => $usuario,
            'PerfilRoles' => $activeRoles,
            'PerfilHasRoles' => count($activeRoles) > 0,
            'PerfilRolesLabel' => $rolesLabel,
            'PasswordFormToken' => CSRF::token('perfil_password'),
            'PasswordErrors' => $this->passwordErrors,
            'PasswordHasErrors' => count($this->passwordErrors) > 0,
            'PasswordSuccess' => $this->passwordSuccess,
            'PasswordErrorGeneral' => strval($this->passwordErrors['general'] ?? ''),
            'PasswordErrorCurrent' => strval($this->passwordErrors['current_password'] ?? ''),
            'PasswordErrorNew' => strval($this->passwordErrors['new_password'] ?? ''),
            'PasswordErrorConfirm' => strval($this->passwordErrors['confirm_password'] ?? ''),
        ]);
    }

    /**
     * Procesa el cambio de contrasena del usuario autenticado.
     *
     * @param array $usuario Cuenta actual.
     * @return void
     */
    private function handlePasswordChange(array $usuario): void
    {
        if (strval($_POST['form_action'] ?? '') !== 'cambiar_clave') {
            return;
        }

        $this->passwordErrors = [];
        $this->passwordSuccess = '';

        if (!CSRF::validate('perfil_password', $_POST['token'] ?? '')) {
            $this->passwordErrors['general'] = 'El formulario expiro o no es valido. Recarga la pagina e intenta de nuevo.';
            return;
        }

        $actual = strval($_POST['current_password'] ?? '');
        $nueva = strval($_POST['new_password'] ?? '');
        $confirmacion = strval($_POST['confirm_password'] ?? '');

        if (Validators::IsEmpty($actual)) {
            $this->passwordErrors['current_password'] = 'Ingresa tu contrasena actual.';
        } elseif (!SecurityDao::verifyPassword($actual, strval($usuario['clave_usuario'] ?? ''))) {
            $this->passwordErrors['current_password'] = 'La contrasena actual no es correcta.';
        }

        if (Validators::IsEmpty($nueva)) {
            $this->passwordErrors['new_password'] = 'Ingresa una nueva contrasena.';
        } elseif (!Validators::IsValidPassword($nueva)) {
            $this->passwordErrors['new_password'] = 'La nueva contrasena debe tener entre 8 y 32 caracteres, una mayuscula, una minuscula, un numero y un simbolo.';
        } elseif ($nueva === $actual) {
            $this->passwordErrors['new_password'] = 'La nueva contrasena debe ser diferente a la actual.';
        }

        if ($confirmacion === '') {
            $this->passwordErrors['confirm_password'] = 'Confirma la nueva contrasena.';
        } elseif ($nueva !== $confirmacion) {
            $this->passwordErrors['confirm_password'] = 'La confirmacion no coincide con la nueva contrasena.';
        }

        if (count($this->passwordErrors) > 0) {
            Bitacora::registrar(
                'CAMBIAR_CONTRASENA',
                'Seguridad',
                Bitacora::RESULTADO_DENEGADO,
                intval($usuario['id_usuario'] ?? 0),
                null,
                ['motivo' => 'Validacion del formulario']
            );
            return;
        }

        $updated = SecurityDao::updatePassword(
            intval($usuario['id_usuario'] ?? 0),
            $nueva
        );

        if ($updated <= 0) {
            $this->passwordErrors['general'] = 'No se pudo actualizar la contrasena. Intenta de nuevo.';
            return;
        }

        Bitacora::registrar(
            'CAMBIAR_CONTRASENA',
            'Seguridad',
            Bitacora::RESULTADO_EXITO,
            intval($usuario['id_usuario'] ?? 0),
            null,
            ['motivo' => 'Cambio desde el perfil']
        );

        $this->passwordSuccess = 'Contrasena actualizada correctamente.';
    }

    private function buildUserData(array $usuario, string $primaryRole): array
    {
        unset($usuario['userpswd'], $usuario['clave_usuario']);

        $usuario['userestDsc'] = $this->userStatusLabel($usuario['userest'] ?? '');
        $usuario['usertipoDsc'] = $primaryRole;
        $usuario['initials'] = $this->initials(
            $usuario['username'] ?? '',
            $usuario['useremail'] ?? ''
        );

        $ultimoAcceso = $usuario['ultimo_acceso_usuario'] ?? '';
        $usuario['userfchingFmt'] = !empty($ultimoAcceso)
            ? $this->formatDate($ultimoAcceso)
            : 'Sesión actual';

        $usuario['userpswdchgFmt'] = !empty($usuario['userpswdchg'] ?? '')
            ? $this->formatDate($usuario['userpswdchg'])
            : 'No registrada';

        $usuario['userpswdexpFmt'] = !empty($usuario['userpswdexp'] ?? '')
            ? $this->formatDate($usuario['userpswdexp'])
            : 'Sin expiración';

        return $usuario;
    }

    private function buildPasswordData(string $expiry): array
    {
        $expiryTime = $expiry !== '' ? strtotime($expiry) : false;
        if ($expiryTime === false) {
            return [
                'passwordStatus' => 'Activa',
                'passwordBadge' => 'badge--success',
                'passwordNotice' => 'Contraseña protegida mediante algoritmo seguro.',
                'passwordExpired' => false,
            ];
        }

        $days = (int) floor(($expiryTime - time()) / 86400);
        if ($days < 0) {
            return [
                'passwordStatus' => 'Vencida',
                'passwordBadge' => 'badge--danger',
                'passwordNotice' => 'La contraseña venció hace ' . $this->daysLabel(abs($days)) . '.',
                'passwordExpired' => true,
            ];
        }

        if ($days <= 30) {
            return [
                'passwordStatus' => 'Por vencer',
                'passwordBadge' => 'badge--warning',
                'passwordNotice' => 'La contraseña vence en ' . $this->daysLabel($days) . '.',
                'passwordExpired' => false,
            ];
        }

        return [
            'passwordStatus' => 'Vigente',
            'passwordBadge' => 'badge--success',
            'passwordNotice' => 'La contraseña vence en ' . $this->daysLabel($days) . '.',
            'passwordExpired' => false,
        ];
    }

    private function userStatusLabel(string $status): string
    {
        if ($status === 'ACT' || $status === '1') {
            return 'Activo';
        }
        if ($status === 'INA' || $status === '0') {
            return 'Inactivo';
        }
        return 'Sin asignar';
    }

    private function initials(string $name, string $email): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = trim($email);
        }
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }

        return $initials !== '' ? $initials : '?';
    }

    private function formatDate(string $value): string
    {
        if ($value === '') {
            return 'No registrada';
        }

        $time = strtotime($value);
        if ($time === false) {
            return 'No registrada';
        }

        $month = self::MONTHS[(int) date('n', $time)] ?? '';
        return date('d', $time) . ' ' . $month . ' ' . date('Y', $time);
    }

    private function daysLabel(int $days): string
    {
        return $days === 1 ? '1 día' : $days . ' días';
    }
}
