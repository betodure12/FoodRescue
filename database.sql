-- =========================================================
-- FoodRescue Database Schema
-- Gestión de Excedentes de Comida para Pequeños Comercios
-- =========================================================

CREATE DATABASE IF NOT EXISTS `foodrescue_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `foodrescue_db`;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `rol` ENUM('cliente', 'comercio') NOT NULL DEFAULT 'cliente',
    `direccion` VARCHAR(255) NULL,
    `telefono` VARCHAR(30) NULL,
    `fecha_registro` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de Productos (Excedentes de comida publicados por comercios)
CREATE TABLE IF NOT EXISTS `productos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `comercio_id` INT NOT NULL,
    `titulo` VARCHAR(150) NOT NULL,
    `descripcion` TEXT NULL,
    `categoria` VARCHAR(50) DEFAULT 'General',
    `precio_original` DECIMAL(10,2) NOT NULL,
    `precio_descuento` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 1,
    `horario_retiro` VARCHAR(100) NULL,
    `imagen_url` VARCHAR(255) NULL,
    `estado` ENUM('disponible', 'agotado') NOT NULL DEFAULT 'disponible',
    `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_productos_comercio` 
        FOREIGN KEY (`comercio_id`) REFERENCES `usuarios`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de Reservas
CREATE TABLE IF NOT EXISTS `reservas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `producto_id` INT NOT NULL,
    `cantidad` INT NOT NULL DEFAULT 1,
    `codigo_retiro` VARCHAR(20) NOT NULL,
    `fecha_reserva` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `estado` ENUM('reservado', 'retirado', 'cancelado') NOT NULL DEFAULT 'reservado',
    CONSTRAINT `fk_reservas_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reservas_producto` 
        FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de Puntos de Lealtad e Impacto
CREATE TABLE IF NOT EXISTS `puntos_lealtad` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL UNIQUE,
    `total_compras` INT NOT NULL DEFAULT 0,
    `puntos` INT NOT NULL DEFAULT 0,
    `descuento_desbloqueado` TINYINT(1) NOT NULL DEFAULT 0,
    `nivel` VARCHAR(30) NOT NULL DEFAULT 'Bronce',
    `ultima_actualizacion` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_lealtad_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de Reseñas y Calificaciones
CREATE TABLE IF NOT EXISTS `resenas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reserva_id` INT NOT NULL UNIQUE,
    `usuario_id` INT NOT NULL,
    `comercio_id` INT NOT NULL,
    `producto_id` INT NOT NULL,
    `calificacion` INT NOT NULL,
    `comentario` TEXT NULL,
    `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_resenas_reserva` 
        FOREIGN KEY (`reserva_id`) REFERENCES `reservas`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resenas_usuario` 
        FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resenas_comercio` 
        FOREIGN KEY (`comercio_id`) REFERENCES `usuarios`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_resenas_producto` 
        FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- Datos iniciales de demostración (Seed Data - Zona Itauguá, Central)
-- Contraseña para todos los usuarios de prueba: password123
-- =========================================================

-- Inserción de usuarios de prueba en Itauguá (Comercios y Clientes)
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `direccion`, `telefono`) VALUES
(1, 'Panadería & Confitería Ñandutí', 'comercio@laespiga.com', '$2y$10$2R.msOuaqdDR3TfZp1s.jOUTb.ZIWUzN9HaTV5YxtUB.j6Xh8R6qa', 'comercio', 'Ruta PY02 Km 30 c/ Tte. Martínez, Itauguá', '+595 981 334455'),
(2, 'Lucas Gómez', 'cliente@foodrescue.com', '$2y$10$2R.msOuaqdDR3TfZp1s.jOUTb.ZIWUzN9HaTV5YxtUB.j6Xh8R6qa', 'cliente', 'Barrio San Antonio c/ Defensores del Chaco, Itauguá', '+595 982 987654'),
(3, 'Café & Pastelería San Rafael', 'sanrafael@itaugua.com', '$2y$10$2R.msOuaqdDR3TfZp1s.jOUTb.ZIWUzN9HaTV5YxtUB.j6Xh8R6qa', 'comercio', 'Defensores del Chaco c/ Mcal. López, Centro de Itauguá', '+595 983 112233'),
(4, 'Chipería & Panificados Itauguá Poty', 'itauguapoty@gmail.com', '$2y$10$2R.msOuaqdDR3TfZp1s.jOUTb.ZIWUzN9HaTV5YxtUB.j6Xh8R6qa', 'comercio', 'Ruta PY02 Km 28 (Frente a Petropar), Itauguá', '+595 984 556677'),
(5, 'Frutería y Huertario Central', 'huertacentral@gmail.com', '$2y$10$2R.msOuaqdDR3TfZp1s.jOUTb.ZIWUzN9HaTV5YxtUB.j6Xh8R6qa', 'comercio', 'Mercado Municipal de Itauguá, Casilla 14', '+595 985 778899')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Puntos de lealtad iniciales para el cliente
INSERT INTO `puntos_lealtad` (`usuario_id`, `total_compras`, `puntos`, `descuento_desbloqueado`, `nivel`) VALUES
(2, 4, 180, 1, 'Plata')
ON DUPLICATE KEY UPDATE `usuario_id`=`usuario_id`;

