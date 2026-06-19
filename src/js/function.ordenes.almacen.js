// Versión simplificada del archivo JavaScript para personal de almacén
// Solo incluye funciones de visualización y búsqueda de órdenes

let tblOrdenes; // Variable para la instancia de DataTable

document.addEventListener('DOMContentLoaded', function () {
    cargarDatosIniciales();
    configurarEventListeners();
});

// Cargar datos iniciales
async function cargarDatosIniciales() {
    try {
        const response = await fetch(base_url + 'Orden/getInitialData');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            inicializarDataTable(); // Llamamos a inicializar la tabla de órdenes
            await actualizarProgresoOrdenes();
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        notifi('Error al cargar datos iniciales', 'error');
    }
}

function configurarEventListeners() {
    // Evento para búsqueda
    document.getElementById('formBuscarDesp').addEventListener('submit', buscarOrdenes);
}

// Formatear fecha
function formatFecha(fecha) {
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}

// ========== PROGRESS BAR ==========

async function actualizarProgresoOrdenes() {
    try {
        const response = await fetch(base_url + 'Orden/getMonthlyStats');
        if (!response.ok) throw new Error('Error al obtener estadísticas mensuales.');

        const result = await response.json();
        if (result.success) {
            const { current_orders, target_orders, percentage } = result.data;

            const percentageEl = document.getElementById('progressPercentage');
            const fillEl = document.getElementById('progressBarFill');
            const currentEl = document.getElementById('progressCurrent');

            if (percentageEl) percentageEl.textContent = `${percentage}%`;
            if (fillEl) fillEl.style.width = `${percentage}%`;
            if (currentEl) currentEl.textContent = `${current_orders} órdenes`;
        }
    } catch (error) {
        console.error('Error actualizando progreso de órdenes:', error);
    }
}

// ========== GESTIÓN DE ÓRDENES ==========

// Inicializar DataTable
function inicializarDataTable() {
    if (tblOrdenes) {
        tblOrdenes.destroy();
    }

    tblOrdenes = $('#tblOrdenes').DataTable({
        "processing": true, // Muestra el indicador de "Cargando..."
        "serverSide": false, // Paginación del lado del cliente
        "autoWidth": false,
        language: {
            url: base_url + 'src/plugins/js/es_es.json'
        },
        "ajax": {
            "url": base_url + "Orden/getOrdenes",
            "dataSrc": "data" // Indicamos que los datos están en el array 'data'
        },
        "columns": [
            { "data": "id_despacho" },
            { "data": "fecha_aprobacion" },
            {
                "data": null, "render": function (data, type, row) {
                    return `${row.id_unidad} - ${row.modelo_unidad}`;
                }
            },
            {
                "data": "estado_orden", "className": "text-center", "render": function (data, type, row) {
                    return formatEstadoOrden(data);
                }
            },
            { "data": "creador_nombre" },
            {
                "data": "total_articulos", "className": "text-center", "render": function (data, type, row) {
                    return `<span class="badge badge-info">${data} artículos</span>`;
                }
            },
            {
                "data": null, "orderable": false, "className": "text-center", "render": function (data, type, row) {
                    return getOrdenActionButtons(row);
                }
            }
        ],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
        order: [[0, 'desc']],
        "responsive": true,
        "bDestroy": true,
        dom: 'lBfrtip', // Estructura DOM para AdminLTE
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-info' }
        ]
    });

    // Aplicar filtro por defecto para almacén
    tblOrdenes.on('init.dt', function () {
        let defaultFilter = '';
        if (userRole === 'ALMACEN') {
            defaultFilter = 'Aprobada';
        }

        if (defaultFilter) {
            tblOrdenes.column(3).search(defaultFilter, true, false).draw(); // Columna 3 es "ESTADO"
        }
    });
}

function formatEstadoOrden(estado) {
    let badgeClass = 'badge-secondary';
    let statusText = 'Desconocido';

    switch (parseInt(estado)) {
        case 1:
            badgeClass = 'badge-warning';
            statusText = 'Requisición';
            break;
        case 2:
            badgeClass = 'badge-info';
            statusText = 'Aprobada';
            break;
        case 3:
            badgeClass = 'badge-success';
            statusText = 'Despachada';
            break;
        case 4:
            badgeClass = 'badge-danger';
            statusText = 'Rechazada';
            break;
    }
    return `<span class="badge ${badgeClass}">${statusText}</span>`;
}

