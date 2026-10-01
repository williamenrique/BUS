-- =====================================================================
-- SCRIPT 2: TABLA DE BIENES DEL TALLER
-- =====================================================================
-- Crea table_bienes_taller_inventario (independiente del inventario general)
-- Idempotente.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `table_bienes_taller_inventario` (
  `id_bien_taller` INT(11) NOT NULL AUTO_INCREMENT,
  `bien_depatamento_id` VARCHAR(10) NOT NULL DEFAULT '',
  `grupo_id` VARCHAR(50) DEFAULT NULL,
  `subgrupo_id` VARCHAR(50) DEFAULT NULL,
  `seccion_id` VARCHAR(50) NOT NULL DEFAULT '',
  `descripcion_bien` TEXT DEFAULT NULL,
  `fecha_adquisicion` VARCHAR(50) DEFAULT NULL,
  `status_bien` VARCHAR(50) DEFAULT NULL COMMENT 'EN USO, EXTRAVIADO, EN REPARACION, DAÑADO',
  `fecha_creacion` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `user_id` INT(11) NOT NULL,
  `org` INT(11) DEFAULT NULL,
  `edo` INT(11) DEFAULT NULL,
  `status` INT(11) DEFAULT 1 COMMENT '1: Activo, 0: Inactivo',
  PRIMARY KEY (`id_bien_taller`),
  KEY `idx_bt_depto`   (`bien_depatamento_id`),
  KEY `idx_bt_grupo`   (`grupo_id`),
  KEY `idx_bt_subgrupo`(`subgrupo_id`),
  KEY `idx_bt_seccion` (`seccion_id`),
  KEY `idx_bt_status`  (`status`),
  CONSTRAINT `fk_bt_depto`    FOREIGN KEY (`bien_depatamento_id`) REFERENCES `table_bienes_departamentos`(`depatamento_bien_id`),
  CONSTRAINT `fk_bt_grupo`    FOREIGN KEY (`grupo_id`)            REFERENCES `table_bienes_grupo`(`id_grupo`),
  CONSTRAINT `fk_bt_subgrupo` FOREIGN KEY (`subgrupo_id`)         REFERENCES `table_bienes_subgrupo`(`subgrupo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'SCRIPT 2 OK' AS resultado;
SELECT 'Bienes Taller' AS tabla, COUNT(*) AS total FROM table_bienes_taller_inventario;