-- =====================================================================
-- AGREGAR numero_orden A table_alm_despacho
-- Correlativo por institución
-- =====================================================================

-- 1. Agregar la columna
ALTER TABLE `table_alm_despacho` 
    ADD COLUMN `numero_orden` INT(11) DEFAULT NULL AFTER `id_despacho`;

-- 2. Migrar los datos existentes: numero_orden = id_despacho para las existentes
UPDATE `table_alm_despacho` SET `numero_orden` = `id_despacho` WHERE `numero_orden` IS NULL;

-- 3. Índice único compuesto (id_institucion + numero_orden)
--    Evita duplicados por institución
ALTER TABLE `table_alm_despacho` 
    ADD UNIQUE KEY `uk_institucion_numero` (`id_institucion`, `numero_orden`);

-- 4. (Opcional) Ajustar el siguiente AUTO_INCREMENT si quieres que empiece arriba
-- ALTER TABLE `table_alm_despacho` AUTO_INCREMENT = 10000;

-- =====================================================================
-- VERIFICACIÓN
-- =====================================================================
-- SELECT id_despacho, numero_orden, id_institucion, fecha_despacho 
-- FROM table_alm_despacho 
-- ORDER BY id_despacho DESC 
-- LIMIT 10;