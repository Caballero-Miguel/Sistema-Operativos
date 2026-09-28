<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

/**
 * Inicia la sesión PHP una sola vez.
 */
function iniciarSesion(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Retorna los datos actuales del usuario autenticado.
 */
function usuarioActual(): ?array
{
    iniciarSesion();

    $usuarioId = $_SESSION['usuario_id'] ?? null;

    if (!is_int($usuarioId) && !ctype_digit((string) $usuarioId)) {
        return null;
    }

    static $usuario = null;
    static $usuarioConsultado = false;

    if ($usuarioConsultado) {
        return $usuario;
    }

    $usuarioConsultado = true;
    $consulta = db()->prepare('SELECT id, nombre, email, rol FROM usuarios WHERE id = :id LIMIT 1');
    $consulta->execute(['id' => (int) $usuarioId]);
    $usuario = $consulta->fetch() ?: null;

    if ($usuario === null) {
        unset($_SESSION['usuario_id']);
    }

    return $usuario;
}

/**
 * Indica si existe una sesión de usuario válida.
 */
function estaLogueado(): bool
{
    return usuarioActual() !== null;
}

/**
 * Indica si el usuario actual tiene permisos administrativos.
 */
function esAdmin(): bool
{
    return (usuarioActual()['rol'] ?? null) === 'admin';
}

/**
 * Verifica credenciales y guarda el identificador del usuario en sesión.
 */
function login(string $email, string $password): bool
{
    iniciarSesion();

    $consulta = db()->prepare('SELECT id, password FROM usuarios WHERE email = :email LIMIT 1');
    $consulta->execute(['email' => strtolower(trim($email))]);
    $usuario = $consulta->fetch();

    if (!$usuario || !password_verify($password, $usuario['password'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $usuario['id'];

    return true;
}

/**
 * Destruye la sesión actual y vuelve al inicio.
 */
function logout(): void
{
    iniciarSesion();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $parametros['path'], $parametros['domain'], $parametros['secure'], $parametros['httponly']);
    }

    session_destroy();
    header('Location: ?r=inicio');
    exit;
}

/**
 * Obliga a que exista una sesión de usuario.
 */
function requerirLogin(): void
{
    if (!estaLogueado()) {
        header('Location: ?r=login');
        exit;
    }
}

/**
 * Obliga a que el usuario actual sea administrador.
 */
function requerirAdmin(): void
{
    if (!esAdmin()) {
        header('Location: ?r=login');
        exit;
    }
}

/**
 * Guarda un mensaje de éxito para la siguiente petición.
 */
function flashExito(string $mensaje): void
{
    iniciarSesion();
    $_SESSION['flash_exito'] = $mensaje;
}

/**
 * Guarda un mensaje de error para la siguiente petición.
 */
function flashError(string $mensaje): void
{
    iniciarSesion();
    $_SESSION['flash_error'] = $mensaje;
}

/**
 * Retorna y elimina el mensaje de éxito pendiente.
 */
function obtenerFlashExito(): ?string
{
    iniciarSesion();
    $mensaje = $_SESSION['flash_exito'] ?? null;
    unset($_SESSION['flash_exito']);

    return is_string($mensaje) ? $mensaje : null;
}

/**
 * Retorna y elimina el mensaje de error pendiente.
 */
function obtenerFlashError(): ?string
{
    iniciarSesion();
    $mensaje = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);

    return is_string($mensaje) ? $mensaje : null;
}

/**
 * Genera y retorna el token CSRF de la sesión.
 */
function generarTokenCSRF(): string
{
    iniciarSesion();

    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token_csrf'];
}

/**
 * Valida un token CSRF usando comparación segura.
 */
function verificarTokenCSRF(?string $token): bool
{
    iniciarSesion();

    return is_string($token)
        && isset($_SESSION['token_csrf'])
        && hash_equals($_SESSION['token_csrf'], $token);
}

/**
 * Convierte una fecha SQL en una expresión breve y legible.
 */
function tiempoTranscurrido(string $fecha): string
{
    $segundos = max(0, time() - strtotime($fecha));
    if ($segundos < 60) {
        return 'hace menos de un minuto';
    }
    if ($segundos < 3600) {
        return 'hace ' . (int) floor($segundos / 60) . ' minutos';
    }
    if ($segundos < 86400) {
        return 'hace ' . (int) floor($segundos / 3600) . ' horas';
    }
    if ($segundos < 172800) {
        return 'ayer';
    }

    return 'hace ' . (int) floor($segundos / 86400) . ' días';
}
