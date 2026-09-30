/**
 * Archivo: function.compras.js
 * Descripción: Lógica del módulo de Compras con multi-institución.
 *              Envía id_institucion en cada AJAX y pasa el nombre de la institución a los PDFs.
 */

let tableComprasPendientes;
let tableComprasCosteadas;
let detalleCosteadoData = {};

// Institución activa leída del hidden input
const idInstitucionCompras = document.getElementById('id_institucion')?.value || 1;
const nombreInstitucionCompras = document.getElementById('nombre_institucion')?.value || '';

/**
 * Punto de entrada.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Listener para el formulario de costos
    $('#formAsignarCosto').on('submit', guardarCosto);

    // Formulario de reporte
    const formReporte = document.getElementById('formReporteCompras');
    if (formReporte) {
        formReporte.addEventListener('submit', generarReporte);
        const today = new Date();
        document.getElementById('fechaInicio').value = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        document.getElementById('fechaFin').value = today.toISOString().split('T')[0];

        document.getElementById('conteoReporte').innerHTML = '<i class="fas fa-info-circle mr-1"></i> Seleccione una unidad para ver las órdenes disponibles.';
    }

    // Formulario de reporte diario (si existe)
    const formReporteDiario = document.getElementById('formReporteComprasDiarias');
    if (formReporteDiario) {
        formReporteDiario.addEventListener('submit', generarReporteDiario);
    }

    // Filtros costeadas
    const formFiltroCosteadas = document.getElementById('formFiltroCosteadas');
    if (formFiltroCosteadas) {
        formFiltroCosteadas.addEventListener('submit', function (e) {
            e.preventDefault();
            inicializarTablaCosteadas(
                document.getElementById('fechaInicioCosteadas').value,
                document.getElementById('fechaFinCosteadas').value
            );
        });
        document.getElementById('btnLimpiarFiltroCosteadas').addEventListener('click', limpiarFiltroCosteadas);
        document.getElementById('btnExportarPdfCosteadas').addEventListener('click', generarPDFCosteadasFiltrado);
    }

    // PDF costo individual
    const btnPdfCosto = document.getElementById('btnGenerarPdfCosto');
    if (btnPdfCosto) {
        btnPdfCosto.addEventListener('click', generarPDFCostoIndividual);
    }

    // Anular costo
    const btnAnularCosto = document.getElementById('btnAnularCosto');
    if (btnAnularCosto) {
        btnAnularCosto.addEventListener('click', fntAnularCosto);
    }

    // Cambio de pestaña
    $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target).attr("href");
        if (targetTab === '#costeados' && !$.fn.DataTable.isDataTable('#tableComprasCosteadas')) {
            inicializarTablaCosteadas();
        }
    });

    cargarUnidadesReporte();
    inicializarTablaCompras();
});

/**
 * Carga las unidades de la flota en el select del formulario de reportes.
 * Filtra por la institución activa.
 */
async function cargarUnidadesReporte() {
    const selectUnidad = document.getElementById('listUnidadReporte');
    if (!selectUnidad) return;

    try {
        const response = await fetch(base_url + "Compras/getFlota?id_institucion=" + idInstitucionCompras);
        const result = await response.json();
        if (result.status) {
            selectUnidad.innerHTML = '<option value="">Seleccione una unidad</option>';
            result.data.forEach(unidad => {
                const option = `<option value="${unidad.id_flota}">${unidad.id_unidad} - ${unidad.modelo_unidad}</option>`;
                selectUnidad.insertAdjacentHTML('beforeend', option);
            });

            $(selectUnidad).select2({
                placeholder: "Buscar y seleccionar una unidad",
                allowClear: true,
                theme: 'bootstrap4'
            });

            $(selectUnidad).on('change', actualizarConteoReporte);
            $('#fechaInicio, #fechaFin').on('change', actualizarConteoReporte);
        }
    } catch (error) {
        console.error("Error cargando unidades:", error);
    }
}

/**
 * Actualiza el conteo de órdenes disponibles para el reporte.
 */
