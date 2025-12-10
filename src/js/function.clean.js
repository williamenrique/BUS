/**
 * Archivo: function.cleanAlmacen.js
 * Descripción: Contiene la lógica para la página de mantenimiento de almacén,
 *              permitiendo la edición completa de los datos de un producto.
 */

document.addEventListener('DOMContentLoaded', function () {
    cargarDatosMantenimiento();
});

/**
 * Carga los datos iniciales para los selects de la página de mantenimiento.
 */
async function cargarDatosMantenimiento() {
    try {
        const response = await fetch(base_url + 'Clean/getInitialDat');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { productos, enlaces, proveedores, ubicaciones } = result.data;

            // Poblar selects
            populateSelect('listProductoStock', productos, 'id_producto', item => `${item.producto} (${item.enlace_producto})`, 'Seleccione un artículo para editar');
            populateSelect('listEnlace', enlaces, 'id_enlace_producto', 'enlace_producto', 'Seleccione un tipo');
            populateSelect('listProveedor', proveedores, 'id_proveedor', 'empresa_proveedor', 'Seleccione un proveedor');
            populateSelect('listUbicacion', ubicaciones, 'id_ubicacion', 'ubicacion', 'Seleccione una ubicación');

            // Inicializar Select2
            $('#listProductoStock').select2({ theme: 'default' });
            $('#listEnlace').select2({ theme: 'default' });
            $('#listProveedor').select2({ theme: 'default' });
            $('#listUbicacion').select2({ theme: 'default' });

            // Asignar eventos DESPUÉS de inicializar Select2 para garantizar compatibilidad
            $('#listProductoStock').on('change', getProductoForEdit);
            document.getElementById('formUpdateProducto').addEventListener('submit', handleUpdateFullProducto);

        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en cargarDatosMantenimiento:', error);
        notifi('Error de conexión al cargar datos.', 'error');
    }
}

/**
 * Obtiene los datos completos de un producto y los carga en el formulario de edición.
 */
async function getProductoForEdit() {
    const idProducto = document.getElementById('listProductoStock').value;
    const form = document.getElementById('formUpdateProducto');

    if (idProducto == 0) {
        form.reset();
        // Resetear selects de Select2
        $('#listEnlace, #listProveedor, #listUbicacion').val(0).trigger('change');
        // Desmarcar radio buttons
        form.querySelectorAll('input[name="optionsArticulo"]').forEach(radio => {
            radio.checked = false;
        });
        return;
    }

    try {
        const response = await fetch(base_url + 'Clean/getProducto/' + idProducto);
        const result = await response.json();
        if (result.success) {
            const producto = result.data;
            form.querySelector('#id_producto').value = producto.id_producto;
            form.querySelector('#txtArticulo').value = producto.producto;
            form.querySelector('#txtCantidad').value = producto.cant_producto;
            form.querySelector('#optionsPresentacion').value = producto.present_producto;

            // Asignar valor y luego notificar a Select2 para que actualice la UI
            $('#listEnlace').val(producto.id_enlace_producto).trigger('change');
            $('#listProveedor').val(producto.id_proveedor).trigger('change');
            $('#listUbicacion').val(producto.id_ubicacion).trigger('change');

            // Seleccionar el radio button correcto para el tipo de artículo
            form.querySelector(`input[name="optionsArticulo"][value="${producto.tag_producto}"]`).checked = true;

        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en getProductoForEdit:', error);
        notifi('Error al obtener los datos del producto.', 'error');
    }
}

/**
 * Maneja el envío del formulario para actualizar los datos completos del producto.
 * @param {Event} e - Evento del formulario.
 */
async function handleUpdateFullProducto(e) {
    e.preventDefault();
    const form = e.target;
    const idProducto = form.querySelector('#id_producto').value;

    if (idProducto == "0") {
        notifi('No se ha seleccionado un producto válido.', 'warning');
        return;
    }

    Swal.fire({
        title: 'Confirmar Actualización',
        text: `¿Está seguro de que desea guardar los cambios para este producto?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, guardar cambios',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData(form);
                const response = await fetch(base_url + 'Clean/updateFullProducto', {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();

                if (res.success) {
                    notifi(res.message, 'success');
                    form.reset();
                    $('#listProductoStock').val(0).trigger('change'); // Resetear select principal
                } else {
                    notifi(res.message, 'error');
                }
            } catch (error) {
                console.error('Error en handleUpdateFullProducto:', error);
                notifi('Error de conexión al actualizar el producto.', 'error');
            }
        }
    });
}

/**
 * Rellena un elemento <select> con datos.
 * @param {string} selectId - ID del elemento select.
 * @param {Array} data - Array de objetos con los datos.
 * @param {string} valueField - Nombre del campo para el `value` de la opción.
 * @param {string|Function} textField - Nombre del campo o función para el texto de la opción.
 * @param {string} defaultOptionText - Texto para la opción por defecto.
 */
function populateSelect(selectId, data, valueField, textField, defaultOptionText) {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = `<option value="0" selected>${defaultOptionText}</option>`;

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = typeof textField === 'function' ? textField(item) : item[textField];
            select.appendChild(option);
        });
    }
}