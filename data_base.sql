-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.4.24-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win64
-- HeidiSQL Version:             12.16.0.7229
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table fodrescue.productos
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comercio_id` int(11) NOT NULL,
  `titulo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `precio_original` decimal(10,2) NOT NULL,
  `precio_descuento` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 1,
  `horario_retiro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `imagen_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('disponible','agotado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_productos_comercio` (`comercio_id`),
  CONSTRAINT `fk_productos_comercio` FOREIGN KEY (`comercio_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fodrescue.productos: ~6 rows (approximately)
DELETE FROM `productos`;
INSERT INTO `productos` (`id`, `comercio_id`, `titulo`, `descripcion`, `categoria`, `precio_original`, `precio_descuento`, `stock`, `horario_retiro`, `imagen_url`, `estado`, `fecha_creacion`) VALUES
	(1, 1, 'Pack Sorpresa: Medialunas y Facturas x12', 'Docena surtida de medialunas dulces de manteca y facturas rellenas del dia.', 'Panadería', 50000.00, 25000.00, 4, '19:30 a 20:30 hs', NULL, 'disponible', '2026-10-05 21:14:02'),
	(2, 1, 'Chipa Tradicional y Baguettes Artesanales', 'Bolsa con 4 chipas almidon recien horneadas y 2 baguettes crujientes.', 'Panadería', 35000.00, 18000.00, 3, '20:00 a 21:00 hs', NULL, 'disponible', '2026-10-05 21:14:02'),
	(3, 1, 'Sandwiches de Miga Mixtos x6', 'Exquisitos sandwiches de jamon cocido y queso en pan de miga artesanal.', 'Cafetería', 42000.00, 20000.00, 3, '19:00 a 20:30 hs', NULL, 'disponible', '2026-10-05 21:14:02'),
	(4, 3, 'Pack Dulce: Pastafrola Artesanal y Porciones de Tarta', 'Pastafrola casera de guayaba y 2 porciones de tarta de manzana recién elaboradas.', 'Pastelería', 36000.00, 18000.00, 2, '18:30 a 20:00 hs', NULL, 'disponible', '2026-10-07 22:50:29'),
	(5, 4, 'Bolsa Rescate: Chipa Pirí y Pan Felipe Casero (1.5 kg)', 'Mix de chipitas tostadas crocantes y pan felipe fresco del día.', 'Panadería', 30000.00, 14000.00, 3, '19:00 a 21:00 hs', NULL, 'disponible', '2026-10-07 22:50:29'),
	(6, 5, 'Canasta Rescate: Frutas y Cítricos Seleccionados (3 kg)', 'Mix fresco de bananas, naranjas para jugo y manzanas de temporada con óptima maduración.', 'Verdulería / Frutas', 28000.00, 12000.00, 3, '18:00 a 19:30 hs', NULL, 'disponible', '2026-10-07 22:50:29');

-- Dumping structure for table fodrescue.puntos_lealtad
CREATE TABLE IF NOT EXISTS `puntos_lealtad` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `total_compras` int(11) NOT NULL DEFAULT 0,
  `puntos` int(11) NOT NULL DEFAULT 0,
  `descuento_desbloqueado` tinyint(1) NOT NULL DEFAULT 0,
  `nivel` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Bronce',
  `ultima_actualizacion` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_lealtad_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fodrescue.puntos_lealtad: ~1 rows (approximately)
DELETE FROM `puntos_lealtad`;
INSERT INTO `puntos_lealtad` (`id`, `usuario_id`, `total_compras`, `puntos`, `descuento_desbloqueado`, `nivel`, `ultima_actualizacion`) VALUES
	(1, 2, 6, 180, 1, 'Plata', '2026-10-17 17:13:15');

-- Dumping structure for table fodrescue.resenas
CREATE TABLE IF NOT EXISTS `resenas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reserva_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comercio_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `calificacion` int(11) NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reserva_id` (`reserva_id`),
  KEY `fk_resenas_usuario` (`usuario_id`),
  KEY `fk_resenas_comercio` (`comercio_id`),
  KEY `fk_resenas_producto` (`producto_id`),
  CONSTRAINT `fk_resenas_comercio` FOREIGN KEY (`comercio_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resenas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resenas_reserva` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resenas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fodrescue.resenas: ~1 rows (approximately)
DELETE FROM `resenas`;
INSERT INTO `resenas` (`id`, `reserva_id`, `usuario_id`, `comercio_id`, `producto_id`, `calificacion`, `comentario`, `fecha_creacion`) VALUES
	(1, 4, 2, 1, 2, 5, 'íExcelente calidad de las chipas y baguettes! S·per frescas y crujientes, excelente iniciativa para no desperdiciar comida.', '2026-10-16 16:33:09');

-- Dumping structure for table fodrescue.reservas
CREATE TABLE IF NOT EXISTS `reservas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `codigo_retiro` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_reserva` datetime DEFAULT current_timestamp(),
  `estado` enum('reservado','retirado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reservado',
  PRIMARY KEY (`id`),
  KEY `fk_reservas_usuario` (`usuario_id`),
  KEY `fk_reservas_producto` (`producto_id`),
  CONSTRAINT `fk_reservas_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reservas_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fodrescue.reservas: ~5 rows (approximately)
DELETE FROM `reservas`;
INSERT INTO `reservas` (`id`, `usuario_id`, `producto_id`, `cantidad`, `codigo_retiro`, `fecha_reserva`, `estado`) VALUES
	(1, 2, 5, 1, 'FR-559B6', '2026-10-08 22:30:51', 'reservado'),
	(2, 2, 5, 1, 'FR-BFA6E', '2026-10-11 14:30:46', 'reservado'),
	(3, 2, 1, 1, 'FR-DEMO1', '2026-10-17 15:33:09', 'retirado'),
	(4, 2, 2, 1, 'FR-PAST1', '2026-10-16 16:33:09', 'retirado'),
	(5, 2, 6, 1, 'FR-00F08', '2026-10-17 17:13:15', 'reservado');

-- Dumping structure for table fodrescue.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('cliente','comercio') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitud` decimal(10,7) DEFAULT NULL,
  `longitud` decimal(10,7) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table fodrescue.usuarios: ~5 rows (approximately)
DELETE FROM `usuarios`;
INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `direccion`, `telefono`, `latitud`, `longitud`, `fecha_registro`) VALUES
	(1, 'Panadería & Confitería Ñandutí', 'comercio@laespiga.com', '$2y$10$yO.CjvoteUyZQqRaRpB5dO.z0KmSVxEf98t6QakIFExsR363SaJp.', 'comercio', 'Ruta PY02 Km 30 c/ Tte. Martínez, Itauguá', '+595 981 456789', -25.3868000, -57.3395000, '2026-10-05 21:14:02'),
	(2, 'Lucas Gómez', 'cliente@foodrescue.com', '$2y$10$yO.CjvoteUyZQqRaRpB5dO.z0KmSVxEf98t6QakIFExsR363SaJp.', 'cliente', 'Palma 340 c/ Chile, Asuncion', '+595 982 987654', NULL, NULL, '2026-10-05 21:14:02'),
	(3, 'Café & Pastelería San Rafael', 'sanrafael@itaugua.com', '$2y$10$yO.CjvoteUyZQqRaRpB5dO.z0KmSVxEf98t6QakIFExsR363SaJp.', 'comercio', 'Defensores del Chaco c/ Mcal. López, Centro de Itauguá', '+595 983 112233', -25.3852000, -57.3421000, '2026-10-07 22:50:29'),
	(4, 'Chipería & Panificados Itauguá Poty', 'itauguapoty@gmail.com', '$2y$10$yO.CjvoteUyZQqRaRpB5dO.z0KmSVxEf98t6QakIFExsR363SaJp.', 'comercio', 'Ruta PY02 Km 28 (Frente a Petropar), Itauguá', '+595 984 556677', -25.3885000, -57.3368000, '2026-10-07 22:50:29'),
	(5, 'Frutería y Huertario Central', 'huertacentral@gmail.com', '$2y$10$yO.CjvoteUyZQqRaRpB5dO.z0KmSVxEf98t6QakIFExsR363SaJp.', 'comercio', 'Mercado Municipal de Itauguá, Casilla 14', '+595 985 778899', -25.3832000, -57.3445000, '2026-10-07 22:50:29');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
