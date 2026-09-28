<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';
iniciarSesion();
$error = obtenerFlashError();
$exito = obtenerFlashExito();
$tituloPagina = 'Crear reporte — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Reportar un problema</h1>
            <a class="btn btn-outline-secondary" href="?r=inicio">Volver al mapa</a>
        </div>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($exito !== null): ?>
            <div class="alert alert-success" role="alert"><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form id="formReporte" method="post" action="?r=reporte.guardar" enctype="multipart/form-data">
            <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="latitud" id="latitud">
            <input type="hidden" name="longitud" id="longitud">
            <input type="hidden" name="forzar" id="forzar" value="0">

            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="mb-3">
                        <label class="form-label" for="titulo">Título</label>
                        <input class="form-control" type="text" id="titulo" name="titulo" required maxlength="150">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="categoria_id">Categoría</label>
                        <select class="form-select" id="categoria_id" name="categoria_id" required>
                            <option value="">Selecciona una categoría</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= (int) $categoria['id'] ?>"><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" maxlength="10000"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="direccion">Dirección <span class="text-muted">(opcional)</span></label>
                        <input class="form-control" type="text" id="direccion" name="direccion" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="foto">Foto <span class="text-muted">(opcional)</span></label>
                        <input class="form-control" type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp" maxlength="2097152">
                        <div class="form-text">Máximo 2MB. Formatos permitidos: JPG, PNG, WEBP</div>
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Reportar problema</button>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0" for="mapa-crear">Ubicación obligatoria</label>
                        <button class="btn btn-sm btn-outline-primary" type="button" id="usar-ubicacion">Usar mi ubicación actual</button>
                    </div>
                    <div id="mapa-crear" class="rounded border" style="height: 430px;" data-lat="8.9824" data-lng="-79.5199"></div>
                    <p class="form-text">Haz clic en el mapa para seleccionar el lugar del problema.</p>
                    <div id="ubicacion-seleccionada" class="small text-muted">Aún no has seleccionado una ubicación.</div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modal-duplicado" tabindex="-1" aria-labelledby="titulo-modal-duplicado" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="titulo-modal-duplicado">Reporte similar encontrado</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="texto-duplicado"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="apoyar-duplicado">Sí, apoyarlo</button>
                <button type="button" class="btn btn-warning" id="crear-duplicado">No, crear de todas formas</button>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/app.js"></script>
<script>
    document.getElementById('formReporte')?.addEventListener('submit', function (evento) {
        const archivo = document.getElementById('foto')?.files[0];
        const limite = 2 * 1024 * 1024;
        let alerta = document.getElementById('error-foto-cliente');

        if (!alerta) {
            alerta = document.createElement('div');
            alerta.id = 'error-foto-cliente';
            alerta.className = 'alert alert-danger mt-3';
            alerta.setAttribute('role', 'alert');
            document.getElementById('formReporte').prepend(alerta);
        }

        alerta.classList.add('d-none');

        if (archivo && archivo.size > limite) {
            evento.preventDefault();
            alerta.textContent = 'La imagen supera 2MB.';
            alerta.classList.remove('d-none');
            document.getElementById('foto').focus();
        }
    });
</script>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
