-- =====================================================================
-- MIGRACIÓN COMPLETA A MULTI-INSTITUCIÓN
-- Fecha: 2026-09-30
-- Base de datos: busyaracuydata
-- =====================================================================
-- Este script ejecuta TODAS las fases en orden:
--   1. Instituciones + columnas de flota
--   2. Columnas de almacén (productos, despachos, compras)
--   3. numero_orden correlativo por institución
--   4. Tabla de bienes del taller
--   5. Rediseño del sistema de menús
-- =====================================================================
-- IMPORTANTE:
--   - HAZ BACKUP DE LA BD ANTES DE EJECUTAR.
--   - Ejecutar SOLO una vez, después de reemplazar los archivos PHP.
--   - Cada bloque es idempotente donde aplica, pero los ALTER TABLE
--     fallarán si ya se ejecutaron antes. Si necesitas re-ejecutar,
--     hazlo bloque por bloque.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET TIME_ZONE = '+00:00';

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
(1, 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY', 1),
(2, 'TALLER', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);


-- =====================================================================
-- FASE 2: COLUMNAS id_institucion EN TABLAS DE FLOTA
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

ALTER TABLE `table_flota` 
  ADD CONSTRAINT `fk_flota_institucion` 
  FOREIGN KEY (`id_institucion`) REFERENCES `table_instituciones`(`id_institucion`);

-- Migrar los datos existentes a institución 1
UPDATE `table_flota` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;
UPDATE `table_flota_kilometraje` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;
UPDATE `table_flota_aceite_historial` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;
UPDATE `table_flota_mantenimiento` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;
UPDATE `table_flota_status` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;


-- =====================================================================
-- FASE 3: COLUMNAS id_institucion EN TABLAS DE ALMACÉN
-- =====================================================================
ALTER TABLE `table_alm_producto` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_producto`,
    ADD INDEX `idx_producto_institucion` (`id_institucion`);

UPDATE `table_alm_producto` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

ALTER TABLE `table_alm_relacion_producto` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_producto`,
    ADD INDEX `idx_relprod_institucion` (`id_institucion`);

UPDATE `table_alm_relacion_producto` rp
    INNER JOIN `table_alm_producto` p ON rp.id_producto = p.id_producto
SET rp.id_institucion = p.id_institucion
WHERE rp.id_institucion IS NULL OR rp.id_institucion = 0;

ALTER TABLE `table_alm_despacho` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `fecha_aprobacion`,
    ADD INDEX `idx_despacho_institucion` (`id_institucion`);

UPDATE `table_alm_despacho` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

ALTER TABLE `table_alm_relacion_despacho` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_despacho`,
    ADD INDEX `idx_reldesp_institucion` (`id_institucion`);

UPDATE `table_alm_relacion_despacho` rd
    INNER JOIN `table_alm_despacho` d ON rd.id_despacho = d.id_despacho
SET rd.id_institucion = d.id_institucion
WHERE rd.id_institucion IS NULL OR rd.id_institucion = 0;

ALTER TABLE `table_compras_pendientes` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_costeo`,
    ADD INDEX `idx_pendientes_institucion` (`id_institucion`);

UPDATE `table_compras_pendientes` cp
    INNER JOIN `table_alm_despacho` d ON cp.id_despacho = d.id_despacho
SET cp.id_institucion = d.id_institucion
WHERE cp.id_institucion IS NULL OR cp.id_institucion = 0;

ALTER TABLE `table_compras_costos` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `id_usuario_costeo`,
    ADD INDEX `idx_costos_institucion` (`id_institucion`);

UPDATE `table_compras_costos` cc
    INNER JOIN `table_compras_pendientes` cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
SET cc.id_institucion = cp.id_institucion
WHERE cc.id_institucion IS NULL OR cc.id_institucion = 0;


-- =====================================================================
-- FASE 4: numero_orden CORRELATIVO POR INSTITUCIÓN
-- =====================================================================
ALTER TABLE `table_alm_despacho` 
    ADD COLUMN `numero_orden` INT(11) DEFAULT NULL AFTER `id_despacho`;

UPDATE `table_alm_despacho` SET `numero_orden` = `id_despacho` WHERE `numero_orden` IS NULL;

ALTER TABLE `table_alm_despacho` 
    ADD UNIQUE KEY `uk_institucion_numero` (`id_institucion`, `numero_orden`);


-- =====================================================================
-- FASE 5: TABLA DE BIENES DEL TALLER
-- =====================================================================
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
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;


-- =====================================================================
-- FASE 6: REDISEÑO DEL SISTEMA DE MENÚS
-- =====================================================================
-- 6.1 Backup de tablas viejas
RENAME TABLE `table_men_menu`              TO `table_men_menu_backup_2026`;
RENAME TABLE `table_men_submenu`           TO `table_men_submenu_backup_2026`;
RENAME TABLE `table_men_usuario_menu`      TO `table_men_usuario_menu_backup_2026`;
RENAME TABLE `table_men_usuario_submenu`   TO `table_men_usuario_submenu_backup_2026`;
RENAME TABLE `table_men_departamento_menu` TO `table_men_departamento_menu_backup_2026`;

-- 6.2 Crear nuevas tablas
CREATE TABLE `table_men_menu` (
  `menu_id` INT(11) NOT NULL AUTO_INCREMENT,
  `menu_padre_id` INT(11) DEFAULT NULL COMMENT 'NULL si es raíz; FK a menu_id',
  `menu_nombre` VARCHAR(100) NOT NULL,
  `menu_icono` VARCHAR(50) DEFAULT NULL COMMENT 'Clase FontAwesome',
  `menu_ruta` VARCHAR(255) DEFAULT NULL COMMENT 'URL relativa (ej: flota/taller)',
  `menu_orden` INT(11) DEFAULT 0,
  `menu_estado` TINYINT(1) DEFAULT 1,
  `menu_scope` VARCHAR(50) DEFAULT 'general' COMMENT 'Contexto: general, sslMty, taller',
  `menu_es_desplegable` TINYINT(1) DEFAULT 0 COMMENT 'Autocalculado',
  PRIMARY KEY (`menu_id`),
  KEY `idx_padre` (`menu_padre_id`),
  KEY `idx_scope` (`menu_scope`),
  KEY `idx_estado` (`menu_estado`),
  CONSTRAINT `fk_menu_padre` FOREIGN KEY (`menu_padre_id`) 
    REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `table_men_rutas` (
  `ruta_id` INT(11) NOT NULL AUTO_INCREMENT,
  `menu_id` INT(11) NOT NULL,
  `patron` VARCHAR(255) NOT NULL COMMENT 'Ej: flota, flota/taller, flota/taller/*',
  `activa` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`ruta_id`),
  KEY `idx_menu` (`menu_id`),
  CONSTRAINT `fk_ruta_menu` FOREIGN KEY (`menu_id`) 
    REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `table_men_usuario_menu` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` INT(11) NOT NULL,
  `menu_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_menu` (`usuario_id`, `menu_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_menu` (`menu_id`),
  CONSTRAINT `fk_um_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `table_usuarios`(`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_um_menu` FOREIGN KEY (`menu_id`) 
    REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `table_men_departamento_menu` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `departamento_id` INT(11) NOT NULL,
  `menu_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dep_menu` (`departamento_id`, `menu_id`),
  CONSTRAINT `fk_dm_departamento` FOREIGN KEY (`departamento_id`) 
    REFERENCES `table_departamentos`(`departamento_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dm_menu` FOREIGN KEY (`menu_id`) 
    REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 6.3 Migrar datos
INSERT INTO `table_men_menu` 
    (`menu_id`, `menu_padre_id`, `menu_nombre`, `menu_icono`, `menu_ruta`, `menu_orden`, `menu_estado`, `menu_scope`, `menu_es_desplegable`)
SELECT 
    `menu_id`, NULL, `menu_nombre`, `menu_icono`, `menu_link`, `menu_orden`, `menu_estado`, 'general', `menu_es_desplegable`
FROM `table_men_menu_backup_2026`;

INSERT INTO `table_men_menu` 
    (`menu_id`, `menu_padre_id`, `menu_nombre`, `menu_icono`, `menu_ruta`, `menu_orden`, `menu_estado`, `menu_scope`, `menu_es_desplegable`)
SELECT 
    `submenu_id` + 1000, `menu_id`, `submenu_nombre`, 'far fa-circle', `submenu_link`, `submenu_orden`, `submenu_estado`, 'general', 0
FROM `table_men_submenu_backup_2026`;

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT `submenu_id` + 1000, `submenu_link`, 1
FROM `table_men_submenu_backup_2026`
WHERE `submenu_link` IS NOT NULL AND `submenu_link` != '';

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT `menu_id`, `menu_link`, 1
FROM `table_men_menu_backup_2026`
WHERE `menu_es_desplegable` = 0 AND `menu_link` IS NOT NULL AND `menu_link` != '';

INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `menu_id`
FROM `table_men_usuario_menu_backup_2026`
WHERE `menu_id` IS NOT NULL;

INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `submenu_id` + 1000
FROM `table_men_usuario_submenu_backup_2026`
WHERE `submenu_id` IS NOT NULL;

INSERT INTO `table_men_departamento_menu` (`departamento_id`, `menu_id`)
SELECT `departamento_id`, `menu_id`
FROM `table_men_departamento_menu_backup_2026`;

-- 6.4 Recalcular menu_es_desplegable
UPDATE `table_men_menu` m
SET m.`menu_es_desplegable` = 1
WHERE EXISTS (SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`);

UPDATE `table_men_menu` m
SET m.`menu_es_desplegable` = 0
WHERE NOT EXISTS (SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`);

-- 6.5 Corregir rutas de submenús clave
UPDATE `table_men_rutas` 
SET `patron` = 'flota/taller' 
WHERE `menu_id` = 1019 AND `patron` = 'taller';

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1019, 'flota/taller', 1
WHERE NOT EXISTS (SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1019 AND `patron` = 'flota/taller');

UPDATE `table_men_rutas` 
SET `patron` = 'flota/talleraceite' 
WHERE `menu_id` = 1020 AND `patron` = 'talleraceite';

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1020, 'flota/talleraceite', 1
WHERE NOT EXISTS (SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1020 AND `patron` = 'flota/talleraceite');

-- 6.6 Eliminar comodines conflictivos
DELETE FROM `table_men_rutas` 
WHERE `patron` = 'flota/*' AND `menu_id` IN (1003, 1014);

-- 6.7 Agregar comodines correctos
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1019, 'flota/taller/*', 1
WHERE NOT EXISTS (SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1019 AND `patron` = 'flota/taller/*');

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1020, 'flota/talleraceite/*', 1
WHERE NOT EXISTS (SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1020 AND `patron` = 'flota/talleraceite/*');

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1011, 'flota/statusaceite/*', 1
WHERE NOT EXISTS (SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1011 AND `patron` = 'flota/statusaceite/*');


SET FOREIGN_KEY_CHECKS = 1;
SET SQL_MODE = '';
SET TIME_ZONE = 'SYSTEM';

-- =====================================================================
-- VERIFICACIÓN FINAL
-- =====================================================================
-- Ejecuta estos SELECT para confirmar que todo quedó bien:

-- 1. Instituciones
SELECT * FROM table_instituciones;

-- 2. Distribución por institución en las tablas principales
SELECT 'Flota' as tabla, id_institucion, COUNT(*) as total FROM table_flota GROUP BY id_institucion
UNION ALL
SELECT 'Productos', id_institucion, COUNT(*) FROM table_alm_producto GROUP BY id_institucion
UNION ALL
SELECT 'Despachos', id_institucion, COUNT(*) FROM table_alm_despacho GROUP BY id_institucion
UNION ALL
SELECT 'Compras Pendientes', id_institucion, COUNT(*) FROM table_compras_pendientes GROUP BY id_institucion
UNION ALL
SELECT 'Compras Costos', id_institucion, COUNT(*) FROM table_compras_costos GROUP BY id_institucion;

-- 3. Verificar numero_orden
SELECT id_despacho, numero_orden, id_institucion, fecha_despacho 
FROM table_alm_despacho 
ORDER BY id_despacho DESC LIMIT 10;

-- 4. Menús y rutas
SELECT 'Menús' as tabla, COUNT(*) as total FROM table_men_menu
UNION ALL
SELECT 'Rutas', COUNT(*) FROM table_men_rutas
UNION ALL
SELECT 'Permisos usuarios', COUNT(*) FROM table_men_usuario_menu;

-- 5. Tabla de bienes del taller
SELECT COUNT(*) as total FROM table_bienes_taller_inventario;

-- =====================================================================
-- FIN DEL SCRIPT
-- =====================================================================
-- ROLLBACK (solo si algo falla y necesitas restaurar los menús):
-- =====================================================================
/*
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `table_men_menu`;
DROP TABLE IF EXISTS `table_men_rutas`;
DROP TABLE IF EXISTS `table_men_usuario_menu`;
DROP TABLE IF EXISTS `table_men_departamento_menu`;

RENAME TABLE `table_men_menu_backup_2026`              TO `table_men_menu`;
RENAME TABLE `table_men_submenu_backup_2026`           TO `table_men_submenu`;
RENAME TABLE `table_men_usuario_menu_backup_2026`      TO `table_men_usuario_menu`;
RENAME TABLE `table_men_usuario_submenu_backup_2026`   TO `table_men_usuario_submenu`;
RENAME TABLE `table_men_departamento_menu_backup_2026` TO `table_men_departamento_menu`;
SET FOREIGN_KEY_CHECKS = 1;
*/
-- =====================================================================