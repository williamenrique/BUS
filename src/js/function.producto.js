let tableProducto;
let tableHistory;
let tableInventario;

// Institución activa leída del hidden input
const idInstitucionProducto = document.getElementById('id_institucion')?.value || 1;

// =================================================================================
// INICIALIZACIÓN Y EVENTOS PRINCIPALES
// =================================================================================

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('tableProducto')) {
        cargarDatosIniciales();
        configurarEventListeners();
        inicializarDataTable();
    } else if (document.getElementById('tableHistory')) {
        inicializarHistoryTable();
    } else if (document.getElementById('tableInventario')) {
        inicializarInventarioTable();
    } else if (document.getElementById('formUpdateProducto')) {
        // La lógica está en function.cleanAlmacen.js
    }
});

/**
 * Carga los datos iniciales para los selects.
 */
async function cargarDatosIniciales() {
    try {
        const response = await fetch(base_url + 'Producto/getInitialData?id_institucion=' + idInstitucionProducto);
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

function configurarEventListeners() {
    const formNewArticulo = document.getElementById('formNewArticulo');
    if (formNewArticulo) formNewArticulo.addEventListener('submit', setProducto);

    const formArticuloExistente = document.getElementById('formArticuloExistente');
    if (formArticuloExistente) formArticuloExistente.addEventListener('submit', updateProducto);

    const listArticuloExistente = document.getElementById('listArticuloExistente');
    if (listArticuloExistente) listArticuloExistente.addEventListener('change', getProducto);
}

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

// =================================================================================
// CREAR Y ACTUALIZAR PRODUCTOS
// =================================================================================

async function setProducto(e) {
    e.preventDefault();
    const form = e.target;
    try {
        const formData = new FormData(form);
        formData.append('id_institucion', idInstitucionProducto);

        const response = await fetch(base_url + 'Producto/setProducto', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            notifi(result.message, 'success');
            form.reset();
            tableProducto.ajax.reload();
            cargarDatosIniciales();
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en setProducto:', error);
        notifi('Error al guardar el producto.', 'error');
    }
}

async function updateProducto(e) {
    e.preventDefault();
    const form = e.target;
    try {
        const formData = new FormData(form);
        formData.append('id_institucion', idInstitucionProducto);

        const response = await fetch(base_url + 'Producto/updateProducto', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            notifi(result.message, 'success');
            form.reset();
            document.getElementById('txtCantidadActual').value = '';
            tableProducto.ajax.reload();
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error en updateProducto:', error);
        notifi('Error al actualizar el stock.', 'error');
    }
}

async function getProducto() {
    const idProducto = document.getElementById('listArticuloExistente').value;
    if (idProducto == 0) {
        document.getElementById('txtCantidadActual').value = '';
        return;
    }
    try {
        const response = await fetch(base_url + 'Producto/getProducto/' + idProducto + '?id_institucion=' + idInstitucionProducto);
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
// DATATABLE DE PRODUCTOS
// =================================================================================

function inicializarDataTable() {
    tableProducto = $('#tableProducto').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": { "url": base_url + "src/plugins/js/es_es.json" },
        "ajax": {
            "url": base_url + "Producto/getProductos?id_institucion=" + idInstitucionProducto,
            "type": "POST",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id_producto" },
            { "data": "producto" },
            { "data": "enlace_producto" },
            { "data": "empresa_proveedor" },
            { "data": "ubicacion" },
            { "data": "cant_producto", "className": "text-center" },
            { "data": null, "defaultContent": "", "orderable": false }
        ],
        "createdRow": function (row, data, dataIndex) {
            let stockCell = $(row).find('td:eq(5)');
            const stock = parseFloat(data.cant_producto);
            if (stock <= 0) {
                stockCell.html(`<span class="stock-badge stock-out">Sin Stock</span>`);
            } else if (stock < 10) {
                stockCell.html(`<span class="stock-badge stock-low">${stock} ${data.present_producto}</span>`);
            } else {
                stockCell.html(`<span class="stock-badge stock-high">${stock} ${data.present_producto}</span>`);
            }

            let actionsCell = $(row).find('td:eq(6)');
            actionsCell.addClass('text-center').html(`
                <button class="btn btn-danger btn-sm" onClick="fntDelProducto(${data.id_producto})" title="Eliminar">
                    <i class="far fa-trash-alt"></i>
                </button>
            `);
        },
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "dom": 'lfrtip'
    });
}

function reloadTable() {
    tableProducto.ajax.reload();
}

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
                formData.append('id_institucion', idInstitucionProducto);

                const response = await fetch(base_url + 'Producto/delProducto', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.success) {
                    notifi(res.message, 'success');
                    tableProducto.ajax.reload();
                } else {
                    notifi(res.message, 'error');
                }
            } catch (error) {
                notifi('Error al intentar eliminar el producto.', 'error');
            }
        }
    });
}

