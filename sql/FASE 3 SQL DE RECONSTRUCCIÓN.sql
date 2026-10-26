-- ============================================================
-- SITGO · Reconstrucción completa de menús (2026-10-22)
-- ============================================================
-- - Limpia las 4 tablas de menús
-- - Reconstruye 17 menús para WILL (usuario 2)
-- - Deja todo lo demás vacío para reasignar manualmente
-- - BACKUP ya existe en: backup_men_*_20261022
-- ============================================================

START TRANSACTION;

-- ------------------------------------------------------------
-- 1) LIMPIAR TABLAS
-- ------------------------------------------------------------
DELETE FROM table_men_usuario_menu;
DELETE FROM table_men_departamento_menu;
DELETE FROM table_men_rutas;
DELETE FROM table_men_menu;

-- ------------------------------------------------------------
-- 2) INSERTAR MENÚS (IDs desde 1000)
-- ------------------------------------------------------------
-- Jerarquía:
--   1000 Dashboard
--   1100 Operaciones
--     1101 Flota
--     1102 Cambio Aceite
--     1103 Historial Flota
--     1104 Historial Despachos
--   1200 Almacén
--     1201 Producto
--     1202 Despacho
--   1300 Compras
--     1301 Reporte Compra
--   1400 Bienes
--   1500 Usuarios
--     1501 Gestión de Usuarios
--     1502 Personal
--     1503 Recuperar
--   1600 Estación
--     1601 Data Venta
--   1700 Menú (admin)
-- ------------------------------------------------------------

INSERT INTO table_men_menu
    (menu_id, menu_padre_id, menu_nombre, menu_icono, menu_ruta, menu_orden, menu_estado, menu_scope, menu_es_desplegable)
VALUES
    -- Dashboard
    (1000, NULL, 'Dashboard', 'home', 'dashboard', 1, 1, 'general', 0),

    -- Operaciones (padre)
    (1100, NULL, 'Operaciones', 'bus', NULL, 2, 1, 'general', 1),
    (1101, 1100, 'Flota', 'truck', 'flota/i/1', 1, 1, 'general', 0),
    (1102, 1100, 'Cambio Aceite', 'droplet', 'flota/aceite/i/1', 2, 1, 'general', 0),
    (1103, 1100, 'Historial Flota', 'history', 'flota/status/i/1', 3, 1, 'general', 0),
    (1104, 1100, 'Historial Despachos', 'clipboard-list', 'despachos/i/1/historial', 4, 1, 'general', 0),

    -- Almacén (padre)
    (1200, NULL, 'Almacén', 'package', NULL, 3, 1, 'general', 1),
    (1201, 1200, 'Producto', 'box', 'productos/i/1', 1, 1, 'general', 0),
    (1202, 1200, 'Despacho', 'truck', 'despachos/i/1', 2, 1, 'general', 0),

    -- Compras (padre)
    (1300, NULL, 'Compras', 'shopping-cart', NULL, 4, 1, 'general', 1),
    (1301, 1300, 'Reporte Compra', 'file-text', 'compras/i/1/costeadas', 1, 1, 'general', 0),

    -- Bienes
    (1400, NULL, 'Bienes', 'folder-open', 'bienes/i/1', 5, 1, 'general', 0),

    -- Usuarios (padre)
    (1500, NULL, 'Usuarios', 'users', NULL, 6, 1, 'general', 1),
    (1501, 1500, 'Gestión de Usuarios', 'user-cog', 'usuarios', 1, 1, 'general', 0),
    (1502, 1500, 'Personal', 'user', 'personal', 2, 1, 'general', 0),
    (1503, 1500, 'Recuperar', 'key-round', 'recuperar', 3, 1, 'general', 0),

    -- Estación (padre)
    (1600, NULL, 'Estación', 'fuel', NULL, 7, 1, 'general', 1),
    (1601, 1600, 'Data Venta', 'bar-chart-3', 'estacion/dataventa', 1, 1, 'general', 0),

    -- Menú (admin)
    (1700, NULL, 'Menú', 'settings', 'menu', 8, 1, 'general', 0);

-- ------------------------------------------------------------
-- 3) INSERTAR PATRONES DE RUTA
-- ------------------------------------------------------------
-- Patrones con "i" para que matcheen con cualquier institución
-- (flota/i/1, flota/i/2, etc.)
-- ------------------------------------------------------------

INSERT INTO table_men_rutas (menu_id, patron, activa)
VALUES
    (1000, 'dashboard', 1),

    (1101, 'flota/i', 1),
    (1102, 'flota/aceite', 1),
    (1103, 'flota/status', 1),
    (1104, 'despachos/i', 1),

    (1201, 'productos/i', 1),
    (1202, 'despachos/i', 1),

    (1301, 'compras/i', 1),

    (1400, 'bienes/i', 1),

    (1501, 'usuarios', 1),
    (1502, 'personal', 1),
    (1503, 'recuperar', 1),

    (1601, 'estacion/dataventa', 1),

    (1700, 'menu', 1);

-- ------------------------------------------------------------
-- 4) ASIGNAR TODOS LOS MENÚS A WILL (usuario 2)
-- ------------------------------------------------------------
-- IMPORTANTE: WILL ya es del depto Sistema (1), así que el
-- MenuGuard lo dejará pasar igual. Pero le asignamos todos los
-- menús para que el sidebar se vea completo.
-- ------------------------------------------------------------

INSERT INTO table_men_usuario_menu (usuario_id, menu_id)
SELECT 2, menu_id FROM table_men_menu;

-- ------------------------------------------------------------
-- 5) VERIFICACIÓN (dentro de la transacción)
-- ------------------------------------------------------------
-- Esta consulta muestra qué quedó:
-- ------------------------------------------------------------

SELECT 'MENÚS' AS seccion, COUNT(*) AS filas FROM table_men_menu
UNION ALL
SELECT 'RUTAS',              COUNT(*) FROM table_men_rutas
UNION ALL
SELECT 'USUARIO_MENU',       COUNT(*) FROM table_men_usuario_menu
UNION ALL
SELECT 'DEPTO_MENU',         COUNT(*) FROM table_men_departamento_menu;

-- ------------------------------------------------------------
-- 6) CONFIRMAR LA TRANSACCIÓN
-- ------------------------------------------------------------
-- Si la verificación del punto 5 se ve bien:
--   COMMIT;
-- Si algo salió mal:
--   ROLLBACK;
-- ------------------------------------------------------------

COMMIT;