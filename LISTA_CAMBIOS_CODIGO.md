# LISTA DE CAMBIOS DE CÓDIGO - ELIMINACIÓN DE COLUMNAS REDUNDANTES EN table_alm_despacho

**Fecha:** 2026-10-02  
**Objetivo:** Eliminar las columnas `operador`, `mecanico`, `despachador` de `table_alm_despacho` y actualizar todo el código que las referencia para usar JOINs con `table_personal` a través de los IDs (`operador_id`, `mecanico_id`, `despachador_id`).

---

## RESUMEN DE COLUMNAS A ELIMINAR

| Columna | Tipo | Reemplazo |
|---------|------|-----------|
| `operador` | varchar(50) | `operador_id` → JOIN `table_personal` |
| `mecanico` | varchar(50) | `mecanico_id` → JOIN `table_personal` |
| `despachador` | varchar(50) | `despachador_id` → JOIN `table_personal` |

---

## ARCHIVOS QUE REQUIEREN MODIFICACIÓN

### 1. system/app/Models/OrdenModel.php

#### Método `insertDespacho()` (líneas ~115-135)
**CAMBIO:** Eliminar los parámetros de nombres y las columnas del INSERT
```php
// ANTES
public function insertDespacho(int $intUnidad, string $srtOper, string $srtMec, string $srtDesp, 
                                int $idOper, int $idMec, int $idDesp,
                                int $intIdUser, string $srtObs, string $strDate)

// DESPUÉS
public function insertDespacho(int $intUnidad, int $idOper, int $idMec, int $idDesp,
                                int $intIdUser, string $srtObs, string $strDate)
```
**Query INSERT:** Eliminar `operador, operador_id, mecanico, mecanico_id, despachador, despachador_id` → solo `operador_id, mecanico_id, despachador_id`

#### Método `updateDespacho()` (líneas ~137-155)
**CAMBIO:** Eliminar parámetros de nombres y columnas del UPDATE
```php
// ANTES
public function updateDespacho(int $idDespacho, int $intUnidad, string $srtOper, string $srtMec, string $srtDesp, 
                                int $idOper, int $idMec, int $idDesp,
                                string $srtObs, string $strDate)

// DESPUÉS
public function updateDespacho(int $idDespacho, int $intUnidad, int $idOper, int $idMec, int $idDesp,
                                string $srtObs, string $strDate)
```
**Query UPDATE:** Eliminar `operador = ?, mecanico = ?, despachador = ?` → solo actualizar los `_id`

#### Método `selectOrdenes()` (líneas ~220-250)
**ESTADO:** ✅ **YA USA JOINs CON COALESCE** - Solo verificar que funcione sin las columnas varchar
- Ya hace `LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal`
- Usa `COALESCE(CONCAT(p_op.personal_nombre...), d.operador)` → cambiar a solo `CONCAT(p_op.personal_nombre...)` o manejar NULL

#### Método `getListBuscarOrdenes()` (líneas ~255-310)
**ESTADO:** ✅ **YA USA JOINs CON COALESCE** - Igual que arriba

#### Método `selectDepacho()` (líneas ~320-360)
**ESTADO:** ✅ **YA USA JOINs CON COALESCE** - Igual que arriba

---

### 2. system/app/Controllers/OrdenController.php

#### Método `setOrdenD()` (líneas ~200-260)
**CAMBIO MAYOR:** Eliminar la obtención de nombres para las columnas varchar
```php
// ELIMINAR ESTE BLOQUE (líneas ~223-240):
$operadorData = $this->ordenModel->selectPersonal($idOper);
$mecanicoData = $this->ordenModel->selectPersonal($idMec);
$despachadorData = $this->ordenModel->selectPersonal($idDesp);

$strOper = !empty($operadorData) ? strtoupper(trim(...)) : 'SIN OPERADOR';
$strMec = !empty($mecanicoData) ? strtoupper(trim(...)) : 'SIN MECÁNICO';
$strDesp = !empty($despachadorData) ? strtoupper(trim(...)) : 'SIN DESPACHADOR';

// CAMBIAR LLAMADAS A:
$this->ordenModel->insertDespacho($intUnidad, $idOper, $idMec, $idDesp, $intIdUser, $srtObs, $strDate);
$this->ordenModel->updateDespacho($idDespacho, $intUnidad, $idOper, $idMec, $idDesp, $srtObs, $strDate);
```

---

### 3. system/app/Models/PublicoModel.php

