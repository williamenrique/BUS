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

-- Volcando estructura para tabla busyaracuydata.table_bienes_taller_inventario
CREATE TABLE IF NOT EXISTS `table_bienes_taller_inventario` (
  `id_bien_taller` int(11) NOT NULL AUTO_INCREMENT,
  `bien_depatamento_id` varchar(10) NOT NULL DEFAULT '',
  `grupo_id` varchar(50) DEFAULT NULL,
  `subgrupo_id` varchar(50) DEFAULT NULL,
  `seccion_id` varchar(50) NOT NULL DEFAULT '',
  `descripcion_bien` text DEFAULT NULL,
  `fecha_adquisicion` varchar(50) DEFAULT NULL,
  `status_bien` varchar(50) DEFAULT NULL COMMENT 'EN USO, EXTRAVIADO, EN REPARACION, DAÑADO',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL,
  `org` int(11) DEFAULT NULL,
  `edo` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo (eliminación lógica)',
  PRIMARY KEY (`id_bien_taller`),
  KEY `bien_depatamento_id` (`bien_depatamento_id`),
  KEY `grupo_id` (`grupo_id`),
  KEY `subgrupo_id` (`subgrupo_id`),
  KEY `seccion_id` (`seccion_id`),
  KEY `status` (`status`),
  CONSTRAINT `table_bienes_taller_inventario_ibfk_1` FOREIGN KEY (`bien_depatamento_id`) REFERENCES `table_bienes_departamentos` (`depatamento_bien_id`),
  CONSTRAINT `table_bienes_taller_inventario_ibfk_2` FOREIGN KEY (`grupo_id`) REFERENCES `table_bienes_grupo` (`id_grupo`),
  CONSTRAINT `table_bienes_taller_inventario_ibfk_3` FOREIGN KEY (`subgrupo_id`) REFERENCES `table_bienes_subgrupo` (`subgrupo_id`)
  -- Nota: No se agrega FK para seccion_id porque table_bienes_seccion.seccion_id no es PRIMARY KEY
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
