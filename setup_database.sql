-- Script para crear la base de datos y tablas del sistema de tickets
-- Ejecutar en phpMyAdmin o cliente MySQL

CREATE DATABASE IF NOT EXISTS tickets_ayuda;
USE tickets_ayuda;

-- Tabla de usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('usuario', 'admin') NOT NULL DEFAULT 'usuario',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tickets
CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    descripcion LONGTEXT NOT NULL,
    ubicacion ENUM('Finca El Jardín', 'San Ignacio') NOT NULL,
    area ENUM('Administracion','Poscosecha') NOT NULL DEFAULT 'Administracion',
    estado ENUM('Nuevo', 'En proceso', 'Resuelto', 'Cerrado') NOT NULL DEFAULT 'Nuevo',
    asignado_a INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (asignado_a) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_ubicacion (ubicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si ya existe la tabla y falta la columna 'area', se puede agregar con la siguiente instrucción (MySQL 8+):
-- ALTER TABLE tickets ADD COLUMN IF NOT EXISTS area ENUM('Administracion','Poscosecha') NOT NULL DEFAULT 'Administracion';

-- Tabla de respuestas en tickets
CREATE TABLE IF NOT EXISTS respuestas_ticket (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensaje LONGTEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_ticket (ticket_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de notificaciones internas para admins
CREATE TABLE IF NOT EXISTS notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'ticket_nuevo',
    mensaje TEXT NOT NULL,
    referencia_id INT NULL,
    leida TINYINT(1) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha_creacion (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin inicial (contraseña: admin123)
-- Hash: $2y$10$YKVgKEKSTlq8oJfKDY7C2.YXvJV.6W4eWFCWr5Ux1hb/qQ3WfPSZu
INSERT INTO usuarios (nombre, email, password, rol) 
VALUES ('Administrador', 'admin@tickets.local', '$2y$10$YKVgKEKSTlq8oJfKDY7C2.YXvJV.6W4eWFCWr5Ux1hb/qQ3WfPSZu', 'admin')
ON DUPLICATE KEY UPDATE password = VALUES(password);

-- Insertar usuario de prueba (contraseña: usuario123)
-- Hash: $2y$10$D9LLd8b9sHZWVvGNnNJzXuRhQ5P1kI8Y7Z3wM2b9cK6pL4xJ5V0Q6
INSERT INTO usuarios (nombre, email, password, rol) 
VALUES ('Usuario Prueba', 'usuario@tickets.local', '$2y$10$D9LLd8b9sHZWVvGNnNJzXuRhQ5P1kI8Y7Z3wM2b9cK6pL4xJ5V0Q6', 'usuario')
ON DUPLICATE KEY UPDATE password = VALUES(password);