#### Método `getDespachosPublic()` (líneas ~210-240)
**CAMBIO CRÍTICO:** Cambiar SELECT directo a JOINs
```sql
-- ANTES
SELECT 
    d.id_despacho, d.id_flota, f.id_unidad,
    d.operador, d.mecanico, d.despachador,  -- ← ELIMINAR ESTAS
    d.fecha_despacho, d.observacion, d.estado_orden, d.status_despacho
FROM table_alm_despacho d
LEFT JOIN table_flota f ON d.id_flota = f.id_flota
...

-- DESPUÉS
SELECT 
    d.id_despacho, d.id_flota, f.id_unidad,
    d.operador_id, d.mecanico_id, d.despachador_id,  -- ← USAR IDs
    CONCAT(p_op.personal_nombre, ' ', NULLIF(p_op.personal_apellido, '0')) AS operador_nombre,
    CONCAT(p_mec.personal_nombre, ' ', NULLIF(p_mec.personal_apellido, '0')) AS mecanico_nombre,
    CONCAT(p_desp.personal_nombre, ' ', NULLIF(p_desp.personal_apellido, '0')) AS despachador_nombre,
    d.fecha_despacho, d.observacion, d.estado_orden, d.status_despacho
FROM table_alm_despacho d
LEFT JOIN table_flota f ON d.id_flota = f.id_flota
LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
...
```

---

### 4. system/app/Controllers/PublicoController.php

#### Método `getMovimientos()` (líneas ~60-80)
**CAMBIO:** Usar los nombres desde los JOINs en lugar de las columnas directas
```php
// ANTES
'operador' => $d['operador'],
'mecanico' => $d['mecanico'],
'despachador' => $d['despachador'],

// DESPUÉS
'operador' => $d['operador_nombre'] ?? 'N/A',
'mecanico' => $d['mecanico_nombre'] ?? 'N/A',
'despachador' => $d['despachador_nombre'] ?? 'N/A',
```

---

### 5. system/app/Models/HomeModel.php

#### Método `getUltimasOrdenes()` (líneas ~152-165)
**CAMBIO:** Agregar JOIN con table_personal
```sql
-- ANTES
SELECT 
    d.id_despacho, d.numero_orden, d.fecha_despacho,
    d.operador,  -- ← ELIMINAR
    d.estado_orden, f.id_unidad, mo.modelo_unidad,
    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos
FROM table_alm_despacho d
INNER JOIN table_flota f ON d.id_flota = f.id_flota
INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
WHERE d.status_despacho = 1 AND d.id_institucion = ?
ORDER BY d.id_despacho DESC LIMIT $limit

-- DESPUÉS
SELECT 
    d.id_despacho, d.numero_orden, d.fecha_despacho,
    CONCAT(p_op.personal_nombre, ' ', NULLIF(p_op.personal_apellido, '0')) AS operador_nombre,
    d.estado_orden, f.id_unidad, mo.modelo_unidad,
    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos
FROM table_alm_despacho d
INNER JOIN table_flota f ON d.id_flota = f.id_flota
INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
WHERE d.status_despacho = 1 AND d.id_institucion = ?
ORDER BY d.id_despacho DESC LIMIT $limit
```

---

### 6. system/app/Models/ProductoModel.php

#### Método `getHistorialDespachoProducto()` (líneas ~275-295)
**CAMBIO:** Agregar JOIN con table_personal
```sql
-- ANTES
SELECT 
    d.id_despacho, d.fecha_despacho, rd.cant_despacho,
    f.id_unidad, m.modelo_unidad, d.operador AS operador_nombre  -- ← CAMBIAR
FROM table_alm_relacion_despacho rd
INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
INNER JOIN table_flota f ON d.id_flota = f.id_flota
INNER JOIN table_flota_modelo m ON f.id_modelo = m.id_modelo
LEFT JOIN table_personal p ON d.user_id = p.id_personal  -- ← ESTE JOIN ES INCORRECTO (user_id ≠ operador_id)
WHERE rd.id_producto = ? AND d.status_despacho = 1 AND d.id_institucion = ?
ORDER BY d.fecha_despacho DESC, d.id_despacho DESC LIMIT {$perPage} OFFSET {$offset}

-- DESPUÉS
SELECT 
    d.id_despacho, d.fecha_despacho, rd.cant_despacho,
    f.id_unidad, m.modelo_unidad,
    CONCAT(p_op.personal_nombre, ' ', NULLIF(p_op.personal_apellido, '0')) AS operador_nombre
FROM table_alm_relacion_despacho rd
INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
INNER JOIN table_flota f ON d.id_flota = f.id_flota
INNER JOIN table_flota_modelo m ON f.id_modelo = m.id_modelo
LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal  -- ← JOIN CORRECTO
WHERE rd.id_producto = ? AND d.status_despacho = 1 AND d.id_institucion = ?
ORDER BY d.fecha_despacho DESC, d.id_despacho DESC LIMIT {$perPage} OFFSET {$offset}
```

---

