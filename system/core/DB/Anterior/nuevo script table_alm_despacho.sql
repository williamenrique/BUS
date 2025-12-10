-- =================================================================
-- SCRIPT PARA ADAPTAR `table_alm_despacho` CON CAMPOS DE ORIGEN
-- =================================================================

-- Paso 1: Añadir las columnas para identificar el origen del despacho.
-- Esto nos permitirá saber si un despacho fue directo o si nació de una requisición.
ALTER TABLE `table_alm_despacho`
    ADD COLUMN `origen_despacho` VARCHAR(20) NOT NULL DEFAULT 'Almacen' COMMENT 'Identifica si el despacho es directo de Almacen o viene de una Requisicion',
    ADD COLUMN `id_requisicion_origen` INT(11) NULL DEFAULT NULL COMMENT 'FK a table_mto_requisicion si el origen es una requisicion';

-- Paso 2: (Opcional pero recomendado) Añadir la clave foránea para mantener la integridad de los datos.
-- Esto asegura que un `id_requisicion_origen` siempre apunte a una requisición válida.
-- Asegúrate de que la tabla `table_mto_requisicion` ya exista antes de ejecutar esta parte.
ALTER TABLE `table_alm_despacho`
ADD CONSTRAINT `fk_despacho_req_origen` 
FOREIGN KEY (`id_requisicion_origen`) 
REFERENCES `table_mto_requisicion`(`id_requisicion`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Paso 3: Actualizar los registros existentes.
-- Todas tus órdenes de despacho antiguas se marcarán con el origen 'Almacen',
-- asegurando que tu historial permanezca intacto y coherente.
UPDATE `table_alm_despacho`
SET 
    `origen_despacho` = 'Almacen'
WHERE 
    `origen_despacho` != 'Requisicion';


CREATE TABLE `table_notificaciones` (
  `id_notificacion` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo_notificacion` VARCHAR(50) NOT NULL COMMENT 'Ej: requisicion, recuperacion_pass',
  `id_referencia` INT(11) NOT NULL COMMENT 'ID del despacho, solicitud, etc.',
  `mensaje` VARCHAR(255) NOT NULL,
  `leido` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: no leido, 1: leido',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_notificacion`),
  INDEX `idx_tipo_leido` (`tipo_notificacion`, `leido`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