-- Inserción de productos y excedentes del día en Itauguá (Guaraníes)
INSERT INTO `productos` (`id`, `comercio_id`, `titulo`, `descripcion`, `categoria`, `precio_original`, `precio_descuento`, `stock`, `horario_retiro`, `estado`) VALUES
(1, 1, 'Pack Sorpresa: Medialunas y Facturas Rellenas x12', 'Docena surtida de medialunas dulces de manteca y facturas con dulce de guayaba y crema pastelera del día.', 'Panadería', 48000.00, 24000.00, 4, '19:30 a 20:30 hs', 'disponible'),
(2, 1, 'Chipa Tradicional y Baguettes Artesanales', 'Bolsa con 4 chipas almidón recién horneadas y 2 baguettes crujientes para la cena.', 'Panadería', 35000.00, 17000.00, 3, '20:00 a 21:00 hs', 'disponible'),
(3, 3, 'Sándwiches de Miga y Empanadas al Horno x6', 'Exquisitos sándwiches de jamón y queso artesanal más empanadas surtidas de carne y choclo.', 'Cafetería', 42000.00, 20000.00, 3, '19:00 a 20:30 hs', 'disponible'),
(4, 3, 'Pack Dulce: Pastafrola Artesanal y Porciones de Tarta', 'Pastafrola casera de guayaba y 2 porciones de tarta de manzana recién elaboradas.', 'Pastelería', 36000.00, 18000.00, 2, '18:30 a 20:00 hs', 'disponible'),
(5, 4, 'Bolsa Rescate: Chipa Pirí y Pan Felipe Casero (1.5 kg)', 'Mix de chipitas tostadas crocantes y pan felipe fresco del día.', 'Panadería', 30000.00, 14000.00, 5, '19:00 a 21:00 hs', 'disponible'),
(6, 5, 'Canasta Rescate: Frutas y Cítricos Seleccionados (3 kg)', 'Mix fresco de bananas, naranjas para jugo y manzanas de temporada con óptima maduración.', 'Verdulería / Frutas', 28000.00, 12000.00, 4, '18:00 a 19:30 hs', 'disponible')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Reservas de demostración (activas y completadas para la defensa del examen)
INSERT INTO `reservas` (`id`, `usuario_id`, `producto_id`, `cantidad`, `codigo_retiro`, `fecha_reserva`, `estado`) VALUES
(1, 2, 1, 1, 'FR-DEMO1', NOW() - INTERVAL 1 HOUR, 'reservado'),
(2, 2, 2, 1, 'FR-PAST1', NOW() - INTERVAL 1 DAY, 'retirado')
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Reseña de demostración para el producto retirado
INSERT INTO `resenas` (`id`, `reserva_id`, `usuario_id`, `comercio_id`, `producto_id`, `calificacion`, `comentario`, `fecha_creacion`) VALUES
(1, 2, 2, 1, 2, 5, '¡Excelente calidad de las chipas y baguettes! Súper frescas y crujientes, excelente iniciativa para no desperdiciar comida.', NOW() - INTERVAL 1 DAY)
ON DUPLICATE KEY UPDATE `id`=`id`;


