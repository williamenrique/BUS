-- ¿Qué usuario es WILL? (usuario_id = 2 según lo que me dijiste)
SELECT usuario_id, usuario_nick, usuario_departamento_id
FROM table_usuarios
WHERE usuario_nick = 'WILL';

-- ¿Qué menús tiene WILL asignados hoy?
SELECT um.id, um.usuario_id, um.menu_id, m.menu_nombre, m.menu_ruta,
       m.menu_padre_id, m.menu_orden, m.menu_estado
FROM table_men_usuario_menu um
INNER JOIN table_men_menu m ON m.menu_id = um.menu_id
WHERE um.usuario_id = 2
ORDER BY m.menu_orden, m.menu_id;

-- ¿Qué rutas están ligadas a esos menús?
SELECT r.ruta_id, r.menu_id, m.menu_nombre, r.patron, r.activa
FROM table_men_rutas r
INNER JOIN table_men_menu m ON m.menu_id = r.menu_id
WHERE r.menu_id IN (
    SELECT menu_id FROM table_men_usuario_menu WHERE usuario_id = 2
)
ORDER BY r.menu_id;