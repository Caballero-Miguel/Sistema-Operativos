<?php

declare(strict_types=1);

/**
 * Valida y guarda la fotografía de un reporte.
 */
function subirFoto(array $archivo): ?array
{
    $resultado = ['ok' => false, 'ruta' => '', 'error' => ''];

    if (!isset($archivo['error']) || is_array($archivo['error'])) {
        $resultado['error'] = 'El archivo recibido no es válido.';
        return $resultado;
    }

    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $resultado['error'] = match ($archivo['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'La imagen supera el tamaño máximo de 2MB',
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió, intenta de nuevo',
            default => 'Error al subir el archivo.',
        };
        return $resultado;
    }

    if (!isset($archivo['tmp_name'], $archivo['size'])) {
        $resultado['error'] = 'Error al subir el archivo.';
        return $resultado;
    }

    if ((int) $archivo['size'] > 2 * 1024 * 1024) {
        $resultado['error'] = 'La imagen supera 2MB.';
        return $resultado;
    }

    if (!is_uploaded_file($archivo['tmp_name'])) {
        $resultado['error'] = 'El archivo no proviene de una subida válida.';
        return $resultado;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($archivo['tmp_name']);
    $tiposPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($tiposPermitidos[$mime])) {
        $resultado['error'] = 'La foto debe ser JPG, PNG o WEBP.';
        return $resultado;
    }

    $directorio = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';

    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        $resultado['error'] = 'No fue posible preparar el directorio de fotos.';
        return $resultado;
    }

    $nombre = uniqid('', true) . '.' . $tiposPermitidos[$mime];
    $destino = $directorio . DIRECTORY_SEPARATOR . $nombre;

    if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
        $resultado['error'] = 'No fue posible guardar la foto.';
        return $resultado;
    }

    $resultado['ok'] = true;
    $resultado['ruta'] = 'uploads/' . $nombre;

    return $resultado;
}
