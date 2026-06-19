// Este código debe ir en tu archivo: src/js/function.requisicion.js
let tableRequisicion;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTables para la tabla de requisiciones (si existe)
    inicializarTablaRequisiciones();
    // Inicializar Select2 en los campos correspondientes
    inicializarSelects();

    // Cargar datos iniciales para los selects
    cargarDatosParaSelects();

    // Configurar los event listeners del formulario
    configurarEventListenersFormulario();

    // Verificar si se pasó un ID de requisición en la URL
    const idRequisicionUrl = document.getElementById('id_requisicion_url').value;
    if (idRequisicionUrl && idRequisicionUrl > 0) {
        // Ocultar el formulario de creación y cargar la requisición para aprobar
        const formCreacion = document.querySelector('.card-primary');
        if (formCreacion) {
            formCreacion.style.display = 'none';
        }
        fntLoadRequisicionParaAprobar(idRequisicionUrl); // Cargar la requisición para aprobar
    }
});

/**
 * Inicializa la DataTable para mostrar la lista de requisiciones.
 */
function inicializarTablaRequisiciones() {
    tableRequisicion = $('#tableRequisicion').DataTable({
        "aProcessing": true,
        "aServerSide": false, // La paginación será del lado del cliente
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": {
            "url": base_url + "Requisicion/getRequisiciones",
            "dataSrc": "data" // La data viene en la propiedad "data" del JSON
        },
        "columns": [
            { "data": "id_despacho" },
            { "data": "fecha_despacho" },
            { "data": "id_unidad" },
            { "data": "tipo_orden" },
            {
                "data": null,
                "render": function (data, type, row) {
                    return `${row.creador_nombre || ''} ${row.creador_apellido || ''}`;
                }
            },
            { "data": "estado_orden", "className": "text-center" },
            { "data": "acciones", "orderable": false, "className": "text-center" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "dom": "lfrtip" // Estructura simple para la tabla
    });
}

/**
 * Inicializa los plugins de Select2 en los campos del formulario.
 */
function inicializarSelects() {
    const config = {
        theme: 'bootstrap4',
        language: "es",
        width: '100%'
    };

    // Aplicar Select2 y configurar estilos para todos los selects
    ['listUnidad', 'listMecanico', 'listArticulo'].forEach(id => {
        $(`#${id}`).select2({
            ...config,
            placeholder: $(`#${id}`).data('placeholder') || 'Seleccione una opción'
        }).on('select2:open', function () {
            // Aplicar altura máxima y scroll al desplegable cuando se abre
            $('.select2-results__options').css({
                'max-height': '250px',
                'overflow-y': 'auto'
            });
            // Enfocar el campo de búsqueda
            setTimeout(() => {
                document.querySelector('.select2-search__field').focus();
            }, 50);
        }).next('.select2-container').find('.select2-selection').css({
            'min-height': 'calc(2.25rem + 2px)', // Altura estándar de Bootstrap 4 form-control
            'border': '1px solid #ced4da' // Borde estándar de Bootstrap 4 form-control
        });
    });
}

/**
 * Carga los datos para las unidades, mecánicos y artículos desde el backend.
 */
async function cargarDatosParaSelects() {
    try {
        // Reutilizamos el endpoint del controlador de Órdenes que ya nos da estos datos.
        const response = await fetch(base_url + 'Orden/getInitialData');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { flota, mecanicos, articulos } = result.data;

            populateSelect('listUnidad', flota, 'id_flota', item => `${item.id_unidad} - ${item.modelo_unidad}`, 'Buscar y seleccionar una unidad');
            // Usamos la cédula del mecánico como valor, según el nuevo flujo
            populateSelect('listMecanico', mecanicos, 'personal_cedula', item => `${item.personal_cedula} - ${item.personal_nombre} ${item.personal_apellido}`, 'Buscar y seleccionar un mecánico');
            populateSelect('listArticulo', articulos, 'id_producto', item => `${item.producto} (Stock: ${item.cant_producto})`, 'Buscar y seleccionar un artículo', item => ({ 'data-stock': item.cant_producto || 0 }));
        } else {
            notifi(result.message || 'No se pudieron cargar los datos.', 'error');
        }
    } catch (error) {
        console.error('Error en cargarDatosParaSelects:', error);
        notifi('Error de conexión al cargar datos del formulario.', 'error');
    }
}

