# Plan de Migración - Tabla de Personal

Este plan detalla los cambios necesarios para migrar la gestión de **Personal** del sistema jQuery DataTables tradicional al sistema de tablas dinámicas personalizado (`DataTableRefactor.js`).

## Cambios Propuestos

### 1. Biblioteca de Tablas (`DataTableRefactor.js`)
#### [MODIFY] [DataTableRefactor.js](file:///c:/xampp/htdocs/BUS/src/js/DataTableRefactor.js)
- Agregar el método `initPersonalDynamicTable()` que configure las columnas del personal:
  - **Cédula**: Campo `personal_cedula`.
  - **Nombre Completo**: Combinación de `personal_nombre` y `personal_apellido`.
  - **Cargo**: Campo `cargo`.
  - **Teléfono**: Campo `personal_tlf` o "N/A".
  - **Email**: Campo `personal_email` o "N/A".
  - **Estado**: Renderización dinámica con badges de color según el estado:
    - `1`: Activo (Verde)
    - `0`: Inactivo (Rojo)
    - `2`: Vacaciones (Azul)
    - `3`: Reposo (Amarillo)
    - Se mantendrá la funcionalidad interactiva en el click del badge para llamar a `fntStatusPersonal(id)`.
  - **Acciones**: Botones de Ver (`fntViewPersonal`), Editar (`fntEditPersonal`) y Eliminar (`fntDelPersonal`).

---

### 2. Vista HTML/PHP (`personal.php`)
#### [MODIFY] [personal.php](file:///c:/xampp/htdocs/BUS/system/app/Views/Personal/personal.php)
- Reemplazar la estructura estática de la tabla `<table id="tablePersonal">` por el contenedor `<div id="tablePersonal_wrapper">`.
- Agregar estilos CSS específicos para deshabilitar las clases de DataTables del lado del cliente y forzar la visualización correcta de la nueva tabla dinámica.

---

### 3. Controlador PHP (`PersonalController.php`)
#### [MODIFY] [PersonalController.php](file:///c:/xampp/htdocs/BUS/system/app/Controllers/PersonalController.php)
- En el método `personal()`, agregar `DataTableRefactor.js` al arreglo de `'page_extra_scripts'`.
- En el método `getPersonal()`, simplificar la respuesta del backend para que devuelva la estructura de datos crudos formateada como `{ success: true, data: [...] }`. Toda la lógica de renderizado HTML se trasladará a la vista en JavaScript.

---

### 4. Controlador de Lógica JavaScript (`function.personal.js`)
#### [MODIFY] [function.personal.js](file:///c:/xampp/htdocs/BUS/src/js/function.personal.js)
- Cambiar la verificación de carga inicial `if (document.getElementById('tablePersonal'))` para que verifique el wrapper `tablePersonal_wrapper`.
- Reemplazar la inicialización de DataTable tradicional por el llamado a `initPersonalDynamicTable()`.
- Reemplazar los llamados de recarga `tablePersonal.ajax.reload()` por `tablePersonal.reload()`.

---

## Plan de Verificación

### Verificación Manual
1. Acceder a `http://sistema.test/personal`.
2. Verificar que el listado de personal cargue completamente y se muestre en el nuevo formato.
3. Probar la búsqueda en tiempo real, el ordenamiento por cabeceras y la paginación.
4. Registrar un nuevo miembro del personal y verificar que se agregue automáticamente a la tabla.
5. Modificar el estado de un personal haciendo click en su badge, rellenando el motivo de cambio de estado y verificando el cambio.
6. Editar la información de un personal y verificar los cambios en la tabla.
7. Eliminar lógicamente un miembro del personal y verificar que desaparezca de la lista.