function generarPDF(data, reportScript, reportTitle, fechaInicio = null, fechaFin = null) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${base_url}data/${reportScript}`;
    form.target = '_blank';

    const reportData = { title: reportTitle, data: data };
    if (fechaInicio && fechaFin) {
        reportData.fechaInicio = fechaInicio;
        reportData.fechaFin = fechaFin;
    }

    const dataInput = document.createElement('input');
    dataInput.type = 'hidden';
    dataInput.name = 'reportData';
    dataInput.value = JSON.stringify(reportData);
    form.appendChild(dataInput);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

async function fntReporteProductosPDF() {
    await fntReporteInventarioPDF();
}

// =================================================================================
// INVENTARIO
// =================================================================================

function inicializarInventarioTable() {
    tableInventario = $('#tableInventario').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": { "url": base_url + "src/plugins/js/es_es.json" },
        "ajax": {
            "url": base_url + "Producto/getInventario?id_institucion=" + idInstitucionProducto,
            "type": "POST",
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id_producto", "title": "ID" },
            { "data": "producto", "title": "Artículo" },
            { "data": "enlace_producto", "title": "Tipo" },
            { "data": "empresa_proveedor", "title": "Proveedor" },
            { "data": "ubicacion", "title": "Ubicación" },
            { "data": "cant_producto", "title": "Stock", "className": "text-center" }
        ],
        "createdRow": function (row, data, dataIndex) {
            let stockCell = $(row).find('td:eq(5)');
            const stock = parseFloat(data.cant_producto);
            const presentacion = data.present_producto || 'Und';

            if (stock <= 0) {
                stockCell.html(`<span class="stock-badge stock-out">Sin Stock</span>`);
            } else if (stock < 10) {
                stockCell.html(`<span class="stock-badge stock-low">${stock} ${presentacion}</span>`);
            } else {
                stockCell.html(`<span class="stock-badge stock-high">${stock} ${presentacion}</span>`);
            }
        },
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 15,
        "order": [[0, "desc"]],
        "dom": 'lfrtip'
    });
}

function reloadInventarioTable() {
    if (tableInventario) {
        tableInventario.ajax.reload();
    }
}

async function fntReporteInventarioPDF() {
    notifi('Generando reporte de inventario...', 'info');
    try {
        const response = await fetch(base_url + 'Clean/getProductosReporte?id_institucion=' + idInstitucionProducto);
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
// HISTORIAL
// =================================================================================

function inicializarHistoryTable() {
    tableHistory = $('#tableHistory').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": { "url": base_url + "src/plugins/js/es_es.json" },
        "ajax": {
            "url": base_url + "Producto/getHistorySummary?id_institucion=" + idInstitucionProducto,
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
            let productNameCell = $(row).find('td:eq(1)');
            const escapedProductName = data.producto.replace(/'/g, "\\'");
            productNameCell.html(`<a href="#" onclick="viewProductHistory(${data.id_producto}, '${escapedProductName}')" class="font-weight-bold">${data.producto}</a>`);
        },
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 15,
        "order": [[1, "asc"]],
        "initComplete": function (settings, json) {
            setupHistoryControls();
        }
    });
}

async function viewProductHistory(idProducto, nombreProducto, page = 1) {
    document.getElementById('summaryView').classList.add('d-none');
    document.getElementById('detailView').classList.remove('d-none');
    document.getElementById('detailProductName').textContent = `Historial de: ${nombreProducto}`;

    const timelineContainer = document.getElementById('timelineContainer');
    const paginationContainer = document.getElementById('pagination-container');
    timelineContainer.innerHTML = '';
    paginationContainer.innerHTML = '';

    try {
        const response = await fetch(base_url + 'Producto/getDetailHistory/' + idProducto, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ page: page, id_institucion: idInstitucionProducto })
        });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            renderTimeline(result.data, timelineContainer);
            renderPagination(result.pagination, idProducto, nombreProducto, paginationContainer);
        } else {
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

function renderTimeline(items, container) {
    items.forEach(item => {
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
    container.insertAdjacentHTML('beforeend', '<div><i class="fas fa-clock bg-gray"></i></div>');
}

function renderPagination(paginationData, idProducto, nombreProducto, container) {
    const { total_pages, current_page } = paginationData;

    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let paginationHTML = '<ul class="pagination pagination-sm m-0 float-right">';

    paginationHTML += `
        <li class="page-item ${current_page === 1 ? 'disabled' : ''}">
            <a class="page-link pagination-btn" href="#" data-page="${current_page - 1}">&laquo;</a>
        </li>
    `;

    for (let i = 1; i <= total_pages; i++) {
        paginationHTML += `
            <li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link pagination-btn" href="#" data-page="${i}">${i}</a>
            </li>
        `;
    }

    paginationHTML += `
        <li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
            <a class="page-link pagination-btn" href="#" data-page="${current_page + 1}">&raquo;</a>
        </li>
    `;

    paginationHTML += '</ul>';
    container.innerHTML = paginationHTML;

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

function showSummaryView() {
    document.getElementById('detailView').classList.add('d-none');
    document.getElementById('summaryView').classList.remove('d-none');
}

// =================================================================================
// MODAL DE ÓRDENES
// =================================================================================

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

function mostrarModalOrden(orden) {
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

function cerrarModal(modalId) {
    $('#' + modalId).modal('hide');
}

function fntImpDespacho(idDespacho) {
    window.open(base_url + 'data/almacen/reportePDFdesp.php?id=' + idDespacho, '_blank');
}

// =================================================================================
// UTILIDADES
// =================================================================================

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

function setupHistoryControls() {
    if (document.getElementById('history-controls-row')) return;

    let wrapper = $('#tableHistory_wrapper');
    if (wrapper.length === 0) {
        wrapper = $('#tableHistory').closest('.dataTables_wrapper');
    }

    const controlsHtml = `
        <div id="history-controls-row" class="row mb-3 ml-1">
            <div class="col-md-3">
                <label>Desde:</label>
                <input type="date" id="txtFechaInicioHist" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
                <label>Hasta:</label>
                <input type="date" id="txtFechaFinHist" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-danger btn-sm btn-block" onclick="fntGenerarReporteHistorial()">
                    <i class="fas fa-file-pdf"></i> Generar PDF
                </button>
            </div>
        </div>
    `;
    wrapper.prepend(controlsHtml);
}

function fntGenerarReporteHistorial() {
    const fechaInicio = document.getElementById('txtFechaInicioHist').value;
    const fechaFin = document.getElementById('txtFechaFinHist').value;

    if (!fechaInicio || !fechaFin) {
        notifi("Debe seleccionar un rango de fechas para generar el reporte.", "warning");
        return;
    }

    fetch(base_url + 'Producto/getHistorySummaryByDateRange', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            fechaInicio: fechaInicio,
            fechaFin: fechaFin,
            id_institucion: idInstitucionProducto
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (result.data.length === 0) {
                    notifi("No hay datos para generar el reporte en el rango de fechas seleccionado.", "warning");
                    return;
                }
                generarPDF(result.data, 'almacen/historia_productos.php', 'Reporte de Historial de Productos', fechaInicio, fechaFin);
            } else {
                notifi(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error generando reporte:', error);
            notifi('Error de conexión al generar el reporte.', 'error');
        });
}