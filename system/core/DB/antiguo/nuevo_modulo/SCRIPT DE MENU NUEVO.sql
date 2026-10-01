-- =====================================================================
-- REDISEÑO COMPLETO DEL SISTEMA DE MENÚS (v2)
-- Fecha: 2026-09-29
-- Base de datos: busyaracuydata
-- =====================================================================
-- Este script hace TODO lo necesario:
--   1. Backup de tablas viejas (renombrado, NO borra).
--   2. Creación de nuevas tablas.
--   3. Migración de datos.
--   4. Corrección de patrones (fix de flota/taller).
--   5. Eliminación de comodines conflictivos.
--   6. Recalculo de menu_es_desplegable.
-- =====================================================================
-- IMPORTANTE:
--   - HAZ BACKUP ANTES DE EJECUTAR.
--   - Si algo falla, el rollback está al final.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- PASO 1: BACKUP DE TABLAS VIEJAS (RENOMBRAR)
-- =====================================================================
RENAME TABLE `table_men_menu`              TO `table_men_menu_backup_2026`;
RENAME TABLE `table_men_submenu`           TO `table_men_submenu_backup_2026`;
RENAME TABLE `table_men_usuario_menu`      TO `table_men_usuario_menu_backup_2026`;
RENAME TABLE `table_men_usuario_submenu`   TO `table_men_usuario_submenu_backup_2026`;
RENAME TABLE `table_men_departamento_menu` TO `table_men_departamento_menu_backup_2026`;

-- =====================================================================
-- PASO 2: CREAR LAS NUEVAS TABLAS
-- =====================================================================

-- ---------------------------------------------------------------------
-- 2.1 Tabla principal de menús (árbol con auto-referencia)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 2.2 Rutas que activan cada menú (patrones con comodín *)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 2.3 Permisos por usuario (apunta a cualquier nivel del árbol)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- 2.4 Permisos por departamento (opcional, mismo esquema)
-- ---------------------------------------------------------------------
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

-- =====================================================================
-- PASO 3: MIGRAR DATOS
-- =====================================================================

-- ---- 3.1 Menús padre (raíces) ----
INSERT INTO `table_men_menu` 
    (`menu_id`, `menu_padre_id`, `menu_nombre`, `menu_icono`, `menu_ruta`, `menu_orden`, `menu_estado`, `menu_scope`, `menu_es_desplegable`)
SELECT 
    `menu_id`, 
    NULL, 
    `menu_nombre`, 
    `menu_icono`, 
    `menu_link`, 
    `menu_orden`, 
    `menu_estado`, 
    'general', 
    `menu_es_desplegable`
FROM `table_men_menu_backup_2026`;

-- ---- 3.2 Submenús como hijos (ID + 1000) ----
INSERT INTO `table_men_menu` 
    (`menu_id`, `menu_padre_id`, `menu_nombre`, `menu_icono`, `menu_ruta`, `menu_orden`, `menu_estado`, `menu_scope`, `menu_es_desplegable`)
SELECT 
    `submenu_id` + 1000, 
    `menu_id`, 
    `submenu_nombre`, 
    'far fa-circle', 
    `submenu_link`, 
    `submenu_orden`, 
    `submenu_estado`, 
    'general', 
    0
FROM `table_men_submenu_backup_2026`;

-- ---- 3.3 Rutas que activan cada submenú (patrón original) ----
-- OJO: Aquí usamos submenu_link (URL real), no submenu_pagina.
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 
    `submenu_id` + 1000, 
    `submenu_link`, 
    1
FROM `table_men_submenu_backup_2026`
WHERE `submenu_link` IS NOT NULL AND `submenu_link` != '';

-- ---- 3.4 Rutas de menús hoja (los que NO son desplegables) ----
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 
    `menu_id`, 
    `menu_link`, 
    1
FROM `table_men_menu_backup_2026`
WHERE `menu_es_desplegable` = 0 
  AND `menu_link` IS NOT NULL 
  AND `menu_link` != '';

-- ---- 3.5 Permisos de usuarios ----
-- Permisos de menú padre
INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `menu_id`
FROM `table_men_usuario_menu_backup_2026`
WHERE `menu_id` IS NOT NULL;

-- Permisos de submenú (ahora apuntan al árbol con ID + 1000)
INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `submenu_id` + 1000
FROM `table_men_usuario_submenu_backup_2026`
WHERE `submenu_id` IS NOT NULL;

-- ---- 3.6 Permisos por departamento ----
INSERT INTO `table_men_departamento_menu` (`departamento_id`, `menu_id`)
SELECT `departamento_id`, `menu_id`
FROM `table_men_departamento_menu_backup_2026`;

