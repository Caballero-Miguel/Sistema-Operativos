<?php

declare(strict_types=1);

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'auth.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'upload.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'duplicados.php';

class ReporteController
{
    /**
     * Muestra el formulario o procesa la creación del reporte.
     */
    public static function crear(): void
    {
        iniciarSesion();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            self::procesarCreacion();
            return;
        }

        $categorias = db()->query('SELECT id, nombre, color FROM categorias WHERE activa = 1 ORDER BY nombre')->fetchAll();
        $tokenCSRF = generarTokenCSRF();

        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reportes' . DIRECTORY_SEPARATOR . 'crear.php';
    }

    /**
     * Valida los datos, detecta duplicados y guarda el nuevo reporte.
     */
    private static function procesarCreacion(): void
    {
        if (self::peticionSuperaPostMaximo()) {
            self::errorCrear('La imagen supera el tamaño máximo de 2MB');
        }

        if (!verificarTokenCSRF($_POST['token_csrf'] ?? null)) {
            self::errorCrear('La sesión del formulario expiró. Intenta nuevamente.');
        }

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        $categoriaId = filter_var($_POST['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $direccion = trim((string) ($_POST['direccion'] ?? ''));
        $latitud = filter_var($_POST['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
        $longitud = filter_var($_POST['longitud'] ?? null, FILTER_VALIDATE_FLOAT);
        $forzar = filter_var($_POST['forzar'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($titulo === '' || mb_strlen($titulo) > 150) {
            self::errorCrear('El título es obligatorio y no puede superar 150 caracteres.');
        }

        if ($categoriaId === false || $categoriaId < 1) {
            self::errorCrear('Selecciona una categoría válida.');
        }

        if ($latitud === false || $longitud === false || $latitud < -90 || $latitud > 90 || $longitud < -180 || $longitud > 180) {
            self::errorCrear('Debes seleccionar una ubicación válida en el mapa.');
        }

        if (mb_strlen($descripcion) > 10000 || mb_strlen($direccion) > 255) {
            self::errorCrear('La descripción o dirección supera el tamaño permitido.');
        }

        $categoria = db()->prepare('SELECT id FROM categorias WHERE id = :id AND activa = 1 LIMIT 1');
        $categoria->execute(['id' => $categoriaId]);
        if (!$categoria->fetch()) {
            self::errorCrear('La categoría seleccionada no está disponible.');
        }

        $foto = null;
        if (isset($_FILES['foto'])) {
            $resultadoFoto = subirFoto($_FILES['foto']);
            if ($resultadoFoto !== null && !$resultadoFoto['ok']) {
                flashError($resultadoFoto['error']);
                header('Location: /barrio-alerta/public/?r=reporte.crear');
                exit;
            }
            $foto = $resultadoFoto !== null && $resultadoFoto['ruta'] !== ''
                ? $resultadoFoto['ruta']
                : null;
        }

        $duplicado = buscarDuplicado((int) $categoriaId, (float) $latitud, (float) $longitud, $titulo);
        if ($duplicado !== null && !$forzar) {
            if ($foto !== null) {
                @unlink(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $foto);
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['duplicado' => true, 'reporte' => $duplicado], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $insertar = db()->prepare('INSERT INTO reportes
            (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, foto, expira_en)
            VALUES (:usuario_id, :categoria_id, 1, :titulo, :descripcion, :latitud, :longitud, :direccion, :foto, NOW() + INTERVAL 24 HOUR)');
        $insertar->execute([
            'usuario_id' => usuarioActual()['id'],
            'categoria_id' => $categoriaId,
            'titulo' => $titulo,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'direccion' => $direccion !== '' ? $direccion : null,
            'foto' => $foto,
        ]);

        $reporteId = (int) db()->lastInsertId();
        header('Location: ?r=reporte.ver&id=' . $reporteId);
        exit;
    }

    /**
     * Carga el detalle público de un reporte.
     */
    public static function ver(int $id): void
    {
        iniciarSesion();

        if ($id < 1) {
            http_response_code(404);
            $mensajeNoDisponible = 'El reporte solicitado no existe.';
            require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reportes' . DIRECTORY_SEPARATOR . 'no-disponible.php';
            return;
        }

        db()->exec('UPDATE reportes SET expirado = 1 WHERE expira_en <= NOW() AND expirado = 0');

        $consulta = db()->prepare('SELECT
                r.id, r.titulo, r.descripcion, r.latitud, r.longitud, r.direccion,
                r.foto, r.created_at, r.estado_id,
                c.nombre AS categoria_nombre, c.color AS categoria_color,
                e.nombre AS estado_nombre, e.color AS estado_color,
                u.nombre AS autor,
                COUNT(DISTINCT v.id) AS apoyos
            FROM reportes r
            INNER JOIN categorias c ON c.id = r.categoria_id
            INNER JOIN estados e ON e.id = r.estado_id
            INNER JOIN usuarios u ON u.id = r.usuario_id
            LEFT JOIN votos v ON v.reporte_id = r.id
            WHERE r.id = :id AND r.expirado = 0
            GROUP BY r.id, r.titulo, r.descripcion, r.latitud, r.longitud,
                r.direccion, r.foto, r.created_at, r.estado_id,
                c.nombre, c.color, e.nombre, e.color, u.nombre');
        $consulta->execute(['id' => $id]);
        $reporte = $consulta->fetch();

        if (!$reporte) {
            $consultaExistencia = db()->prepare('SELECT expirado FROM reportes WHERE id = :id LIMIT 1');
            $consultaExistencia->execute(['id' => $id]);
            $reporteExistente = $consultaExistencia->fetch();

            http_response_code(404);
            $mensajeNoDisponible = $reporteExistente !== false && (int) $reporteExistente['expirado'] === 1
                ? 'Este reporte ya no está disponible (puede haber expirado).'
                : 'El reporte solicitado no existe.';
            require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reportes' . DIRECTORY_SEPARATOR . 'no-disponible.php';
            return;
        }

        $reporte['apoyos'] = (int) $reporte['apoyos'];
        $reporte['ya_apoyo'] = false;
        $usuario = usuarioActual();

        if ($usuario !== null) {
            $apoyo = db()->prepare('SELECT COUNT(*) FROM votos WHERE reporte_id = :reporte_id AND usuario_id = :usuario_id');
            $apoyo->execute(['reporte_id' => $id, 'usuario_id' => $usuario['id']]);
            $reporte['ya_apoyo'] = (int) $apoyo->fetchColumn() > 0;
        }

        $confirmaciones = ['si' => 0, 'no' => 0];
        $reporte['ya_confirmo'] = false;
        $reporte['voto_confirmacion'] = null;
        if ((int) $reporte['estado_id'] === 3) {
            $consultaConfirmaciones = db()->prepare('SELECT voto, COUNT(*) AS total FROM confirmaciones WHERE reporte_id = :reporte_id GROUP BY voto');
            $consultaConfirmaciones->execute(['reporte_id' => $id]);
            foreach ($consultaConfirmaciones->fetchAll() as $confirmacion) {
                $confirmaciones[$confirmacion['voto']] = (int) $confirmacion['total'];
            }

            if ($usuario !== null) {
                $consultaVoto = db()->prepare('SELECT voto FROM confirmaciones WHERE reporte_id = :reporte_id AND usuario_id = :usuario_id LIMIT 1');
                $consultaVoto->execute(['reporte_id' => $id, 'usuario_id' => $usuario['id']]);
                $votoConfirmacion = $consultaVoto->fetchColumn();
                if ($votoConfirmacion !== false) {
                    $reporte['ya_confirmo'] = true;
                    $reporte['voto_confirmacion'] = (string) $votoConfirmacion;
                }
            }
        }

        $tokenCSRF = generarTokenCSRF();
        require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'reportes' . DIRECTORY_SEPARATOR . 'ver.php';
    }

    /**
     * Guarda un error y vuelve al formulario de creación.
     */
    private static function errorCrear(string $mensaje): void
    {
        flashError($mensaje);
        header('Location: /barrio-alerta/public/?r=reporte.crear');
        exit;
    }

    /**
     * Detecta cuando PHP descarta POST y FILES por superar post_max_size.
     */
    private static function peticionSuperaPostMaximo(): bool
    {
        $limite = ini_get('post_max_size');
        $contenido = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);

        if ($limite === false || $limite === '' || $contenido === 0) {
            return false;
        }

        $unidad = strtolower(substr(trim($limite), -1));
        $valor = (float) $limite;
        $multiplicador = match ($unidad) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return $contenido > (int) ($valor * $multiplicador);
    }
}
