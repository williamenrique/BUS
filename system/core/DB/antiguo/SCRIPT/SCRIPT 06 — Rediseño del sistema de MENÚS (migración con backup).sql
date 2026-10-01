-- =====================================================================
-- SCRIPT 06: MIGRACIÓN DE MENÚS
-- Renombra tablas viejas a *_backup_2026 y crea el nuevo esquema.
-- Idempotente: si ya se migró (existe table_men_rutas), no hace nada.
-- =====================================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ---------- Detectar si ya se migró ----------
SET @ya_migrado = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                   WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_men_rutas');
SET @viejo_existe = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
                     WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_men_submenu');

-- =====================================================================
-- PROCEDIMIENTO TEMPORAL
-- =====================================================================
DROP PROCEDURE IF EXISTS sp_migracion_menus;
DELIMITER $$
CREATE PROCEDURE sp_migracion_menus()
BEGIN
    DECLARE v_nuevo INT DEFAULT 0;
    DECLARE v_viejo INT DEFAULT 0;

    SELECT COUNT(*) INTO v_nuevo FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_men_rutas';
    SELECT COUNT(*) INTO v_viejo FROM INFORMATION_SCHEMA.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_men_submenu';

    IF v_nuevo = 0 AND v_viejo = 1 THEN

        -- ----- 1) Renombrar tablas viejas -----
        RENAME TABLE `table_men_menu`              TO `table_men_menu_backup_2026`;
        RENAME TABLE `table_men_submenu`           TO `table_men_submenu_backup_2026`;
        RENAME TABLE `table_men_usuario_menu`      TO `table_men_usuario_menu_backup_2026`;
        RENAME TABLE `table_men_usuario_submenu`   TO `table_men_usuario_submenu_backup_2026`;
        RENAME TABLE `table_men_departamento_menu` TO `table_men_departamento_menu_backup_2026`;

        -- ----- 2) Crear nuevas tablas -----
        CREATE TABLE `table_men_menu` (
          `menu_id` INT(11) NOT NULL AUTO_INCREMENT,
          `menu_padre_id` INT(11) DEFAULT NULL,
          `menu_nombre` VARCHAR(100) NOT NULL,
          `menu_icono` VARCHAR(50) DEFAULT NULL,
          `menu_ruta` VARCHAR(255) DEFAULT NULL,
          `menu_orden` INT(11) DEFAULT 0,
          `menu_estado` TINYINT(1) DEFAULT 1,
          `menu_scope` VARCHAR(50) DEFAULT 'general',
          `menu_es_desplegable` TINYINT(1) DEFAULT 0,
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
          `patron` VARCHAR(255) NOT NULL,
          `activa` TINYINT(1) DEFAULT 1,
          PRIMARY KEY (`ruta_id`),
          UNIQUE KEY `uk_menu_patron` (`menu_id`,`patron`),
          KEY `idx_menu` (`menu_id`),
          CONSTRAINT `fk_ruta_menu` FOREIGN KEY (`menu_id`)
            REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

        CREATE TABLE `table_men_usuario_menu` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` INT(11) NOT NULL,
          `menu_id` INT(11) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_usuario_menu` (`usuario_id`,`menu_id`),
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
          UNIQUE KEY `uk_dep_menu` (`departamento_id`,`menu_id`),
          CONSTRAINT `fk_dm_departamento` FOREIGN KEY (`departamento_id`)
            REFERENCES `table_departamentos`(`departamento_id`) ON DELETE CASCADE,
          CONSTRAINT `fk_dm_menu` FOREIGN KEY (`menu_id`)
            REFERENCES `table_men_menu`(`menu_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

        -- ----- 3) Migrar menús padre -----
        INSERT INTO `table_men_menu`
          (`menu_id`,`menu_padre_id`,`menu_nombre`,`menu_icono`,`menu_ruta`,
           `menu_orden`,`menu_estado`,`menu_scope`,`menu_es_desplegable`)
        SELECT `menu_id`, NULL, `menu_nombre`, `menu_icono`, `menu_link`,
               `menu_orden`, `menu_estado`, 'general', `menu_es_desplegable`
        FROM `table_men_menu_backup_2026`;

        -- ----- 4) Migrar submenús como hijos (ID + 1000) -----
        INSERT INTO `table_men_menu`
          (`menu_id`,`menu_padre_id`,`menu_nombre`,`menu_icono`,`menu_ruta`,
           `menu_orden`,`menu_estado`,`menu_scope`,`menu_es_desplegable`)
        SELECT `submenu_id`+1000, `menu_id`, `submenu_nombre`, 'far fa-circle',
               `submenu_link`, `submenu_orden`, `submenu_estado`, 'general', 0
        FROM `table_men_submenu_backup_2026`;

        -- ----- 5) Rutas de submenús -----
        INSERT INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
        SELECT `submenu_id`+1000, `submenu_link`, 1
        FROM `table_men_submenu_backup_2026`
        WHERE `submenu_link` IS NOT NULL AND `submenu_link` != '';

        -- ----- 6) Rutas de menús hoja -----
        INSERT INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
        SELECT `menu_id`, `menu_link`, 1
        FROM `table_men_menu_backup_2026`
        WHERE `menu_es_desplegable`=0 AND `menu_link` IS NOT NULL AND `menu_link`!='';

        -- ----- 7) Permisos usuarios (menús padre) -----
        INSERT IGNORE INTO `table_men_usuario_menu` (`usuario_id`,`menu_id`)
        SELECT `usuario_id`,`menu_id`
        FROM `table_men_usuario_menu_backup_2026`
        WHERE `menu_id` IS NOT NULL;

        -- ----- 8) Permisos usuarios (submenús) -----
        INSERT IGNORE INTO `table_men_usuario_menu` (`usuario_id`,`menu_id`)
        SELECT `usuario_id`,`submenu_id`+1000
        FROM `table_men_usuario_submenu_backup_2026`
        WHERE `submenu_id` IS NOT NULL;

        -- ----- 9) Permisos por departamento -----
        INSERT IGNORE INTO `table_men_departamento_menu` (`departamento_id`,`menu_id`)
        SELECT `departamento_id`,`menu_id`
        FROM `table_men_departamento_menu_backup_2026`;

        -- ----- 10) Recalcular menu_es_desplegable -----
        UPDATE `table_men_menu` m
        SET m.`menu_es_desplegable` = 1
        WHERE EXISTS (SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`);

        UPDATE `table_men_menu` m
        SET m.`menu_es_desplegable` = 0
        WHERE NOT EXISTS (SELECT 1 FROM `table_men_menu` h WHERE h.`menu_padre_id` = m.`menu_id`);

        -- ----- 11) Corregir rutas de submenús clave -----
        UPDATE `table_men_rutas` SET `patron`='flota/taller'
          WHERE `menu_id`=1019 AND `patron`='taller';
        INSERT IGNORE INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
          SELECT 1019,'flota/taller',1 WHERE EXISTS (SELECT 1 FROM `table_men_menu` WHERE `menu_id`=1019);

        UPDATE `table_men_rutas` SET `patron`='flota/talleraceite'
          WHERE `menu_id`=1020 AND `patron`='talleraceite';
        INSERT IGNORE INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
          SELECT 1020,'flota/talleraceite',1 WHERE EXISTS (SELECT 1 FROM `table_men_menu` WHERE `menu_id`=1020);

        -- ----- 12) Eliminar comodines conflictivos -----
        DELETE FROM `table_men_rutas` WHERE `patron`='flota/*' AND `menu_id` IN (1003, 1014);

        -- ----- 13) Agregar comodines correctos -----
        INSERT IGNORE INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
          SELECT 1019,'flota/taller/*',1 WHERE EXISTS (SELECT 1 FROM `table_men_menu` WHERE `menu_id`=1019);

        INSERT IGNORE INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
          SELECT 1020,'flota/talleraceite/*',1 WHERE EXISTS (SELECT 1 FROM `table_men_menu` WHERE `menu_id`=1020);

        INSERT IGNORE INTO `table_men_rutas` (`menu_id`,`patron`,`activa`)
          SELECT 1011,'flota/statusaceite/*',1 WHERE EXISTS (SELECT 1 FROM `table_men_menu` WHERE `menu_id`=1011);

    END IF;
END$$
DELIMITER ;

CALL sp_migracion_menus();
DROP PROCEDURE IF EXISTS sp_migracion_menus;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'SCRIPT 06 OK' AS resultado;