-- =====================================================================
-- PASO 4: RECALCULAR menu_es_desplegable
-- =====================================================================
UPDATE `table_men_menu` m
SET m.`menu_es_desplegable` = 1
WHERE EXISTS (
    SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`
);

UPDATE `table_men_menu` m
SET m.`menu_es_desplegable` = 0
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`
);

-- =====================================================================
-- PASO 5: CORREGIR RUTAS DE LOS SUBMENÚS CLAVE
-- =====================================================================
-- Los submenús del taller tenían patrones incorrectos en el backup
-- (usaban "flota_taller" y "aceite_taller" en vez de la URL real).
-- Corregimos las rutas que se generaron automáticamente desde 
-- submenu_link en el PASO 3.3.

-- 5.1 Corregir "Flota taller" (menú 1019)
-- El submenu_link original era "taller" que NO es la URL real.
UPDATE `table_men_rutas` 
SET `patron` = 'flota/taller' 
WHERE `menu_id` = 1019 
  AND `patron` = 'taller';

-- Si no existía la ruta (porque submenu_link estaba vacío),
-- la creamos directamente:
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1019, 'flota/taller', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1019 AND `patron` = 'flota/taller'
);

-- 5.2 Corregir "Taller aceite" (menú 1020)
UPDATE `table_men_rutas` 
SET `patron` = 'flota/talleraceite' 
WHERE `menu_id` = 1020 
  AND `patron` = 'talleraceite';

INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1020, 'flota/talleraceite', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1020 AND `patron` = 'flota/talleraceite'
);

-- =====================================================================
-- PASO 6: ELIMINAR COMODINES CONFLICTIVOS
-- =====================================================================
-- Los comodines flota/* en menús de SSLMTY capturaban también las rutas
-- del taller. Los eliminamos para que cada menú tenga su ruta exacta.

DELETE FROM `table_men_rutas` 
WHERE `patron` = 'flota/*' 
  AND `menu_id` IN (1003, 1014);

-- =====================================================================
-- PASO 7: AGREGAR COMODINES CORRECTOS
-- =====================================================================
-- Solo agregamos comodines a los menús que realmente necesitan
-- activarse en sub-rutas.

-- 7.1 Comodín para "Flota taller" (para activarse en sub-rutas como flota/tallerhistorial/123)
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1019, 'flota/taller/*', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1019 AND `patron` = 'flota/taller/*'
);

-- 7.2 Comodín para "Taller aceite"
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1020, 'flota/talleraceite/*', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1020 AND `patron` = 'flota/talleraceite/*'
);

-- 7.3 Comodín para "Cambio Aceite" SSLMTY (por si tiene sub-rutas)
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 1011, 'flota/statusaceite/*', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `table_men_rutas` WHERE `menu_id` = 1011 AND `patron` = 'flota/statusaceite/*'
);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- PASO 8: VERIFICACIÓN
-- =====================================================================

-- 8.1 Contar filas migradas
SELECT 'Menús' as tabla, COUNT(*) as total FROM table_men_menu
UNION ALL
SELECT 'Rutas', COUNT(*) FROM table_men_rutas
UNION ALL
SELECT 'Permisos usuarios', COUNT(*) FROM table_men_usuario_menu;

-- 8.2 Ver el árbol de menús
SELECT 
    m.menu_id,
    m.menu_padre_id,
    m.menu_nombre,
    m.menu_ruta,
    m.menu_scope,
    m.menu_es_desplegable,
    p.menu_nombre as padre_nombre
FROM table_men_menu m
LEFT JOIN table_men_menu p ON m.menu_padre_id = p.menu_id
ORDER BY COALESCE(m.menu_padre_id, m.menu_id), m.menu_orden;

-- 8.3 Ver las rutas del sistema flota/taller
SELECT 
    r.ruta_id,
    r.menu_id,
    m.menu_nombre,
    m.menu_padre_id,
    p.menu_nombre as padre_nombre,
    r.patron
FROM table_men_rutas r
JOIN table_men_menu m ON r.menu_id = m.menu_id
LEFT JOIN table_men_menu p ON m.menu_padre_id = p.menu_id
WHERE r.patron LIKE 'flota%'
ORDER BY r.menu_id, r.patron;

-- =====================================================================
-- FIN DEL SCRIPT
-- =====================================================================
-- RESULTADO ESPERADO:
--   - Menús:       ~30 (11 raíz + ~19 hijos)
--   - Rutas:       ~25
--   - Permisos:    177 (los mismos que había antes)
-- =====================================================================

-- =====================================================================
-- ROLLBACK (SOLO SI ALGO FALLA)
-- =====================================================================
-- Si algo sale mal, ejecuta esto para restaurar las tablas viejas:
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