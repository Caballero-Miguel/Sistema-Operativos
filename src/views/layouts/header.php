<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';
iniciarSesion();
$usuario = usuarioActual();
$mensajeExito = obtenerFlashExito();
$mensajeError = obtenerFlashError();
$tituloPagina = $tituloPagina ?? 'Barrio Alerta';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Barrio Alerta: reporta problemas del barrio, consulta el mapa y confirma soluciones ciudadanas.">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="?r=inicio">🗺️ Barrio Alerta</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navegacionPrincipal" aria-controls="navegacionPrincipal" aria-expanded="false" aria-label="Abrir navegación">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navegacionPrincipal">
            <div class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <?php if ($usuario !== null): ?>
                    <?php if (esAdmin()): ?>
                        <a class="btn btn-outline-light" href="?r=admin.dashboard">Panel Admin</a>
                    <?php endif; ?>
                    <a class="btn btn-warning" href="?r=reporte.crear">+ Reportar</a>
                    <span class="navbar-text text-white"><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <a class="btn btn-outline-light" href="?r=logout">Salir</a>
                <?php else: ?>
                    <a class="btn btn-outline-light" href="?r=login">Ingresar</a>
                    <a class="btn btn-warning" href="?r=registro">Registrarse</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php if ($mensajeExito !== null): ?>
        <div class="alert alert-success" role="alert"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($mensajeError !== null): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
