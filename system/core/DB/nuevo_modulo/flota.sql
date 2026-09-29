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



-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_flota_aceite_historial_taller
CREATE TABLE IF NOT EXISTS `table_flota_aceite_historial_taller` (
  `id_aceite_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `fecha_cambio` date NOT NULL,
  `kilometraje_cambio` int(11) NOT NULL COMMENT 'Kilometraje al momento del cambio',
  `kilometraje_anterior` int(11) DEFAULT NULL,
  `kilometraje_proximo_cambio` int(11) NOT NULL COMMENT 'Kilometraje estimado para el siguiente cambio',
  `usuario_id` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_aceite_historial`),
  KEY `fk_aceite_flota` (`id_flota`),
  CONSTRAINT `fk_aceite_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_flota_kilometraje_taller
CREATE TABLE IF NOT EXISTS `table_flota_kilometraje_taller` (
  `id_kilometraje` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `kilometraje_actual` int(11) NOT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id_kilometraje`),
  KEY `fk_kilometraje_flota` (`id_flota`),
  CONSTRAINT `fk_kilometraje_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_flota_mantenimiento_taller
CREATE TABLE IF NOT EXISTS `table_flota_mantenimiento_taller` (
  `id_unidad_mantenimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `ruta_unidad` mediumtext DEFAULT NULL,
  `operardor_unidad` mediumtext DEFAULT NULL,
  `nomb_mecanico` mediumtext DEFAULT NULL,
  `km_unidad` varchar(20) DEFAULT NULL,
  `tipo_mantenimiento` char(1) DEFAULT NULL,
  `diagnostico` mediumtext DEFAULT NULL,
  `recomendacion` mediumtext DEFAULT NULL,
  `obsOperador` mediumtext DEFAULT NULL,
  `obsSupervisor` mediumtext DEFAULT NULL,
  `obsSalida` mediumtext DEFAULT NULL,
  `fecha_entrada` varchar(25) DEFAULT NULL,
  `fecha_salida` varchar(25) DEFAULT NULL,
  `status_mantenimiento` char(1) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id_unidad_mantenimiento`),
  KEY `fk_table_flota_mantenimiento_taller_table_flota1_idx` (`id_flota`),
  KEY `fk_table_flota_mantenimiento_taller_table_usuarios1_idx` (`usuario_id`),
  CONSTRAINT `fk_table_flota_mantenimiento_taller_table_flota1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `fk_table_flota_mantenimiento_taller_table_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla busyaracuydata.table_flota_status_taller
CREATE TABLE IF NOT EXISTS `table_flota_status_taller` (
  `idCambioStatus` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `idstatus` int(11) DEFAULT NULL,
  `textCambio` mediumtext DEFAULT NULL,
  `fechaCambio` timestamp NULL DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`idCambioStatus`),
  KEY `fk_table_flota_status_taller_table_flota1_idx` (`id_flota`),
  KEY `fk_table_flota_status_taller_table_usuarios1_idx` (`usuario_id`),
  CONSTRAINT `fk_table_flota_status_taller_table_flota1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `fk_table_flota_status_taller_table_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
