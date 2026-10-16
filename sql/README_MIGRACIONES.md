# Migraciones necesarias en `busyaracuydata`

## 1. `table_notificaciones` — falta PK y AUTO_INCREMENT

Actualmente:
```sql
CREATE TABLE `table_notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  ...
);