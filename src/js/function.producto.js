let tableProducto;
let tableHistory;
let tableInventario;

// =================================================================================
// INICIALIZACIÓN Y EVENTOS PRINCIPALES
// =================================================================================

document.addEventListener('DOMContentLoaded', function () {
    // Detecta en qué página estamos para ejecutar el código correspondiente
    if (document.getElementById('tableProducto_wrapper')) {
        cargarDatosIniciales().then(() => {
            // Verificar si hay un id_producto en la URL
            const urlParams = new URLSearchParams(window.location.search);
            const idProductoUrl = urlParams.get('id_producto');
            if (idProductoUrl) {
                // Pequeño retardo para asegurar que Select2 esté inicializado
                setTimeout(() => {
                    cargarStockForm(idProductoUrl);
                }, 300);
            }
        });
        configurarEventListeners();
        inicializarDataTable();
    } else if (document.getElementById('tableHistory')) {
        inicializarHistoryTable();
    } else if (document.getElementById('tableInventario_wrapper')) {
        inicializarInventarioTable();
    } else if (document.getElementById('formUpdateProducto')) {
        // La lógica para esta página ahora está en function.cleanAlmacen.js
    }
});

/*
 *Carga los datos iniciales para los selects de los formularios.
 */
async function cargarDatosIniciales() {
    try {
        const response = await fetch(base_url + 'Producto/getInitialData');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { enlaces, proveedores, ubicaciones, productos } = result.data;
            populateSelect('listEnlace', enlaces, 'id_enlace_producto', 'enlace_producto', 'Seleccione un tipo');
            populateSelect('listProveedor', proveedores, 'id_proveedor', 'empresa_proveedor', 'Seleccione un proveedor');
            populateSelect('listUbicacion', ubicaciones, 'id_ubicacion', 'ubicacion', 'Seleccione una ubicación');
            populateSelect('listArticuloExistente', productos, 'id_producto', item => `${item.producto} (${item.enlace_producto})`, 'Seleccione un artículo');
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en cargarDatosIniciales:', error);
        notifi('Error de conexión al cargar datos.', 'error');
    }
}

/**
 * Configura los event listeners para los formularios de la página de productos.
 */
function configurarEventListeners() {
    const formNewArticulo = document.getElementById('formNewArticulo');
    if (formNewArticulo) formNewArticulo.addEventListener('submit', setProducto);

    const formArticuloExistente = document.getElementById('formArticuloExistente');
    if (formArticuloExistente) formArticuloExistente.addEventListener('submit', updateProducto);

    const listArticuloExistente = document.getElementById('listArticuloExistente');
    if (listArticuloExistente) listArticuloExistente.addEventListener('change', getProducto);
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

    // Usar una opción vacía para que el placeholder de Select2 funcione correctamente
    select.innerHTML = `<option value=""></option>`;

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = typeof textField === 'function' ? textField(item) : item[textField];
            select.appendChild(option);
        });
    }

    // Inicializar o reinicializar Select2 con la configuración y estilos deseados
    const select2Instance = $(select).select2({
        placeholder: defaultOptionText,
        language: "es",
        theme: "bootstrap4",
    }).on('select2:open', function () {
        // Aplicar altura máxima y scroll al desplegable cuando se abre
        $('.select2-results__options').css({
            'max-height': '250px',
            'overflow-y': 'auto'
        });
    });

    // Forzar la altura y el borde para que coincida con los inputs de Bootstrap 4
    select2Instance.next('.select2-container').find('.select2-selection').css({
        'height': 'calc(2.25rem + 2px)',
        'border': '1px solid #ced4da'
    });
}

// =================================================================================
// FUNCIONES PARA CREAR Y ACTUALIZAR PRODUCTOS
// =================================================================================

/**
 * Envía el formulario para crear un nuevo producto.
 * @param {Event} e - Evento del formulario.
 */
