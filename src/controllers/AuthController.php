<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';

class AuthController
{
    /**
     * Decide si la ruta de login muestra o procesa el formulario.
     */
    public static function login(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::procesarLogin();
            return;
        }

        self::mostrarLogin();
    }

    /**
     * Muestra el formulario de ingreso.
     */
    public static function mostrarLogin(): void
    {
        iniciarSesion();
        $error = obtenerFlashError();
        $exito = obtenerFlashExito();
        $tokenCSRF = generarTokenCSRF();

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'login.php';
    }

    /**
     * Valida las credenciales del formulario de ingreso.
     */
    public static function procesarLogin(): void
    {
        iniciarSesion();

        if (!verificarTokenCSRF($_POST['token_csrf'] ?? null)) {
            flashError('La sesión del formulario expiró. Intenta nuevamente.');
            header('Location: ?r=login');
            exit;
        }

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            flashError('Ingresa un correo electrónico y una contraseña válidos.');
            header('Location: ?r=login');
            exit;
        }

        if (!login($email, $password)) {
            flashError('El correo o la contraseña son incorrectos.');
            header('Location: ?r=login');
            exit;
        }

        header('Location: ?r=inicio');
        exit;
    }

    /**
     * Decide si la ruta de registro muestra o procesa el formulario.
     */
    public static function registro(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::procesarRegistro();
            return;
        }

        self::mostrarRegistro();
    }

    /**
     * Muestra el formulario de registro.
     */
    public static function mostrarRegistro(): void
    {
        iniciarSesion();
        $error = obtenerFlashError();
        $tokenCSRF = generarTokenCSRF();

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'auth' . DIRECTORY_SEPARATOR . 'registro.php';
    }

    /**
     * Valida y crea una cuenta ciudadana.
     */
    public static function procesarRegistro(): void
    {
        iniciarSesion();

        if (!verificarTokenCSRF($_POST['token_csrf'] ?? null)) {
            flashError('La sesión del formulario expiró. Intenta nuevamente.');
            header('Location: ?r=registro');
            exit;
        }

        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $confirmarPassword = (string) ($_POST['confirmar_password'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            self::errorRegistro('El nombre es obligatorio y no puede superar 100 caracteres.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            self::errorRegistro('Ingresa un correo electrónico válido.');
        }

        if (mb_strlen($password) < 6) {
            self::errorRegistro('La contraseña debe tener al menos 6 caracteres.');
        }

        if ($password !== $confirmarPassword) {
            self::errorRegistro('Las contraseñas no coinciden.');
        }

        $consulta = db()->prepare('SELECT id FROM usuarios WHERE email = :email LIMIT 1');
        $consulta->execute(['email' => $email]);

        if ($consulta->fetch()) {
            self::errorRegistro('Ya existe una cuenta con ese correo electrónico.');
        }

        $insertar = db()->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (:nombre, :email, :password, :rol)');
        $insertar->execute([
            'nombre' => $nombre,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => 'ciudadano',
        ]);

        $_SESSION['usuario_id'] = (int) db()->lastInsertId();
        flashExito('Tu cuenta fue creada correctamente.');
        header('Location: ?r=inicio');
        exit;
    }

    /**
     * Guarda un error de registro y vuelve al formulario.
     */
    private static function errorRegistro(string $mensaje): void
    {
        flashError($mensaje);
        header('Location: ?r=registro');
        exit;
    }

    /**
     * Cierra la sesión del usuario.
     */
    public static function logout(): void
    {
        logout();
    }
}
