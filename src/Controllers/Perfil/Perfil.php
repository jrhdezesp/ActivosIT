<?php

namespace Controllers\Perfil;

use Controllers\PrivateController;
use Dao\Security\Security as SecurityDao;
use Dao\Usuarios\Usuarios as UsuariosDao;
use Utilities\Security;
use Utilities\Site;
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
        ]);
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
