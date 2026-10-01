-- =====================================================================
-- SCRIPT 01: INFRAESTRUCTURA MULTI-INSTITUCIÓN + BACKFILL DE IDs
-- =====================================================================
-- Secciones:
--   A) table_instituciones
--   B) id_institucion en tablas de FLOTA
--   C) Columnas críticas en table_alm_despacho
--   D) id_institucion en ALMACÉN / COMPRAS
--   E) Propagación de datos existentes a institución 1
--   F) BACKFILL de operador_id / mecanico_id / despachador_id
--   G) Verificación final
--
-- Idempotente. No borra datos.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =====================================================================
-- A) TABLA DE INSTITUCIONES
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
-- B) id_institucion EN TABLAS DE FLOTA
-- =====================================================================

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
  'ALTER TABLE `table_flota` ADD CONSTRAINT `fk_flota_institucion` FOREIGN KEY (`id_institucion`) REFERENCES `table_instituciones`(`id_institucion`)',
  'SELECT "fk_flota_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

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
-- C) COLUMNAS CRÍTICAS DE table_alm_despacho
-- =====================================================================

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='operador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `operador_id` INT(11) DEFAULT NULL AFTER `operador`',
  'SELECT "operador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='mecanico_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `mecanico_id` INT(11) DEFAULT NULL AFTER `mecanico`',
  'SELECT "mecanico_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='despachador_id');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `despachador_id` INT(11) DEFAULT NULL AFTER `despachador`',
  'SELECT "despachador_id ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

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

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND COLUMN_NAME='numero_orden');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD COLUMN `numero_orden` INT(11) DEFAULT NULL AFTER `id_despacho`',
  'SELECT "numero_orden ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

