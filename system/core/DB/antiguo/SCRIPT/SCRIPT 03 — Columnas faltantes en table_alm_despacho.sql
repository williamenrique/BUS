-- =====================================================================
-- SCRIPT 03: Columnas faltantes en table_alm_despacho
--   - operador_id, mecanico_id, despachador_id  (usadas por OrdenModel)
--   - id_institucion
--   - numero_orden
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------- operador_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='operador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `operador_id` INT(11) DEFAULT NULL AFTER `operador`',
  'SELECT "operador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- mecanico_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='mecanico_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `mecanico_id` INT(11) DEFAULT NULL AFTER `mecanico`',
  'SELECT "mecanico_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- despachador_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='despachador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `despachador_id` INT(11) DEFAULT NULL AFTER `despachador`',
  'SELECT "despachador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- id_institucion ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `fecha_aprobacion`',
  'SELECT "id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND INDEX_NAME='idx_despacho_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD INDEX `idx_despacho_institucion` (`id_institucion`)',
  'SELECT "idx_despacho_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- numero_orden ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='numero_orden');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `numero_orden` INT(11) DEFAULT NULL AFTER `id_despacho`',
  'SELECT "numero_orden ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Llenar numero_orden solo si está NULL (evita colisión con UNIQUE)
UPDATE `table_alm_despacho` SET `numero_orden` = `id_despacho` WHERE `numero_orden` IS NULL;

UPDATE `table_alm_despacho` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

-- ---------- UNIQUE (id_institucion, numero_orden) ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND INDEX_NAME='uk_institucion_numero');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD UNIQUE KEY `uk_institucion_numero` (`id_institucion`,`numero_orden`)',
  'SELECT "uk_institucion_numero ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- FK opcional a table_personal ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_operador');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_operador`
     FOREIGN KEY (`operador_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_operador ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_mecanico');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_mecanico`
     FOREIGN KEY (`mecanico_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_mecanico ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_despachador');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_despachador`
     FOREIGN KEY (`despachador_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_despachador ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'SCRIPT 03 OK' AS resultado;