async function setProducto(e) {
    e.preventDefault();
    const form = e.target;
    try {
        const formData = new FormData(form);
        const response = await fetch(base_url + 'Producto/setProducto', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            notifi(result.message, 'success');
            form.reset();
            if (tableProducto && typeof tableProducto.reload === 'function') {
                tableProducto.reload();
            }
            cargarDatosIniciales(); // Recargar selects por si hay nuevos productos
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en setProducto:', error);
        notifi('Error al guardar el producto.', 'error');
    }
}

/**
 * Envía el formulario para actualizar el stock de un producto existente.
 * @param {Event} e - Evento del formulario.
 */
async function updateProducto(e) {
    e.preventDefault();
    const form = e.target;
    try {
        const formData = new FormData(form);
        const response = await fetch(base_url + 'Producto/updateProducto', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            notifi(result.message, 'success');
            form.reset();
            document.getElementById('txtCantidadActual').value = '';
            if (tableProducto && typeof tableProducto.reload === 'function') {
                tableProducto.reload();
            }
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en updateProducto:', error);
        notifi('Error al actualizar el stock.', 'error');
    }
}

/**
 * Obtiene la cantidad de stock actual de un producto seleccionado.
 */
async function getProducto() {
    const idProducto = document.getElementById('listArticuloExistente').value;
    if (idProducto == 0) {
        document.getElementById('txtCantidadActual').value = '';
        return;
    }
    try {
        const response = await fetch(base_url + 'Producto/getProducto/' + idProducto);
        const result = await response.json();
        if (result.success) {
            document.getElementById('txtCantidadActual').value = result.data.cant_producto || 0;
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en getProducto:', error);
        notifi('Error al obtener datos del producto.', 'error');
    }
}

// =================================================================================
// GESTIÓN DE LA TABLA DE PRODUCTOS (DATATABLE)
// =================================================================================

/**
 * Inicializa la DataTable para mostrar la lista de productos.
 */
function inicializarDataTable() {
    console.log('Inicializando tabla de productos dinámica...');
    if (typeof initProductosDynamicTable !== 'undefined') {
        tableProducto = initProductosDynamicTable();
    } else {
        console.error('initProductosDynamicTable no está disponible. Verificar carga de DataTableRefactor.js');
    }
}

/**
 * Genera un PDF enviando datos a un script PHP.
 * @param {object} data - Los datos para el reporte.
 * @param {string} reportScript - El nombre del script PHP que genera el PDF.
 * @param {string} reportTitle - El título del reporte.
 */
function generarPDF(data, reportScript, reportTitle) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${base_url}data/${reportScript}`;
    form.target = '_blank';

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'reportData';
    dataInput.value = JSON.stringify({ title: reportTitle, data: data });
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera un reporte en PDF del inventario de productos.
 */
async function fntReporteProductosPDF() {
    await fntReporteInventarioPDF();
}

function reloadTable() {
    if (tableProducto && typeof tableProducto.reload === 'function') {
        tableProducto.reload();
    }
}

/**
 * Muestra una confirmación y elimina un producto.
 * @param {number} idProducto - ID del producto a eliminar.
 */
function fntDelProducto(idProducto) {
    Swal.fire({
        title: 'Eliminar Producto',
        text: "¿Realmente quiere eliminar este producto? Esta acción es irreversible.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id_producto', idProducto);
                const response = await fetch(base_url + 'Producto/delProducto', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.success) {
                    notifi(res.message, 'success');
                    if (tableProducto && typeof tableProducto.reload === 'function') {
                        tableProducto.reload();
                    }
                } else {
                    notifi(res.message, 'error');
                }
            } catch (error) {
                notifi('Error al intentar eliminar el producto.', 'error');
            }
        }
    });
}

/**
 * Inicializa la tabla dinámica para mostrar el inventario de productos.
 */
function inicializarInventarioTable() {
    console.log('Inicializando tabla de inventario dinámica...');
    if (typeof initInventarioDynamicTable !== 'undefined') {
        tableInventario = initInventarioDynamicTable();
    } else {
        console.error('initInventarioDynamicTable no está disponible. Verificar carga de DataTableRefactor.js');
    }
}

/**
 * Recarga la tabla de inventario.
 */
function reloadInventarioTable() {
    if (tableInventario && typeof tableInventario.reload === 'function') {
        tableInventario.reload();
    }
}

/**
 * Genera un reporte en PDF del inventario de productos.
 */
async function fntReporteInventarioPDF() {
    notifi('Generando reporte de inventario...', 'info');
    try {
        const response = await fetch(base_url + 'Clean/getProductosReporte');
        const result = await response.json();
        if (result.success) {
            generarPDF(result.data, 'almacen/reporte_inventario.php', 'Reporte de Inventario por Ubicación');
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error al generar el reporte PDF:', error);
        notifi('Error de conexión al generar el reporte.', 'error');
    }
}

// =================================================================================
// FUNCIONES PARA EL HISTORIAL DE PRODUCTOS
// =================================================================================

/**
 * Inicializa la DataTable para la página de historial de productos.
 */
function inicializarHistoryTable() {
    tableHistory = $('#tableHistory').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": { "url": base_url + "src/plugins/js/es_es.json" },
        "ajax": {
            "url": base_url + "Producto/getHistorySummary",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id_producto" },
            { "data": "producto" },
            { "data": "stock_actual" },
            { "data": "total_despachado" },
            { "data": "primer_despacho" },
            { "data": "ultimo_despacho" }
        ],
        "createdRow": function (row, data, dataIndex) {
            // Hacer que el nombre del producto sea un enlace para ver el historial
            let productNameCell = $(row).find('td:eq(1)');
            // Escapar comillas simples en el nombre del producto para evitar errores de sintaxis en el onclick
            const escapedProductName = data.producto.replace(/'/g, "\\'");

            productNameCell.html(`<a href="#" onclick="viewProductHistory(${data.id_producto}, '${escapedProductName}')" class="font-weight-bold">${data.producto}</a>`);
        },
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 15,
        "order": [[1, "asc"]]
    });
}

/**
 * Muestra la vista de detalle (timeline) para un producto específico.
 * @param {number} idProducto - ID del producto.
 * @param {string} nombreProducto - Nombre del producto.
 */
async function viewProductHistory(idProducto, nombreProducto, page = 1) {
    // Ocultar la vista de resumen y mostrar la de detalle con clases de Bootstrap
    document.getElementById('summaryView').classList.add('d-none');
    document.getElementById('detailView').classList.remove('d-none');
    document.getElementById('detailProductName').textContent = `Historial de: ${nombreProducto}`;

    const timelineContainer = document.getElementById('timelineContainer');
    const paginationContainer = document.getElementById('pagination-container');
    const emptyState = document.getElementById('timelineEmptyState');
    timelineContainer.innerHTML = ''; // Limpiar timeline anterior
    paginationContainer.innerHTML = ''; // Limpiar paginación anterior

    try {
        const response = await fetch(base_url + 'Producto/getDetailHistory/' + idProducto, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ page: page })
        });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            // Renderizar el timeline y la paginación
            renderTimeline(result.data, timelineContainer);
            renderPagination(result.pagination, idProducto, nombreProducto, paginationContainer);
        } else {
            // Si no hay datos, mostrar el mensaje de estado vacío
            timelineContainer.innerHTML = `
                <div id="timelineEmptyState" class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted"></i>
                    <p class="mt-3 text-muted">Este producto aún no ha sido despachado.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error cargando el historial detallado:', error);
        notifi('Error al cargar el historial del producto.', 'error');
    }
}

/**
 * Renderiza los items del historial en el contenedor del timeline.
 * @param {Array} items - Array de objetos con los datos del historial.
 * @param {HTMLElement} container - El elemento contenedor del timeline.
 */
function renderTimeline(items, container) {
    items.forEach(item => {
        // Estructura del timeline de AdminLTE
        const timelineItem = ` 
                    <div>
                        <i class="fas fa-truck bg-blue"></i>
                        <div class="timeline-item">
                            <span class="time"><i class="fas fa-clock"></i> ${item.fecha_despacho}</span>
                            <h3 class="timeline-header">
                                Despacho a la unidad <a href="#">${item.id_unidad} - ${item.modelo_unidad}</a>
                            </h3>
                            <div class="timeline-body">
                                Se despacharon <strong>${item.cant_despacho}</strong> unidades. 
                                <a href="#" onclick="fntViewOrden(${item.id_despacho})" class="btn btn-primary btn-xs">Ver Orden #${item.id_despacho}</a>
                                <br>
                                <small class="text-muted">Operador: ${item.operador_nombre}</small>
                            </div>
                        </div>
                    </div>
                `;
        container.insertAdjacentHTML('beforeend', timelineItem);
    });
    // Añadir el ícono de fin de timeline
    container.insertAdjacentHTML('beforeend', '<div><i class="fas fa-clock bg-gray"></i></div>');
}

/**
 * Renderiza los controles de paginación.
 * @param {object} paginationData - Objeto con `total_pages` y `current_page`.
 * @param {number} idProducto - ID del producto para las llamadas futuras.
 * @param {string} nombreProducto - Nombre del producto para las llamadas futuras.
 * @param {HTMLElement} container - El elemento contenedor de la paginación.
 */
function renderPagination(paginationData, idProducto, nombreProducto, container) {
    const total_pages = parseInt(paginationData.total_pages);
    const current_page = parseInt(paginationData.current_page);

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHTML = '<ul class="pagination pagination-sm m-0 float-right">';

    // Botón "Anterior"
    paginationHTML += `
        <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
            <a class="page-link pagination-btn" href="#" data-page="${current_page - 1}">&laquo;</a>
        </li>
    `;

    // Lógica para mostrar los números de página de forma inteligente
    const pageRange = 2; // Cuántas páginas mostrar alrededor de la actual
    let pagesToShow = [];

    for (let i = 1; i <= total_pages; i++) {
        if (i === 1 || i === total_pages || (i >= current_page - pageRange && i <= current_page + pageRange)) {
            pagesToShow.push(i);
        }
    }

    let lastPage = 0;
    for (const page of pagesToShow) {
        if (lastPage + 1 < page) {
            // Si hay un salto, añadir puntos suspensivos
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `
            <li class="page-item ${page === current_page ? 'active' : ''}">
                <a class="page-link pagination-btn" href="#" data-page="${page}">${page}</a>
            </li>`;
        lastPage = page;
    }

    // Botón "Siguiente"
    paginationHTML += `
        <li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
            <a class="page-link pagination-btn" href="#" data-page="${current_page + 1}">&raquo;</a>
        </li>
    `;

    paginationHTML += '</ul>';
    container.innerHTML = paginationHTML;

    // Agregar event listeners a los nuevos botones
    container.querySelectorAll('.pagination-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            if (page && !this.parentElement.classList.contains('disabled')) {
                viewProductHistory(idProducto, nombreProducto, page);
            }
        });
    });
}

