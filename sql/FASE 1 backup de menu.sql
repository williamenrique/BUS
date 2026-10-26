-- 1) Crear tablas de respaldo con estructura + datos
CREATE TABLE IF NOT EXISTS backup_men_menu_20261022
    AS SELECT * FROM table_men_menu;

CREATE TABLE IF NOT EXISTS backup_men_rutas_20261022
    AS SELECT * FROM table_men_rutas;

CREATE TABLE IF NOT EXISTS backup_men_usuario_menu_20261022
    AS SELECT * FROM table_men_usuario_menu;

CREATE TABLE IF NOT EXISTS backup_men_departamento_menu_20261022
    AS SELECT * FROM table_men_departamento_menu;

-- 2) Verificar que se crearon
SELECT
    'backup_men_menu'                 AS tabla, COUNT(*) AS filas FROM backup_men_menu_20261022
UNION ALL
SELECT
    'backup_men_rutas',                COUNT(*) FROM backup_men_rutas_20261022
UNION ALL
SELECT
    'backup_men_usuario_menu',         COUNT(*) FROM backup_men_usuario_menu_20261022
UNION ALL
SELECT
    'backup_men_departamento_menu',    COUNT(*) FROM backup_men_departamento_menu_20261022;