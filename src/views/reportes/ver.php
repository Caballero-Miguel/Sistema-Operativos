<?php

$tituloPagina = 'Reporte #' . (int) $reporte['id'] . ' — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Detalle del reporte</h1>
            <a class="btn btn-outline-secondary" href="?r=inicio">Volver al mapa</a>
        </div>

        <article class="card shadow-sm">
            <?php if (!empty($reporte['foto'])): ?>
                <img class="card-img-top" src="<?= htmlspecialchars($reporte['foto'], ENT_QUOTES, 'UTF-8') ?>" alt="Foto del reporte">
            <?php endif; ?>
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge" style="background-color: <?= htmlspecialchars($reporte['categoria_color'], ENT_QUOTES, 'UTF-8') ?>;">
                        <?= htmlspecialchars($reporte['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span id="estado-reporte" class="badge" style="background-color: <?= htmlspecialchars($reporte['estado_color'], ENT_QUOTES, 'UTF-8') ?>;">
                        <?= htmlspecialchars($reporte['estado_nombre'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <h2 class="card-title h3"><?= htmlspecialchars($reporte['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="text-muted mb-4">
                    Reportado por <?= htmlspecialchars($reporte['autor'], ENT_QUOTES, 'UTF-8') ?>
                    el <?= htmlspecialchars(date('d/m/Y H:i', strtotime($reporte['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if (!empty($reporte['descripcion'])): ?>
                    <p class="card-text"><?= nl2br(htmlspecialchars($reporte['descripcion'], ENT_QUOTES, 'UTF-8')) ?></p>
                <?php endif; ?>
                <?php if (!empty($reporte['direccion'])): ?>
                    <p><strong>Dirección:</strong> <?= htmlspecialchars($reporte['direccion'], ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <div class="d-flex align-items-center gap-3 my-4">
                    <?php if ($usuario !== null): ?>
                        <form id="form-apoyar" method="post" action="?r=api.apoyar" data-reporte-id="<?= (int) $reporte['id'] ?>" data-csrf-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                            <button id="boton-apoyar" class="btn btn-warning btn-lg" type="submit" <?= $reporte['ya_apoyo'] || (int) $reporte['estado_id'] === 5 ? 'disabled' : '' ?>>
                                <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i>
                                <span id="texto-boton-apoyar"><?= $reporte['ya_apoyo'] ? 'Ya apoyaste este reporte' : ((int) $reporte['estado_id'] === 5 ? 'Reporte rechazado' : 'Apoyar') ?></span>
                                <span>(<strong id="contador-apoyos"><?= (int) $reporte['apoyos'] ?></strong>)</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <a class="btn btn-warning btn-lg" href="?r=login" title="Inicia sesión para apoyar">
                            <i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> Apoyar (<?= (int) $reporte['apoyos'] ?>)
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ((int) $reporte['estado_id'] === 3): ?>
                    <section class="border border-warning border-2 rounded p-3 mb-4 bg-light">
                        <h3 class="h5">🏛️ El municipio marcó este reporte como resuelto. ¿Realmente se solucionó?</h3>
                        <p class="text-muted">La comunidad debe confirmar el trabajo realizado por el municipio.</p>
                        <div class="progress mb-3" role="progressbar" aria-label="Avance de confirmaciones" aria-valuemin="0" aria-valuemax="3" aria-valuenow="<?= min(3, $confirmaciones['si'] + $confirmaciones['no']) ?>">
                            <div class="progress-bar bg-warning" style="width: <?= min(100, (($confirmaciones['si'] + $confirmaciones['no']) / 3) * 100) ?>%"></div>
                        </div>
                        <p id="contador-confirmaciones" class="fw-semibold">Confirmaciones: <?= (int) $confirmaciones['si'] ?> sí · <?= (int) $confirmaciones['no'] ?> no</p>
                        <?php if ($reporte['ya_confirmo']): ?>
                            <div class="alert alert-info mb-0">Ya registraste tu voto: <strong><?= $reporte['voto_confirmacion'] === 'si' ? 'Sí, se resolvió' : 'No, sigue igual' ?></strong>.</div>
                        <?php elseif ($usuario !== null): ?>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-success btn-lg boton-confirmacion" type="button" data-reporte-id="<?= (int) $reporte['id'] ?>" data-voto="si" data-csrf-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">✅ Sí, se resolvió</button>
                                <button class="btn btn-danger btn-lg boton-confirmacion" type="button" data-reporte-id="<?= (int) $reporte['id'] ?>" data-voto="no" data-csrf-token="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">❌ No, sigue igual</button>
                            </div>
                        <?php else: ?>
                            <a class="btn btn-warning" href="?r=login">Ingresa para confirmar</a>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <div id="mapa-detalle" class="rounded border" style="height: 280px;" data-lat="<?= htmlspecialchars((string) $reporte['latitud'], ENT_QUOTES, 'UTF-8') ?>" data-lng="<?= htmlspecialchars((string) $reporte['longitud'], ENT_QUOTES, 'UTF-8') ?>"></div>
            </div>
        </article>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
