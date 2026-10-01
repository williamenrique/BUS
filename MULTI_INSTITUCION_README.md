# MULTI-INSTITUCIÓN — Documentación

## Fecha de implementación
2026-09-30

## Instituciones
- **ID 1:** SSLMTY (SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY)
- **ID 2:** TALLER

## Regla de asignación de institución
- Departamento **Sistema** (id 1) → Admin, ve todo
- Departamento **Taller** (id 8) → Institución 2 (Taller)
- Cualquier otro departamento → Institución 1 (SSLMTY)

## Tablas con `id_institucion`
- table_flota
- table_flota_kilometraje
- table_flota_aceite_historial
- table_flota_mantenimiento
- table_flota_status
- table_alm_producto
- table_alm_relacion_producto
- table_alm_despacho (+ numero_orden)
- table_alm_relacion_despacho
- table_compras_pendientes
- table_compras_costos

## Tablas nuevas
- table_instituciones
- table_men_rutas (sistema de menús)
- table_bienes_taller_inventario

## URLs por institución

### Flota
- SSLMTY: `flota`, `flota/statusaceite`, `flota/historialunidad/{id}`
- Taller: `flota/taller`, `flota/talleraceite`, `flota/tallerhistorial/{id}`

### Productos
- SSLMTY: `producto/producto`, `producto/historial`, `producto/inventario`
- Taller: `producto/productoTaller`, `producto/historialTaller`, `producto/inventarioTaller`

### Órdenes de Despacho
- SSLMTY: `orden/orden`
- Taller: `orden/ordenTaller`

### Compras
- SSLMTY: `compras/costos`
- Taller: `compras/costosTaller`

### Dashboard público
- `publico/movimientos` (con selector por sección)

## Correlativo de órdenes
- Cada institución tiene su propio `numero_orden`.
- El `id_despacho` (interno) sigue siendo único global.
- Al crear una orden:
  - SSLMTY: `numero_orden = MAX(numero_orden) + 1 WHERE id_institucion = 1`
  - Taller: `numero_orden = MAX(numero_orden) + 1 WHERE id_institucion = 2`

## Cómo agregar una nueva institución

### 1. BD
```sql
INSERT INTO table_instituciones (nombre, status) VALUES ('NUEVA INSTITUCIÓN', 1);
-- Supongamos id = 3