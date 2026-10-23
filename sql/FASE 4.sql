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