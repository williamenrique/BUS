-- ============================================
-- Migrar iconos de FontAwesome a Lucide
-- ============================================

UPDATE table_men_menu SET menu_icono = 'home'          WHERE menu_nombre LIKE '%Inicio%' OR menu_nombre LIKE '%Home%';
UPDATE table_men_menu SET menu_icono = 'shopping-cart' WHERE menu_nombre LIKE '%Reporte Compra%' OR menu_nombre LIKE '%Compra%';
UPDATE table_men_menu SET menu_icono = 'receipt'       WHERE menu_nombre LIKE '%Venta%';
UPDATE table_men_menu SET menu_icono = 'truck'         WHERE menu_nombre LIKE '%Despacho%';
UPDATE table_men_menu SET menu_icono = 'bus'           WHERE menu_nombre LIKE '%Flota%';
UPDATE table_men_menu SET menu_icono = 'droplet'       WHERE menu_nombre LIKE '%Aceite%';
UPDATE table_men_menu SET menu_icono = 'user-plus'     WHERE menu_nombre LIKE '%Agregar usuario%' OR menu_nombre LIKE '%Usuario%';
UPDATE table_men_menu SET menu_icono = 'package'       WHERE menu_nombre LIKE '%Producto%';
UPDATE table_men_menu SET menu_icono = 'network'       WHERE menu_nombre LIKE '%Departamento%';
UPDATE table_men_menu SET menu_icono = 'shield-user'   WHERE menu_nombre LIKE '%Rol%';
UPDATE table_men_menu SET menu_icono = 'key'           WHERE menu_nombre LIKE '%Recuperar%';
UPDATE table_men_menu SET menu_icono = 'users'         WHERE menu_nombre LIKE '%Personal%';
UPDATE table_men_menu SET menu_icono = 'warehouse'     WHERE menu_nombre LIKE '%Inventario%';
UPDATE table_men_menu SET menu_icono = 'history'       WHERE menu_nombre LIKE '%Historial%';
UPDATE table_men_menu SET menu_icono = 'archive'       WHERE menu_nombre LIKE '%Bienes%';
UPDATE table_men_menu SET menu_icono = 'pencil'        WHERE menu_nombre LIKE '%Actualizar%';
UPDATE table_men_menu SET menu_icono = 'fuel'          WHERE menu_nombre LIKE '%Estacion%' OR menu_nombre LIKE '%Estación%';
UPDATE table_men_menu SET menu_icono = 'menu'          WHERE menu_nombre LIKE '%Menu%' OR menu_nombre LIKE '%Menú%';
UPDATE table_men_menu SET menu_icono = 'line-chart'    WHERE menu_nombre LIKE '%Data Vent%' OR menu_nombre LIKE '%Reporte%';
UPDATE table_men_menu SET menu_icono = 'clipboard-list'WHERE menu_nombre LIKE '%Orden%';
UPDATE table_men_menu SET menu_icono = 'users'         WHERE menu_nombre LIKE '%Cliente%';
UPDATE table_men_menu SET menu_icono = 'package'       WHERE menu_nombre LIKE '%Almacen%' OR menu_nombre LIKE '%Almacén%';
UPDATE table_men_menu SET menu_icono = 'banknote'      WHERE menu_nombre LIKE '%Caja%' OR menu_nombre LIKE '%Pago%';
UPDATE table_men_menu SET menu_icono = 'file-text'     WHERE menu_nombre LIKE '%Factura%';
UPDATE table_men_menu SET menu_icono = 'bar-chart'     WHERE menu_nombre LIKE '%Estadistica%' OR menu_nombre LIKE '%Estadística%';
UPDATE table_men_menu SET menu_icono = 'settings'      WHERE menu_nombre LIKE '%Configura%';

-- Fallback para los que queden sin migrar
UPDATE table_men_menu SET menu_icono = 'circle'
WHERE menu_icono IS NULL
   OR menu_icono = ''
   OR menu_icono LIKE 'fa%';