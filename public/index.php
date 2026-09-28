<?php

declare(strict_types=1);

// Configura el manejo silencioso y registrado de errores en producción.
$raizProyecto = dirname(__DIR__);
$directorioLogs = $raizProyecto . DIRECTORY_SEPARATOR . 'logs';
if (!is_dir($directorioLogs)) {
    mkdir($directorioLogs, 0755, true);
}
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $directorioLogs . DIRECTORY_SEPARATOR . 'error.log');

set_error_handler(static function (int $nivel, string $mensaje, string $archivo, int $linea): bool {
    error_log("PHP {$nivel}: {$mensaje} en {$archivo}:{$linea}");
    return true;
});

$mostrarErrorServidor = static function (): void {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Error — Barrio Alerta</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body><main class="container py-5"><div class="alert alert-danger"><h1 class="h4">Algo salió mal</h1><p class="mb-3">No pudimos completar la solicitud. Intenta nuevamente más tarde.</p><a class="btn btn-warning" href="?r=inicio">Volver al inicio</a></div></main></body></html>';
};

set_exception_handler(static function (Throwable $excepcion) use ($mostrarErrorServidor): void {
    error_log((string) $excepcion);
    $mostrarErrorServidor();
});

register_shutdown_function(static function () use ($mostrarErrorServidor): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log("Fatal {$error['type']}: {$error['message']} en {$error['file']}:{$error['line']}");
        $mostrarErrorServidor();
    }
});

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AuthController.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'MapaController.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'ReporteController.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'VotoController.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'ConfirmacionController.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'AdminController.php';

$ruta = trim((string) ($_GET['r'] ?? 'inicio'));
$rutas = [
    'inicio' => ['metodo' => 'GET', 'descripcion' => 'Página principal con mapa'],
    'login' => ['metodo' => 'GET/POST', 'descripcion' => 'Login'],
    'registro' => ['metodo' => 'GET/POST', 'descripcion' => 'Registro'],
    'logout' => ['metodo' => 'GET', 'descripcion' => 'Cerrar sesión'],
    'reporte.crear' => ['metodo' => 'GET/POST', 'descripcion' => 'Formulario crear reporte'],
    'reporte.guardar' => ['metodo' => 'POST', 'descripcion' => 'Procesar creación'],
    'reporte.ver' => ['metodo' => 'GET', 'descripcion' => 'Detalle del reporte'],
    'api.reportes' => ['metodo' => 'GET', 'descripcion' => 'JSON de reportes activos'],
    'api.apoyar' => ['metodo' => 'POST', 'descripcion' => 'Dar apoyo a un reporte'],
    'api.confirmar' => ['metodo' => 'POST', 'descripcion' => 'Confirmar o negar resolución'],
    'admin.dashboard' => ['metodo' => 'GET', 'descripcion' => 'Panel del municipio'],
    'admin.cambiar_estado' => ['metodo' => 'POST', 'descripcion' => 'Cambiar estado'],
];

if (!isset($rutas[$ruta])) {
    http_response_code(404);
    echo 'Ruta no encontrada.';
    exit;
}

// Protege las rutas administrativas antes de ejecutar cualquier lógica de la ruta.
if (str_starts_with($ruta, 'admin.')) {
    requerirAdmin();
}

// Protege las acciones que solo pueden realizar usuarios autenticados.
$rutasRequierenLogin = [
    'reporte.crear',
    'reporte.guardar',
    'api.apoyar',
    'api.confirmar',
];

if (in_array($ruta, $rutasRequierenLogin, true)) {
    requerirLogin();
}

$metodoPermitido = $rutas[$ruta]['metodo'];
$metodoActual = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$metodosPermitidos = explode('/', $metodoPermitido);

if (!in_array($metodoActual, $metodosPermitidos, true)) {
    header('Allow: ' . implode(', ', $metodosPermitidos));
    http_response_code(405);
    echo 'Método no permitido.';
    exit;
}

if (str_starts_with($ruta, 'api.')) {
    header('Content-Type: application/json; charset=utf-8');
}

http_response_code(200);

if ($ruta === 'login') {
    if ($metodoActual === 'GET') {
        AuthController::mostrarLogin();
    } else {
        AuthController::procesarLogin();
    }
    exit;
}

if ($ruta === 'registro') {
    if ($metodoActual === 'GET') {
        AuthController::mostrarRegistro();
    } else {
        AuthController::procesarRegistro();
    }
    exit;
}

if ($ruta === 'logout') {
    AuthController::logout();
}

if ($ruta === 'api.reportes') {
    MapaController::apiReportes();
}

if ($ruta === 'api.apoyar') {
    VotoController::apoyar();
}

if ($ruta === 'api.confirmar') {
    ConfirmacionController::confirmar();
}

if ($ruta === 'admin.dashboard') {
    AdminController::dashboard();
}

if ($ruta === 'admin.cambiar_estado') {
    AdminController::cambiarEstado();
}

if ($ruta === 'reporte.crear' || $ruta === 'reporte.guardar') {
    ReporteController::crear();
}

if ($ruta === 'reporte.ver') {
    ReporteController::ver((int) ($_GET['id'] ?? 0));
    exit;
}

if ($ruta === 'inicio') {
    MapaController::index();
    exit;
}

echo htmlspecialchars('Barrio Alerta - ' . $rutas[$ruta]['descripcion'], ENT_QUOTES, 'UTF-8');
