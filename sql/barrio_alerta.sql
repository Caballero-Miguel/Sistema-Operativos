CREATE DATABASE IF NOT EXISTS barrio_alerta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE barrio_alerta;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('ciudadano', 'admin') NOT NULL DEFAULT 'ciudadano',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    icono VARCHAR(50) DEFAULT NULL,
    color VARCHAR(7) DEFAULT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY unico_nombre_categoria (nombre),
    INDEX idx_categorias_activa (activa)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    color VARCHAR(7) DEFAULT NULL,
    orden INT NOT NULL,
    UNIQUE KEY unico_orden_estado (orden)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT NOT NULL,
    estado_id INT NOT NULL DEFAULT 1,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    latitud DECIMAL(10,8) NOT NULL,
    longitud DECIMAL(11,8) NOT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    expira_en DATETIME NOT NULL,
    expirado TINYINT(1) NOT NULL DEFAULT 0,
    resuelto_marcado_en DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reportes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_reportes_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    CONSTRAINT fk_reportes_estado FOREIGN KEY (estado_id) REFERENCES estados(id),
    INDEX idx_reportes_expira (expira_en, expirado),
    INDEX idx_reportes_categoria (categoria_id),
    INDEX idx_reportes_estado (estado_id),
    INDEX idx_reportes_ubicacion (latitud, longitud)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporte_id INT NOT NULL,
    usuario_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_votos_reporte FOREIGN KEY (reporte_id) REFERENCES reportes(id) ON DELETE CASCADE,
    CONSTRAINT fk_votos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unico_voto (reporte_id, usuario_id),
    INDEX idx_votos_reporte (reporte_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS confirmaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporte_id INT NOT NULL,
    usuario_id INT NOT NULL,
    voto ENUM('si', 'no') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_confirmaciones_reporte FOREIGN KEY (reporte_id) REFERENCES reportes(id) ON DELETE CASCADE,
    CONSTRAINT fk_confirmaciones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unico_voto_confirmacion (reporte_id, usuario_id),
    INDEX idx_confirmaciones_reporte_voto (reporte_id, voto)
) ENGINE=InnoDB;

UPDATE usuarios
SET email = 'admin@barrio.com',
    password = '$2y$10$kmQBupZGX9XQ/npP6reiRuhGI8L0sxbgDdlWEe9NIIkJ4wj2ExbH.'
WHERE email = 'admin@barrio-alerta.local';

INSERT INTO estados (id, nombre, color, orden) VALUES
    (1, 'Pendiente', '#ffc107', 1),
    (2, 'En proceso', '#17a2b8', 2),
    (3, 'Resuelto (por confirmar)', '#fd7e14', 3),
    (4, 'Resuelto', '#28a745', 4),
    (5, 'Rechazado', '#dc3545', 5)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    color = VALUES(color),
    orden = VALUES(orden);

INSERT INTO categorias (nombre, icono, color, activa) VALUES
    ('Calles y Baches', 'road', '#e74c3c', 1),
    ('Alumbrado', 'lightbulb', '#f39c12', 1),
    ('Basura y Limpieza', 'trash', '#27ae60', 1),
    ('Servicios Básicos', 'water', '#3498db', 1)
ON DUPLICATE KEY UPDATE
    icono = VALUES(icono),
    color = VALUES(color),
    activa = VALUES(activa);

INSERT INTO usuarios (nombre, email, password, rol) VALUES
    ('Administrador municipal', 'admin@barrio.com', '$2y$10$kmQBupZGX9XQ/npP6reiRuhGI8L0sxbgDdlWEe9NIIkJ4wj2ExbH.', 'admin')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    password = VALUES(password),
    rol = VALUES(rol);