/**
 * Muestra la vista de resumen (tabla) y oculta la de detalle.
 */
function showSummaryView() {
    document.getElementById('detailView').classList.add('d-none');
    document.getElementById('summaryView').classList.remove('d-none');
}

// =================================================================================
// FUNCIONES DEL MODAL DE ÓRDENES (MOVIDAS DESDE function.ordenes.js)
// =================================================================================

/**
 * Obtiene los detalles de una orden y muestra el modal.
 * @param {number} idDespacho - ID de la orden de despacho.
 */
async function fntViewOrden(idDespacho) {
    try {
        const response = await fetch(base_url + 'Orden/getOrdenDetalle/' + idDespacho);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();

        if (objData.success) {
            mostrarModalOrden(objData.data);
        } else {
            notifi(objData.message || 'No se pudo cargar el detalle de la orden.', 'error');
        }
    } catch (error) {
        console.error('Error obteniendo detalles de la orden:', error);
        notifi('Error al obtener detalles de la orden', 'error');
    }
}

/**
 * Construye y muestra el modal con los detalles de la orden.
 * @param {object} orden - Objeto con los datos de la orden.
 */
function mostrarModalOrden(orden) {
    // Reutilizamos la función de function.ordenes.js, pero la definimos aquí para que esté disponible
    const modalContent = `
        <div class="modal fade" id="ordenDetailModal" tabindex="-1" role="dialog" aria-labelledby="ordenDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ordenDetailModalLabel">Detalles de Orden #${orden.id_despacho}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Información de la Orden</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Fecha:</strong> ${orden.fecha_despacho}</li>
                                    <li><strong>Unidad:</strong> ${orden.id_unidad} - ${orden.marca_unidad} ${orden.modelo_unidad}</li>
                                    <li><strong>VIN:</strong> ${orden.vim_unidad || 'N/A'}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Personal</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Operador:</strong> ${orden.operador_nombre}</li>
                                    <li><strong>Mecánico:</strong> ${orden.mecanico_nombre}</li>
                                    <li><strong>Despachador:</strong> ${orden.despachador_nombre}</li>
                                </ul>
                            </div>
                        </div>
                        <hr>
                        <h5>Artículos Despachados</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Artículo</th>
                                        <th>Cantidad</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${orden.articulos && orden.articulos.length > 0 ?
            orden.articulos.map(art => `
                                            <tr>
                                                <td>${art.producto}</td>
                                                <td>${art.cant_despacho}</td>
                                                <td>${art.ubicacion || 'N/A'}</td>
                                            </tr>
                                        `).join('') :
            '<tr><td colspan="3" class="text-center">No hay artículos</td></tr>'
        }
                                </tbody>
                            </table>
                        </div>
                        ${orden.observacion ? `
                            <div class="mt-3">
                                <h5>Observaciones</h5>
                                <p class="text-muted">${orden.observacion}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button onclick="fntImpDespacho(${orden.id_despacho})" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir PDF</button>
                    </div>
                                </div>
                            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalContent);

    const modalElement = $('#ordenDetailModal');
    modalElement.modal('show');

    modalElement.on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

