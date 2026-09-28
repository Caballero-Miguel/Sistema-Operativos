<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';

class ConfirmacionController
{
    /**
     * Registra la confirmación ciudadana y evalúa el umbral de resolución.
     */
    public static function confirmar(): void
    {
        requerirLogin();
        iniciarSesion();
        header('Content-Type: application/json; charset=utf-8');

        $token = $_POST['csrf_token'] ?? $_POST['token_csrf'] ?? null;
        if (!verificarTokenCSRF($token)) {
            self::error('Token CSRF inválido.', 419);
        }

        $reporteId = filter_var($_POST['reporte_id'] ?? null, FILTER_VALIDATE_INT);
        $voto = (string) ($_POST['voto'] ?? '');
        if ($reporteId === false || $reporteId < 1 || !in_array($voto, ['si', 'no'], true)) {
            self::error('Los datos de confirmación no son válidos.', 422);
        }

        $consultaReporte = db()->prepare('SELECT id, estado_id, expirado, expira_en FROM reportes WHERE id = :id LIMIT 1');
        $consultaReporte->execute(['id' => $reporteId]);
        $reporte = $consultaReporte->fetch();

        if (!$reporte) {
            self::error('El reporte no existe.', 404);
        }

        if ((int) $reporte['expirado'] === 1 || strtotime((string) $reporte['expira_en']) <= time()) {
            self::error('El reporte está expirado.', 422);
        }

        if ((int) $reporte['estado_id'] !== 3) {
            self::error('Este reporte no está esperando confirmación ciudadana.', 422);
        }

        try {
            $insertar = db()->prepare('INSERT INTO confirmaciones (reporte_id, usuario_id, voto) VALUES (:reporte_id, :usuario_id, :voto)');
            $insertar->execute([
                'reporte_id' => $reporteId,
                'usuario_id' => usuarioActual()['id'],
                'voto' => $voto,
            ]);
        } catch (PDOException $excepcion) {
            if ($excepcion->getCode() === '23000') {
                self::error('Ya confirmaste este reporte.', 409);
            }

            error_log('Error al registrar confirmación: ' . $excepcion->getMessage());
            self::error('No fue posible registrar tu confirmación.', 500);
        }

        $resultado = self::evaluarConfirmacion((int) $reporteId);
        echo json_encode([
            'ok' => true,
            'votos_si' => $resultado['votos_si'],
            'votos_no' => $resultado['votos_no'],
            'estado_actual' => $resultado['estado_actual'],
            'mensaje' => 'Gracias por tu confirmación',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Cambia el estado según el resultado de las confirmaciones ciudadanas.
     */
    private static function evaluarConfirmacion(int $reporteId): array
    {
        $consulta = db()->prepare('SELECT voto, COUNT(*) AS total FROM confirmaciones WHERE reporte_id = :reporte_id GROUP BY voto');
        $consulta->execute(['reporte_id' => $reporteId]);
        $votos = ['si' => 0, 'no' => 0];
        foreach ($consulta->fetchAll() as $fila) {
            $votos[$fila['voto']] = (int) $fila['total'];
        }

        $estadoId = 3;
        if ($votos['si'] >= 3 && $votos['si'] > $votos['no']) {
            $actualizar = db()->prepare('UPDATE reportes
                SET estado_id = 4,
                    expira_en = LEAST(DATE_ADD(NOW(), INTERVAL 48 HOUR), DATE_ADD(created_at, INTERVAL 7 DAY))
                WHERE id = :id AND estado_id = 3 AND expirado = 0');
            $actualizar->execute(['id' => $reporteId]);
            $estadoId = 4;
        } elseif ($votos['no'] >= 3 && $votos['no'] > $votos['si']) {
            $conexion = db();
            $conexion->beginTransaction();
            try {
                $actualizar = $conexion->prepare('UPDATE reportes SET estado_id = 2, resuelto_marcado_en = NULL WHERE id = :id AND estado_id = 3');
                $actualizar->execute(['id' => $reporteId]);
                $borrar = $conexion->prepare('DELETE FROM confirmaciones WHERE reporte_id = :reporte_id');
                $borrar->execute(['reporte_id' => $reporteId]);
                $conexion->commit();
            } catch (Throwable $excepcion) {
                if ($conexion->inTransaction()) {
                    $conexion->rollBack();
                }
                throw $excepcion;
            }
            $estadoId = 2;
        }

        $nombres = [2 => 'En proceso', 3 => 'Resuelto (por confirmar)', 4 => 'Resuelto'];
        return [
            'votos_si' => $votos['si'],
            'votos_no' => $votos['no'],
            'estado_actual' => $nombres[$estadoId],
        ];
    }

    /**
     * Devuelve un error JSON y termina la petición.
     */
    private static function error(string $mensaje, int $estado): void
    {
        http_response_code($estado);
        echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
