// Variables globales
let articulosAgregados = [];
let tblOrdenes; // Variable para la instancia de DataTable

document.addEventListener('DOMContentLoaded', function () {
    inicializarComponentes();
    cargarDatosIniciales();
    configurarEventListeners();
});

function setTitleByRole() {
    const titleElement = document.getElementById('form-title');
    if (userRole === 'JEFE DE TALLER' || userRole === 'JEFE DE PATIO') {
        titleElement.innerHTML = '<i class="fas fa-clipboard-list mr-2"></i> Nueva Requisición';
    } else {
        titleElement.innerHTML = '<i class="fas fa-truck-loading mr-2"></i> Nuevo Despacho';
    }
}
// Inicializar componentes y plugins
function inicializarComponentes() {
    // Establecer fecha actual por defecto
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('txtdate').value = today;
    document.getElementById('fechaDespacho').textContent = formatFecha(today);
}

// Cargar datos iniciales
async function cargarDatosIniciales() {
    try {
        const response = await fetch(base_url + 'Orden/getInitialData');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { flota, operadores, mecanicos, despachadores, articulos } = result.data;
            populateSelect('listUnidad', flota, 'id_flota', item => `${item.id_unidad} - ${item.modelo_unidad}`, 'Seleccione una unidad'); // Mantiene el ID de flota
            populateSelect('listOperador', operadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un operador'); // Cambiado a id_personal
            populateSelect('listMecanico', mecanicos, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un mecánico'); // Cambiado a id_personal
            populateSelect('listDespachador', despachadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un despachador'); // Cambiado a id_personal
            populateSelect('listArticulo', articulos, 'id_producto', item => `${item.producto} (Stock: ${item.cant_producto})`, 'Seleccione un artículo', item => ({ 'data-stock': item.cant_producto }));

            inicializarDataTable(); // Llamamos a inicializar la tabla de órdenes
            await actualizarProgresoOrdenes();
            setTitleByRole(); // Ajustar el título del formulario según el rol
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        notifi('Error al cargar datos iniciales', 'error');
    }
}

function configurarEventListeners() {
    // Evento para cambio de fecha
    document.getElementById('txtdate').addEventListener('change', function () {
        const fecha = this.value;
        document.getElementById('fechaDespacho').textContent = formatFecha(fecha);
        document.getElementById('strDate').value = fecha;
    });

    // Evento para validación de cantidad en tiempo real
    document.getElementById('txtCant').addEventListener('input', validarCantidad);

    // Evento para eliminar filas de la tabla
    document.getElementById('lista').addEventListener('click', function (e) {
        if (e.target.closest('.eliminarRow')) {
            const fila = e.target.closest('tr');
            const idArticulo = fila.querySelector('input[name="cod[]"]').value;

            // Eliminar de la lista de artículos agregados
            articulosAgregados = articulosAgregados.filter(art => art.id != idArticulo);

            fila.remove();
            actualizarEstadoBotonGenerar();
            actualizarResumenOrden();
        }
    });

    // Delegación de eventos para los selects que se cargan dinámicamente
    // Usamos jQuery para escuchar los eventos de select2
    $('#listUnidad').on('select2:select', function (e) {
        const idUnidad = e.params.data.id;
        if (idUnidad > 0) fntGetUnidad(idUnidad);
    });

    $('#listOperador').on('select2:select', function (e) {
        const nombre = e.params.data.id > 0 ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#operador").textContent = nombre;
    });

    $('#listMecanico').on('select2:select', function (e) {
        const nombre = e.params.data.id > 0 ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#mecanico").textContent = nombre;
    });

    $('#listDespachador').on('select2:select', function (e) {
        const nombre = e.params.data.id !== "0" ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#despachador").textContent = nombre;
    });

    $('#listArticulo').on('select2:select', function (e) {
        const idArticulo = e.params.data.id;
        if (idArticulo > 0) fntGetArt(idArticulo);
    });

    // Evento para agregar artículo
    document.getElementById('btnAgrega').addEventListener('click', agregarArticulo);

    // Evento para enviar formulario
    document.getElementById('formDespacho').addEventListener('submit', enviarFormulario);

    // Evento para búsqueda
    document.getElementById('formBuscarDesp').addEventListener('submit', buscarOrdenes);
}

// Formatear fecha
function formatFecha(fecha) {
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}


// Obtener datos de una unidad específica
async function fntGetUnidad(idUnidad) {
    try {
        const response = await fetch(base_url + 'Orden/getUnidad/' + idUnidad);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();
        if (objData.success) {
            document.querySelector("#id_unidad").textContent = objData.data.id_unidad || '-';
            document.querySelector("#vim_unidad").textContent = objData.data.vim_unidad || '-';
            document.querySelector("#marca_unidad").textContent = objData.data.marca_unidad || '-';
            document.querySelector("#modelo_unidad").textContent = objData.data.modelo_unidad || '-';
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error obteniendo unidad:', error);
        notifi('Error al obtener datos de la unidad', 'error');
    }
}

// Función genérica para poblar un select desde un array de datos JSON
function populateSelect(selectId, data, valueField, textFieldFn, defaultOptionText, dataAttributesFn) {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = `<option value="0">${defaultOptionText}</option>`;

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = textFieldFn(item);

            if (selectId === 'listArticulo') { // Si es el select de artículos
                option.setAttribute('data-stock', item.cant_producto);
            }

            if (dataAttributesFn) {
                const attributes = dataAttributesFn(item);
                for (const key in attributes) {
                    option.setAttribute(key, attributes[key]);
                }
            }
            select.appendChild(option);
        });

        // Asegurarse de que Select2 se aplique después de que el DOM se actualice
        const select2Instance = $(select).select2({
            placeholder: defaultOptionText,
            language: "es",
            theme: "bootstrap4",
            // containerCssClass: "form-control" // Esta línea puede causar problemas de estilo, la eliminamos por ahora.
        }).on('select2:open', function (e) {
            // --- INICIO DE LA MODIFICACIÓN ---
            // Aplicar altura máxima y scroll al desplegable cuando se abre
            $('.select2-results__options').css({
                'max-height': '250px',
                'overflow-y': 'auto'
            });
            // Usamos un setTimeout para asegurar que el campo de búsqueda esté listo antes de enfocarlo.
            // Esto resuelve conflictos de foco y permite la navegación con teclado.
            setTimeout(function () {
                document.querySelector('.select2-search__field').focus();
            }, 50); // Un pequeño retraso es suficiente
            // --- FIN DE LA MODIFICACIÓN ---
        });

        // Forzar la altura y el borde para que coincida con Bootstrap
        select2Instance.next('.select2-container').find('.select2-selection').css({
            'min-height': 'calc(2.25rem + 2px)', // Altura estándar de Bootstrap 4 form-control
            'border': '1px solid #ced4da' // Borde estándar de Bootstrap 4 form-control
        });
    }
}
// Obtener datos de un artículo específico
async function fntGetArt(idArt) {
    try {
        const response = await fetch(base_url + 'Orden/getArt/' + idArt);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();
        if (objData.success) {
            document.getElementById("cantDispo").value = objData.data.cant_producto;
            document.getElementById("stockDisponible").textContent = objData.data.cant_producto;
            document.getElementById("txtCant").value = "";
            validarCantidad();
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error obteniendo artículo:', error);
        notifi('Error al obtener datos del artículo', 'error');
    }
}

// Validar cantidad en tiempo real
function validarCantidad() {
    const cantidadInput = document.getElementById('txtCant');
    const cantidad = parseInt(cantidadInput.value);
    const stockDisponible = parseInt(document.getElementById('cantDispo').value);
    const validationElement = document.getElementById('stockValidation');

    if (isNaN(cantidad) || cantidad <= 0) {
        validationElement.textContent = 'Cantidad no válida';
        validationElement.className = 'text-danger text-sm mt-1';
        validationElement.style.display = 'block';
        return false;
    }

    if (cantidad > stockDisponible) {
        validationElement.textContent = `No hay suficiente stock. Disponible: ${stockDisponible}`;
        validationElement.className = 'text-danger text-sm mt-1';
        validationElement.style.display = 'block';
        return false;
    }

    validationElement.textContent = 'Cantidad válida';
    validationElement.className = 'text-success text-sm mt-1';
    validationElement.style.display = 'block';
    return true;
}

// Agregar artículo a la lista
function agregarArticulo() {
    const select = document.getElementById('listArticulo');
    const selectedOption = select.options[select.selectedIndex];
    const cantidadInput = document.getElementById('txtCant');
    const cantidad = parseInt(cantidadInput.value);

    if (selectedOption.value == "0" || isNaN(cantidad) || cantidad <= 0) {
        notifi("Debe seleccionar un artículo y especificar una cantidad válida", 'info');
        return;
    }

    if (!validarCantidad()) {
        notifi("La cantidad no es válida", 'error');
        return;
    }

    // Verificar si el artículo ya fue agregado
    if (articulosAgregados.some(art => art.id == selectedOption.value)) {
        notifi("Este artículo ya fue agregado a la orden", 'warning');
        return;
    }

    // Agregar a la lista de artículos
    articulosAgregados.push({
        id: selectedOption.value,
        nombre: selectedOption.text,
        cantidad: cantidad
    });

    // Limpiar mensaje de tabla vacía si existe
    if (document.querySelector('#lista tr td[colspan]')) {
        document.querySelector('#lista').innerHTML = '';
    }

    // Agregar fila a la tabla
    const item = `
        <tr class="articulo-item">
            <td>
                <input type="hidden" name="cod[]" value="${selectedOption.value}"/>
                <span>COD ${selectedOption.value.toString().padStart(4, '0')}</span>
            </td>
            <td>
                <input type="hidden" name="articulo[]" value="${selectedOption.text}"/>
                <span>${selectedOption.text}</span>
            </td>
            <td>
                <input type="hidden" name="cantidad[]" value="${cantidad}"/>
                <span>${cantidad}</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm eliminarRow">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    document.getElementById("lista").insertAdjacentHTML('beforeend', item);
    cantidadInput.value = "";
    document.getElementById('stockValidation').style.display = 'none';

    actualizarEstadoBotonGenerar();
    actualizarResumenOrden();
}

// Actualizar estado del botón de generar
function actualizarEstadoBotonGenerar() {
    const btnGenerar = document.getElementById('btnGenerar');
    btnGenerar.disabled = articulosAgregados.length === 0;
}

// Actualizar resumen de la orden
function actualizarResumenOrden() {
    document.getElementById('totalArticulos').textContent = articulosAgregados.length;
    const totalUnidades = articulosAgregados.reduce((total, art) => total + art.cantidad, 0);
    document.getElementById('totalUnidades').textContent = totalUnidades;
}

// ========== GESTIÓN DE FORMULARIOS ==========

// Enviar formulario de orden
async function enviarFormulario(e) {
    e.preventDefault();
    const btnGenerar = document.getElementById('btnGenerar');

    // Validar campos obligatorios
    const unidad = document.getElementById('listUnidad').value;
    const operador = document.getElementById('listOperador').value;
    const mecanico = document.getElementById('listMecanico').value;
    const despachador = document.getElementById('listDespachador').value;
    const fecha = document.getElementById('txtdate').value;

    if (unidad == "0" || operador == "0" || mecanico == "0" || despachador == "0" || !fecha) {
        notifi("Debe completar todos los campos obligatorios", 'error');
        return;
    }

    if (articulosAgregados.length === 0) {
        notifi("Debe agregar al menos un artículo a la orden", 'error');
        return;
    }

    // Mostrar loading
    const originalText = btnGenerar.innerHTML;
    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
    btnGenerar.disabled = true;

    try {
        const formData = new FormData(document.getElementById('formDespacho'));
        const response = await fetch(base_url + 'Orden/setOrdenD', {
            method: 'POST',
            body: formData
        });

        const objData = await response.json();

        if (objData.success) {
            notifi(objData.message, 'success');
            // Resetear formulario
            document.getElementById('formDespacho').reset();
            document.getElementById('lista').innerHTML = '';
            articulosAgregados = [];

            // Limpiar los selects de Select2
            $('#listUnidad').val('0').trigger('change');
            $('#listOperador').val('0').trigger('change');
            $('#listMecanico').val('0').trigger('change');
            $('#listDespachador').val('0').trigger('change');
            $('#listArticulo').val('0').trigger('change');

            // Restablecer información mostrada
            document.querySelector("#id_unidad").textContent = '-';
            document.querySelector("#vim_unidad").textContent = '-';
            document.querySelector("#marca_unidad").textContent = '-';
            document.querySelector("#modelo_unidad").textContent = '-';
            document.querySelector("#operador").textContent = '-';
            document.querySelector("#mecanico").textContent = '-';
            document.querySelector("#despachador").textContent = '-';
            document.querySelector("#totalArticulos").textContent = '0';
            document.querySelector("#totalUnidades").textContent = '0';

            // Recargar la tabla de órdenes y la barra de progreso
            tblOrdenes.ajax.reload();
            await actualizarProgresoOrdenes();

            // Restablecer fecha actual
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('txtdate').value = today;
            document.getElementById('fechaDespacho').textContent = formatFecha(today);
            document.getElementById('strDate').value = today;

        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error enviando formulario:', error);
        notifi('Error al procesar la orden', 'error');
    } finally {
        // Restaurar botón
        btnGenerar.innerHTML = originalText;
        btnGenerar.disabled = false;
    }
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

    // Aplicar filtro por defecto según el rol del usuario
    tblOrdenes.on('init.dt', function () {
        let defaultFilter = '';
        if (userRole === 'COMPRAS') {
            defaultFilter = 'Requisición';
        } else if (userRole === 'ALMACEN') {
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

async function fntNotificarEnProceso(idDespacho) {
    const result = await showConfirm(
        'Notificar a Operaciones',
        `¿Desea notificar a Operaciones que la Orden #${idDespacho} está en proceso de compra?`,
        'question',
        'Sí, notificar'
    );
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

async function fntAprobarOrden(idDespacho) {
    const result = await showConfirm('Aprobar Requisición', '¿Está seguro de aprobar esta requisición para que sea procesada por almacén?', 'question', 'Sí, aprobar');
    if (result.isConfirmed) {
        const params = new URLSearchParams({ id_despacho: idDespacho });
        const response = await fetch(base_url + 'Orden/aprobarOrden', { method: 'POST', body: params });
        const data = await response.json();
        if (data.success) {
            notifi(data.message, 'success');
            // Notificar automáticamente a Operaciones que la requisición fue aprobada
            const paramsNotif = new URLSearchParams({ id_despacho: idDespacho, tipo: 'aprobada' });
            await fetch(base_url + 'Orden/notificarOperaciones', { method: 'POST', body: paramsNotif });
            tblOrdenes.ajax.reload();
        } else {
            notifi(data.message, 'error');
        }
    }
}

async function fntDespacharOrden(idDespacho) {
    const result = await showConfirm('Despachar Orden', 'Esta acción marcará la orden como despachada y descontará los artículos del inventario. ¿Continuar?', 'warning', 'Sí, despachar');
    if (result.isConfirmed) {
        const params = new URLSearchParams({ id_despacho: idDespacho });
        const response = await fetch(base_url + 'Orden/despacharOrden', { method: 'POST', body: params });
        const data = await response.json();
        if (data.success) {
            notifi(data.message, 'success');
            tblOrdenes.ajax.reload();
        } else {
            notifi(data.message, 'error');
        }
    }
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

// Cerrar modal
function cerrarModal(modalId) {
    // Bootstrap se encarga de cerrar el modal con el atributo data-dismiss o con jQuery
    $('#' + modalId).modal('hide');
}

// Eliminar orden
async function fntdelDesp(idDesp) {
    const { value: text } = await Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción no se puede deshacer",
        input: 'textarea',
        inputPlaceholder: 'Motivo de la eliminación...',
        inputAttributes: {
            'aria-label': 'Motivo de la eliminación'
        },
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return 'Debe especificar un motivo';
            }
        }
    });

    if (text) {
        try {
            const params = new URLSearchParams();
            params.append('idDesp', idDesp);
            params.append('srtText', text);

            const response = await fetch(base_url + 'Orden/delOrden', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params
            });

            const objData = await response.json();

            if (objData.success) {
                notifi(objData.message, 'success');
                $('#tblOrdenes').DataTable().ajax.reload(); // Recargar listado
            } else {
                notifi(objData.message, 'error');
            }
        } catch (error) {
            console.error('Error eliminando orden:', error);
            notifi('Error al eliminar la orden', 'error');
        }
    }
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