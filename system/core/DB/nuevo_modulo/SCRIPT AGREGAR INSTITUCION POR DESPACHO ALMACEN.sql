-- =====================================================================
-- MULTI-INSTITUCIÓN EN MÓDULO DE ALMACÉN
-- Fecha: 2026-09-30
-- Base de datos: busyaracuydata
-- =====================================================================
-- Ejecutar en producción.
-- Todas las tablas quedan con id_institucion = 1 (SSLMTY) para datos existentes.
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1. table_alm_producto
-- =====================================================================
ALTER TABLE `table_alm_producto` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_producto`,
    ADD INDEX `idx_producto_institucion` (`id_institucion`);

UPDATE `table_alm_producto` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

-- =====================================================================
-- 2. table_alm_relacion_producto
-- =====================================================================
ALTER TABLE `table_alm_relacion_producto` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_producto`,
    ADD INDEX `idx_relprod_institucion` (`id_institucion`);

UPDATE `table_alm_relacion_producto` rp
    INNER JOIN `table_alm_producto` p ON rp.id_producto = p.id_producto
SET rp.id_institucion = p.id_institucion
WHERE rp.id_institucion IS NULL OR rp.id_institucion = 0;

-- =====================================================================
-- 3. table_alm_despacho
-- =====================================================================
ALTER TABLE `table_alm_despacho` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `fecha_aprobacion`,
    ADD INDEX `idx_despacho_institucion` (`id_institucion`);

UPDATE `table_alm_despacho` SET `id_institucion` = 1 WHERE `id_institucion` IS NULL OR `id_institucion` = 0;

-- =====================================================================
-- 4. table_alm_relacion_despacho
-- =====================================================================
ALTER TABLE `table_alm_relacion_despacho` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `cant_despacho`,
    ADD INDEX `idx_reldesp_institucion` (`id_institucion`);

UPDATE `table_alm_relacion_despacho` rd
    INNER JOIN `table_alm_despacho` d ON rd.id_despacho = d.id_despacho
SET rd.id_institucion = d.id_institucion
WHERE rd.id_institucion IS NULL OR rd.id_institucion = 0;

-- =====================================================================
-- 5. table_compras_pendientes
-- =====================================================================
ALTER TABLE `table_compras_pendientes` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `status_costeo`,
    ADD INDEX `idx_pendientes_institucion` (`id_institucion`);

UPDATE `table_compras_pendientes` cp
    INNER JOIN `table_alm_despacho` d ON cp.id_despacho = d.id_despacho
SET cp.id_institucion = d.id_institucion
WHERE cp.id_institucion IS NULL OR cp.id_institucion = 0;

-- =====================================================================
-- 6. table_compras_costos
-- =====================================================================
ALTER TABLE `table_compras_costos` 
    ADD COLUMN `id_institucion` INT(11) NOT NULL DEFAULT 1 AFTER `id_usuario_costeo`,
    ADD INDEX `idx_costos_institucion` (`id_institucion`);

UPDATE `table_compras_costos` cc
    INNER JOIN `table_compras_pendientes` cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
SET cc.id_institucion = cp.id_institucion
WHERE cc.id_institucion IS NULL OR cc.id_institucion = 0;

-- =====================================================================
-- 7. Recalcular stock por institución (opcional pero recomendado)
-- =====================================================================
-- Si un producto ya existía en la tabla, su stock ahora vive con id_institucion = 1.
-- Los productos nuevos del Taller tendrán su propio registro separado en 
-- table_alm_relacion_producto con id_institucion = 2.

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- VERIFICACIÓN
-- =====================================================================
-- SELECT 'producto' as t, id_institucion, COUNT(*) FROM table_alm_producto GROUP BY id_institucion
-- UNION ALL
-- SELECT 'relacion_producto', id_institucion, COUNT(*) FROM table_alm_relacion_producto GROUP BY id_institucion
-- UNION ALL
-- SELECT 'despacho', id_institucion, COUNT(*) FROM table_alm_despacho GROUP BY id_institucion
-- UNION ALL
-- SELECT 'relacion_despacho', id_institucion, COUNT(*) FROM table_alm_relacion_despacho GROUP BY id_institucion
-- UNION ALL
-- SELECT 'compras_pendientes', id_institucion, COUNT(*) FROM table_compras_pendientes GROUP BY id_institucion
-- UNION ALL
-- SELECT 'compras_costos', id_institucion, COUNT(*) FROM table_compras_costos GROUP BY id_institucion;
-- =====================================================================