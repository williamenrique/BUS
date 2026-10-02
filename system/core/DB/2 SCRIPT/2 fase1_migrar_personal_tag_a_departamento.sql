-- =====================================================================
-- SCRIPT FASE 1: MIGRACIÓN personal_tag → personal_departamento
-- =====================================================================
-- Fecha: 2026-10-02
-- Descripción: Agrega columna personal_departamento (FK a table_departamentos),
--              migra datos desde personal_tag, y prepara para eliminar personal_tag
-- =====================================================================

USE busyaracuydata;

-- Deshabilitar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- PASO 1: AGREGAR NUEVA COLUMNA personal_departamento
-- =====================================================================
-- Usa INT para referenciar table_departamentos.departamento_id

ALTER TABLE `table_personal` 
ADD COLUMN `personal_departamento` INT(11) NULL 
COMMENT 'FK a table_departamentos.departamento_id' 
AFTER `personal_tag`;

-- =====================================================================
-- PASO 2: MIGRAR DATOS EXISTENTES DESDE personal_tag
-- =====================================================================
-- Mapeo usando table_departamentos:
--   tag=2 (almacen/despachador) → 4 (Almacen)
--   tag=1 (informatica)         → 1 (Sistema)
--   tag=0 (normal/operadores/mecánicos) → NULL (sin departamento asignado)
--   Los operadores y mecánicos se filtran por personal_cargo, NO por departamento

UPDATE `table_personal` 
SET `personal_departamento` = CASE 
    WHEN `personal_tag` = 2 THEN 4   -- ALMACEN (despachadores)
    WHEN `personal_tag` = 1 THEN 1   -- SISTEMA (informática)
    ELSE NULL                        -- OPERADORES, MECÁNICOS, RESTO = SIN DEPARTAMENTO
END
WHERE `personal_departamento` IS NULL;

-- =====================================================================
-- PASO 3: AGREGAR FOREIGN KEY
-- =====================================================================

ALTER TABLE `table_personal` 
ADD CONSTRAINT `fk_personal_departamento` 
FOREIGN KEY (`personal_departamento`) 
REFERENCES `table_departamentos` (`departamento_id`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- =====================================================================
-- PASO 4: AGREGAR ÍNDICE PARA CONSULTAS RÁPIDAS
-- =====================================================================

ALTER TABLE `table_personal` 
ADD INDEX `idx_personal_departamento` (`personal_departamento`);

-- =====================================================================
-- PASO 5: VERIFICAR MIGRACIÓN
-- =====================================================================

-- Verificar distribución de departamentos
SELECT 
    d.departamento_id,
    d.departamento_nombre,
    COUNT(p.id_personal) as total_personal
FROM table_departamentos d
LEFT JOIN table_personal p ON p.personal_departamento = d.departamento_id
GROUP BY d.departamento_id, d.departamento_nombre
ORDER BY total_personal DESC;

-- Verificar personal SIN departamento (debe ser operadores, mecánicos, etc.)
SELECT COUNT(*) as total_sin_departamento 
FROM table_personal 
WHERE personal_departamento IS NULL;

-- Verificar estructura final
DESCRIBE `table_personal`;

-- =====================================================================
-- HABILITAR VERIFICACIÓN DE CLAVES FORÁNEAS
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- NOTAS IMPORTANTES
-- =====================================================================
-- 1. Ejecutar este script EN TRANSACCIÓN o con backup previo
-- 2. Después de validar que todo funciona, ejecutar FASE 2 (código)
-- 3. FASE 3 será eliminar personal_tag: ALTER TABLE table_personal DROP COLUMN personal_tag;
-- 4. Referencia table_departamentos (INT) no table_bienes_departamentos (VARCHAR)
-- 5. Mapeo: tag=2→4(Almacen), tag=1→1(Sistema), tag=0→NULL (operadores/mecánicos filtran por cargo)
-- 6. selectListOper() y selectListMec() NO CAMBIAN (filtran por personal_cargo)
-- 7. Solo selectListDesp() cambia: personal_tag=2 → personal_departamento=4