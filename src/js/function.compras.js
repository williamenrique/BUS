/**
 * Archivo: function.compras.js
 * Descripción: Contiene toda la lógica de JavaScript para el módulo de Compras.
 *              Gestiona las tablas de compras pendientes y costeadas, la asignación de costos,
 *              la anulación y la generación de reportes en PDF, utilizando Bootstrap y AdminLTE.
 * Autor: Gemini Code Assist
 * Fecha: [Fecha Actual]
 */

let tableComprasPendientes;
let tableComprasCosteadas;
let detalleCosteadoData = {}; // Para almacenar datos del modal para el PDF

/**
 * Punto de entrada principal. Se ejecuta cuando el DOM está completamente cargado.
 * Configura todos los event listeners para los formularios y botones de la página.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Listener para el envío del formulario de costos (asociado directamente al modal)
    $('#formAsignarCosto').on('submit', guardarCosto);

    // Listener para el formulario de reporte
    const formReporte = document.getElementById('formReporteCompras');
    if (formReporte) {
        formReporte.addEventListener('submit', generarReporte);
        // Establecer fechas por defecto (mes actual)
        const today = new Date();
        document.getElementById('fechaInicio').value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        document.getElementById('fechaFin').value = today.toISOString().split('T')[0];

        // Inicializar la leyenda del conteo
        document.getElementById('conteoReporte').innerHTML = '<i class="fas fa-info-circle mr-1"></i> Seleccione una unidad para ver las órdenes disponibles.';
    }

    // Listener para el formulario de reporte diario
    const formReporteDiario = document.getElementById('formReporteComprasDiarias');
    if (formReporteDiario) {
        formReporteDiario.addEventListener('submit', generarReporteDiario);
    }

    // Listeners para el filtro de la tabla de costeadas
    const formFiltroCosteadas = document.getElementById('formFiltroCosteadas');
    if (formFiltroCosteadas) {
        formFiltroCosteadas.addEventListener('submit', function (e) {
            e.preventDefault();
            inicializarTablaCosteadas(document.getElementById('fechaInicioCosteadas').value, document.getElementById('fechaFinCosteadas').value);
        });
        document.getElementById('btnLimpiarFiltroCosteadas').addEventListener('click', limpiarFiltroCosteadas);
        document.getElementById('btnExportarPdfCosteadas').addEventListener('click', generarPDFCosteadasFiltrado);
    }

    // Listener para el botón de generar PDF de costo individual
    const btnPdfCosto = document.getElementById('btnGenerarPdfCosto');
    if (btnPdfCosto) {
        btnPdfCosto.addEventListener('click', generarPDFCostoIndividual);
    }

    // Listener para el botón de anular costo
    const btnAnularCosto = document.getElementById('btnAnularCosto');
    if (btnAnularCosto) {
        btnAnularCosto.addEventListener('click', fntAnularCosto);
    }

    // Lógica para las pestañas usando eventos de Bootstrap
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        // El target es la pestaña que se acaba de mostrar
        const targetTab = $(e.target).attr("href");

        // Si la pestaña mostrada es la de "Costeados" y la tabla aún no se ha inicializado
        if (targetTab === '#costeados' && !$.fn.DataTable.isDataTable('#tableComprasCosteadas')) {
            inicializarTablaCosteadas();
        }
    });

    cargarUnidadesReporte();
    inicializarTablaCompras();
});

/**
 * Carga las unidades de la flota en el select del formulario de reportes.
 * Utiliza Select2 para hacer el selector buscable y más amigable.
 */
async function cargarUnidadesReporte() {
    const selectUnidad = document.getElementById('listUnidadReporte');
    if (!selectUnidad) return;

    try {
        const response = await fetch(base_url + "Compras/getFlota");
        const result = await response.json();
        if (result.status) {
            selectUnidad.innerHTML = '<option value="">Seleccione una unidad</option>';
            result.data.forEach(unidad => {
                const option = `<option value="${unidad.id_flota}">${unidad.id_unidad} - ${unidad.modelo_unidad}</option>`;
                selectUnidad.insertAdjacentHTML('beforeend', option);
            });

            // Inicializar Select2 para hacer el selector buscable
            $(selectUnidad).select2({
                placeholder: "Buscar y seleccionar una unidad",
                allowClear: true,
                theme: 'bootstrap4' // Integración con Bootstrap 4
            });

            // Listeners para los filtros del reporte (se adjuntan después de inicializar Select2)
            $(selectUnidad).on('change', actualizarConteoReporte); // Usar jQuery para consistencia
            $('#fechaInicio, #fechaFin').on('change', actualizarConteoReporte);
        }
    } catch (error) {
        console.error("Error cargando unidades:", error);
    }
}

