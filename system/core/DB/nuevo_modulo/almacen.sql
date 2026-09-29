-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.21.0.7344
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Volcando estructura para tabla busyaracuydata.table_alm_despacho_taller
CREATE TABLE IF NOT EXISTS `table_alm_despacho_taller` (
  `id_despacho` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `operador` varchar(50) NOT NULL,
  `mecanico` varchar(50) NOT NULL,
  `despachador` varchar(50) NOT NULL,
  `fecha_despacho` varchar(12) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `observacion` mediumtext DEFAULT NULL,
  `status_despacho` int(11) NOT NULL,
  `estado_orden` tinyint(1) NOT NULL DEFAULT 3 COMMENT '1: Requisicion, 2: Aprobada por Compras, 3: Despachada, 4: Rechazada',
  `usuario_aprobador_id` int(11) DEFAULT NULL COMMENT 'FK a table_usuarios. ID del usuario que aprueba.',
  `fecha_aprobacion` datetime DEFAULT NULL COMMENT 'Fecha y hora de la aprobación por Compras',
  PRIMARY KEY (`id_despacho`),
  KEY `id_flota` (`id_flota`),
  KEY `user_id` (`user_id`),
  KEY `fk_despacho_aprobador` (`usuario_aprobador_id`),
  CONSTRAINT `fk_despacho_aprobador` FOREIGN KEY (`usuario_aprobador_id`) REFERENCES `table_usuarios` (`usuario_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `table_alm_despacho_taller_ibfk_1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `table_alm_despacho_taller_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_alm_historial_cambio_taller
CREATE TABLE IF NOT EXISTS `table_alm_historial_cambio_taller` (
  `id_historial_cambio` int(11) NOT NULL AUTO_INCREMENT,
  `obs` text CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `info` text CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `fecha_historia` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial_cambio`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `table_alm_historial_cambio_taller_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_alm_producto_taller
CREATE TABLE IF NOT EXISTS `table_alm_producto_taller` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_enlace_producto` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `producto` varchar(45) DEFAULT NULL,
  `tag_producto` int(11) DEFAULT NULL,
  `present_producto` varchar(20) NOT NULL,
  `status_producto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `id_enlace_producto` (`id_enlace_producto`),
  KEY `id_ubicacion` (`id_ubicacion`),
  CONSTRAINT `table_alm_producto_taller_ibfk_1` FOREIGN KEY (`id_enlace_producto`) REFERENCES `table_alm_enlace_producto` (`id_enlace_producto`),
  CONSTRAINT `table_alm_producto_taller_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `table_alm_ubicacion_taller` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_alm_relacion_despacho_taller
CREATE TABLE IF NOT EXISTS `table_alm_relacion_despacho_taller` (
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cant_despacho` float DEFAULT NULL,
  KEY `id_despacho` (`id_despacho`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `table_alm_relacion_despacho_taller_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `table_alm_despacho_taller` (`id_despacho`),
  CONSTRAINT `table_alm_relacion_despacho_taller_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto_taller` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_alm_relacion_producto_taller
CREATE TABLE IF NOT EXISTS `table_alm_relacion_producto_taller` (
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `cant_producto` float NOT NULL,
  KEY `id_producto` (`id_producto`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `table_alm_relacion_producto_taller_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto_taller` (`id_producto`),
  CONSTRAINT `table_alm_relacion_producto_taller_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `table_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_alm_ubicacion_taller
CREATE TABLE IF NOT EXISTS `table_alm_ubicacion_taller` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
