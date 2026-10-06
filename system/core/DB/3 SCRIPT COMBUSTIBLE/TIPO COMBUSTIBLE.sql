-- 1. Crear tabla de tipos de combustible
CREATE TABLE IF NOT EXISTS `table_es_tipos_combustible` (
  `id_tipo_combustible` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `status_tipo_combustible` int(11) DEFAULT 1,
  PRIMARY KEY (`id_tipo_combustible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 2. Insertar los tipos por defecto
INSERT INTO `table_es_tipos_combustible` (`id_tipo_combustible`, `nombre`, `descripcion`, `status_tipo_combustible`) VALUES
(1, 'Gasolina', 'Combustible tipo gasolina', 1),
(2, 'Diesel', 'Combustible tipo diesel', 1);

-- 3. Agregar columna a table_es_venta
ALTER TABLE `table_es_venta`
  ADD COLUMN `id_tipo_combustible` int(11) NOT NULL DEFAULT 1 AFTER `id_tipo_vehiculo`,
  ADD KEY `fk_venta_combustible` (`id_tipo_combustible`),
  ADD CONSTRAINT `fk_venta_combustible` FOREIGN KEY (`id_tipo_combustible`) REFERENCES `table_es_tipos_combustible` (`id_tipo_combustible`);