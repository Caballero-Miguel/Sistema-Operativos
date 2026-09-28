<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';

class AdminController
{
    /**
     * Carga estadísticas y reportes activos para el municipio.
     */
    public static function dashboard(): void
    {
        requerirAdmin();
        iniciarSesion();
        db()->exec('UPDATE reportes SET expirado = 1 WHERE expira_en <= NOW() AND expirado = 0');

        $filtroEstado = filter_input(INPUT_GET, 'estado', FILTER_VALIDATE_INT);
        $filtroCategoria = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
        $estadisticas = [
            'activos' => (int) db()->query('SELECT COUNT(*) FROM reportes WHERE expirado = 0')->fetchColumn(),
            'pendientes' => self::contarEstado(1),
            'proceso' => self::contarEstado(2),
            'confirmacion' => self::contarEstado(3),
        ];

        $condiciones = ['r.expirado = 0'];
        $parametros = [];
        if ($filtroEstado !== false && $filtroEstado !== null && $filtroEstado > 0) {
            $condiciones[] = 'r.estado_id = :estado_id';
            $parametros['estado_id'] = $filtroEstado;
        }
        if ($filtroCategoria !== false && $filtroCategoria !== null && $filtroCategoria > 0) {
            $condiciones[] = 'r.categoria_id = :categoria_id';
            $parametros['categoria_id'] = $filtroCategoria;
        }

        $consulta = db()->prepare('SELECT
                r.id, r.titulo, r.created_at, r.estado_id,
                c.nombre AS categoria_nombre, c.color AS categoria_color,
                e.nombre AS estado_nombre, e.color AS estado_color,
                COUNT(v.id) AS apoyos
            FROM reportes r
            INNER JOIN categorias c ON c.id = r.categoria_id
            INNER JOIN estados e ON e.id = r.estado_id
            LEFT JOIN votos v ON v.reporte_id = r.id
            WHERE ' . implode(' AND ', $condiciones) . '
            GROUP BY r.id, r.titulo, r.created_at, r.estado_id,
                c.nombre, c.color, e.nombre, e.color
            ORDER BY r.created_at DESC');
        $consulta->execute($parametros);
        $reportes = $consulta->fetchAll();
        $categorias = db()->query('SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre')->fetchAll();
        $estados = db()->query('SELECT id, nombre FROM estados ORDER BY orden')->fetchAll();
        $tokenCSRF = generarTokenCSRF();
        $mensajeExito = obtenerFlashExito();
        $mensajeError = obtenerFlashError();

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'dashboard.php';
    }

    /**
     * Cambia un reporte solo a estados permitidos por la regla de negocio.
     */
    public static function cambiarEstado(): void
    {
        requerirAdmin();
        iniciarSesion();
        $token = $_POST['csrf_token'] ?? $_POST['token_csrf'] ?? null;
        if (!verificarTokenCSRF($token)) {
            self::errorEstado('Token CSRF inválido.');
        }

        $reporteId = filter_var($_POST['reporte_id'] ?? null, FILTER_VALIDATE_INT);
        $nuevoEstado = filter_var($_POST['nuevo_estado_id'] ?? null, FILTER_VALIDATE_INT);
        if ($reporteId === false || $reporteId < 1 || !in_array($nuevoEstado, [2, 3, 5], true)) {
            self::errorEstado('El estado solicitado no es válido.');
        }

        $consulta = db()->prepare('SELECT estado_id FROM reportes WHERE id = :id AND expirado = 0 LIMIT 1');
        $consulta->execute(['id' => $reporteId]);
        $reporte = $consulta->fetch();
        $transiciones = [
            1 => [2, 5],
            2 => [3, 5],
        ];

        if (!$reporte || !in_array($nuevoEstado, $transiciones[(int) $reporte['estado_id']] ?? [], true)) {
            self::errorEstado('La transición de estado no está permitida.');
        }

        if ($nuevoEstado === 3) {
            $actualizar = db()->prepare('UPDATE reportes SET estado_id = 3, resuelto_marcado_en = NOW() WHERE id = :id AND expirado = 0');
        } else {
            $actualizar = db()->prepare('UPDATE reportes SET estado_id = :estado_id WHERE id = :id AND expirado = 0');
        }
        $parametros = ['id' => $reporteId];
        if ($nuevoEstado !== 3) {
            $parametros['estado_id'] = $nuevoEstado;
        }
        $actualizar->execute($parametros);

        if (strtolower($_SERVER['HTTP_ACCEPT'] ?? '') !== 'application/json') {
            flashExito('Estado actualizado correctamente.');
            header('Location: /barrio-alerta/public/?r=admin.dashboard');
            exit;
        }

        echo json_encode(['ok' => true, 'mensaje' => 'Estado actualizado correctamente.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Cuenta reportes activos de un estado.
     */
    private static function contarEstado(int $estadoId): int
    {
        $consulta = db()->prepare('SELECT COUNT(*) FROM reportes WHERE expirado = 0 AND estado_id = :estado_id');
        $consulta->execute(['estado_id' => $estadoId]);
        return (int) $consulta->fetchColumn();
    }

    /**
     * Guarda un error y vuelve al dashboard.
     */
    private static function errorEstado(string $mensaje): void
    {
        if (strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => $mensaje], JSON_UNESCAPED_UNICODE);
            exit;
        }
        flashError($mensaje);
        header('Location: /barrio-alerta/public/?r=admin.dashboard');
        exit;
    }
}
