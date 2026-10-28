<?php

/**
 * Genera el hash de la clave para el alta inicial del administrador.
 *
 * Uso:
 *   php scripts/create_admin.php "ClaveSegura#2026"
 *
 * El hash se pega en docs/scripts/admin_bootstrap.sql. Este script no
 * crea usuarios ni guarda credenciales en el repositorio.
 */

require __DIR__ . '/../vendor/autoload.php';

use Utilities\Site;
use Utilities\Validators;

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Este script se ejecuta por CLI.\n");
    exit(1);
}

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDOUT, 'Clave del administrador: ');
    $password = trim(strval(fgets(STDIN)));
}

if (!Validators::IsValidPassword($password)) {
    fwrite(STDERR, "La clave debe tener entre 8 y 32 caracteres, una mayuscula, una minuscula, un numero y un caracter especial.\n");
    exit(1);
}

Site::configure();

echo \Dao\Security\Security::hashPassword($password), PHP_EOL;