/**
 * Función genérica para poblar un <select> con datos.
 */
function populateSelect(selectId, data, valueField, textFieldFn, defaultOptionText, dataAttributesFn = null) {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = ''; // Limpiar opciones existentes
    select.innerHTML = `<option value=""></option>`; // Opción vacía para el placeholder de Select2

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField] || ''; // Asegurarse de que el valor no sea undefined
            option.textContent = textFieldFn(item);

            // Esta es la parte clave que no se estaba ejecutando correctamente.
            if (dataAttributesFn) {
                const attributes = dataAttributesFn(item);
                for (const key in attributes) {
                    option.setAttribute(key, attributes[key]);
                }
            }
            select.appendChild(option);
        });
    }
    // Establecer el placeholder en el atributo data-placeholder del select
    $(select).data('placeholder', defaultOptionText);

    // Forzar la actualización de Select2 para que muestre el placeholder
    // y aplique los estilos si ya estaba inicializado.
    if ($(select).data('select2')) {
        $(select).trigger('change');
    }
}


/**
 * Configura todos los listeners para los botones y el formulario de creación.
 */
function configurarEventListenersFormulario() {
    // Configurar el formulario de CREACIÓN de requisición (solo si existe)
    const formRequisicion = document.getElementById('formRequisicion');
    if (formRequisicion) {
        const btnAgregar = document.getElementById('btnAgregaArticulo');
        const cantidadInput = document.getElementById('txtCant');
        const tablaArticulos = document.getElementById('tblArticulosAgregados').querySelector('tbody');

        cantidadInput.addEventListener('input', validarCantidadRequisicion);
        btnAgregar.addEventListener('click', agregarArticuloATabla);

        tablaArticulos.addEventListener('click', function (e) {
            if (e.target.closest('.btn-eliminar-articulo')) {
                e.target.closest('tr').remove();
                actualizarResumenArticulos(); // CORRECCIÓN: Actualizar resumen al eliminar.
            }
        });

        // --- INICIO DE LA CORRECCIÓN ---
        // Mover los listeners aquí para que se activen al cargar la página.
        $('#listUnidad').on('select2:select', function (e) {
            const data = e.params.data;
            document.getElementById('resumenUnidad').textContent = data.text || 'No seleccionada';
        });

        $('#listMecanico').on('select2:select', function (e) {
            const data = e.params.data;
            const nombre = data.text.split(' - ')[1] || 'No seleccionado';
            document.getElementById('resumenMecanico').textContent = nombre;
        });
        // --- FIN DE LA CORRECCIÓN ---

        document.getElementById('btnCancel').addEventListener('click', () => {
            formRequisicion.reset();
            $('#listUnidad, #listMecanico, #listArticulo').val(null).trigger('change');
            tablaArticulos.innerHTML = '';
            cantidadInput.value = '';
            cantidadInput.disabled = true;
            document.getElementById('stockValidationRequisicion').textContent = '';
        });

        $('#listArticulo').on('select2:select', function (e) {
            const selectedData = e.params.data;
            fntGetArtRequisicion(selectedData);
        });

        formRequisicion.addEventListener('submit', async function (e) {
            e.preventDefault();

            const idUnidad = document.getElementById('listUnidad').value;
            const idMecanico = document.getElementById('listMecanico').value;

            if (!idUnidad || !idMecanico) {
                notifi('Debe seleccionar la Unidad y el Mecánico.', 'warning');
                return;
            }

            const articulos = [];
            tablaArticulos.querySelectorAll('tr').forEach(fila => {
                articulos.push({
                    id: fila.dataset.idArticulo,
                    // CORRECCIÓN: Asegurarse de que la cantidad sea un número
                    cantidad: parseFloat(fila.cells[2].textContent)
                });
            });

            if (articulos.length === 0) {
                notifi('Debe agregar al menos un artículo a la requisición.', 'warning');
                return;
            }

            actualizarResumenArticulos(); // Actualizar el resumen una última vez antes de enviar

            const formData = new FormData(formRequisicion);
            formData.append('articulos', JSON.stringify(articulos));

            const btnSubmit = document.getElementById('btnActionForm');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Guardando...`;

            try {
                const response = await fetch(base_url + 'Requisicion/setRequisicion', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    notifi(result.msg, 'success');
                    formRequisicion.reset();
                    $('#listUnidad, #listMecanico, #listArticulo').val(null).trigger('change');
                    tablaArticulos.innerHTML = '';
                    resetResumen(); // Limpiar el resumen después de guardar
                    if (tableRequisicion) {
                        tableRequisicion.ajax.reload();
                    }
                } else {
                    notifi(result.msg, 'error');
                }
            } catch (error) {
                console.error('Error al guardar requisición:', error);
                notifi('Error de conexión al guardar la requisición.', 'error');
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<i class="fas fa-save"></i> Guardar Requisición`;
            }
        });
    }

    // Configurar la sección de APROBACIÓN de requisición (solo si existe)
    const formAprobacion = document.getElementById('viewRequisicionUrl');
    if (formAprobacion) {
        formAprobacion.querySelector('#btnAprobarUrl').addEventListener('click', function () {
            fntAprobarRequisicion(this.dataset.idDespacho);
        });
    }
}

