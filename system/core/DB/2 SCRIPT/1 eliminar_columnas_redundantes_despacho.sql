-- =====================================================================
-- SCRIPT DE ELIMINACIÓN DE COLUMNAS REDUNDANTES EN table_alm_despacho
-- =====================================================================
-- Fecha: 2026-10-02
-- Descripción: Elimina las columnas operador, mecanico, despachador 
--              ya que sus IDs (operador_id, mecanico_id, despachador_id) 
--              referencian a table_personal donde están los nombres actualizados
-- =====================================================================

-- IMPORTANTE: Ejecutar este script SOLO después de haber actualizado todo el código
-- que referencia estas columnas (ver documento LISTA_CAMBIOS_CODIGO.md)

USE busyaracuydata;

-- Deshabilitar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- ELIMINAR COLUMNAS REDUNDANTES
-- =====================================================================

-- Eliminar la columna operador (varchar(50))
ALTER TABLE `table_alm_despacho` DROP COLUMN `operador`;

-- Eliminar la columna mecanico (varchar(50))
ALTER TABLE `table_alm_despacho` DROP COLUMN `mecanico`;

-- Eliminar la columna despachador (varchar(50))
ALTER TABLE `table_alm_despacho` DROP COLUMN `despachador`;

-- =====================================================================
-- VERIFICAR ESTRUCTURA RESULTANTE
-- =====================================================================

DESCRIBE `table_alm_despacho`;

-- =====================================================================
-- HABILITAR VERIFICACIÓN DE CLAVES FORÁNEAS
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- NOTAS IMPORTANTES
-- =====================================================================
-- Las foreign keys existentes (fk_despacho_operador, fk_despacho_mecanico, 
-- fk_despacho_despachador) se mantienen intactas ya que referencian 
-- operador_id, mecanico_id, despachador_id respectivamente.
-- 
-- Para obtener los nombres, usar JOINs con table_personal:
--   LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
--   LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
--   LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
-- 
-- Y luego: CONCAT(p_op.personal_nombre, ' ', NULLIF(p_op.personal_apellido, '0')) AS operador_nombre