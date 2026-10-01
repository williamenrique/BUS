-- =====================================================================
-- SCRIPT 04: id_institucion en tablas de ALMACÉN
-- Afecta: table_alm_producto, table_alm_relacion_producto,
--         table_alm_relacion_despacho,
--         table_compras_pendientes, table_compras_costos
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------- table_alm_producto ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_producto'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_producto` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_producto`',
  'SELECT "table_alm_producto.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_producto'
            AND INDEX_NAME='idx_producto_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_producto` ADD INDEX `idx_producto_institucion` (`id_institucion`)',
  'SELECT "idx_producto_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_alm_relacion_producto ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_producto'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_producto` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_producto`',
  'SELECT "table_alm_relacion_producto.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_alm_relacion_despacho ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_despacho'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_despacho` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_despacho`',
  'SELECT "table_alm_relacion_despacho.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_compras_pendientes ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_pendientes'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_pendientes` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_costeo`',
  'SELECT "table_compras_pendientes.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- table_compras_costos ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_costos'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_costos` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `id_usuario_costeo`',
  'SELECT "table_compras_costos.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- Propagación de institución ----------
-- Productos: todos a institución 1
UPDATE `table_alm_producto` SET `id_institucion` = 1
 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

-- Relación producto: hereda del producto
UPDATE `table_alm_relacion_producto` rp
  INNER JOIN `table_alm_producto` p ON rp.id_producto = p.id_producto
SET rp.`id_institucion` = p.`id_institucion`
WHERE rp.`id_institucion` IS NULL OR rp.`id_institucion` = 0;

-- Relación despacho: hereda del despacho
UPDATE `table_alm_relacion_despacho` rd
  INNER JOIN `table_alm_despacho` d ON rd.id_despacho = d.id_despacho
SET rd.`id_institucion` = d.`id_institucion`
WHERE rd.`id_institucion` IS NULL OR rd.`id_institucion` = 0;

-- Compras pendientes: hereda del despacho
UPDATE `table_compras_pendientes` cp
  INNER JOIN `table_alm_despacho` d ON cp.id_despacho = d.id_despacho
SET cp.`id_institucion` = d.`id_institucion`
WHERE cp.`id_institucion` IS NULL OR cp.`id_institucion` = 0;

-- Compras costos: hereda del pendiente
UPDATE `table_compras_costos` cc
  INNER JOIN `table_compras_pendientes` cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
SET cc.`id_institucion` = cp.`id_institucion`
WHERE cc.`id_institucion` IS NULL OR cc.`id_institucion` = 0;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'SCRIPT 04 OK' AS resultado;