/**
 * Actualiza dinámicamente el conteo de órdenes disponibles para un reporte
 * basado en la unidad y el rango de fechas seleccionados. Habilita o deshabilita
 * el botón de generar reporte según si se encuentran órdenes.
 */
async function actualizarConteoReporte() {
    const selectUnidad = document.getElementById('listUnidadReporte');
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const conteoDiv = document.getElementById('conteoReporte');
    const btnGenerar = document.getElementById('btnGenerarReporte');

    // Resetear si no hay unidad seleccionada
    if (selectUnidad.value === "" || selectUnidad.value === "0") {
        conteoDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Seleccione una unidad para ver las órdenes disponibles.';
        btnGenerar.disabled = true;
        return;
    }

    // Validar que ambas fechas estén presentes si una lo está
    if (!fechaInicio || !fechaFin) {
        conteoDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Seleccione un rango de fechas.';
        btnGenerar.disabled = true;
        return;
    }

    try {
        const formData = new FormData();
        formData.append('idFlota', selectUnidad.value);
        formData.append('fechaInicio', fechaInicio);
        formData.append('fechaFin', fechaFin);

        const response = await fetch(base_url + 'Compras/getConteoOrdenesCosteadas', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.status) {
            if (result.total > 0) {
                conteoDiv.innerHTML = `<i class="fas fa-check-circle text-success mr-1"></i> Se encontraron <strong>${result.total}</strong> órdenes para este reporte.`;
                btnGenerar.disabled = false;
            } else {
                conteoDiv.innerHTML = `<i class="fas fa-exclamation-circle text-warning mr-1"></i> No hay órdenes para esta unidad en el rango de fechas.`;
                btnGenerar.disabled = true;
            }
        }
    } catch (error) {
        console.error("Error actualizando conteo:", error);
        conteoDiv.innerHTML = '<span class="text-danger">Error al consultar.</span>';
        btnGenerar.disabled = true;
    }
}

/**
 * Inicializa o reinicializa la DataTable para las compras costeadas.
 * Permite filtrar los datos por un rango de fechas.
 * @param {string} [fechaInicio=''] - La fecha de inicio para el filtro.
 * @param {string} [fechaFin=''] - La fecha de fin para el filtro.
 */
function inicializarTablaCosteadas(fechaInicio = '', fechaFin = '') {
    // Si la tabla ya es una DataTable, la destruimos para poder re-inicializarla con nuevos parámetros
    if ($.fn.DataTable.isDataTable('#tableComprasCosteadas')) {
        $('#tableComprasCosteadas').DataTable().destroy();
    }

    tableComprasCosteadas = $('#tableComprasCosteadas').DataTable({
        "aProcessing": true,
        "aServerSide": true, // Cambiado a true para procesamiento del lado del servidor
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": {
            "url": base_url + "Compras/getComprasCosteadas",
            "type": "POST",
            "data": function (d) { // Enviamos las fechas como data adicional
                d.fechaInicio = fechaInicio;
                d.fechaFin = fechaFin;
            }
        },
        "columns": [
            { "data": "id_despacho" },
            { "data": "fecha_despacho" },
            { "data": "id_unidad", "render": function (data, type, row) { return `${row.id_unidad} - ${row.modelo_unidad}`; } },
            { "data": "articulos_costeados", "className": "text-center" },
            { "data": "total_divisa", "render": function (data) { return `$. ${parseFloat(data).toFixed(2)}`; } },
            { "data": "total_bs", "render": function (data) { return `Bs. ${parseFloat(data).toFixed(2)}`; } },
            { "data": "acciones" }
        ], "responsive": true, "bDestroy": true, "iDisplayLength": 10, "order": [[0, "desc"]],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "buttons": [
            { "extend": "excelHtml5", "text": "<i class='fas fa-file-excel'></i> Excel", "className": "btn btn-success" }
        ]
    });
}

/**
 * Limpia los filtros de fecha de la tabla de costeadas y la recarga
 * para mostrar todos los registros.
 */
function limpiarFiltroCosteadas() {
    document.getElementById('formFiltroCosteadas').reset();
    inicializarTablaCosteadas(); // Recargamos la tabla sin filtros
}

/**
 * Inicializa la DataTable para las compras pendientes de costeo.
 */
