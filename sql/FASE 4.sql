--sql
INSERT INTO table_men_rutas (menu_id, patron, activa)
VALUES (1101, 'flota', 1);
--Qué logra:

--/flota → matchea con flota → menu_id = 1101 → el guard exige 1101 → JZ-10 recibe 403.

--/flota/i/1 → matchea con flota/i (más largo) → 1101 → guard exige 1101 → JZ-10 recibe 403.

--Sobre tu duda de "local vs servidor real": el INSERT es idempotente en la práctica si lo pruebas antes. En el servidor real, simplemente ejecuta:

--sql
-- Verificar primero
SELECT * FROM table_men_rutas WHERE menu_id = 1101 AND patron = 'flota';
--Si devuelve 0 filas → ejecuta el INSERT.

--Si devuelve 1 fila → ya existe, no hagas nada.


-- para despachos 
--Paso 0 · SQL · Eliminar patrón duplicado de Historial Despachos (1104)
--Verificación previa (no borres nada aún)
--Ejecuta esto y pásame el resultado:

--sql
SELECT ruta_id, menu_id, patron, activa
FROM table_men_rutas
WHERE menu_id IN (1104, 1202)
   OR patron LIKE '%despachos%'
ORDER BY menu_id, ruta_id;
--Esperado: 2 filas (una con 1104 y otra con 1202, ambas con patron = 'despachos/i').

--Si el resultado es el esperado → borrar
--sql
DELETE FROM table_men_rutas
WHERE menu_id = 1104 AND patron = 'despachos/i';
--Verificación post-borrado
--sql
SELECT ruta_id, menu_id, patron, activa
FROM table_men_rutas
WHERE patron LIKE '%despachos%'
ORDER BY menu_id;
--Esperado: 1 fila (1202 → despachos/i).

--Pásame los 3 resultados (pre, DELETE, post) antes de aplicar el controlador.