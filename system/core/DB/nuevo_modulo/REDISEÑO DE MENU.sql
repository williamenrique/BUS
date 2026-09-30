-- =====================================================================
-- REDISEÑO DEL SISTEMA DE MENÚS
-- Fecha: 2026-09-29
-- Base de datos: busyaracuydata
-- =====================================================================
-- IMPORTANTE:
--   1. HAZ BACKUP ANTES DE EJECUTAR ESTE SCRIPT.
--   2. Las tablas viejas se renombran a *_backup, NO se borran.
--   3. Si algo falla, puedes restaurar renombrando de vuelta.
-- =====================================================================

-- =====================================================================
-- PASO 1: RENOMBRAR TABLAS VIEJAS (para backup)
-- =====================================================================
RENAME TABLE `table_men_menu`            TO `table_men_menu_backup_2026`;
RENAME TABLE `table_men_submenu`         TO `table_men_submenu_backup_2026`;
RENAME TABLE `table_men_usuario_menu`    TO `table_men_usuario_menu_backup_2026`;
RENAME TABLE `table_men_usuario_submenu` TO `table_men_usuario_submenu_backup_2026`;
RENAME TABLE `table_men_departamento_menu` TO `table_men_departamento_menu_backup_2026`;

-- =====================================================================
-- PASO 2: CREAR LAS NUEVAS TABLAS
-- =====================================================================

-- ---------------------------------------------------------------------
-- Tabla principal de menús (árbol con auto-referencia)
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
  `menu_es_desplegable` TINYINT(1) DEFAULT 0 COMMENT 'Se autocalcula pero se guarda para performance',
  PRIMARY KEY (`menu_id`),
  KEY `idx_padre` (`menu_padre_id`),
  KEY `idx_scope` (`menu_scope`),
  KEY `idx_estado` (`menu_estado`),
  CONSTRAINT `fk_menu_padre` FOREIGN KEY (`menu_padre_id`) 
    REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- ---------------------------------------------------------------------
-- Rutas que activan cada menú (patrones con soporte de comodín *)
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
-- Permisos por usuario (apunta a cualquier nivel del árbol)
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
-- Permisos por departamento (opcional, mismo esquema)
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
-- PASO 3: MIGRAR DATOS DE LAS TABLAS VIEJAS
-- =====================================================================
-- Estrategia:
--   - Menús padre → van como raíces (menu_padre_id = NULL).
--   - Submenús    → van como hijos del menú padre.
--   - Se preservan los IDs originales de menús.
--   - Los submenús se insertan con ID + 1000 para no colisionar con menús.
--   - Permisos de usuarios se migran al nuevo esquema.
-- =====================================================================

-- ---- 3.1: Insertar menús padre (raíces) ----
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

-- ---- 3.2: Insertar submenús (con ID + 1000 para no colisionar) ----
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

-- ---- 3.3: Insertar rutas que activan cada submenú (una por defecto) ----
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 
    `submenu_id` + 1000, 
    `submenu_pagina`, 
    1
FROM `table_men_submenu_backup_2026`
WHERE `submenu_pagina` IS NOT NULL AND `submenu_pagina` != '';

-- ---- 3.4: Insertar rutas de menús hoja (los que NO son desplegables) ----
-- Estos menús raíz apuntan a una URL propia y también necesitan patrón.
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`)
SELECT 
    `menu_id`, 
    `menu_link`, 
    1
FROM `table_men_menu_backup_2026`
WHERE `menu_es_desplegable` = 0 
  AND `menu_link` IS NOT NULL 
  AND `menu_link` != '';

-- ---- 3.5: Migrar permisos de usuarios ----
-- Los permisos antiguos estaban en 2 tablas: menu y submenu.
-- En el nuevo esquema, todo apunta a table_men_menu.
-- Los submenús ahora tienen ID + 1000.

-- Permisos de menú padre
INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `menu_id`
FROM `table_men_usuario_menu_backup_2026`
WHERE `menu_id` IS NOT NULL;

-- Permisos de submenú (ahora apuntan a la nueva tabla como menús hijos)
INSERT INTO `table_men_usuario_menu` (`usuario_id`, `menu_id`)
SELECT `usuario_id`, `submenu_id` + 1000
FROM `table_men_usuario_submenu_backup_2026`
WHERE `submenu_id` IS NOT NULL;

-- ---- 3.6: Migrar permisos por departamento (si existían) ----
INSERT INTO `table_men_departamento_menu` (`departamento_id`, `menu_id`)
SELECT `departamento_id`, `menu_id`
FROM `table_men_departamento_menu_backup_2026`;

-- =====================================================================
-- PASO 4: RECALCULAR menu_es_desplegable
-- =====================================================================
-- Un menú es desplegable si tiene al menos un hijo.
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
-- PASO 5: AGREGAR COMODINES PARA RUTAS DINÁMICAS
-- =====================================================================
-- Para que "Flota" quede activa también en subrutas como
-- "flota/historialunidad/123", agregamos un patrón con comodín.
-- Ajusta según tus submenús reales.

-- Rutas con comodín para los submenús del taller
INSERT INTO `table_men_rutas` (`menu_id`, `patron`, `activa`) VALUES
    (1019, 'flota/taller/*', 1),
    (1011, 'flota/statusaceite/*', 1),
    (1003, 'flota/*', 1),
    (1014, 'flota/*', 1),
    (1020, 'flota/talleraceite/*', 1);

-- =====================================================================
-- PASO 6: VERIFICACIÓN
-- =====================================================================
-- Ejecuta estos SELECT para confirmar que la migración fue correcta.

-- SELECT COUNT(*) as total_menus FROM table_men_menu;
-- SELECT COUNT(*) as total_rutas FROM table_men_rutas;
-- SELECT COUNT(*) as total_permisos FROM table_men_usuario_menu;
-- SELECT * FROM table_men_menu ORDER BY menu_padre_id, menu_orden;
-- SELECT * FROM table_men_rutas ORDER BY menu_id;

-- =====================================================================
-- FIN DEL SCRIPT
-- =====================================================================