function inicializarTablaCompras() {
    tableComprasPendientes = $('#tableComprasPendientes').DataTable({
        "aProcessing": true,
        "aServerSide": false, // Cambiaremos a true si implementamos paginación del lado del servidor
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": {
            "url": base_url + "Compras/getComprasPendientes",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_despacho" },
            { "data": "fecha_despacho" },
            { "data": "id_unidad", "render": function (data, type, row) { return `${row.id_unidad} - ${row.modelo_unidad}`; } },
            {
                "data": "articulos_pendientes",
                "className": "text-center",
                "render": function (data) {
                    return `<span class="badge badge-info">${data}</span>`;
                }
            },
            { "data": "acciones" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "dom": "lfrtip", // Usar el DOM por defecto de DataTables para Bootstrap
        // Callback para modificar el contenido después de que se dibuja la fila
        "fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            // Encontrar el botón de asignar costo y añadirle la clase de estilo
            const btnAsignar = $(nRow).find('button[onclick^="fntAsignarCosto"]');
            btnAsignar.addClass('btn-primary');
        }
    });
}

/**
 * Cierra el modal de asignación de costos y resetea su formulario.
 */
function closeModalCosto() {
    $('#modalAsignarCosto').modal('hide');
    $('#formAsignarCosto')[0].reset(); // Usar jQuery para resetear el formulario
}

/**
 * Abre el modal para asignar costos a un despacho específico.
 * Carga la información del despacho y la lista de artículos pendientes.
 * @param {HTMLElement} button - El botón que disparó el evento.
 */
async function fntAsignarCosto(button) {
    const idDespacho = button.getAttribute('data-iddespacho');

    document.querySelector("#titleModalCosto").innerHTML = `Asignar Costos a Despacho #${idDespacho}`;
    document.querySelector("#btnActionTextCosto").innerHTML = "Guardar Costo";
    document.getElementById('formAsignarCosto').reset();
    document.getElementById('idDespacho').value = idDespacho;

    try {
        const response = await fetch(base_url + "Compras/getArticulosPorDespacho/" + idDespacho);
        if (!response.ok) throw new Error('Error en la petición: ' + response.statusText);

        const objData = await response.json();
        if (objData.status) {
            const { info, articulos } = objData.data;

            // Llenar info general del despacho
            document.getElementById('infoUnidad').textContent = `${info.id_unidad} - ${info.modelo_unidad}`;
            document.getElementById('infoFecha').textContent = info.fecha_despacho;

            // Llenar la tabla de artículos
            const tablaBody = document.getElementById('tablaArticulosCosto');
            tablaBody.innerHTML = ''; // Limpiar tabla

            articulos.forEach(articulo => {
                const row = `
                    <tr data-id-pendiente="${articulo.id_compra_pendiente}" data-cantidad="${articulo.cant_despacho}">
                        <td>${articulo.producto}</td>
                        <td class="text-center">${articulo.cant_despacho}</td>
                        <td>
                            <input type="number" step="0.01" class="form-control form-control-sm monto-divisa text-center" placeholder="0.00">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm monto-bs text-center" readonly>
                        </td>
                    </tr>
                `;
                tablaBody.insertAdjacentHTML('beforeend', row);
            });

            // Añadir listeners a los nuevos inputs
            document.querySelectorAll('.monto-divisa, #tasaDia').forEach(input => {
                input.addEventListener('input', calcularMontos);
            });

            $('#modalAsignarCosto').modal('show');
        } else {
            Swal.fire("Error", objData.msg, "error");
        }
    } catch (error) {
        console.error("Error al obtener datos del despacho:", error);
        Swal.fire("Error", "No se pudieron cargar los detalles del despacho.", "error");
    }
}

/**
 * Calcula y actualiza los montos en Bolívares en tiempo real mientras el usuario
 * introduce la tasa del día y los montos en divisas.
 */
function calcularMontos() {
    const tasa = parseFloat(document.getElementById('tasaDia').value) || 0;
    document.querySelectorAll('#tablaArticulosCosto tr').forEach(row => {
        const divisaInput = row.querySelector('.monto-divisa');
        const bsInput = row.querySelector('.monto-bs');

        // Obtener la cantidad del atributo data-cantidad para mayor robustez
        const cantidad = parseFloat(row.dataset.cantidad) || 0;

        // El valor que el usuario ingresa ahora es el precio UNITARIO
        const precioUnitarioDivisa = parseFloat(divisaInput.value) || 0;

        // Calcular el monto total en divisas y luego en bolívares
        const montoTotalDivisa = precioUnitarioDivisa * cantidad;
        bsInput.value = (tasa * montoTotalDivisa).toFixed(2);
    });
}

/**
 * Procesa y guarda los costos asignados a los artículos de un despacho.
 * @param {Event} e - El evento de envío del formulario.
 */