UPDATE `table_alm_despacho` SET `numero_orden` = `id_despacho` WHERE `numero_orden` IS NULL;
UPDATE `table_alm_despacho` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND INDEX_NAME='uk_institucion_numero');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD UNIQUE KEY `uk_institucion_numero` (`id_institucion`,`numero_orden`)',
  'SELECT "uk_institucion_numero ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_operador');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_operador` FOREIGN KEY (`operador_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_operador ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_mecanico');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_mecanico` FOREIGN KEY (`mecanico_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_mecanico ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
            AND CONSTRAINT_NAME='fk_despacho_despachador');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_despacho` ADD CONSTRAINT `fk_despacho_despachador` FOREIGN KEY (`despachador_id`) REFERENCES `table_personal`(`id_personal`) ON DELETE SET NULL',
  'SELECT "fk_despacho_despachador ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
-- D) id_institucion EN ALMACÉN / COMPRAS
-- =====================================================================

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
-- E) PROPAGACIÓN DE id_institucion
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
-- F) BACKFILL DE operador_id / mecanico_id / despachador_id
-- =====================================================================

-- F.0 REPORTE PREVIO
SELECT 'ANTES: operador_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND operador_id IS NULL
  AND operador IS NOT NULL AND TRIM(operador) <> '';

SELECT 'ANTES: mecanico_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND mecanico_id IS NULL
  AND mecanico IS NOT NULL AND TRIM(mecanico) <> '';

SELECT 'ANTES: despachador_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND despachador_id IS NULL
  AND despachador IS NOT NULL AND TRIM(despachador) <> '';

-- F.1 Tabla temporal de personas con variantes normalizadas
DROP TEMPORARY TABLE IF EXISTS tmp_personal_norm;
CREATE TEMPORARY TABLE tmp_personal_norm (
  id_personal      INT NOT NULL,
  personal_cargo   INT,
  personal_tag     INT,
  personal_cedula  VARCHAR(15)  CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  v1_nombre_ap     VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  v2_ap_nombre     VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  v3_nombre_ap_0   VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  v4_ap_nombre_0   VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  v5_solo_nombre   VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  INDEX idx_v1 (v1_nombre_ap),
  INDEX idx_v2 (v2_ap_nombre),
  INDEX idx_v3 (v3_nombre_ap_0),
  INDEX idx_v4 (v4_ap_nombre_0),
  INDEX idx_v5 (v5_solo_nombre),
  INDEX idx_ced (personal_cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO tmp_personal_norm
SELECT
  p.id_personal,
  p.personal_cargo,
  p.personal_tag,
  UPPER(TRIM(COALESCE(p.personal_cedula,''))) COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    CONCAT(COALESCE(p.personal_nombre,''), ' ',
           COALESCE(NULLIF(p.personal_apellido,'0'),'')),
    'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),',','')))
  COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    CONCAT(COALESCE(NULLIF(p.personal_apellido,'0'),''), ' ',
           COALESCE(p.personal_nombre,'')),
    'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),',','')))
  COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    CONCAT(COALESCE(p.personal_nombre,''), ' ',
           COALESCE(p.personal_apellido,''), ' 0'),
    'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),',','')))
  COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    CONCAT(COALESCE(p.personal_apellido,''), ' ',
           COALESCE(p.personal_nombre,''), ' 0'),
    'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),',','')))
  COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    COALESCE(p.personal_nombre,''),
    'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),',','')))
  COLLATE utf8mb4_spanish_ci
FROM table_personal p
WHERE p.personal_status IS NULL OR p.personal_status <> 0;

UPDATE tmp_personal_norm SET
  v1_nombre_ap   = TRIM(REPLACE(REPLACE(REPLACE(v1_nombre_ap,   '  ',' '),'  ',' '),'  ',' ')),
  v2_ap_nombre   = TRIM(REPLACE(REPLACE(REPLACE(v2_ap_nombre,   '  ',' '),'  ',' '),'  ',' ')),
  v3_nombre_ap_0 = TRIM(REPLACE(REPLACE(REPLACE(v3_nombre_ap_0, '  ',' '),'  ',' '),'  ',' ')),
  v4_ap_nombre_0 = TRIM(REPLACE(REPLACE(REPLACE(v4_ap_nombre_0, '  ',' '),'  ',' '),'  ',' ')),
  v5_solo_nombre = TRIM(REPLACE(REPLACE(REPLACE(v5_solo_nombre, '  ',' '),'  ',' '),'  ',' '));

-- F.2 Tabla temporal con textos normalizados de despacho
DROP TEMPORARY TABLE IF EXISTS tmp_despacho_norm;
CREATE TEMPORARY TABLE tmp_despacho_norm (
  id_despacho  INT NOT NULL,
  op_norm      VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  mec_norm     VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  desp_norm    VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci,
  PRIMARY KEY (id_despacho),
  INDEX idx_op   (op_norm),
  INDEX idx_mec  (mec_norm),
  INDEX idx_desp (desp_norm)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO tmp_despacho_norm
SELECT
  id_despacho,
  UPPER(TRIM(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      COALESCE(operador,''),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),
    '(^|[[:space:]])0([[:space:]]|$)', ' '))) COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      COALESCE(mecanico,''),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),
    '(^|[[:space:]])0([[:space:]]|$)', ' '))) COLLATE utf8mb4_spanish_ci,
  UPPER(TRIM(REGEXP_REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
      COALESCE(despachador,''),
      'Á','A'),'É','E'),'Í','I'),'Ó','O'),'Ú','U'),'Ñ','N'),'.',''),
    '(^|[[:space:]])0([[:space:]]|$)', ' '))) COLLATE utf8mb4_spanish_ci
FROM table_alm_despacho
WHERE status_despacho = 1;

UPDATE tmp_despacho_norm SET
  op_norm   = TRIM(REPLACE(REPLACE(REPLACE(op_norm,   '  ',' '),'  ',' '),'  ',' ')),
  mec_norm  = TRIM(REPLACE(REPLACE(REPLACE(mec_norm,  '  ',' '),'  ',' '),'  ',' ')),
  desp_norm = TRIM(REPLACE(REPLACE(REPLACE(desp_norm, '  ',' '),'  ',' '),'  ',' '));

-- ---------------------------------------------------------------------
-- F.3 MATCH OPERADOR (cargo 23)
-- ---------------------------------------------------------------------
UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm <> '' AND dn.op_norm NOT LIKE 'SIN OPERADOR%'
  AND pn.personal_cargo = 23;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v2_ap_nombre = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm <> '' AND dn.op_norm NOT LIKE 'SIN OPERADOR%'
  AND pn.personal_cargo = 23;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v3_nombre_ap_0 = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm <> '' AND dn.op_norm NOT LIKE 'SIN OPERADOR%'
  AND pn.personal_cargo = 23;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v4_ap_nombre_0 = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm <> '' AND dn.op_norm NOT LIKE 'SIN OPERADOR%'
  AND pn.personal_cargo = 23;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn 
  ON dn.id_despacho = d.id_despacho
INNER JOIN (
  SELECT v5_solo_nombre, MIN(id_personal) AS id_personal
  FROM tmp_personal_norm
  WHERE personal_cargo = 23
  GROUP BY v5_solo_nombre
  HAVING COUNT(*) = 1
) pn ON pn.v5_solo_nombre = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm <> '' AND dn.op_norm NOT LIKE 'SIN OPERADOR%';

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.op_norm
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL
  AND dn.op_norm LIKE 'SIN OPERADOR%'
  AND pn.personal_cargo = 23;

-- ---------------------------------------------------------------------
-- F.4 MATCH MECANICO (cargos 26-29, 32, 43)
-- ---------------------------------------------------------------------
UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm <> '' AND dn.mec_norm NOT LIKE 'SIN MECANICO%'
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v2_ap_nombre = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm <> '' AND dn.mec_norm NOT LIKE 'SIN MECANICO%'
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v3_nombre_ap_0 = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm <> '' AND dn.mec_norm NOT LIKE 'SIN MECANICO%'
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v4_ap_nombre_0 = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm <> '' AND dn.mec_norm NOT LIKE 'SIN MECANICO%'
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn 
  ON dn.id_despacho = d.id_despacho
INNER JOIN (
  SELECT v5_solo_nombre, MIN(id_personal) AS id_personal
  FROM tmp_personal_norm
  WHERE personal_cargo BETWEEN 26 AND 29 OR personal_cargo IN (32,43)
  GROUP BY v5_solo_nombre
  HAVING COUNT(*) = 1
) pn ON pn.v5_solo_nombre = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm <> '' AND dn.mec_norm NOT LIKE 'SIN MECANICO%';

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.mec_norm
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND dn.mec_norm LIKE 'SIN MECANICO%'
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

-- ---------------------------------------------------------------------
-- F.5 MATCH DESPACHADOR (personal_tag = 2)
-- ---------------------------------------------------------------------
UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm <> '' AND dn.desp_norm NOT LIKE 'SIN DESPACHADOR%'
  AND pn.personal_tag = 2;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v2_ap_nombre = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm <> '' AND dn.desp_norm NOT LIKE 'SIN DESPACHADOR%'
  AND pn.personal_tag = 2;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v3_nombre_ap_0 = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm <> '' AND dn.desp_norm NOT LIKE 'SIN DESPACHADOR%'
  AND pn.personal_tag = 2;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v4_ap_nombre_0 = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm <> '' AND dn.desp_norm NOT LIKE 'SIN DESPACHADOR%'
  AND pn.personal_tag = 2;

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn 
  ON dn.id_despacho = d.id_despacho
INNER JOIN (
  SELECT v5_solo_nombre, MIN(id_personal) AS id_personal
  FROM tmp_personal_norm
  WHERE personal_tag = 2
  GROUP BY v5_solo_nombre
  HAVING COUNT(*) = 1
) pn ON pn.v5_solo_nombre = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm <> '' AND dn.desp_norm NOT LIKE 'SIN DESPACHADOR%';

UPDATE table_alm_despacho d
INNER JOIN tmp_despacho_norm dn ON dn.id_despacho = d.id_despacho
INNER JOIN tmp_personal_norm pn ON pn.v1_nombre_ap = dn.desp_norm
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL
  AND dn.desp_norm LIKE 'SIN DESPACHADOR%'
  AND pn.personal_tag = 2;

-- ---------------------------------------------------------------------
-- F.6 MATCH POR CÉDULA EMBEBIDA
-- ---------------------------------------------------------------------
UPDATE table_alm_despacho d
INNER JOIN tmp_personal_norm pn
  ON pn.personal_cedula <> ''
 AND UPPER(d.operador) COLLATE utf8mb4_spanish_ci
     LIKE CONCAT('%', pn.personal_cedula, '%') COLLATE utf8mb4_spanish_ci
SET d.operador_id = pn.id_personal
WHERE d.operador_id IS NULL AND pn.personal_cargo = 23;

UPDATE table_alm_despacho d
INNER JOIN tmp_personal_norm pn
  ON pn.personal_cedula <> ''
 AND UPPER(d.mecanico) COLLATE utf8mb4_spanish_ci
     LIKE CONCAT('%', pn.personal_cedula, '%') COLLATE utf8mb4_spanish_ci
SET d.mecanico_id = pn.id_personal
WHERE d.mecanico_id IS NULL
  AND (pn.personal_cargo BETWEEN 26 AND 29 OR pn.personal_cargo IN (32,43));

UPDATE table_alm_despacho d
INNER JOIN tmp_personal_norm pn
  ON pn.personal_cedula <> ''
 AND UPPER(d.despachador) COLLATE utf8mb4_spanish_ci
     LIKE CONCAT('%', pn.personal_cedula, '%') COLLATE utf8mb4_spanish_ci
SET d.despachador_id = pn.id_personal
WHERE d.despachador_id IS NULL AND pn.personal_tag = 2;

DROP TEMPORARY TABLE IF EXISTS tmp_personal_norm;
DROP TEMPORARY TABLE IF EXISTS tmp_despacho_norm;

-- =====================================================================
-- G) VERIFICACIÓN FINAL
-- =====================================================================

SELECT 'SCRIPT 01 OK' AS resultado;

SELECT 'INSTITUCIONES' AS bloque, COUNT(*) AS total FROM table_instituciones;

SELECT 'Flota' AS tabla, id_institucion, COUNT(*) AS total FROM table_flota GROUP BY id_institucion
UNION ALL SELECT 'Productos', id_institucion, COUNT(*) FROM table_alm_producto GROUP BY id_institucion
UNION ALL SELECT 'Despachos', id_institucion, COUNT(*) FROM table_alm_despacho GROUP BY id_institucion
UNION ALL SELECT 'Compras Pendientes', id_institucion, COUNT(*) FROM table_compras_pendientes GROUP BY id_institucion
UNION ALL SELECT 'Compras Costos', id_institucion, COUNT(*) FROM table_compras_costos GROUP BY id_institucion;

SELECT 'DESPUES: operador_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND operador_id IS NULL
  AND operador IS NOT NULL AND TRIM(operador) <> '';

SELECT 'DESPUES: mecanico_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND mecanico_id IS NULL
  AND mecanico IS NOT NULL AND TRIM(mecanico) <> '';

SELECT 'DESPUES: despachador_id pendientes' AS chequeo, COUNT(*) AS total
FROM table_alm_despacho
WHERE status_despacho=1 AND despachador_id IS NULL
  AND despachador IS NOT NULL AND TRIM(despachador) <> '';

SELECT id_despacho, numero_orden, id_institucion,
       operador, operador_id,
       mecanico, mecanico_id,
       despachador, despachador_id
FROM table_alm_despacho
ORDER BY id_despacho DESC
LIMIT 20;

SELECT 'PENDIENTES OPERADOR' AS bloque, operador AS texto, COUNT(*) AS repeticiones
FROM table_alm_despacho
WHERE status_despacho=1 AND operador_id IS NULL
  AND operador IS NOT NULL AND TRIM(operador) <> ''
GROUP BY operador ORDER BY repeticiones DESC LIMIT 20;

SELECT 'PENDIENTES MECANICO' AS bloque, mecanico AS texto, COUNT(*) AS repeticiones
FROM table_alm_despacho
WHERE status_despacho=1 AND mecanico_id IS NULL
  AND mecanico IS NOT NULL AND TRIM(mecanico) <> ''
GROUP BY mecanico ORDER BY repeticiones DESC LIMIT 20;

SELECT 'PENDIENTES DESPACHADOR' AS bloque, despachador AS texto, COUNT(*) AS repeticiones
FROM table_alm_despacho
WHERE status_despacho=1 AND despachador_id IS NULL
  AND despachador IS NOT NULL AND TRIM(despachador) <> ''
GROUP BY despachador ORDER BY repeticiones DESC LIMIT 20;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'SCRIPT 01 FINALIZADO' AS resultado;