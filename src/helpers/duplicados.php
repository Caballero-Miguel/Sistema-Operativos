<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';

/**
 * Busca un reporte activo cercano y con un título suficientemente parecido.
 */
function buscarDuplicado(int $categoriaId, float $latitud, float $longitud, string $titulo): ?array
{
    $sql = <<<'SQL'
        SELECT
            r.id,
            r.titulo,
            r.latitud,
            r.longitud,
            6371000 * 2 * ASIN(SQRT(
                POWER(SIN(RADIANS(:latitud - r.latitud) / 2), 2) +
                COS(RADIANS(:latitudReferencia)) * COS(RADIANS(r.latitud)) *
                POWER(SIN(RADIANS(:longitud - r.longitud) / 2), 2)
            )) AS distancia,
            COUNT(v.id) AS apoyos
        FROM reportes r
        LEFT JOIN votos v ON v.reporte_id = r.id
        WHERE r.categoria_id = :categoria_id
          AND r.expirado = 0
        GROUP BY r.id, r.titulo, r.latitud, r.longitud
        HAVING distancia < 50
        ORDER BY distancia ASC
    SQL;

    $consulta = db()->prepare($sql);
    $consulta->execute([
        'categoria_id' => $categoriaId,
        'latitud' => $latitud,
        'latitudReferencia' => $latitud,
        'longitud' => $longitud,
    ]);

    $mejorCoincidencia = null;
    $mayorSimilitud = 0.0;

    foreach ($consulta->fetchAll() as $reporte) {
        similar_text(mb_strtolower($titulo), mb_strtolower((string) $reporte['titulo']), $similitud);

        if ($similitud > 60 && $similitud > $mayorSimilitud) {
            $mayorSimilitud = $similitud;
            $mejorCoincidencia = [
                'id' => (int) $reporte['id'],
                'titulo' => (string) $reporte['titulo'],
                'distancia' => round((float) $reporte['distancia'], 2),
                'apoyos' => (int) $reporte['apoyos'],
                'similitud' => round($similitud, 2),
            ];
        }
    }

    return $mejorCoincidencia;
}