/**
 * Obtiene el stock de un artículo seleccionado y lo muestra.
 */
async function fntGetArtRequisicion(selectedData) {
    const cantidadInput = document.getElementById('txtCant');
    const stockValidationElement = document.getElementById('stockValidationRequisicion');
    cantidadInput.value = ''; // Limpiar cantidad al cambiar de artículo

    // Si no hay datos seleccionados (por ejemplo, al deseleccionar), deshabilitar y limpiar.
    if (!selectedData || !selectedData.id || selectedData.id === "0") {
        stockValidationElement.textContent = '';
        cantidadInput.disabled = true;
        return;
    }

    try {
        // CORRECCIÓN: Accedemos al stock directamente desde el objeto 'data' que nos da Select2.
        // Buscamos el elemento HTML original para leer el atributo 'data-stock'.
        const selectedOptionElement = $(selectedData.element);
        const stockDisponible = parseFloat(selectedOptionElement.data('stock')) || 0;

        document.getElementById('txtCant').dataset.stockDisponible = stockDisponible; // Guardar stock en el dataset del input
        stockValidationElement.textContent = `Stock disponible: ${stockDisponible}`;
        stockValidationElement.className = 'form-text text-muted';
        cantidadInput.disabled = false;
        validarCantidadRequisicion(); // Validar por si ya había un valor
    } catch (error) {
        console.error('Error obteniendo stock del artículo:', error);
        notifi('Error al obtener stock del artículo', 'error');
        stockValidationElement.textContent = 'Error al cargar stock.';
        stockValidationElement.className = 'form-text text-danger';
        cantidadInput.disabled = true;
    }
}

/**
 * Valida la cantidad ingresada en tiempo real contra el stock disponible.
 * Para requisiciones, permite solicitar más del stock disponible, pero muestra una advertencia.
 * Siempre retorna true si la cantidad es > 0, para permitir la adición a la requisición.
 */
