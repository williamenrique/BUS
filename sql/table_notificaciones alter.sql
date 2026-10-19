ALTER TABLE `table_notificaciones`
  MODIFY `id_notificacion` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id_notificacion`);

  ALTER TABLE table_bienes_inventario
    ADD COLUMN id_institucion INT(11) NOT NULL DEFAULT 1 AFTER status,
    ADD INDEX idx_bienes_institucion (id_institucion);


    -- 1. Tipos de combustible
CREATE TABLE IF NOT EXISTS `table_es_tipos_combustible` (
  `id_tipo_combustible` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `status_tipo_combustible` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_combustible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `table_es_tipos_combustible` (`id_tipo_combustible`, `nombre`) VALUES
(1, 'Gasolina'),
(2, 'Diesel');

-- 2. Columna combustible en ventas
ALTER TABLE `table_es_venta`
  ADD COLUMN `id_tipo_combustible` INT NOT NULL DEFAULT 1 AFTER `id_tipo_vehiculo`,
  ADD INDEX `idx_venta_combustible` (`id_tipo_combustible`);
  
  ALTER TABLE `table_usuarios`
  ADD COLUMN `usuario_estacion_sesion` INT NULL DEFAULT NULL AFTER `usuario_estacion_id`;
  
  ALTER TABLE `table_usuario_sessions`
  ADD COLUMN `id_estacion` INT NULL DEFAULT NULL AFTER `usuario_agent`;