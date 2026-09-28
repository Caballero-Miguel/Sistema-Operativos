<?php

$tituloPagina = 'Iniciar sesión — Barrio Alerta';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-4">Iniciar Sesión</h1>

                <?php if ($error !== null): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if ($exito !== null): ?>
                    <div class="alert alert-success" role="alert"><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" action="?r=login">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($tokenCSRF, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-3">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-control" type="email" id="email" name="email" required maxlength="150" autocomplete="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-warning w-100" type="submit">Ingresar</button>
                </form>

                <p class="text-center mt-3 mb-0">¿No tienes cuenta? <a href="?r=registro">Regístrate</a></p>
            </div>
        </div>
    </div>
</div>
<?php require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php'; ?>
