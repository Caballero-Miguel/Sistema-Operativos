<?php

$tituloPagina = 'Mapa de reportes — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">Reportes del barrio</h1>
        <p class="text-muted mb-0">Consulta los problemas activos y apóyalos si también te afectan.</p>
    </div>
    <?php if ($usuario !== null): ?>
        <a class="btn btn-warning" href="?r=reporte.crear">+ Reportar</a>
    <?php endif; ?>
</div>

<div class="row g-2 mb-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="filtro-categoria">Categoría</label>
        <select class="form-select" id="filtro-categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $categoria): ?>
                <option value="<?= (int) $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="filtro-estado">Estado</label>
        <select class="form-select" id="filtro-estado">
            <option value="">Todos los estados</option>
            <?php foreach ($estados as $estado): ?>
                <option value="<?= (int) $estado['id'] ?>"><?= htmlspecialchars($estado['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div id="mapa-reportes" class="rounded border" style="height: calc(100vh - 300px); min-height: 420px;" data-lat="8.9824" data-lng="-79.5199"></div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
