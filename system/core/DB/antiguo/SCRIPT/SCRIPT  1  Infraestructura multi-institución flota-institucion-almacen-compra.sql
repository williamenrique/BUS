-- =====================================================================
-- SCRIPT 1: INFRAESTRUCTURA MULTI-INSTITUCIÓN
-- =====================================================================
-- Incluye:
--   - Creación de table_instituciones
--   - id_institucion en tablas de FLOTA
--   - Columnas críticas en table_alm_despacho:
--       operador_id, mecanico_id, despachador_id,
--       id_institucion, numero_orden + UNIQUE + FKs
--   - id_institucion en ALMACÉN/COMPRAS
--   - Índices de rendimiento
--   - Propagación de datos existentes a institución 1
--
-- Idempotente. Puede ejecutarse varias veces sin fallar.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =====================================================================
-- 1. TABLA DE INSTITUCIONES
-- =====================================================================
CREATE TABLE IF NOT EXISTS `table_instituciones` (
  `id_institucion` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `rif` VARCHAR(20) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_institucion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `table_instituciones` (`id_institucion`,`nombre`,`status`) VALUES
(1,'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY',1),
(2,'TALLER',1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- =====================================================================
-- 2. HELPER LOGIC (se repite el patrón IF EXISTS para cada columna)
-- =====================================================================

-- ---------- 2.1 table_flota.id_institucion ----------
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

-- ---------- 2.2 Otras tablas de flota ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_kilometraje'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_kilometraje` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_kilometraje.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_aceite_historial'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_aceite_historial` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_aceite_historial.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_mantenimiento'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_mantenimiento` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_mantenimiento.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_flota_status'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_flota_status` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1',
  'SELECT "table_flota_status.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
-- 3. COLUMNAS CRÍTICAS DE table_alm_despacho
-- =====================================================================

-- ---------- 3.1 operador_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='operador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `operador_id` INT(11) DEFAULT NULL AFTER `operador`',
  'SELECT "operador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 3.2 mecanico_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='mecanico_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `mecanico_id` INT(11) DEFAULT NULL AFTER `mecanico`',
  'SELECT "mecanico_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 3.3 despachador_id ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='despachador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `despachador_id` INT(11) DEFAULT NULL AFTER `despachador`',
  'SELECT "despachador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 3.4 id_institucion ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `fecha_aprobacion`',
  'SELECT "table_alm_despacho.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND INDEX_NAME='idx_despacho_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD INDEX `idx_despacho_institucion` (`id_institucion`)',
  'SELECT "idx_despacho_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 3.5 numero_orden ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='numero_orden');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `numero_orden` INT(11) DEFAULT NULL AFTER `id_despacho`',
  'SELECT "numero_orden ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- Llenar numero_orden con id_despacho (solo si NULL)
UPDATE `table_alm_despacho` SET `numero_orden` = `id_despacho` WHERE `numero_orden` IS NULL;
UPDATE `table_alm_despacho` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

-- ---------- 3.6 UNIQUE (id_institucion, numero_orden) ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND INDEX_NAME='uk_institucion_numero');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD UNIQUE KEY `uk_institucion_numero` (`id_institucion`,`numero_orden`)',
  'SELECT "uk_institucion_numero ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 3.7 FKs a table_personal ----------
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

-- =====================================================================
-- 4. id_institucion EN ALMACÉN / COMPRAS
-- =====================================================================

-- ---------- 4.1 table_alm_producto ----------
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

-- ---------- 4.2 table_alm_relacion_producto ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_producto'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_producto` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_producto`',
  'SELECT "table_alm_relacion_producto.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_producto'
            AND INDEX_NAME='idx_relprod_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_producto` ADD INDEX `idx_relprod_institucion` (`id_institucion`)',
  'SELECT "idx_relprod_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 4.3 table_alm_relacion_despacho ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_despacho'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_despacho` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_despacho`',
  'SELECT "table_alm_relacion_despacho.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_despacho'
            AND INDEX_NAME='idx_reldesp_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_despacho` ADD INDEX `idx_reldesp_institucion` (`id_institucion`)',
  'SELECT "idx_reldesp_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 4.4 table_compras_pendientes ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_pendientes'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_pendientes` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_costeo`',
  'SELECT "table_compras_pendientes.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_pendientes'
            AND INDEX_NAME='idx_pendientes_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_pendientes` ADD INDEX `idx_pendientes_institucion` (`id_institucion`)',
  'SELECT "idx_pendientes_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------- 4.5 table_compras_costos ----------
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_costos'
            AND COLUMN_NAME='id_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_costos` ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `id_usuario_costeo`',
  'SELECT "table_compras_costos.id_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_costos'
            AND INDEX_NAME='idx_costos_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_costos` ADD INDEX `idx_costos_institucion` (`id_institucion`)',
  'SELECT "idx_costos_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
-- 5. PROPAGACIÓN DE DATOS (todos los registros existentes → institución 1)
-- =====================================================================
UPDATE `table_flota`                  SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_kilometraje`      SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_aceite_historial` SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_mantenimiento`    SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;
UPDATE `table_flota_status`           SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;

UPDATE `table_alm_producto` SET `id_institucion`=1 WHERE `id_institucion` IS NULL OR `id_institucion`=0;

UPDATE `table_alm_relacion_producto` rp
  INNER JOIN `table_alm_producto` p ON rp.id_producto = p.id_producto
SET rp.`id_institucion` = p.`id_institucion`
WHERE rp.`id_institucion` IS NULL OR rp.`id_institucion`=0;

UPDATE `table_alm_relacion_despacho` rd
  INNER JOIN `table_alm_despacho` d ON rd.id_despacho = d.id_despacho
SET rd.`id_institucion` = d.`id_institucion`
WHERE rd.`id_institucion` IS NULL OR rd.`id_institucion`=0;

UPDATE `table_compras_pendientes` cp
  INNER JOIN `table_alm_despacho` d ON cp.id_despacho = d.id_despacho
SET cp.`id_institucion` = d.`id_institucion`
WHERE cp.`id_institucion` IS NULL OR cp.`id_institucion`=0;

UPDATE `table_compras_costos` cc
  INNER JOIN `table_compras_pendientes` cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
SET cc.`id_institucion` = cp.`id_institucion`
WHERE cc.`id_institucion` IS NULL OR cc.`id_institucion`=0;

-- =====================================================================
-- VERIFICACIÓN FINAL
-- =====================================================================
SELECT 'SCRIPT 1 OK' AS resultado;
SELECT 'INSTITUCIONES' AS bloque, COUNT(*) AS total FROM table_instituciones;
SELECT 'Flota' AS tabla, id_institucion, COUNT(*) AS total FROM table_flota GROUP BY id_institucion
UNION ALL SELECT 'Productos', id_institucion, COUNT(*) FROM table_alm_producto GROUP BY id_institucion
UNION ALL SELECT 'Despachos', id_institucion, COUNT(*) FROM table_alm_despacho GROUP BY id_institucion
UNION ALL SELECT 'Compras Pendientes', id_institucion, COUNT(*) FROM table_compras_pendientes GROUP BY id_institucion
UNION ALL SELECT 'Compras Costos', id_institucion, COUNT(*) FROM table_compras_costos GROUP BY id_institucion;

SET FOREIGN_KEY_CHECKS = 1;