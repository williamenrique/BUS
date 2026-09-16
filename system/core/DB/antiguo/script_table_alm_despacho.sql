-- =================================================================
-- SCRIPT PARA ACTUALIZAR LA ESTRUCTURA DE LA TABLA table_alm_despacho
-- =================================================================
-- OBJETIVO: Agregar las columnas para el nuevo flujo de aprobación de órdenes.
--
-- Se asume que la tabla `table_alm_despacho` ya existe en la base de datos
-- de destino, pero sin las últimas tres columnas.
-- =================================================================

-- Se utiliza una sola sentencia ALTER TABLE para mayor eficiencia.
ALTER TABLE `table_alm_despacho`
    -- 1. Se añade la columna para el estado de la orden.
    ADD COLUMN `estado_orden` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: Requisicion, 2: Aprobada por Compras, 3: Despachada, 4: Rechazada' AFTER `status_despacho`,

    -- 2. Se añade la columna para el ID del usuario que aprueba.
    ADD COLUMN `usuario_aprobador_id` INT(11) DEFAULT NULL COMMENT 'FK a table_usuarios. ID del usuario que aprueba.' AFTER `estado_orden`,

    -- 3. Se añade la columna para la fecha de aprobación.
    ADD COLUMN `fecha_aprobacion` DATETIME DEFAULT NULL COMMENT 'Fecha y hora de la aprobación por Compras' AFTER `usuario_aprobador_id`;

-- =================================================================
-- PASO 2: Agregar la clave foránea (Foreign Key)
-- =================================================================
-- Se añade la restricción para asegurar la integridad referencial
-- entre `table_alm_despacho` y `table_usuarios`.

ALTER TABLE `table_alm_despacho`
ADD CONSTRAINT `fk_despacho_aprobador`
    FOREIGN KEY (`usuario_aprobador_id`)
    REFERENCES `table_usuarios` (`usuario_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

-- =================================================================
-- FIN DEL SCRIPT
-- =================================================================