### 7. system/app/Views/Publico/movimientos.php

#### Tabla de movimientos (líneas ~538-540)
**CAMBIO:** Las variables ya vienen del controlador actualizado, solo verificar que use los nombres correctos
```php
// Verificar que use:
$mov['operador']  // ahora viene como operador_nombre del controlador
$mov['despachador']  // ahora viene como despachador_nombre del controlador
```

---

### 8. Reportes (data/almacen/) - VERIFICACIÓN

#### reporte.php y reportePDFdesp.php
**ESTADO:** ✅ **YA USAN NOMBRES DESDE JOINs** (`operador_nombre`, `mecanico_nombre`, `despachador_nombre`)
- Estos reportes reciben datos del controlador/modelo que ya hace los JOINs
- **NO REQUIEREN CAMBIOS** si el modelo/controlador devuelve los nombres correctos

---

## ORDEN DE EJECUCIÓN RECOMENDADO

### FASE 1: Actualizar Modelos (Backend)
1. `system/app/Models/OrdenModel.php` - Métodos `insertDespacho`, `updateDespacho`
2. `system/app/Models/PublicoModel.php` - Método `getDespachosPublic`
3. `system/app/Models/HomeModel.php` - Método `getUltimasOrdenes`
4. `system/app/Models/ProductoModel.php` - Método `getHistorialDespachoProducto`

### FASE 2: Actualizar Controladores
5. `system/app/Controllers/OrdenController.php` - Método `setOrdenD`
6. `system/app/Controllers/PublicoController.php` - Método `getMovimientos`

### FASE 3: Verificar Vistas y Reportes
7. `system/app/Views/Publico/movimientos.php` - Verificar variables
8. `data/almacen/reporte.php` - Verificar (probablemente OK)
9. `data/almacen/reportePDFdesp.php` - Verificar (probablemente OK)

### FASE 4: Ejecutar Script SQL
10. Ejecutar `system/core/DB/eliminar_columnas_redundantes_despacho.sql`

### FASE 5: Pruebas
11. Probar creación/edición de órdenes de despacho
12. Probar listado de órdenes
13. Probar búsqueda de órdenes
14. Probar reporte PDF de despacho
15. Probar módulo público de movimientos
16. Probar dashboard (últimas órdenes)
17. Probar historial de productos

---

## NOTAS TÉCNICAS IMPORTANTES

### Manejo de NULL en nombres
Cuando el personal no existe (ID NULL o eliminado), el JOIN devolverá NULL. Usar:
```sql
COALESCE(CONCAT(p_op.personal_nombre, ' ', NULLIF(p_op.personal_apellido, '0')), 'SIN OPERADOR') AS operador_nombre
```

### Foreign Keys existentes (SE MANTIENEN)
```sql
CONSTRAINT `fk_despacho_operador` FOREIGN KEY (`operador_id`) REFERENCES `table_personal` (`id_personal`) ON DELETE SET NULL
CONSTRAINT `fk_despacho_mecanico` FOREIGN KEY (`mecanico_id`) REFERENCES `table_personal` (`id_personal`) ON DELETE SET NULL
CONSTRAINT `fk_despacho_despachador` FOREIGN KEY (`despachador_id`) REFERENCES `table_personal` (`id_personal`) ON DELETE SET NULL
```

### Índices existentes (SE MANTIENEN)
```sql
KEY `fk_despacho_operador` (`operador_id`)
KEY `fk_despacho_mecanico` (`mecanico_id`)
KEY `fk_despacho_despachador` (`despachador_id`)
```

---

## VERIFICACIÓN POST-EJECUCIÓN

Después de ejecutar el script SQL, verificar:

1. **Estructura de tabla:**
   ```sql
   DESCRIBE table_alm_despacho;
   -- NO debe aparecer: operador, mecanico, despachador
   -- DEBE aparecer: operador_id, mecanico_id, despachador_id
   ```

2. **Foreign keys:**
   ```sql
   SHOW CREATE TABLE table_alm_despacho;
   -- Verificar que las 3 FKs siguen existiendo
   ```

3. **Datos de prueba:**
   - Insertar una orden nueva
   - Verificar que se guardan solo los IDs
   - Verificar que los listados muestran nombres correctos desde table_personal
   - Editar un nombre en table_personal y verificar que se refleja en las órdenes

---

## ARCHIVOS CREADOS EN ESTA TAREA

1. `system/core/DB/eliminar_columnas_redundantes_despacho.sql` - Script SQL de eliminación
2. `LISTA_CAMBIOS_CODIGO.md` - Este documento

---

**⚠️ ADVERTENCIA:** No ejecutar el script SQL hasta que TODOS los cambios de código estén implementados y probados en ambiente de desarrollo.