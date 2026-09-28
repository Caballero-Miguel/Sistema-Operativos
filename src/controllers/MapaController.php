<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';

class MapaController
{
    /**
     * Limpia reportes vencidos y carga los datos necesarios para el mapa.
     */
    public static function index(): void
    {
        self::limpiarExpirados();
        $categorias = db()->query('SELECT id, nombre, color FROM categorias WHERE activa = 1 ORDER BY nombre')->fetchAll();
        $estados = db()->query('SELECT id, nombre, color FROM estados ORDER BY orden')->fetchAll();

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reportes' . DIRECTORY_SEPARATOR . 'index.php';
    }

    /**
     * Retorna los reportes activos para los marcadores del mapa.
     */
    public static function apiReportes(): void
    {
        self::limpiarExpirados();
        $condiciones = ['r.expirado = 0'];
        $parametros = [];

        $categoriaId = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
        if ($categoriaId !== false && $categoriaId !== null && $categoriaId > 0) {
            $condiciones[] = 'r.categoria_id = :categoria_id';
            $parametros['categoria_id'] = $categoriaId;
        }

        $estadoId = filter_input(INPUT_GET, 'estado', FILTER_VALIDATE_INT);
        if ($estadoId !== false && $estadoId !== null && $estadoId > 0) {
            $condiciones[] = 'r.estado_id = :estado_id';
            $parametros['estado_id'] = $estadoId;
        }

        $sql = 'SELECT
                    r.id,
                    r.titulo,
                    r.latitud AS lat,
                    r.longitud AS lng,
                    c.nombre AS categoria_nombre,
                    c.color AS categoria_color,
                    e.nombre AS estado_nombre,
                    e.color AS estado_color,
                    COUNT(v.id) AS apoyos,
                    r.created_at
                FROM reportes r
                INNER JOIN categorias c ON c.id = r.categoria_id
                INNER JOIN estados e ON e.id = r.estado_id
                LEFT JOIN votos v ON v.reporte_id = r.id
                WHERE ' . implode(' AND ', $condiciones) . '
                GROUP BY r.id, r.titulo, r.latitud, r.longitud,
                    c.nombre, c.color, e.nombre, e.color, r.created_at
                ORDER BY apoyos DESC, r.created_at DESC';

        $consulta = db()->prepare($sql);
        $consulta->execute($parametros);
        $reportes = [];

        foreach ($consulta->fetchAll() as $reporte) {
            $reportes[] = [
                'id' => (int) $reporte['id'],
                'titulo' => (string) $reporte['titulo'],
                'lat' => (float) $reporte['lat'],
                'lng' => (float) $reporte['lng'],
                'categoria' => [
                    'nombre' => (string) $reporte['categoria_nombre'],
                    'color' => (string) $reporte['categoria_color'],
                ],
                'estado' => [
                    'nombre' => (string) $reporte['estado_nombre'],
                    'color' => (string) $reporte['estado_color'],
                ],
                'apoyos' => (int) $reporte['apoyos'],
                'created_at' => (string) $reporte['created_at'],
            ];
        }

        echo json_encode(['ok' => true, 'reportes' => $reportes], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Marca como expirados los reportes cuya vigencia terminó.
     */
    public static function limpiarExpirados(): void
    {
        db()->exec('UPDATE reportes SET expirado = 1 WHERE expira_en <= NOW() AND expirado = 0');
    }
}
