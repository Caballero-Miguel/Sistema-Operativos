<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';

class VotoController
{
    /**
     * Registra un apoyo único y extiende la vigencia hasta un máximo de siete días.
     */
    public static function apoyar(): void
    {
        requerirLogin();
        iniciarSesion();
        header('Content-Type: application/json; charset=utf-8');

        $token = $_POST['csrf_token'] ?? $_POST['token_csrf'] ?? null;
        if (!verificarTokenCSRF($token)) {
            self::responderError('Token CSRF inválido.', 419);
        }

        $reporteId = filter_var($_POST['reporte_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reporteId === false || $reporteId < 1) {
            self::responderError('Reporte inválido.', 422);
        }

        $consultaReporte = db()->prepare('SELECT id, estado_id, expirado, expira_en, created_at FROM reportes WHERE id = :id LIMIT 1');
        $consultaReporte->execute(['id' => $reporteId]);
        $reporte = $consultaReporte->fetch();

        if (!$reporte) {
            self::responderError('El reporte no existe.', 404);
        }

        if ((int) $reporte['expirado'] === 1 || strtotime((string) $reporte['expira_en']) <= time()) {
            self::responderError('El reporte está expirado.', 422);
        }

        if ((int) $reporte['estado_id'] === 5) {
            self::responderError('No puedes apoyar un reporte rechazado.', 422);
        }

        $conexion = db();
        try {
            $conexion->beginTransaction();

            $insertar = $conexion->prepare('INSERT INTO votos (reporte_id, usuario_id) VALUES (:reporte_id, :usuario_id)');
            $insertar->execute([
                'reporte_id' => $reporteId,
                'usuario_id' => usuarioActual()['id'],
            ]);

            $actualizar = $conexion->prepare('UPDATE reportes
                SET expira_en = LEAST(DATE_ADD(expira_en, INTERVAL 24 HOUR), DATE_ADD(created_at, INTERVAL 7 DAY))
                WHERE id = :id AND expirado = 0 AND estado_id <> 5 AND expira_en > NOW()');
            $actualizar->execute(['id' => $reporteId]);

            if ($actualizar->rowCount() !== 1) {
                $conexion->rollBack();
                self::responderError('El reporte ya no está disponible para recibir apoyos.', 422);
            }

            $conexion->commit();
        } catch (PDOException $excepcion) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }

            if ($excepcion->getCode() === '23000') {
                self::responderError('Ya apoyaste este reporte', 409);
            }

            error_log('Error al registrar apoyo: ' . $excepcion->getMessage());
            self::responderError('No fue posible registrar tu apoyo.', 500);
        }

        $apoyos = self::contarApoyos((int) $reporteId);
        $consultaVigencia = $conexion->prepare('SELECT expira_en FROM reportes WHERE id = :id LIMIT 1');
        $consultaVigencia->execute(['id' => $reporteId]);
        $expiraEn = (string) $consultaVigencia->fetchColumn();

        echo json_encode([
            'ok' => true,
            'apoyos' => $apoyos,
            'mensaje' => '¡Gracias por tu apoyo!',
            'expira_en' => $expiraEn,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Cuenta los apoyos registrados para un reporte.
     */
    public static function contarApoyos(int $reporteId): int
    {
        $consulta = db()->prepare('SELECT COUNT(*) FROM votos WHERE reporte_id = :reporte_id');
        $consulta->execute(['reporte_id' => $reporteId]);

        return (int) $consulta->fetchColumn();
    }

    /**
     * Devuelve un error JSON y termina la petición.
     */
    private static function responderError(string $mensaje, int $estado): void
    {
        http_response_code($estado);
        echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
