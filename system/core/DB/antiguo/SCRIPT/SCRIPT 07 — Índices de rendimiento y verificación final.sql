-- =====================================================================
-- SCRIPT 07: Índices adicionales y verificación
-- =====================================================================

-- Índice en table_compras_pendientes por institución
SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_pendientes'
            AND INDEX_NAME='idx_pendientes_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_pendientes` ADD INDEX `idx_pendientes_institucion` (`id_institucion`)',
  'SELECT "idx_pendientes_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_compras_costos'
            AND INDEX_NAME='idx_costos_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_compras_costos` ADD INDEX `idx_costos_institucion` (`id_institucion`)',
  'SELECT "idx_costos_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_despacho'
            AND INDEX_NAME='idx_reldesp_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_despacho` ADD INDEX `idx_reldesp_institucion` (`id_institucion`)',
  'SELECT "idx_reldesp_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_relacion_producto'
            AND INDEX_NAME='idx_relprod_institucion');
SET @s = IF(@c=0,
  'ALTER TABLE `table_alm_relacion_producto` ADD INDEX `idx_relprod_institucion` (`id_institucion`)',
  'SELECT "idx_relprod_institucion ya existe" AS info');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
-- VERIFICACIÓN
-- =====================================================================
SELECT 'INSTITUCIONES' AS bloque, COUNT(*) AS total FROM table_instituciones;

SELECT 'Flota' AS tabla, id_institucion, COUNT(*) AS total
  FROM table_flota GROUP BY id_institucion
UNION ALL
SELECT 'Productos', id_institucion, COUNT(*)
  FROM table_alm_producto GROUP BY id_institucion
UNION ALL
SELECT 'Despachos', id_institucion, COUNT(*)
  FROM table_alm_despacho GROUP BY id_institucion
UNION ALL
SELECT 'Compras Pendientes', id_institucion, COUNT(*)
  FROM table_compras_pendientes GROUP BY id_institucion
UNION ALL
SELECT 'Compras Costos', id_institucion, COUNT(*)
  FROM table_compras_costos GROUP BY id_institucion;

SELECT 'Menús' AS tabla, COUNT(*) AS total FROM table_men_menu
UNION ALL
SELECT 'Rutas', COUNT(*) FROM table_men_rutas
UNION ALL
SELECT 'Permisos usuarios', COUNT(*) FROM table_men_usuario_menu
UNION ALL
SELECT 'Permisos departamento', COUNT(*) FROM table_men_departamento_menu;

SELECT 'Bienes Taller' AS tabla, COUNT(*) AS total FROM table_bienes_taller_inventario;

-- Verificar columnas críticas de table_alm_despacho
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='table_alm_despacho'
  AND COLUMN_NAME IN ('operador_id','mecanico_id','despachador_id','id_institucion','numero_orden');

SELECT 'SCRIPT 07 OK - VERIFICACIÓN COMPLETA' AS resultado;