async function actualizarConteoReporte() {
    const selectUnidad = document.getElementById('listUnidadReporte');
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const conteoDiv = document.getElementById('conteoReporte');
    const btnGenerar = document.getElementById('btnGenerarReporte');

    if (selectUnidad.value === "" || selectUnidad.value === "0") {
        conteoDiv.innerHTML = '<i class="fas fa-info-circle mr-1"></i> Seleccione una unidad para ver las órdenes disponibles.';
        btnGenerar.disabled = true;
        return;
    }

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
        formData.append('id_institucion', idInstitucionCompras);

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
 * Inicializa la DataTable de Compras Costeadas.
 */
function inicializarTablaCosteadas(fechaInicio = '', fechaFin = '') {
    if ($.fn.DataTable.isDataTable('#tableComprasCosteadas')) {
        $('#tableComprasCosteadas').DataTable().destroy();
    }

    tableComprasCosteadas = $('#tableComprasCosteadas').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": {
            "url": base_url + "Compras/getComprasCosteadas",
            "type": "POST",
            "data": function (d) {
                d.fechaInicio = fechaInicio;
                d.fechaFin = fechaFin;
                d.id_institucion = idInstitucionCompras;
            }
        },
        "columns": [
            { "data": "numero_orden" },
            { "data": "fecha_despacho" },
            { "data": "id_unidad", "render": function (data, type, row) { return `${row.id_unidad} - ${row.modelo_unidad}`; } },
            { "data": "articulos_costeados", "className": "text-center" },
            { "data": "total_divisa", "render": function (data) { return `$. ${parseFloat(data).toFixed(2)}`; } },
            { "data": "total_bs", "render": function (data) { return `Bs. ${parseFloat(data).toFixed(2)}`; } },
            { "data": "acciones" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "buttons": [
            { "extend": "excelHtml5", "text": "<i class='fas fa-file-excel'></i> Excel", "className": "btn btn-success" }
        ]
    });
}

/**
 * Limpia filtros de costeadas.
 */
function limpiarFiltroCosteadas() {
    document.getElementById('formFiltroCosteadas').reset();
    inicializarTablaCosteadas();
}

/**
 * Inicializa la DataTable de Compras Pendientes.
 */
function inicializarTablaCompras() {
    tableComprasPendientes = $('#tableComprasPendientes').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": {
            "url": base_url + "Compras/getComprasPendientes?id_institucion=" + idInstitucionCompras,
            "dataSrc": ""
        },
        "columns": [
            { "data": "numero_orden" },
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
        "dom": "lfrtip",
        "fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
            const btnAsignar = $(nRow).find('button[onclick^="fntAsignarCosto"]');
            btnAsignar.addClass('btn-primary');
        }
    });
}

/**
 * Cierra el modal de asignar costo.
 */
function closeModalCosto() {
    $('#modalAsignarCosto').modal('hide');
    $('#formAsignarCosto')[0].reset();
}

/**
 * Abre el modal para asignar costos.
 */
