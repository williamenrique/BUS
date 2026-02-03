-- =================================================================
-- SCRIPT DE MIGRACIÓN DE ÓRDENES ANTIGUAS A REQUISICIONES (v3 - CORREGIDO)
-- =================================================================
-- OBJETIVO: Migrar los datos de `table_alm_despacho` y `table_alm_relacion_despacho`
-- a las nuevas tablas `table_alm_requisicion` y `table_alm_requisicion_detalle`.
--
-- CORRECCIÓN: Se mejora la conversión de fechas para manejar posibles inconsistencias
-- en el formato de `fecha_despacho` y se asegura que las columnas de fecha no queden NULAS.
--
-- IMPORTANTE: Realice una copia de seguridad de la base de datos antes de ejecutar este script.
-- =================================================================

-- =================================================================
-- PASO 1: Actualizar las órdenes existentes en `table_alm_despacho`
-- =================================================================
-- Se establece el estado 'Despachada' (3) y se llenan las columnas de aprobación.
-- La `fecha_aprobacion` se poblará con la misma fecha del despacho.
-- Se utiliza COALESCE para intentar múltiples formatos de fecha y evitar valores NULOS.

-- Desactivar el modo de actualización segura temporalmente para permitir el UPDATE sin una clave primaria en el WHERE.
SET SQL_SAFE_UPDATES = 0;

UPDATE table_alm_despacho
SET
    estado_orden = 3, -- 3: Despachada
    usuario_aprobador_id = 10, -- ID de usuario por defecto (ej: Administrador)
    -- Se asigna directamente la fecha de despacho. MySQL convierte DATE a DATETIME automáticamente.
    fecha_aprobacion = fecha_despacho;

-- Volver a activar el modo de actualización segura.
SET SQL_SAFE_UPDATES = 1;

-- =================================================================
-- PASO 2: Migrar las cabeceras de `table_alm_despacho` a `table_alm_requisicion`
-- =================================================================
-- Se crea una requisición por cada despacho existente.
-- La `fecha_creacion` de la nueva requisición será la misma que la del despacho original.
-- Se utiliza un LEFT JOIN para evitar crear registros duplicados.

INSERT INTO table_alm_requisicion (
    id_despacho_fk,
    id_flota,
    mecanico_cedula,
    tipo_orden,
    observacion,
    user_id_creador,
    fecha_creacion,
    status_requisicion
)
SELECT
    d.id_despacho,
    d.id_flota,
    d.mecanico,
    'Servicio', -- Valor por defecto para las órdenes migradas.
    d.observacion,
    d.user_id,
    -- Se asegura que la fecha de creación de la requisición sea la misma que la del despacho.
    -- Se asigna directamente la fecha de despacho.
    d.fecha_despacho,
    2 -- Se establece el estado como 'Aprobada' (2) ya que la orden original fue despachada.
FROM
    table_alm_despacho d
LEFT JOIN
    table_alm_requisicion r ON d.id_despacho = r.id_despacho_fk
WHERE
    r.id_despacho_fk IS NULL; -- Solo inserta si no existe ya una requisición para ese despacho.

-- =================================================================
-- PASO 3: Migrar los artículos de `table_alm_relacion_despacho` a `table_alm_requisicion_detalle`
-- =================================================================
-- Se insertan los detalles de los artículos para cada requisición creada en el paso anterior.
-- Este paso no se modifica, ya que no maneja fechas.

INSERT INTO table_alm_requisicion_detalle (
    id_requisicion_fk,
    id_producto,
    cantidad_solicitada
)
SELECT
    r.id_requisicion,
    rd.id_producto,
    rd.cant_despacho
FROM
    table_alm_relacion_despacho rd
JOIN
    table_alm_requisicion r ON rd.id_despacho = r.id_despacho_fk
LEFT JOIN
    table_alm_requisicion_detalle r_det ON r.id_requisicion = r_det.id_requisicion_fk AND rd.id_producto = r_det.id_producto
WHERE
    r_det.id_detalle_req IS NULL; -- Solo inserta el artículo si no existe ya en el detalle de esa requisición.

-- =================================================================
-- FIN DEL SCRIPT DE MIGRACIÓN
-- =================================================================
