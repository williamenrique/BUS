-- =====================================================================
-- SCRIPT 01: TABLA DE INSTITUCIONES
-- Idempotente. Ejecutar primero.
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE IF NOT EXISTS `table_instituciones` (
  `id_institucion` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `rif` VARCHAR(20) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_institucion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `table_instituciones` (`id_institucion`, `nombre`, `status`) VALUES
(1, 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY', 1),
(2, 'TALLER', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'SCRIPT 01 OK' AS resultado;