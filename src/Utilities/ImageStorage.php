<?php

namespace Utilities;

/**
 * Guarda y sirve las imágenes del sistema fuera de la carpeta publica.
 *
 * Los archivos viven en storage/ y se sirven mediante controladores que
 * comprueban la sesion. La base de datos solo guarda la ruta relativa y los
 * metadatos del archivo.
 */
class ImageStorage
{
    private const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_FILES = 8;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Guarda una imagen subida y devuelve sus metadatos.
     *
     * @param array $file Entrada de $_FILES.
     * @param string $folder Carpeta relativa dentro de storage/.
     * @return array{ruta:string,nombre_original:string,tipo_mime:string,tamano:int}
     */
    public static function save(array $file, string $folder): array
    {
        $error = intval($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new \RuntimeException('Selecciona una imagen para subir.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(self::uploadErrorMessage($error));
        }

        $temporary = strval($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_uploaded_file($temporary)) {
            throw new \RuntimeException('No se pudo leer la imagen seleccionada.');
        }

        $size = intval(@filesize($temporary));
        if ($size <= 0 || $size > self::MAX_BYTES) {
            throw new \RuntimeException('La imagen supera el máximo de 5 MB.');
        }

        $info = @getimagesize($temporary);
        if ($info === false) {
            throw new \RuntimeException('El archivo no es una imagen válida.');
        }

        $mime = self::detectMime($temporary);
        if (!isset(self::MIME_EXTENSIONS[$mime]) && isset($info['mime'])) {
            $mime = strtolower(strval($info['mime']));
        }
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            throw new \RuntimeException('Formato no permitido. Usa JPG, PNG o WebP.');
        }

        $folder = self::normalizeFolder($folder);
        $absoluteFolder = self::root() . '/' . $folder;
        if (
            !is_dir($absoluteFolder)
            && !@mkdir($absoluteFolder, 0775, true)
            && !is_dir($absoluteFolder)
        ) {
            throw new \RuntimeException('No se pudo preparar la carpeta de imágenes.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::MIME_EXTENSIONS[$mime];
        $relative = $folder . '/' . $name;
        $destination = self::root() . '/' . $relative;

        if (!@move_uploaded_file($temporary, $destination)) {
            throw new \RuntimeException('No se pudo guardar la imagen en el servidor.');
        }
        @chmod($destination, 0644);

        return [
            'ruta' => $relative,
            'nombre_original' => self::cleanName(strval($file['name'] ?? '')),
            'tipo_mime' => $mime,
            'tamano' => $size,
        ];
    }

    /**
     * Indica si el campo $_FILES contiene al menos un archivo.
     *
     * @param mixed $files Estructura de $_FILES.
     */
    public static function hasUploads($files): bool
    {
        return count(self::normalizeFiles($files)) > 0;
    }

    /**
     * Guarda varias imágenes de un mismo campo $_FILES.
     *
     * @param mixed $files Estructura de $_FILES con name/tmp_name/error/size.
     * @param string $folder Carpeta relativa dentro de storage/.
     * @return array<int,array{ruta:string,nombre_original:string,tipo_mime:string,tamano:int}>
     */
    public static function saveMany($files, string $folder): array
    {
        $list = self::normalizeFiles($files);
        if (count($list) === 0) {
            throw new \RuntimeException('Selecciona al menos una imagen.');
        }
        if (count($list) > self::MAX_FILES) {
            throw new \RuntimeException('Puedes subir hasta 8 imágenes a la vez.');
        }

        $saved = [];
        try {
            foreach ($list as $file) {
                $saved[] = self::save($file, $folder);
            }
        } catch (\Throwable $ex) {
            foreach ($saved as $item) {
                self::delete($item['ruta']);
            }
            throw $ex;
        }

        return $saved;
    }

    /** Elimina un archivo si existe dentro de storage/. */
    public static function delete(string $relative): void
    {
        $absolute = self::absolute($relative);
        if ($absolute !== null && is_file($absolute)) {
            @unlink($absolute);
        }
    }

    /** Envia una imagen al navegador con cabeceras seguras. */
    public static function stream(string $relative, string $mime = ''): void
    {
        $absolute = self::absolute($relative);
        if ($absolute === null || !is_file($absolute)) {
            http_response_code(404);
            exit;
        }

        $mime = strtolower(trim($mime));
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            $mime = self::detectMime($absolute);
        }
        if (!isset(self::MIME_EXTENSIONS[$mime])) {
            $mime = 'application/octet-stream';
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . strval(intval(filesize($absolute))));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($absolute);
    }

    /** Resuelve una ruta relativa y evita salir de storage/. */
    public static function absolute(string $relative): ?string
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || strpos($relative, '..') !== false) {
            return null;
        }

        $root = realpath(self::root());
        if ($root === false) {
            return null;
        }

        $candidate = realpath($root . '/' . $relative);
        if ($candidate === false) {
            return null;
        }

        if (strpos($candidate, $root . DIRECTORY_SEPARATOR) !== 0) {
            return null;
        }

        return $candidate;
    }

    /** Raiz fisica de las imágenes. */
    public static function root(): string
    {
        return dirname(__DIR__, 2) . '/storage';
    }

    private static function normalizeFolder(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');
        if (
            $folder === ''
            || strpos($folder, '..') !== false
            || preg_match('#^[a-z0-9/_-]+$#i', $folder) !== 1
        ) {
            throw new \RuntimeException('La carpeta de imágenes no es válida.');
        }

        return $folder;
    }

    private static function normalizeFiles($files): array
    {
        if (!is_array($files) || !isset($files['name'])) {
            return [];
        }

        if (!is_array($files['name'])) {
            if (intval($files['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }

            return [$files];
        }

        $list = [];
        foreach ($files['name'] as $index => $name) {
            if (intval($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $list[] = [
                'name' => $name,
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];
        }

        return $list;
    }

    private static function detectMime(string $path): string
    {
        $mime = '';
        if (class_exists(\finfo::class)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = strtolower(strval($finfo->file($path)));
        }

        if ($mime === '') {
            $info = @getimagesize($path);
            if ($info !== false && isset($info['mime'])) {
                $mime = strtolower(strval($info['mime']));
            }
        }

        return $mime;
    }

    private static function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?? '';

        return substr($name, 0, 255);
    }

    private static function uploadErrorMessage(int $error): string
    {
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            return 'La imagen supera el límite de tamaño del servidor.';
        }
        if ($error === UPLOAD_ERR_PARTIAL) {
            return 'La carga de la imagen se interrumpió. Intenta de nuevo.';
        }
        if ($error === UPLOAD_ERR_NO_TMP_DIR) {
            return 'El servidor no tiene una carpeta temporal para las imágenes.';
        }
        if ($error === UPLOAD_ERR_CANT_WRITE) {
            return 'El servidor no pudo escribir la imagen.';
        }

        return 'No se pudo subir la imagen.';
    }
}