function validarCantidadRequisicion() {
    const cantidadInput = document.getElementById('txtCant');
    const cantidad = parseInt(cantidadInput.value);
    const stockDisponible = parseFloat(cantidadInput.dataset.stockDisponible) || 0;
    const validationElement = document.getElementById('stockValidationRequisicion');

    if (isNaN(cantidad) || cantidad <= 0) {
        validationElement.textContent = `Stock disponible: ${stockDisponible}. Cantidad no válida.`;
        validationElement.className = 'form-text text-danger';
        return false;
    }

    if (cantidad > stockDisponible) {
        validationElement.textContent = `Stock insuficiente. Disponible: ${stockDisponible}. Se solicitará a Compras.`;
        validationElement.className = 'form-text text-warning'; // Advertencia, no error
        return true; // Permitir agregar a la requisición, es una solicitud
    }

    validationElement.textContent = `Stock disponible: ${stockDisponible}. Cantidad válida.`;
    validationElement.className = 'form-text text-success';
    return true;
}

/**
 * Agrega el artículo seleccionado a la tabla de visualización.
 */
function agregarArticuloATabla() {
    const selectArticulo = document.getElementById('listArticulo');
    const cantidadInput = document.getElementById('txtCant');
    const idArticulo = selectArticulo.value;
    const stockDisponible = parseFloat(cantidadInput.dataset.stockDisponible) || 0;
    const cantidad = parseFloat(cantidadInput.value);

    if (!idArticulo || idArticulo === "0") {
        notifi('Seleccione un artículo.', 'warning');
        return;
    }

    // Validar que la cantidad sea mayor a 0.
    // La función validarCantidadRequisicion ahora solo asegura que la cantidad sea > 0
    // y muestra el mensaje de stock, pero siempre retorna true si la cantidad es válida.
    if (isNaN(cantidad) || cantidad <= 0) {
        notifi('La cantidad solicitada debe ser mayor a 0.', 'warning');
        return;
    }

    // Evitar duplicados
    if (document.querySelector(`#tblArticulosAgregados tr[data-id-articulo="${idArticulo}"]`)) {
        notifi('Este artículo ya ha sido agregado. Edite la cantidad si desea modificarla.', 'info');
        return;
    }

    const stockBadge = stockDisponible <= 0 ? '<span class="badge badge-danger ml-2">Sin Stock</span>' : '';

    const nombreArticulo = selectArticulo.options[selectArticulo.selectedIndex].text.split(' (Stock:')[0];
    const tablaBody = document.getElementById('tblArticulosAgregados').querySelector('tbody');

    const fila = `
        <tr data-id-articulo="${idArticulo}">
            <td>${idArticulo}</td>
            <td>${nombreArticulo} ${stockBadge}</td>
            <td class="text-center">${cantidad}</td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-articulo" title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    tablaBody.insertAdjacentHTML('beforeend', fila);

    // Limpiar campos
    cantidadInput.value = '';
    cantidadInput.disabled = true;
    document.getElementById('stockValidationRequisicion').textContent = '';
    $('#listArticulo').val(null).trigger('change');
    actualizarResumenArticulos(); // CORRECCIÓN: Actualizar resumen al agregar.
}
// --- INICIO DE LA CORRECCIÓN ---
/**
 * Actualiza el conteo de artículos y unidades en la tarjeta de resumen.
 */
function actualizarResumenArticulos() {
    const tablaBody = document.getElementById('tblArticulosAgregados')?.querySelector('tbody');
    if (!tablaBody) return; // Salir si la tabla no existe

    const filas = tablaBody.querySelectorAll('tr');
    const totalArticulos = filas.length;
    let totalUnidades = 0;

    filas.forEach(fila => {
        totalUnidades += parseFloat(fila.cells[2].textContent) || 0;
    });

    const resumenTotalArticulosElem = document.getElementById('resumenTotalArticulos');
    const resumenTotalUnidadesElem = document.getElementById('resumenTotalUnidades');

    if (resumenTotalArticulosElem) resumenTotalArticulosElem.textContent = totalArticulos;
    if (resumenTotalUnidadesElem) resumenTotalUnidadesElem.textContent = totalUnidades;
}

/**
 * Resetea la tarjeta de resumen a su estado inicial.
 */
function resetResumen() {
    document.getElementById('resumenUnidad').textContent = 'No seleccionada';
    document.getElementById('resumenMecanico').textContent = 'No seleccionado';
    actualizarResumenArticulos(); // Esto pondrá los contadores de artículos en 0
}
// --- FIN DE LA CORRECCIÓN ---

/**
 * Muestra los detalles de una requisición en un modal.
 * @param {number} idDespacho - El ID del despacho (que es el id_despacho_fk de la requisición).
 */
async function fntViewRequisicion(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/getRequisicionDetails/' + idDespacho);
        if (!response.ok) throw new Error('Error en la respuesta del servidor.');

        const result = await response.json();

        if (result.success) {
            const req = result.data;

            // Poblar los campos del modal con los datos de la requisición
            document.getElementById('modalViewIdRequisicion').textContent = req.id_despacho_flujo; // Mostrar el ID de despacho
            document.getElementById('modalViewFechaRequisicion').textContent = req.fecha_requisicion_formatted;
            document.getElementById('modalViewUnidad').textContent = `${req.id_unidad} - ${req.modelo_unidad}`;
            document.getElementById('modalViewTipoOrden').textContent = req.tipo_orden;
            document.getElementById('modalViewStatusRequisicion').innerHTML = req.status_display; // Usar innerHTML para el badge

            document.getElementById('modalViewJefePatio').textContent = req.jefe_patio_nombre;
            document.getElementById('modalViewMecanico').textContent = req.mecanico_nombre || req.mecanico_cedula;

            // --- INICIO DE LA CORRECCIÓN ---
            // Mostrar los datos del operador y despachador final si la orden ya fue despachada (estado 3)
            const operadorFinalContainer = document.getElementById('modalOperadorFinalContainer');
            const despachadorFinalContainer = document.getElementById('modalDespachadorFinalContainer');

            if (req.status_requisicion == 3) {
                document.getElementById('modalViewOperadorFinal').textContent = req.operador_final || 'N/A';
                document.getElementById('modalViewDespachadorFinal').textContent = req.despachador_final || 'N/A';
                operadorFinalContainer.style.display = 'list-item';
                despachadorFinalContainer.style.display = 'list-item';
            } else {
                operadorFinalContainer.style.display = 'none';
                despachadorFinalContainer.style.display = 'none';
            }
            // --- FIN DE LA CORRECCIÓN ---

            document.getElementById('modalViewDiagnostico').textContent = req.diagnostico || 'Sin observaciones.';

            // Poblar la tabla de artículos
            const tablaArticulosBody = document.getElementById('modalViewTablaArticulosReq');
            tablaArticulosBody.innerHTML = ''; // Limpiar elementos anteriores

            if (req.articulos && req.articulos.length > 0) {
                req.articulos.forEach(articulo => {
                    const isOutOfStock = parseFloat(articulo.stock_actual) < parseFloat(articulo.cant_despacho);
                    const nameHTML = isOutOfStock
                        ? `<a href="${base_url}Producto/producto?id_producto=${articulo.id_producto}" class="text-danger font-weight-bold" title="Haga clic para agregar stock a este artículo">${articulo.producto} (${articulo.present_producto}) <i class="fas fa-external-link-alt fa-xs ml-1"></i></a>`
                        : `${articulo.producto} (${articulo.present_producto})`;
                    const stockStatus = !isOutOfStock 
                        ? `<span class="badge badge-success">Hay Stock (Disp: ${articulo.stock_actual})</span>`
                        : `<span class="badge badge-danger">Sin Stock (Disp: ${articulo.stock_actual})</span>`;
                    const row = `
                        <tr>
                            <td>${nameHTML}</td>
                            <td class="text-center">${articulo.cant_despacho}</td>
                            <td class="text-center">${stockStatus}</td>
                        </tr>
                    `;
                    tablaArticulosBody.insertAdjacentHTML('beforeend', row);
                });
            } else {
                tablaArticulosBody.innerHTML = '<tr><td colspan="3" class="text-center">No hay artículos solicitados.</td></tr>';
            }

            // Mostrar el modal
            $('#modalViewRequisicion').modal('show');

        } else {
            notifi(result.msg, 'error');
        }
    } catch (error) {
        console.error('Error al ver requisición:', error);
        notifi('Error al cargar los detalles de la requisición.', 'error');
    }
}

/**
 * Carga dinámicamente los detalles de una requisición en la sección de aprobación.
 * @param {number} idDespacho - El ID del despacho a cargar.
 */
async function fntLoadRequisicionParaAprobar(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/getRequisicionDetails/' + idDespacho);
        if (!response.ok) throw new Error('Error al cargar los datos de la requisición.');

        const result = await response.json();
        if (result.success) {
            const req = result.data;
            const container = document.getElementById('viewRequisicionUrl');

            // Llenar los campos de la tarjeta de detalles
            container.querySelector('#viewIdRequisicionUrl').textContent = req.id_despacho_flujo; // Mostrar el ID de despacho
            container.querySelector('#viewFechaRequisicion').textContent = req.fecha_requisicion_formatted;
            container.querySelector('#viewUnidad').textContent = `${req.id_unidad} - ${req.modelo_unidad}`;
            container.querySelector('#viewTipoOrden').textContent = req.tipo_orden;
            container.querySelector('#viewStatusRequisicion').innerHTML = req.status_display;
            container.querySelector('#viewJefePatio').textContent = req.jefe_patio_nombre;
            container.querySelector('#viewMecanico').textContent = req.mecanico_cedula;
            container.querySelector('#viewDiagnostico').textContent = req.diagnostico || 'N/A';

            // Llenar tabla de artículos
            const tablaBody = container.querySelector('#viewTablaArticulosReq');
            tablaBody.innerHTML = '';
            if (req.articulos && req.articulos.length > 0) {
                req.articulos.forEach(articulo => {
                    const isOutOfStock = parseFloat(articulo.stock_actual) < parseFloat(articulo.cant_despacho);
                    const nameHTML = isOutOfStock
                        ? `<a href="${base_url}Producto/producto?id_producto=${articulo.id_producto}" class="text-danger font-weight-bold" title="Haga clic para agregar stock a este artículo">${articulo.producto} <i class="fas fa-external-link-alt fa-xs ml-1"></i></a>`
                        : `${articulo.producto}`;
                    const stockStatus = !isOutOfStock 
                        ? `<span class="badge badge-success">Hay Stock (Disp: ${articulo.stock_actual})</span>`
                        : `<span class="badge badge-danger">Sin Stock (Disp: ${articulo.stock_actual})</span>`;
                    tablaBody.innerHTML += `
                        <tr>
                            <td>${nameHTML}</td>
                            <td class="text-center">${articulo.cant_despacho}</td>
                            <td class="text-center">${stockStatus}</td>
                        </tr>
                    `;
                });
            } else {
                tablaBody.innerHTML = '<tr><td colspan="3" class="text-center">No hay artículos.</td></tr>';
            }

            // Configurar botones
            const btnAprobar = container.querySelector('#btnAprobarUrl');
            const btnEnProceso = container.querySelector('#btnEnProcesoUrl');
            btnAprobar.dataset.idDespacho = idDespacho;

            //const btnEnProceso = container.querySelector('#btnEnProcesoUrl');
            if (btnEnProceso) {
                btnEnProceso.onclick = () => fntNotificarEnProcesoReq(idDespacho);
            }

            // Lógica para habilitar/deshabilitar el botón de aprobar
            let canApprove = true;
            let stockWarningMessage = '';
            if (req.articulos && req.articulos.length > 0) {
                req.articulos.forEach(articulo => {
                    if (articulo.stock_actual < articulo.cant_despacho) {
                        canApprove = false;
                        stockWarningMessage = 'No se puede aprobar: Hay artículos con stock insuficiente.';
                    }
                });
            }

            btnAprobar.disabled = !canApprove;
            if (!canApprove) notifi(stockWarningMessage, 'warning');

            // Mostrar la sección y ocultar el botón de "Ocultar" si no es necesario
            container.style.display = 'block';
            container.querySelector('#btnOcultarUrl').onclick = () => container.style.display = 'none';

            // Desplazarse a la sección
            container.scrollIntoView({ behavior: 'smooth' });

        } else {
            notifi(result.msg, 'error');
        }
    } catch (error) {
        console.error('Error en fntLoadRequisicionParaAprobar:', error);
        notifi('No se pudieron cargar los detalles para aprobación.', 'error');
    }
}

/**
 * Envía la solicitud para aprobar la requisición.
 * @param {number} idDespacho - El ID del despacho a aprobar.
 */
async function fntAprobarRequisicion(idDespacho) {
    const result = await Swal.fire({
        title: '¿Aprobar Requisición?',
        text: "Esta acción notificará a Almacén para que prepare el despacho. ¿Continuar?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('id_despacho_aprobar', idDespacho);
        const response = await fetch(base_url + 'Requisicion/aprobarRequisicion', { method: 'POST', body: formData });
        const res = await response.json();
        notifi(res.msg, res.success ? 'success' : 'error');
        if (res.success) {
            // Notificar automáticamente a Operaciones que fue aprobada
            const paramsNotif = new URLSearchParams({ id_despacho: idDespacho, tipo: 'aprobada' });
            await fetch(base_url + 'Orden/notificarOperaciones', { method: 'POST', body: paramsNotif });
            document.getElementById('viewRequisicionUrl').style.display = 'none';
            if (typeof loadAllNotifications === 'function') loadAllNotifications();
            tableRequisicion.ajax.reload();
        }
    }
}

async function fntNotificarEnProcesoReq(idDespacho) {
    const result = await Swal.fire({
        title: 'Notificar a Operaciones',
        text: `¿Desea notificar a Operaciones que la Requisición #${idDespacho} está en proceso de compra?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, notificar',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;
    try {
        const params = new URLSearchParams({ id_despacho: idDespacho });
        const response = await fetch(base_url + 'Orden/notificarEnProceso', { method: 'POST', body: params });
        const data = await response.json();
        notifi(data.message, data.success ? 'success' : 'error');
    } catch (error) {
        notifi('Error al enviar la notificación.', 'error');
    }
}

/**
 * Prepara y envía los datos para generar el PDF de la requisición.
 * @param {number} idDespacho - El ID del despacho a imprimir.
 */
async function fntImprimirRequisicion(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/generarReporteRequisicion/' + idDespacho);
        if (!response.ok) {
            throw new Error('Error al obtener los datos para el reporte.');
        }
        const result = await response.json();

        if (result.success) {
            // Crear un formulario oculto para enviar los datos por POST
            const form = document.createElement('form');
            form.method = 'POST';
            // La URL apunta al nuevo script PHP que genera el PDF
            form.action = base_url + 'data/almacen/requisicion.php';
            form.target = '_blank'; // Abrir en una nueva pestaña

            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'reporteData';
            hiddenField.value = JSON.stringify(result.data);

            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

        } else {
            notifi(result.message || 'No se pudieron cargar los datos para el reporte.', 'error');
        }
    } catch (error) {
        console.error('Error en fntImprimirRequisicion:', error);
        notifi('Error de conexión al generar el reporte.', 'error');
    }
}