/**
 * Cierra un modal con una animación de salida.
 * @param {string} modalId - ID del modal a cerrar.
 */
function cerrarModal(modalId) {
    $('#' + modalId).modal('hide');
}

// Función para imprimir PDF de orden (necesaria para el modal)
function fntImpDespacho(idDespacho) {
    // Esta función ya debería existir en function.ordenes.js, pero la replicamos aquí para que el modal funcione
    window.open(base_url + 'data/almacen/reportePDFdesp.php?id=' + idDespacho, '_blank');
}

// =================================================================================
// FUNCIONES UTILITARIAS
// =================================================================================

/**
 * Muestra una notificación tipo "toast".
 * @param {string} msg - Mensaje a mostrar.
 * @param {string} tipo - Tipo de notificación (success, error, warning, info).
 */
function notifi(msg, tipo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: msg,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

/**
 * Selecciona un producto en el formulario de agregar stock, hace scroll y enfoca el input de cantidad.
 * @param {number} idProducto - ID del producto a seleccionar.
 */
function cargarStockForm(idProducto) {
    const select = document.getElementById('listArticuloExistente');
    if (!select) return;

    // Seleccionar el valor en el Select2
    $(select).val(idProducto).trigger('change');

    // Desplazamiento suave hacia la tarjeta de agregar stock
    const formContainer = document.getElementById('formArticuloExistente');
    if (formContainer) {
        formContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Enfocar el input de cantidad a agregar
    setTimeout(() => {
        const cantInput = document.getElementById('txtCantidadMas');
        if (cantInput) {
            cantInput.focus();
            cantInput.select();
        }
    }, 600);
}