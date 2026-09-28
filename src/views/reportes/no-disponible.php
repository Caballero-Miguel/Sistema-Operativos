<?php

$tituloPagina = 'Reporte no disponible — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm text-center">
            <div class="card-body p-5">
                <h1 class="h3 mb-3">Reporte no disponible</h1>
                <p class="text-muted mb-4"><?= htmlspecialchars($mensajeNoDisponible, ENT_QUOTES, 'UTF-8') ?></p>
                <a class="btn btn-warning" href="?r=inicio">Volver al mapa</a>
            </div>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
