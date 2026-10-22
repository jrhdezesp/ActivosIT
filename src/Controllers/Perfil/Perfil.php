<?php

namespace Controllers\Perfil;

use Controllers\PrivateController;
use Dao\Security\Security as SecurityDao;
use Dao\Usuarios\Usuarios as UsuariosDao;
use Utilities\Security;
use Utilities\Site;
use Views\Renderer;

class Perfil extends PrivateController
{
    private const USER_TYPE_LABELS = [
        'ADM' => 'Administrador',
        'AUD' => 'Auditor',
        'PBL' => 'Público',
    ];

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

        $usuario = $this->buildUserData($usuario);
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

    private function buildUserData(array $usuario): array
    {
        unset($usuario['userpswd']);

        $usuario['userestDsc'] = $this->userStatusLabel($usuario['userest'] ?? '');
        $usuario['usertipoDsc'] = self::USER_TYPE_LABELS[$usuario['usertipo'] ?? '']
            ?? 'Sin asignar';
        $usuario['initials'] = $this->initials(
            $usuario['username'] ?? '',
            $usuario['useremail'] ?? ''
        );
        $usuario['userfchingFmt'] = $this->formatDate($usuario['userfching'] ?? '');
        $usuario['userpswdchgFmt'] = $this->formatDate($usuario['userpswdchg'] ?? '');
        $usuario['userpswdexpFmt'] = $this->formatDate($usuario['userpswdexp'] ?? '');

        return $usuario;
    }

    private function buildPasswordData(string $expiry): array
    {
        $expiryTime = $expiry !== '' ? strtotime($expiry) : false;
        if ($expiryTime === false) {
            return [
                'passwordStatus' => 'Sin fecha',
                'passwordBadge' => 'badge--outline',
                'passwordNotice' => 'No hay una fecha de vencimiento registrada.',
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
        if ($status === 'ACT') {
            return 'Activo';
        }
        if ($status === 'INA') {
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
