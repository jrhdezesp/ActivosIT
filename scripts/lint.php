<?php

/**
 * Revisa la sintaxis de todos los archivos PHP del proyecto y valida los JSON
 * de configuracion. Se puede ejecutar con:
 *
 *   php scripts/lint.php
 *   composer lint
 */

$root = dirname(__DIR__);
// Directorios generados o locales que no se deben revisar.
$excludedDirs = ['.git', 'vendor', 'node_modules', 'data'];
$errors = [];
$checked = 0;

// Recorre el proyecto y omite los directorios excluidos.
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);

    foreach ($excludedDirs as $dir) {
        if (strpos($relative, $dir . DIRECTORY_SEPARATOR) === 0 || strpos($relative, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR) !== false) {
            continue 2;
        }
    }

    if (strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $checked++;
    // Ejecuta php -l sobre cada archivo encontrado.
    $output = [];
    $exitCode = 0;
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        $errors[] = $relative . PHP_EOL . implode(PHP_EOL, $output);
    }
}

foreach (['composer.json', 'nav.config.json'] as $jsonFile) {
    // Valida los archivos JSON de configuracion del proyecto.
    $jsonPath = $root . DIRECTORY_SEPARATOR . $jsonFile;
    if (!is_file($jsonPath)) {
        $errors[] = 'Falta el archivo ' . $jsonFile;
        continue;
    }

    json_decode(file_get_contents($jsonPath), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = $jsonFile . ': ' . json_last_error_msg();
    }
}

echo PHP_EOL . 'Archivos PHP revisados: ' . $checked . PHP_EOL;
echo 'Archivos JSON revisados: 2' . PHP_EOL;

if (count($errors) > 0) {
    echo PHP_EOL . 'Errores encontrados:' . PHP_EOL . PHP_EOL;
    echo implode(PHP_EOL . PHP_EOL, $errors) . PHP_EOL;
    exit(1);
}

echo 'Sin errores de sintaxis.' . PHP_EOL;
exit(0);
