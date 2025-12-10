-- =================================================================
-- SCRIPT PARA LA CREACIÓN DE LA TABLA DE REQUISICIONES
-- =================================================================

--
-- 1. TABLA PRINCIPAL DE REQUISICIONES
-- Almacena la información general de la requisición creada por el Jefe de Patio.
--
CREATE TABLE `table_alm_requisicion` (
  `id_requisicion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_despacho_fk` INT(11) NOT NULL COMMENT 'Enlace al registro en table_alm_despacho',
  `id_flota` INT(11) NOT NULL,
  `mecanico_cedula` VARCHAR(15) DEFAULT NULL,
  `tipo_orden` ENUM('Servicio','Compra','Reparacion') NOT NULL,
  `observacion` TEXT DEFAULT NULL,
  `user_id_creador` INT(11) NOT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_requisicion` INT(11) NOT NULL DEFAULT 1 COMMENT '1:Pendiente, 2:Aprobada, 3:Rechazada',
  PRIMARY KEY (`id_requisicion`),
  KEY `id_despacho_fk` (`id_despacho_fk`),
  KEY `id_flota` (`id_flota`),
  KEY `user_id_creador` (`user_id_creador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- 2. TABLA DE DETALLE DE ARTÍCULOS DE LA REQUISICIÓN
-- Almacena los artículos y cantidades para cada requisición.
--
CREATE TABLE `table_alm_requisicion_detalle` (
  `id_detalle_req` INT(11) NOT NULL AUTO_INCREMENT,
  `id_requisicion_fk` INT(11) NOT NULL,
  `id_producto` INT(11) NOT NULL,
  `cantidad_solicitada` FLOAT NOT NULL,
  PRIMARY KEY (`id_detalle_req`),
  KEY `id_requisicion_fk` (`id_requisicion_fk`),
  KEY `id_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

--
-- 3. RESTRICCIONES (CLAVES FORÁNEAS)
--
ALTER TABLE `table_alm_requisicion`
  ADD CONSTRAINT `fk_req_despacho` FOREIGN KEY (`id_despacho_fk`) REFERENCES `table_alm_despacho` (`id_despacho`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_req_usuario` FOREIGN KEY (`user_id_creador`) REFERENCES `table_usuarios` (`usuario_id`);

ALTER TABLE `table_alm_requisicion_detalle`
  ADD CONSTRAINT `fk_detreq_req` FOREIGN KEY (`id_requisicion_fk`) REFERENCES `table_alm_requisicion` (`id_requisicion`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detreq_prod` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`);