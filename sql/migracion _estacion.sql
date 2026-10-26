-- 1) Agregar columna id_estacion a table_es_venta
ALTER TABLE table_es_venta
    ADD COLUMN id_estacion INT(11) NULL AFTER id_user,
    ADD KEY idx_venta_estacion (id_estacion),
    ADD CONSTRAINT fk_venta_estacion
        FOREIGN KEY (id_estacion) REFERENCES table_es_estacion (id_estacion)
        ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE table_es_cierre
SET id_estacion = CASE WHEN id_cierre < 200 THEN 1 ELSE 2 END
WHERE id_estacion IS NULL;

UPDATE table_es_venta v
INNER JOIN table_es_cierre c ON c.id_cierre = v.id_cierre_diario
SET v.id_estacion = c.id_estacion
WHERE v.id_estacion IS NULL
  AND v.id_cierre_diario > 0;