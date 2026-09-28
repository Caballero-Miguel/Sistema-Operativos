USE barrio_alerta;

-- Usuarios ciudadanos de demostración. Contraseña de los tres: test123
INSERT INTO usuarios (nombre, email, password, rol) VALUES
    ('María de Prueba', 'maria@test.com', '$2y$10$Ml.Jxy1javT15qlrUpTOyuplphbwm63ScOZLSMJrCmtew1lyzkadO', 'ciudadano'),
    ('Pedro de Prueba', 'pedro@test.com', '$2y$10$Ml.Jxy1javT15qlrUpTOyuplphbwm63ScOZLSMJrCmtew1lyzkadO', 'ciudadano'),
    ('Ana de Prueba', 'ana@test.com', '$2y$10$Ml.Jxy1javT15qlrUpTOyuplphbwm63ScOZLSMJrCmtew1lyzkadO', 'ciudadano')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    password = VALUES(password),
    rol = VALUES(rol);

SELECT id INTO @maria_id FROM usuarios WHERE email = 'maria@test.com';
SELECT id INTO @pedro_id FROM usuarios WHERE email = 'pedro@test.com';
SELECT id INTO @ana_id FROM usuarios WHERE email = 'ana@test.com';
SELECT id INTO @cat_calles FROM categorias WHERE nombre = 'Calles y Baches';
SELECT id INTO @cat_alumbrado FROM categorias WHERE nombre = 'Alumbrado';
SELECT id INTO @cat_basura FROM categorias WHERE nombre = 'Basura y Limpieza';
SELECT id INTO @cat_servicios FROM categorias WHERE nombre = 'Servicios Básicos';

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@maria_id, @cat_calles, 1, 'Bache grande frente al parque', 'El bache dificulta el paso de vehículos y bicicletas.', 8.98240000, -79.51990000, 'Avenida Central, frente al parque', DATE_ADD(NOW(), INTERVAL 20 HOUR), 0, DATE_SUB(NOW(), INTERVAL 4 HOUR));
SET @reporte1 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@pedro_id, @cat_alumbrado, 2, 'Lámpara apagada en la esquina', 'La esquina queda oscura durante la noche.', 8.98510000, -79.51670000, 'Calle 5, esquina con Calle 8', DATE_ADD(NOW(), INTERVAL 18 HOUR), 0, DATE_SUB(NOW(), INTERVAL 8 HOUR));
SET @reporte2 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, resuelto_marcado_en, created_at)
VALUES (@ana_id, @cat_basura, 3, 'Basura acumulada junto a la parada', 'Hay bolsas acumuladas desde el fin de semana.', 8.97980000, -79.52310000, 'Parada de buses del mercado', DATE_ADD(NOW(), INTERVAL 16 HOUR), 0, NOW(), DATE_SUB(NOW(), INTERVAL 10 HOUR));
SET @reporte3 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@maria_id, @cat_servicios, 4, 'Fuga de agua reparada', 'La comunidad confirmó la reparación.', 8.98800000, -79.52100000, 'Calle 12, casa comunal', DATE_ADD(NOW(), INTERVAL 30 HOUR), 0, DATE_SUB(NOW(), INTERVAL 1 DAY));
SET @reporte4 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@pedro_id, @cat_calles, 5, 'Acera rota frente a la escuela', 'Reporte rechazado por estar fuera del área municipal.', 8.97650000, -79.51480000, 'Frente a la escuela del barrio', DATE_ADD(NOW(), INTERVAL 12 HOUR), 0, DATE_SUB(NOW(), INTERVAL 2 DAY));
SET @reporte5 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@ana_id, @cat_alumbrado, 1, 'Farola intermitente', 'La luz se enciende y apaga durante la noche.', 8.99120000, -79.51820000, 'Calle 20, sector norte', DATE_ADD(NOW(), INTERVAL 2 HOUR), 0, DATE_SUB(NOW(), INTERVAL 22 HOUR));
SET @reporte6 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@maria_id, @cat_basura, 2, 'Contenedor de basura lleno', 'El contenedor rebosa y necesita recolección.', 8.97440000, -79.52750000, 'Cancha del barrio', DATE_SUB(NOW(), INTERVAL 1 HOUR), 1, DATE_SUB(NOW(), INTERVAL 2 DAY));
SET @reporte7 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@pedro_id, @cat_servicios, 3, 'Presión baja de agua', 'El servicio llega con poca presión por las mañanas.', 8.98110000, -79.51190000, 'Residencial Las Flores', DATE_ADD(NOW(), INTERVAL 15 HOUR), 0, DATE_SUB(NOW(), INTERVAL 6 HOUR));
SET @reporte8 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@ana_id, @cat_calles, 1, 'Señal de tránsito inclinada', 'La señal está inclinada y casi no se puede leer.', 8.98670000, -79.52900000, 'Rotonda del barrio', DATE_ADD(NOW(), INTERVAL 22 HOUR), 0, DATE_SUB(NOW(), INTERVAL 3 HOUR));
SET @reporte9 = LAST_INSERT_ID();

INSERT INTO reportes (usuario_id, categoria_id, estado_id, titulo, descripcion, latitud, longitud, direccion, expira_en, expirado, created_at)
VALUES (@maria_id, @cat_alumbrado, 2, 'Alumbrado insuficiente en el pasaje', 'Dos luminarias no funcionan.', 8.99340000, -79.52440000, 'Pasaje Los Pinos', DATE_ADD(NOW(), INTERVAL 26 HOUR), 0, DATE_SUB(NOW(), INTERVAL 12 HOUR));
SET @reporte10 = LAST_INSERT_ID();

INSERT INTO votos (reporte_id, usuario_id) VALUES
    (@reporte1, @pedro_id),
    (@reporte1, @ana_id),
    (@reporte2, @maria_id),
    (@reporte2, @ana_id),
    (@reporte3, @maria_id),
    (@reporte8, @maria_id),
    (@reporte8, @ana_id);

INSERT INTO confirmaciones (reporte_id, usuario_id, voto) VALUES
    (@reporte3, @maria_id, 'si'),
    (@reporte3, @pedro_id, 'no'),
    (@reporte8, @maria_id, 'si');
