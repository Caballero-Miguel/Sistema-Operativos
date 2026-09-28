<?php

$tituloPagina = 'Panel municipal — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Panel del municipio</h1>
        <p class="text-muted mb-0">Gestiona el avance de los reportes sin declarar resoluciones por tu cuenta.</p>
    </div>
    <a class="btn btn-outline-secondary" href="?r=inicio">Ver mapa</a>
</div>

<?php if ($mensajeExito !== null): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensajeExito, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($mensajeError !== null): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3"><div class="card border-primary h-100"><div class="card-body"><i class="bi bi-clipboard-data fs-3 text-primary"></i><div class="text-muted">Activos</div><div class="display-6"><?= $estadisticas['activos'] ?></div></div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="card border-warning h-100"><div class="card-body"><i class="bi bi-hourglass-split fs-3 text-warning"></i><div class="text-muted">Pendientes</div><div class="display-6"><?= $estadisticas['pendientes'] ?></div></div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="card border-info h-100"><div class="card-body"><i class="bi bi-tools fs-3 text-info"></i><div class="text-muted">En proceso</div><div class="display-6"><?= $estadisticas['proceso'] ?></div></div></div></div>
    <div class="col-12 col-sm-6 col-xl-3"><div class="card border-warning h-100"><div class="card-body"><i class="bi bi-people fs-3 text-warning"></i><div class="text-muted">Esperando confirmación</div><div class="display-6"><?= $estadisticas['confirmacion'] ?></div></div></div></div>
</div>

<form class="row g-2 mb-4" method="get" action="">
    <input type="hidden" name="r" value="admin.dashboard">
    <div class="col-12 col-md-5"><label class="form-label" for="filtro-estado-admin">Estado</label><select class="form-select" id="filtro-estado-admin" name="estado"><option value="">Todos</option><?php foreach ($estados as $estado): ?><option value="<?= (int) $estado['id'] ?>" <?= (string) $filtroEstado === (string) $estado['id'] ? 'selected' : '' ?>><?= htmlspecialchars($estado['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-5"><label class="form-label" for="filtro-categoria-admin">Categoría</label><select class="form-select" id="filtro-categoria-admin" name="categoria"><option value="">Todas</option><?php foreach ($categorias as $categoria): ?><option value="<?= (int) $categoria['id'] ?>" <?= (string) $filtroCategoria === (string) $categoria['id'] ? 'selected' : '' ?>><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
    <div class="col-12 col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100" type="submit">Filtrar</button></div>
</form>

<div class="table-responsive">
<table class="table table-hover align-middle">
    <thead><tr><th>ID</th><th>Título</th><th>Categoría</th><th>Estado</th><th>Apoyos</th><th>Fecha</th><th>Acciones</th></tr></thead>
    <tbody>
    <?php foreach ($reportes as $reporte): ?>
        <tr id="reporte-<?= (int) $reporte['id'] ?>">
            <td><?= (int) $reporte['id'] ?></td>
            <td><a href="?r=reporte.ver&id=<?= (int) $reporte['id'] ?>"><?= htmlspecialchars($reporte['titulo'], ENT_QUOTES, 'UTF-8') ?></a></td>
            <td><span class="badge" style="background-color: <?= htmlspecialchars($reporte['categoria_color'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reporte['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><span class="badge" style="background-color: <?= htmlspecialchars($reporte['estado_color'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reporte['estado_nombre'], ENT_QUOTES, 'UTF-8') ?></span></td>
            <td><?= (int) $reporte['apoyos'] ?></td>
            <td title="<?= htmlspecialchars(date('d/m/Y H:i', strtotime($reporte['created_at'])), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(tiempoTranscurrido($reporte['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
                <?php if ((int) $reporte['estado_id'] === 1): ?>
                    <button class="btn btn-sm btn-info cambiar-estado" data-reporte-id="<?= (int) $reporte['id'] ?>" data-estado="2" data-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">En proceso</button>
                    <button class="btn btn-sm btn-danger cambiar-estado" data-reporte-id="<?= (int) $reporte['id'] ?>" data-estado="5" data-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">Rechazar</button>
                <?php elseif ((int) $reporte['estado_id'] === 2): ?>
                    <button class="btn btn-sm btn-warning cambiar-estado" data-reporte-id="<?= (int) $reporte['id'] ?>" data-estado="3" data-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">Marcar como resuelto</button>
                    <button class="btn btn-sm btn-danger cambiar-estado" data-reporte-id="<?= (int) $reporte['id'] ?>" data-estado="5" data-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">Rechazar</button>
                <?php elseif ((int) $reporte['estado_id'] === 3): ?>
                    <span class="badge text-bg-warning">Esperando confirmación ciudadana</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$reportes): ?><tr><td colspan="7" class="text-center text-muted py-4">No hay reportes con estos filtros.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>
<script>
    window.barrioAlertaCSRF = <?= json_encode($tokenCSRF, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/js/app.js"></script>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
