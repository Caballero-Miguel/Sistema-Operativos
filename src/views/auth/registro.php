<?php

$tituloPagina = 'Crear cuenta — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-4">Crear Cuenta</h1>

                <?php if ($error !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="?r=registro">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="nombre">Nombre</label>
                        <input class="form-control" type="text" id="nombre" name="nombre" required maxlength="100" autocomplete="name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-control" type="email" id="email" name="email" required maxlength="150" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" type="password" id="password" name="password" required minlength="6" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="confirmar_password">Confirmar contraseña</label>
                        <input class="form-control" type="password" id="confirmar_password" name="confirmar_password" required minlength="6" autocomplete="new-password">
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Crear Cuenta</button>
                </form>

                <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="?r=login">Ingresa</a></p>
            </div>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