async function guardarCosto(e) {
    e.preventDefault();
    const form = e.target;
    const tasa = document.getElementById('tasaDia').value;

    if (tasa.trim() === '' || parseFloat(tasa) <= 0) {
        Swal.fire("Atención", "La tasa del día es obligatoria y debe ser mayor a cero.", "warning");
        return;
    }

    const articulosData = [];
    document.querySelectorAll('#tablaArticulosCosto tr').forEach(row => {
        const precioUnitarioInput = row.querySelector('.monto-divisa');
        const precioUnitario = parseFloat(precioUnitarioInput.value);

        if (precioUnitario && precioUnitario > 0) {
            const cantidad = parseFloat(row.dataset.cantidad) || 0;
            const montoTotal = precioUnitario * cantidad;

            articulosData.push({
                id: row.dataset.idPendiente,
                // Enviamos el monto TOTAL al backend, como se esperaba originalmente.
                monto: montoTotal.toFixed(2)
            });
        }
    });

    if (articulosData.length === 0) {
        Swal.fire("Atención", "Debe ingresar el monto en divisa para al menos un artículo.", "warning");
        return;
    }

    document.getElementById('jsonArticulos').value = JSON.stringify(articulosData);

    try {
        const formData = new FormData(form);
        const response = await fetch(base_url + 'Compras/setCosto', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status) {
            closeModalCosto();
            tableComprasPendientes.ajax.reload();
            if ($.fn.DataTable.isDataTable('#tableComprasCosteadas')) {
                tableComprasCosteadas.ajax.reload();
            }
            notifi(result.msg, "success");
        } else {
            notifi(result.msg, "error");
        }
    } catch (error) {
        console.error("Error al guardar el costo:", error);
        Swal.fire("Error", "Ocurrió un problema al intentar guardar el costo.", "error");
    }
}

/**
 * Obtiene y muestra los detalles de un despacho ya costeado en un modal.
 * @param {number} idDespacho - El ID del despacho a consultar.
 */
async function fntVerDetalleCosto(idDespacho) {
    try {
        const response = await fetch(base_url + "Compras/getDetalleCosteado/" + idDespacho);
        if (!response.ok) throw new Error('Error en la petición: ' + response.statusText);

        const objData = await response.json();
        if (objData.status) {
            const { info, articulos } = objData.data;

            // Almacenar datos para la generación del PDF
            detalleCosteadoData = { info, articulos };

            // Asignar el ID del despacho al botón de anular
            document.getElementById('btnAnularCosto').dataset.iddespacho = info.id_despacho;

            // Llenar título e info general
            document.getElementById('titleModalDetalle').innerHTML = `Detalles del Despacho Costeado #${info.id_despacho}`;
            document.getElementById('detalleInfoUnidad').textContent = `${info.id_unidad} - ${info.modelo_unidad}`;
            document.getElementById('detalleInfoFecha').textContent = info.fecha_despacho;
            // --- INICIO DE LA CORRECCIÓN ---
            // Mostrar la tasa una sola vez y calcular totales
            const tasaAplicada = articulos[0]?.tasa_dia || 0;
            document.getElementById('detalleInfoTasa').textContent = `Bs. ${parseFloat(tasaAplicada).toFixed(2)}`;

            // Llenar tabla de artículos
            const tablaBody = document.getElementById('tablaDetalleCostos');
            tablaBody.innerHTML = ''; // Limpiar tabla
            let totalDivisa = 0;
            let totalBs = 0;

            articulos.forEach(articulo => {
                totalDivisa += parseFloat(articulo.monto_divisa);
                totalBs += parseFloat(articulo.monto_bs);
                const row = `
                    <tr class="text-sm">
                        <td>${articulo.producto}</td>
                        <td class="text-center">${articulo.cant_despacho}</td>
                        <td class="text-right">${parseFloat(articulo.monto_divisa).toFixed(2)}</td>
                        <td class="text-right">${parseFloat(articulo.monto_bs).toFixed(2)}</td>
                    </tr>
                `;
                tablaBody.insertAdjacentHTML('beforeend', row);
            });

            // Llenar los totalizadores en el tfoot
            document.getElementById('totalDetalleDivisa').textContent = `$. ${totalDivisa.toFixed(2)}`;
            document.getElementById('totalDetalleBs').textContent = `Bs. ${totalBs.toFixed(2)}`;
            // --- FIN DE LA CORRECCIÓN ---

            $('#modalVerDetalle').modal('show');

        } else {
            Swal.fire("Error", objData.msg, "error");
        }
    } catch (error) {
        console.error("Error al obtener detalles del costo:", error);
        Swal.fire("Error", "No se pudieron cargar los detalles del despacho.", "error");
    }
}