function getOrdenActionButtons(row) {
    return `<div class="btn-group">${row.acciones}</div>`; // The actions are now rendered by the server
}

// Buscar órdenes
async function buscarOrdenes(e) {
    e.preventDefault();

    try {
        const formData = new FormData(document.getElementById('formBuscarDesp'));
        const response = await fetch(base_url + 'Orden/getBuscarOrden', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        const searchResultsContainer = document.getElementById('searchResultsContainer');
        searchResultsContainer.innerHTML = ''; // Limpiar resultados anteriores

        if (result.success && result.data.length > 0) {
            result.data.forEach(orden => {
                const resultItem = `
                    <div onclick="fntViewOrden(${orden.id_despacho})" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Orden #${orden.id_despacho}</h5>
                            <small>${orden.fecha_despacho}</small>
                        </div>
                        <p class="mb-1 small">
                            <i class="fas fa-bus-alt mr-1"></i> ${orden.id_unidad} - ${orden.modelo_unidad}<br>
                            <i class="fas fa-user-tie mr-1"></i> ${orden.operador_nombre}
                        </p>
                    </div>
                `;
                // Para Bootstrap, es mejor usar un list-group
                if (searchResultsContainer.querySelector('.list-group') === null) {
                    searchResultsContainer.innerHTML = '<div class="list-group"></div>';
                }
                searchResultsContainer.querySelector('.list-group').insertAdjacentHTML('beforeend', resultItem);
            });
        } else {
            const noResultsMessage = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>${result.message || 'No se encontraron resultados.'}</p>
                </div>
            `;
            searchResultsContainer.innerHTML = noResultsMessage;
            if (result.message) {
                notifi(result.message, 'info');

            }
        }
    } catch (error) {
        console.error('Error buscando órdenes:', error);
        notifi('Error al realizar la búsqueda', 'error');
    }
}

// Ver detalles de orden en modal
async function fntViewOrden(idDespacho) {
    try {
        const response = await fetch(base_url + 'Orden/getOrdenDetalle/' + idDespacho);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();

        if (objData.success) {
            // Crear y mostrar modal con los detalles
            mostrarModalOrden(objData.data);
        } else {
            notifi(objData.message || 'No se pudo cargar el detalle.', 'error');
        }
    } catch (error) {
        console.error('Error obteniendo detalles de la orden:', error);
        notifi('Error al obtener detalles de la orden', 'error');
    }
}

// Mostrar modal con detalles de la orden
function mostrarModalOrden(orden) {
    // 1. Crear el HTML del modal
    const modalContent = `
        <div class="modal fade" id="ordenDetailModal" tabindex="-1" role="dialog" aria-labelledby="ordenDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="ordenDetailModalLabel">
                            Detalles de Orden #${orden.id_despacho}
                            <span class="ml-2">${formatEstadoOrden(orden.estado_orden)}</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #000;">
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

    // 2. Agregar el modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalContent);

    // 3. Inicializar y mostrar el modal de Bootstrap
    const modalElement = $('#ordenDetailModal');
    modalElement.modal('show');

    // 4. Limpiar el modal del DOM cuando se cierre para evitar conflictos
    modalElement.on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

// Generar PDF de orden
function fntImpDespacho(idDespacho) {
    // 1. Obtener los datos completos de la orden
    fetch(base_url + 'Orden/getOrdenDetalle/' + idDespacho)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                // 2. Si los datos se obtienen correctamente, generar el PDF
                generarPDFOrden(result.data);
            } else {
                notifi(result.message || 'No se pudieron obtener los datos para el reporte.', 'error');
            }
        })
        .catch(error => {
            console.error('Error al obtener datos para el PDF:', error);
            notifi('Error de conexión al generar el reporte.', 'error');
        });
}

function generarPDFOrden(reporteData) {
    // 3. Crear un formulario oculto para enviar los datos al script PHP
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/almacen/reportePDFdesp.php";
    form.target = '_blank'; // Abrir en una nueva pestaña

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    input.value = JSON.stringify(reporteData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function notifi(message, tipo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function showConfirm(title, text, icon = 'question', confirmButtonText = 'Aceptar', cancelButtonText = 'Cancelar') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText
    });
}