async function fntAsignarCosto(button) {
    const idDespacho = button.getAttribute('data-iddespacho');

    document.querySelector("#titleModalCosto").innerHTML = `Asignar Costos a Despacho #${idDespacho}`;
    document.querySelector("#btnActionTextCosto").innerHTML = "Guardar Costo";
    document.getElementById('formAsignarCosto').reset();
    document.getElementById('idDespacho').value = idDespacho;

    try {
        const response = await fetch(base_url + "Compras/getArticulosPorDespacho/" + idDespacho + "?id_institucion=" + idInstitucionCompras);
        if (!response.ok) throw new Error('Error en la petición: ' + response.statusText);

        const objData = await response.json();
        if (objData.status) {
            const { info, articulos } = objData.data;

            document.getElementById('infoUnidad').textContent = `${info.id_unidad} - ${info.modelo_unidad}`;
            document.getElementById('infoFecha').textContent = info.fecha_despacho;

            const tablaBody = document.getElementById('tablaArticulosCosto');
            tablaBody.innerHTML = '';

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
 * Calcula los montos en Bs en tiempo real.
 */
function calcularMontos() {
    const tasa = parseFloat(document.getElementById('tasaDia').value) || 0;
    document.querySelectorAll('#tablaArticulosCosto tr').forEach(row => {
        const divisaInput = row.querySelector('.monto-divisa');
        const bsInput = row.querySelector('.monto-bs');
        const cantidad = parseFloat(row.dataset.cantidad) || 0;
        const precioUnitarioDivisa = parseFloat(divisaInput.value) || 0;
        const montoTotalDivisa = precioUnitarioDivisa * cantidad;
        bsInput.value = (tasa * montoTotalDivisa).toFixed(2);
    });
}

/**
 * Guarda el costo.
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
        formData.append('id_institucion', idInstitucionCompras);

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
 * Ver detalle de un despacho costeado.
 */
async function fntVerDetalleCosto(idDespacho) {
    try {
        const response = await fetch(base_url + "Compras/getDetalleCosteado/" + idDespacho + "?id_institucion=" + idInstitucionCompras);
        if (!response.ok) throw new Error('Error en la petición: ' + response.statusText);

        const objData = await response.json();
        if (objData.status) {
            const { info, articulos } = objData.data;

            detalleCosteadoData = { info, articulos };

            document.getElementById('btnAnularCosto').dataset.iddespacho = info.id_despacho;
            document.getElementById('titleModalDetalle').innerHTML = `Detalles del Despacho Costeado #${info.numero_orden}`;
            document.getElementById('detalleInfoUnidad').textContent = `${info.id_unidad} - ${info.modelo_unidad}`;
            document.getElementById('detalleInfoFecha').textContent = info.fecha_despacho;

            const tablaBody = document.getElementById('tablaDetalleCostos');
            tablaBody.innerHTML = '';

            articulos.forEach(articulo => {
                const row = `
                    <tr class="text-sm">
                        <td>${articulo.producto}</td>
                        <td class="text-center">${articulo.cant_despacho}</td>
                        <td class="text-right">${parseFloat(articulo.tasa_dia).toFixed(2)}</td>
                        <td class="text-right">${parseFloat(articulo.monto_divisa).toFixed(2)}</td>
                        <td class="text-right">${parseFloat(articulo.monto_bs).toFixed(2)}</td>
                    </tr>
                `;
                tablaBody.insertAdjacentHTML('beforeend', row);
            });

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
 * Cierra el modal de detalle.
 */
function cerrarModalDetalle() {
    $('#modalVerDetalle').modal('hide');
}

/**
 * Anula el costeo.
 */
async function fntAnularCosto(event) {
    const idDespacho = event.currentTarget.dataset.iddespacho;

    const result = await Swal.fire({
        title: '¿Está seguro?',
        text: `Esta acción anulará el costeo del despacho #${idDespacho} y lo devolverá a la lista de pendientes.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, anular costeo',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id_institucion', idInstitucionCompras);

            const response = await fetch(`${base_url}Compras/anularCosto/${idDespacho}`, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Error en la respuesta del servidor.');

            const res = await response.json();

            if (res.status) {
                notifi(res.msg, 'success');
                cerrarModalDetalle();
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
 * Genera el reporte PDF por unidad.
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
        formData.append('id_institucion', idInstitucionCompras);

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
 * Envía los datos al script del PDF.
 */
function fntGenerarPDFCompras(reporteData) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/compra/reporte_compra.php";
    form.target = '_blank';

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'reporteData';
    inputData.value = JSON.stringify(reporteData);
    form.appendChild(inputData);

    const inputInst = document.createElement('input');
    inputInst.type = 'hidden';
    inputInst.name = 'nombreInstitucion';
    inputInst.value = nombreInstitucionCompras;
    form.appendChild(inputInst);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera PDF de costeadas filtradas.
 */
async function generarPDFCosteadasFiltrado() {
    const btn = document.getElementById('btnExportarPdfCosteadas');
    const originalText = btn.innerHTML;

    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Exportando...';
    btn.disabled = true;

    try {
        const fechaInicio = document.getElementById('fechaInicioCosteadas').value;
        const fechaFin = document.getElementById('fechaFinCosteadas').value;
        const searchValue = $('#tableComprasCosteadas').DataTable().search();

        const formData = new FormData();
        formData.append('fechaInicio', fechaInicio);
        formData.append('fechaFin', fechaFin);
        formData.append('searchValue', searchValue);
        formData.append('id_institucion', idInstitucionCompras);

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
 * Envía los datos del PDF costeadas.
 */
function fntGenerarPDFCosteadas(reporteData, fechaInicio, fechaFin, searchValue) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/compra/reporte_costeadas_filtrado.php";
    form.target = '_blank';

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'reporteData';
    inputData.value = JSON.stringify({ data: reporteData, fechaInicio, fechaFin, searchValue });
    form.appendChild(inputData);

    const inputInst = document.createElement('input');
    inputInst.type = 'hidden';
    inputInst.name = 'nombreInstitucion';
    inputInst.value = nombreInstitucionCompras;
    form.appendChild(inputInst);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * PDF del costo individual (modal).
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

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'reporteData';
    inputData.value = JSON.stringify(detalleCosteadoData);
    form.appendChild(inputData);

    const inputInst = document.createElement('input');
    inputInst.type = 'hidden';
    inputInst.name = 'nombreInstitucion';
    inputInst.value = nombreInstitucionCompras;
    form.appendChild(inputInst);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Reporte diario (si existe el form).
 */
async function generarReporteDiario(e) {
    e.preventDefault();
    // Mantener la lógica existente si la usas
    // ...
}

/**
 * Notificación toast.
 */
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