/**
 * Cierra el modal que muestra los detalles de un despacho costeado.
 */
function cerrarModalDetalle() {
    $('#modalVerDetalle').modal('hide');
}

/**
 * Inicia el proceso para anular el costeo de un despacho.
 * Muestra una confirmación y, si se acepta, envía la solicitud al servidor.
 * @param {Event} event - El evento del clic en el botón de anular.
 */
async function fntAnularCosto(event) {
    const idDespacho = event.currentTarget.dataset.iddespacho;

    const result = await Swal.fire({
        title: '¿Está seguro?',
        text: `Esta acción anulará el costeo del despacho #${idDespacho} y lo devolverá a la lista de pendientes. No se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular costeo',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`${base_url}Compras/anularCosto/${idDespacho}`, {
                method: 'POST'
            });

            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor.');
            }

            const res = await response.json();

            if (res.status) {
                notifi(res.msg, 'success');
                cerrarModalDetalle();
                // Recargar ambas tablas para reflejar los cambios
                tableComprasCosteadas.ajax.reload();
                tableComprasPendientes.ajax.reload();
            } else {
                notifi(res.msg, 'error');
            }
        } catch (error) {
            console.error('Error al anular el costo:', error);
            Swal.fire("Error", "Ocurrió un problema al intentar anular el costeo.", "error");
        }
    }
}

/**
 * Genera el reporte en PDF para un rango de fechas y una unidad específica.
 * @param {Event} e - El evento de envío del formulario.
 */
async function generarReporte(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('btnGenerarReporte');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generando...';
    btn.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(base_url + 'Compras/generarReporteCompras', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status) {
            fntGenerarPDFCompras(result.data);
        } else {
            Swal.fire("Atención", result.msg, "warning");
        }
    } catch (error) {
        console.error("Error al generar el reporte:", error);
        Swal.fire("Error", "Ocurrió un problema al generar el reporte.", "error");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

/**
 * Crea un formulario oculto y lo envía para generar el PDF de compras por unidad.
 * @param {Array} reporteData - Los datos del reporte a enviar.
 */
function fntGenerarPDFCompras(reporteData) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/compra/reporte_compra.php";
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    input.value = JSON.stringify(reporteData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Obtiene los datos filtrados de la tabla de costeadas y los prepara
 * para ser enviados al script de generación de PDF.
 */
async function generarPDFCosteadasFiltrado() {
    const btn = document.getElementById('btnExportarPdfCosteadas');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exportando...';
    btn.disabled = true;

    try {
        const fechaInicio = document.getElementById('fechaInicioCosteadas').value;
        const fechaFin = document.getElementById('fechaFinCosteadas').value;
        // Obtenemos el valor del campo de búsqueda de DataTables
        const searchValue = $('#tableComprasCosteadas').DataTable().search();

        const formData = new FormData();
        formData.append('fechaInicio', fechaInicio);
        formData.append('fechaFin', fechaFin);
        formData.append('searchValue', searchValue);

        const response = await fetch(base_url + 'Compras/generarReporteCosteadas', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.status) {
            fntGenerarPDFCosteadas(result.data, fechaInicio, fechaFin, searchValue);
        } else {
            Swal.fire("Atención", result.msg, "warning");
        }
    } catch (error) {
        console.error("Error al exportar el PDF:", error);
        Swal.fire("Error", "Ocurrió un problema al exportar el reporte.", "error");
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

/**
 * Crea un formulario oculto para enviar los datos filtrados y los parámetros de filtro
 * al script PHP que genera el PDF de costeadas.
 * @param {Array} reporteData - Los datos del reporte.
 * @param {string} fechaInicio - La fecha de inicio del filtro.
 * @param {string} fechaFin - La fecha de fin del filtro.
 * @param {string} searchValue - El término de búsqueda aplicado.
 */
function fntGenerarPDFCosteadas(reporteData, fechaInicio, fechaFin, searchValue) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/compra/reporte_costeadas_filtrado.php";
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    // Enviamos los datos del reporte y también los filtros para mostrarlos en la leyenda del PDF
    input.value = JSON.stringify({ data: reporteData, fechaInicio, fechaFin, searchValue });
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera un PDF con el detalle de un único despacho costeado, utilizando
 * los datos almacenados en la variable global `detalleCosteadoData`.
 */
function generarPDFCostoIndividual() {
    if (!detalleCosteadoData || !detalleCosteadoData.info) {
        notifi("No hay datos cargados para generar el PDF.", "error");
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/compra/reporte_costeada.php";
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    input.value = JSON.stringify(detalleCosteadoData); // Usamos los datos ya cargados en el modal
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}