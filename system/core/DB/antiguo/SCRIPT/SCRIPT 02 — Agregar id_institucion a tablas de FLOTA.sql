-- =====================================================================
-- SCRIPT 02: id_institucion en tablas de FLOTA
-- Afecta: table_flota, table_flota_kilometraje,
--         table_flota_aceite_historial, table_flota_mantenimiento,
--         table_flota_status
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------- table_flota ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_unidad`',
  'SELECT "table_flota.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota'
            AND INDEX_NAME='idx_flota_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota` ADD INDEX `idx_flota_institucion` (`id_institucion`)',
  'SELECT "idx_flota_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota'
            AND CONSTRAINT_NAME='fk_flota_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota` ADD CONSTRAINT `fk_flota_institucion`
     FOREIGN KEY (`id_institucion`) REFERENCES `table_instituciones`(`id_institucion`)',
  'SELECT "fk_flota_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_flota_kilometraje ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_kilometraje'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_kilometraje` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_kilometraje.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_flota_aceite_historial ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_aceite_historial'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_aceite_historial` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_aceite_historial.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_flota_mantenimiento ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_mantenimiento'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_mantenimiento` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_mantenimiento.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_flota_status ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_status'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_status` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_status.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- Migrar data existente a institución 1 ----------
UPDATE `table_flota`                 SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_kilometraje`     SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_aceite_historial`SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_mantenimiento`   SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_status`          SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'SCRIPT 02 OK' AS resultado;