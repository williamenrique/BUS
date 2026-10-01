-- =====================================================================
-- FASE 1: TABLA DE INSTITUCIONES
-- =====================================================================
CREATE TABLE IF NOT EXISTS `table_instituciones` (
  `id_institucion` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `rif` VARCHAR(20) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_institucion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `table_instituciones` (`id_institucion`, `nombre`, `status`) VALUES
(1, 'Institución Actual', 1),
(2, 'Taller', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- =====================================================================
-- FASE 2: CAMPO DISCRIMINADOR EN TABLAS DE FLOTA
-- =====================================================================
ALTER TABLE `table_flota` 
  ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_unidad`,
  ADD INDEX `idx_flota_institucion` (`id_institucion`),
  ADD INDEX `idx_flota_institucion_status` (`id_institucion`, `status_unidad`);

ALTER TABLE `table_flota_kilometraje` 
  ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1,
  ADD INDEX `idx_km_institucion` (`id_institucion`);

ALTER TABLE `table_flota_aceite_historial` 
  ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1,
  ADD INDEX `idx_aceite_institucion` (`id_institucion`);

ALTER TABLE `table_flota_mantenimiento` 
  ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1,
  ADD INDEX `idx_mant_institucion` (`id_institucion`);

ALTER TABLE `table_flota_status` 
  ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1,
  ADD INDEX `idx_status_institucion` (`id_institucion`);

-- =====================================================================
-- FASE 3: FK (recomendada)
-- =====================================================================
ALTER TABLE `table_flota` 
  ADD CONSTRAINT `fk_flota_institucion` 
  FOREIGN KEY (`id_institucion`) REFERENCES `table_instituciones`(`id_institucion`);

-- =====================================================================
-- VERIFICACIÓN POST-EJECUCIÓN (opcional pero recomendada)
-- =====================================================================
-- Ejecuta estos para confirmar que todo quedó bien:

-- SELECT * FROM table_instituciones;
-- SELECT id_flota, id_unidad, id_institucion FROM table_flota LIMIT 5;
-- SELECT COUNT(*) as total, id_institucion FROM table_flota GROUP BY id_institucion;
-- SELECT COUNT(*) as total, id_institucion FROM table_flota_kilometraje GROUP BY id_institucion;
-- SELECT COUNT(*) as total, id_institucion FROM table_flota_aceite_historial GROUP BY id_institucion;
-- SELECT COUNT(*) as total, id_institucion FROM table_flota_mantenimiento GROUP BY id_institucion;
-- SELECT COUNT(*) as total, id_institucion FROM table_flota_status GROUP BY id_institucion;