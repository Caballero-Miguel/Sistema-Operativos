<?php

declare(strict_types=1);

/**
 * Lee una variable del archivo .env del proyecto.
 */
function env(string $clave, ?string $porDefecto = null): ?string
{
    static $variables = null;

    if ($variables === null) {
        $variables = [];
        $rutaEnv = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env';

        if (is_readable($rutaEnv)) {
            $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lineas as $linea) {
                $linea = trim($linea);

                if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                    continue;
                }

                [$nombre, $valor] = explode('=', $linea, 2);
                $nombre = trim($nombre);
                $valor = trim($valor);

                if ($valor !== '' && (($valor[0] === '"' && substr($valor, -1) === '"') || ($valor[0] === "'" && substr($valor, -1) === "'"))) {
                    $valor = substr($valor, 1, -1);
                }

                $variables[$nombre] = $valor;
            }
        }
    }

    return $variables[$clave] ?? $porDefecto;
}

/**
 * Retorna una conexión PDO singleton para MySQL.
 */
function db(): PDO
{
    static $conexion = null;

    if ($conexion instanceof PDO) {
        return $conexion;
    }

    $host = env('DB_HOST', 'localhost');
    $nombreBase = env('DB_NAME', 'barrio_alerta');
    $usuario = env('DB_USER', 'root');
    $contrasena = env('DB_PASS', '');
    $dsn = "mysql:host={$host};dbname={$nombreBase};charset=utf8mb4";

    try {
        $conexion = new PDO($dsn, $usuario, $contrasena, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $excepcion) {
        error_log('Error de conexión a la base de datos: ' . $excepcion->getMessage());
        throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $excepcion);
    }

    return $conexion;
}
