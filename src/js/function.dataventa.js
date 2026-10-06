document.addEventListener('DOMContentLoaded', async function () {
    // Iniciar la conexión con QZ Tray al cargar la página
    if (typeof initQZTrayConnection === 'function') {
        initQZTrayConnection();
    }
    const totalLitrosSistemaSpan = document.getElementById('totalLitrosSistema')
    let tableCierres; // Variable para la instancia de DataTable
    let tableVentasCierre; // Variable para la DataTable de ventas de un cierre
    const selectFechaCierre = document.getElementById('selectFechaCierre')
    const totalLitrosFechaSpan = document.getElementById('totalLitrosFecha')
    const cierresTableBody = document.getElementById('cierresTableBody')
    const ventasCierreSection = document.getElementById('ventasCierreSection')
    const cierreIdTitle = document.getElementById('cierreIdTitle')
    const fechaCierreTitle = document.getElementById('fechaCierre')
    const usuarioTitle = document.getElementById('nameEmp')
    const ventasCierreList = document.getElementById('ventasCierreList')
    const btnImprimirCierre = document.getElementById('btnImprimirCierre')
    const btnImprimirPdf = document.getElementById('btnImprimirPdf')
    const openSalesTableBody = document.getElementById('openSalesTableBody')
    const noOpenSalesMessage = document.getElementById('noOpenSalesMessage')

    // --- INICIO: Sección de Tasa del Día ---
    function setupTasaSection() {
        const table = openSalesTableBody ? openSalesTableBody.closest('table') : null;
        const referenceElement = table || noOpenSalesMessage;

        if (referenceElement) {
            const container = document.createElement('div');
            container.className = 'card mb-3 border-info';
            container.innerHTML = `
                <div class="card-header bg-info">
                    <h3 class="card-title mb-0"><i class="fas fa-dollar-sign mr-2"></i>Tasa del Día</h3>
                </div>
                <div class="card-body py-2">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Bs.</span>
                                </div>
                                <input type="number" id="txtTasaDataVenta" class="form-control font-weight-bold" step="0.01" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-success btn-block" id="btnUpdateTasaDataVenta">
                                <i class="fas fa-sync-alt mr-2"></i>Actualizar
                            </button>
                        </div>
                        <div class="col-md-5 text-right">
                             <small class="text-muted font-italic" id="tasaLastUpdateDataVenta"></small>
                        </div>
                    </div>
                </div>
            `;

            referenceElement.parentNode.insertBefore(container, referenceElement);

            loadTasa();
            document.getElementById('btnUpdateTasaDataVenta').addEventListener('click', updateTasa);
        }
    }

    async function loadTasa() {
        try {
            const response = await fetch(base_url + 'Estacion/getTasaCurrent');
            const result = await response.json();
            if (result.success && result.tasa) {
                const tasaInput = document.getElementById('txtTasaDataVenta');
                const lastUpdate = document.getElementById('tasaLastUpdateDataVenta');

                if (tasaInput) tasaInput.value = parseFloat(result.tasa.tasa_dia).toFixed(2);
                if (lastUpdate && result.tasa.tasa_update) {
                    lastUpdate.textContent = 'Última actualización: ' + result.tasa.tasa_update;
                }
            }
        } catch (error) {
            console.error('Error loading tasa:', error);
        }
    }

    async function updateTasa() {
        const tasaInput = document.getElementById('txtTasaDataVenta');
        const nuevaTasa = parseFloat(tasaInput.value);

        if (!nuevaTasa || nuevaTasa <= 0) {
            notifi('Ingrese una tasa válida.', 'warning');
            return;
        }

        try {
            const response = await fetch(base_url + 'Estacion/updateTasa', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tasa: nuevaTasa })
            });
            const result = await response.json();

            if (result.success) {
                notifi(result.message, 'success');
                loadTasa();
                loadInitialData();
            } else {
                notifi(result.message, 'error');
            }
        } catch (error) {
            console.error('Error updating tasa:', error);
            notifi('Error al actualizar la tasa.', 'error');
        }
    }
    // --- FIN: Sección de Tasa del Día ---

    // Función para cargar todos los datos iniciales
    async function loadInitialData() {
        try {
            const response = await fetch(base_url + 'Estacion/getLitrosTotales')
            const result = await response.json()
            if (result.success) {
                totalLitrosSistemaSpan.textContent = `${parseFloat(result.totalLitros).toFixed(2)} L`
            }
        } catch (error) {
            console.error('Error al cargar total de litros:', error)
        }
        try {
            const response = await fetch(base_url + 'Estacion/getHistorialCierres')
            const result = await response.json()
            if (result.success && Array.isArray(result.data)) {
                renderCierresTable(result.data);
            } else {
                throw new Error(result.message || 'Respuesta no exitosa o formato incorrecto.');
            }
        } catch (error) {
            console.error('Error al cargar historial de cierres:', error);
            cierresTableBody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-red-500">Error al cargar datos.</td></tr>'
        }
    }

    /**
     * Cargar y mostrar la lista de ventas abiertas
     */
    async function loadOpenSales() {
        try {
            const response = await fetch(base_url + 'Estacion/getOpenSales')
            const result = await response.json()

            if (result.success && Array.isArray(result.data) && result.data.length > 0) {
                noOpenSalesMessage.style.display = 'none';
                renderOpenSalesTable(result.data)
            } else {
                noOpenSalesMessage.style.display = 'block';
                openSalesTableBody.innerHTML = ''
            }

        } catch (error) {
            console.error('Error al cargar ventas abiertas:', error)
            notifi('Error al cargar las ventas abiertas.', 'error')
        }
    }

    // Función para renderizar la tabla de ventas abiertas
    function renderOpenSalesTable(data) {
        let html = ''
        data.forEach(sale => {
            html += `
                <tr>
                    <td>${sale.nombre} ${sale.apellido}</td>
                    <td>${sale.fecha_venta}</td>
                    <td>${parseFloat(sale.total_litros).toFixed(2)} L</td>
                    <td class="text-center">
                        <button class="btn btn-warning btn-sm view-open-sales-btn" data-fecha="${sale.fecha_venta}" data-iduser="${sale.id_user}" title="Ver Tickets">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-primary btn-sm print-detallado-open-btn" data-fecha="${sale.fecha_venta}" data-iduser="${sale.id_user}" title="Reimprimir Detallado">
                            <i class="fas fa-receipt"></i>
                        </button>
                        <button class="btn btn-success btn-sm close-sale-btn" data-fecha="${sale.fecha_venta}" data-iduser="${sale.id_user}">
                            Cerrar Venta
                        </button>
                        <button class="btn btn-info btn-sm print-pdf-btn" data-id="${sale.id_cierre}" data-iduser="${sale.id_user}" data-fecha="${sale.fecha_venta}" title="Imprimir PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button class="btn btn-danger btn-sm delete-all-sales-btn" data-fecha="${sale.fecha_venta}" data-iduser="${sale.id_user}" title="Eliminar Registro Completo">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `
        })
        openSalesTableBody.innerHTML = html
    }

    // Event listener para los botones de ventas abiertas
    openSalesTableBody.addEventListener('click', async function (e) {
        // --- Cerrar venta (con modo de detallado) ---
        if (e.target.classList.contains('close-sale-btn')) {
            const fechaVenta = e.target.dataset.fecha
            const userId = e.target.dataset.iduser
            Swal.fire({
                title: '¿Deseas cerrar esta venta?',
                text: `Esto generará el reporte de cierre para la venta seleccionada.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, cerrar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        // 1. Preguntar cómo imprimir el detallado
                        const modoDetallado = await fntPreguntarModoDetallado();

                        // 2. Obtener el detallado
                        const detailedResponse = await fetch(base_url + 'Estacion/getDetalleVentas', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idUser: userId, fecha_detalle: fechaVenta })
                        });
                        const detailedResult = await detailedResponse.json();
                        if (detailedResult.success && modoDetallado) {
                            await fntImprimirDetallado(detailedResult.ticketData, modoDetallado);
                        }

                        // 3. Cerrar en el servidor
                        const closeResponse = await fetch(base_url + 'Estacion/cerrarTurnoPendiente', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ userId: userId, fecha_cierre: fechaVenta })
                        });
                        const closeResult = await closeResponse.json();

                        if (closeResult.success) {
                            // 4. Imprimir el cierre
                            if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                                await fntImprimirCierre(closeResult.dataCierre);
                            }
                            loadOpenSales()
                            loadInitialData()

                        } else {
                            notifi(closeResult.message, 'error')
                        }
                    } catch (error) {
                        console.error('Error al cerrar venta:', error)
                        notifi('Error al cerrar la venta', 'error')
                    }
                }
            })
        }

        // --- Reimprimir detallado de una venta abierta (sin cerrar) ---
        if (e.target.closest('.print-detallado-open-btn')) {
            const btn = e.target.closest('.print-detallado-open-btn');
            const fechaVenta = btn.dataset.fecha;
            const userId = btn.dataset.iduser;
            try {
                const modoDetallado = await fntPreguntarModoDetallado();
                if (!modoDetallado) return;

                const response = await fetch(base_url + 'Estacion/getDetalleVentas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idUser: userId, fecha_detalle: fechaVenta })
                });
                const result = await response.json();
                if (result.success) {
                    await fntImprimirDetallado(result.ticketData, modoDetallado);
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                console.error('Error al imprimir detallado:', error);
                notifi('Error al imprimir el detallado.', 'error');
            }
        }

        // --- Imprimir PDF (usando la nueva función con preguntas) ---
        if (e.target.classList.contains('print-pdf-btn')) {
            try {
                const fechaVenta = e.target.dataset.fecha;
                const userId = e.target.dataset.iduser;
                fntGenerarPDF({ idUser: parseInt(userId), fecha: fechaVenta });
            } catch (error) {
                console.error('Error al generar PDF:', error);
                notifi('Error al generar el PDF de ventas.', 'error');
            }
        }

        // Listener para el botón de ver tickets de venta abierta
        if (e.target.closest('.view-open-sales-btn')) {
            const btn = e.target.closest('.view-open-sales-btn');
            const fechaVenta = btn.dataset.fecha;
            const userId = btn.dataset.iduser;

            cierreIdTitle.textContent = "EN CURSO";

            try {
                const response = await fetch(base_url + 'Estacion/getVentasAbiertas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idUser: userId, fecha: fechaVenta })
                });
                const result = await response.json();
                if (result.success) {
                    renderVentasList(result.data);
                    ventasCierreSection.scrollIntoView({ behavior: 'smooth' });
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                console.error('Error al cargar tickets abiertos:', error);
                notifi('Error al cargar los tickets.', 'error');
            }
        }

        // Listener para el botón de eliminar todo el registro de ventas abiertas
        if (e.target.closest('.delete-all-sales-btn')) {
            const btn = e.target.closest('.delete-all-sales-btn');
            const fechaVenta = btn.dataset.fecha;
            const userId = btn.dataset.iduser;

            Swal.fire({
                title: '¿Eliminar registro completo?',
                text: "Se eliminarán todas las ventas abiertas de este usuario para la fecha seleccionada. Esta acción es irreversible.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(base_url + 'Estacion/deleteAllOpenSales', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idUser: userId, fecha: fechaVenta })
                        });
                        const res = await response.json();
                        if (res.success) {
                            notifi(res.message, 'success');
                            loadOpenSales();
                            loadInitialData();

                            if (cierreIdTitle.textContent === "EN CURSO" && document.getElementById('fechaCierre').textContent === fechaVenta) {
                                ventasCierreSection.style.display = 'none';
                            }
                        } else {
                            notifi(res.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        notifi('Error al eliminar los registros.', 'error');
                    }
                }
            });
        }
    })

    function renderCierresTable(data) {
        if (!tableCierres) {
            initializeDataTable();
        }
        tableCierres.clear().rows.add(data).draw();
    }

    function initializeDataTable() {
        tableCierres = $('#cierresTable').DataTable({
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            "language": {
                "url": base_url + "src/plugins/js/es_es.json"
            },
            "columns": [
                { "data": "id_cierre" },
                {
                    "data": null, "render": function (data, type, row) {
                        return `${row.usuario_nombres} ${row.usuario_apellidos}`;
                    }
                },
                {
                    "data": "tasa_dia", "render": function (data) {
                        return parseFloat(data).toFixed(2);
                    }
                },
                { "data": "fecha_cierre" },
                {
                    "data": "efectivo_bs", "render": function (data) {
                        return `${parseFloat(data).toFixed(2)} Bs`;
                    }
                },
                {
                    "data": "debito_bs", "render": function (data) {
                        return `${parseFloat(data).toFixed(2)} Bs`;
                    }
                },
                {
                    "data": "total_bs", "render": function (data) {
                        return `${parseFloat(data).toFixed(2)} Bs`;
                    }
                },
                {
                    // Litros con desglose por combustible en tooltip
                    "data": "total_litros_vendidos",
                    "render": function (data, type, row) {
                        const total = parseFloat(data).toFixed(2);
                        const gas = parseFloat(row.litros_gasolina || 0).toFixed(2);
                        const die = parseFloat(row.litros_diesel || 0).toFixed(2);
                        let detalle = '';
                        if (parseFloat(gas) > 0) detalle += `Gasolina: ${gas} L\n`;
                        if (parseFloat(die) > 0) detalle += `Diesel: ${die} L`;
                        if (detalle) {
                            return `<span title="${detalle.trim()}">${total} L <i class="fas fa-info-circle text-info"></i></span>`;
                        }
                        return `${total} L`;
                    }
                },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return `
                            <button class="btn btn-info btn-sm show-ventas-btn" data-id="${row.id_cierre}" data-iduser="${row.id_user}" data-fecha="${row.fecha_cierre}" title="Ver Ventas"><i class="fas fa-eye"></i></button>
                            <button class="btn btn-primary btn-sm reimprimir-detallado-cierre-btn" data-id="${row.id_cierre}" data-iduser="${row.id_user}" data-fecha="${row.fecha_cierre}" title="Reimprimir Detallado"><i class="fas fa-receipt"></i></button>
                            <button class="btn btn-danger btn-sm delete-cierre-total-btn" data-id="${row.id_cierre}" data-iduser="${row.id_user}" data-fecha="${row.fecha_cierre}" title="Eliminar Cierre y Ventas"><i class="fas fa-trash"></i></button>
                        `;
                    }
                }
            ],
            responsive: false,
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [[3, 'desc']],
            columnDefs: [
                { orderable: false, targets: [8] },
            ]
        });
    }

    // Función para renderizar la lista de ventas de un cierre
    function renderVentasList(data) {
        ventasCierreList.innerHTML = '';

        if (data.length > 0) {
            document.getElementById('fechaCierre').textContent = data[0].fecha_venta;
            document.getElementById('nameEmp').textContent = data[0].empleado;
            btnImprimirCierre.dataset.fecha = data[0].fecha_venta;
            btnImprimirCierre.dataset.idcierre = cierreIdTitle.textContent;
            btnImprimirCierre.dataset.iduser = data[0].id_user;
            btnImprimirPdf.dataset.fecha = data[0].fecha_venta;
            btnImprimirPdf.dataset.iduser = data[0].id_user;
        } else {
            document.getElementById('fechaCierre').textContent = '-';
            document.getElementById('nameEmp').textContent = '-';
            ventasCierreList.innerHTML = '<p class="text-center text-muted">No se encontraron ventas para este cierre.</p>';
            ventasCierreSection.classList.remove('hidden');
            return;
        }

        // Crear la estructura de la tabla dinámicamente (con columna Combustible)
        const tableHTML = `
            <table id="ventasCierreTable" class="table table-bordered table-striped table-sm w-100">
                <thead>
                    <tr>
                        <th># Venta</th>
                        <th>Hora</th>
                        <th>Tipo Vehículo</th>
                        <th>Combustible</th>
                        <th>Litros</th>
                        <th>Tipo Pago</th>
                        <th>Monto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `;
        ventasCierreList.innerHTML = tableHTML;

        if (tableVentasCierre) {
            tableVentasCierre.destroy();
        }

        tableVentasCierre = $('#ventasCierreTable').DataTable({
            "data": data,
            "columns": [
                { "data": "numero_venta" },
                { "data": "hora_venta" },
                { "data": "tipo_vehiculo" },
                {
                    "data": "tipo_combustible",
                    "render": function (d) {
                        if (!d) return 'Gasolina';
                        return d;
                    }
                },
                { "data": "cantidad_litros", "render": function (d) { return `${parseFloat(d).toFixed(2)} L`; } },
                { "data": "tipo_pago" },
                {
                    "data": "monto", "render": function (d, type, row) {
                        const simbolo = row.tipo_pago === 'Efectivo Divisa' ? '$' : 'Bs';
                        return `${parseFloat(d).toFixed(2)} ${simbolo}`;
                    }
                },
                {
                    "data": null,
                    "orderable": false,
                    "className": "text-center",
                    "render": function (d, type, row) {
                        return `<button class="btn btn-info btn-xs print-ticket-btn" data-id="${row.numero_venta}" data-iduser="${row.id_user}" data-fecha="${row.fecha_venta}" title="Imprimir Copia"><i class="fas fa-print"></i></button>
                                <button class="btn btn-danger btn-xs delete-venta-btn" data-id="${row.numero_venta}" data-iduser="${row.id_user}" data-fecha="${row.fecha_venta}" title="Eliminar Ticket"><i class="far fa-trash-alt"></i></button>
                                `;
                    }
                }
            ],
            "language": { "url": base_url + "src/plugins/js/es_es.json" },
            "responsive": true,
            "bDestroy": true,
            "iDisplayLength": 5,
            "lengthMenu": [5, 10, 25],
            "order": [[0, "asc"]]
        });

        ventasCierreSection.style.display = 'block';
    }

    // Event listener para los botones de la tabla de cierres
    cierresTableBody.addEventListener('click', async function (e) {
        const showBtn = e.target.closest('.show-ventas-btn');
        if (showBtn) {
            const idCierre = showBtn.dataset.id;
            const iduser = showBtn.dataset.iduser;
            cierreIdTitle.textContent = idCierre
            const fechaCierre = showBtn.dataset.fecha;
            try {
                const response = await fetch(base_url + 'Estacion/getVentasByCierre', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ iduser: iduser, idCierre: idCierre, fechaCierre: fechaCierre })
                })
                const result = await response.json()
                if (result.success) {
                    renderVentasList(result.data)
                } else {
                    renderVentasList([])
                    notifi(result.message, 'error')
                }
            } catch (error) {
                console.error('Error al obtener las ventas del cierre:', error)
                notifi('Error al cargar las ventas. Intenta de nuevo.', 'error')
            }
        }

        // --- NUEVO: Reimprimir detallado de un cierre histórico ---
        if (e.target.closest('.reimprimir-detallado-cierre-btn')) {
            const btn = e.target.closest('.reimprimir-detallado-cierre-btn');
            const idCierre = btn.dataset.id;
            const idUser = btn.dataset.iduser;
            const fechaCierre = btn.dataset.fecha;
            try {
                // 1. Preguntar modo del detallado
                const modoDetallado = await fntPreguntarModoDetallado();
                if (!modoDetallado) return;

                // 2. Obtener el detallado del cierre (usamos getDataVenta que ya trae las ventas con combustible)
                const response = await fetch(base_url + 'Estacion/getVentasByCierre', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ iduser: idUser, idCierre: idCierre, fechaCierre: fechaCierre })
                });
                const result = await response.json();
                if (result.success) {
                    await fntImprimirDetallado(result.data, modoDetallado);
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                console.error('Error al reimprimir el detallado:', error);
                notifi('Error al reimprimir el detallado.', 'error');
            }
        }

        // Listener para el botón de eliminar cierre TOTAL (Cierre + Ventas)
        if (e.target.closest('.delete-cierre-total-btn')) {
            const btn = e.target.closest('.delete-cierre-total-btn');
            const idCierre = btn.dataset.id;
            const idUser = btn.dataset.iduser;
            const fechaCierre = btn.dataset.fecha;

            Swal.fire({
                title: '¿Eliminar Cierre y Ventas?',
                text: `Se eliminará el cierre #${idCierre} del día ${fechaCierre} y TODAS sus ventas asociadas. Esta acción es irreversible.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar todo',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(base_url + 'Estacion/deleteCierreTotal', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idCierre: idCierre, idUser: idUser, fecha: fechaCierre })
                        });
                        const res = await response.json();
                        if (res.success) {
                            notifi(res.message, 'success');
                            loadInitialData();
                            if (cierreIdTitle.textContent == idCierre) {
                                ventasCierreSection.style.display = 'none';
                                ventasCierreList.innerHTML = '';
                            }
                        } else {
                            notifi(res.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        notifi('Error al eliminar el cierre.', 'error');
                    }
                }
            });
        }
    })

    // Event listener para el botón de imprimir copia de ticket
    ventasCierreList.addEventListener('click', async function (e) {
        const printButton = e.target.closest('.print-ticket-btn');
        if (printButton) {
            try {
                const idVenta = printButton.dataset.id;
                const fechaTicket = printButton.dataset.fecha;
                const idUser = printButton.dataset.iduser;

                const response = await fetch(base_url + 'Estacion/getTicket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idVenta: idVenta, fechaTicket: fechaTicket, idUser: idUser })
                });
                const result = await response.json();
                if (result.success) {
                    fntImprimirTicket({ ticketData: result.ticketData, copia: 1 });
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                notifi('Error al obtener los datos del ticket para imprimir.', 'error');
            }
        }
    })

    // Event listener para los botones de eliminar en la lista de ventas
    ventasCierreList.addEventListener('click', async function (e) {
        if (e.target.classList.contains('delete-venta-btn')) {
            const idVenta = e.target.dataset.id
            const idUser = e.target.dataset.iduser
            const fechaTicket = e.target.dataset.fecha
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede revertir. Se eliminará el ticket de venta permanentemente.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await fetch(base_url + 'Estacion/deleteVenta', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idVenta: idVenta, fechaTicket: fechaTicket, idUser: idUser })
                        })
                        const result = await response.json()
                        if (result.success) {
                            notifi('¡Eliminado!', 'success')
                            const idCierre = cierreIdTitle.textContent

                            if (idCierre === "EN CURSO") {
                                const responseRefresh = await fetch(base_url + 'Estacion/getVentasAbiertas', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ idUser: idUser, fecha: fechaTicket })
                                })
                                const resultRefresh = await responseRefresh.json()
                                if (resultRefresh.success) {
                                    if (resultRefresh.data.length === 0) {
                                        ventasCierreSection.style.display = 'none';
                                    } else {
                                        renderVentasList(resultRefresh.data);
                                    }
                                }
                            } else {
                                const responseRefresh = await fetch(base_url + 'Estacion/getVentasByCierre', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json' },
                                    body: JSON.stringify({ iduser: idUser, idCierre: idCierre, fechaCierre: fechaTicket })
                                })
                                const resultRefresh = await responseRefresh.json()
                                if (resultRefresh.success) {
                                    if (resultRefresh.data.length === 0) {
                                        ventasCierreSection.style.display = 'none';
                                    } else {
                                        renderVentasList(resultRefresh.data);
                                    }
                                } else {
                                    notifi(resultRefresh.message || 'Error al refrescar las ventas.', 'error');
                                }
                            }
                        } else {
                            notifi(result.message, 'error')
                        }
                    } catch (error) {
                        notifi('Error al eliminar el ticket. Intenta de nuevo.', 'error')
                    }
                    loadInitialData();
                }
            })
        }
    })

    // Event listener para el botón de imprimir cierre
    btnImprimirCierre.addEventListener('click', async function () {
        try {
            const idUser = this.dataset.iduser;
            const fechaVenta = this.dataset.fecha;
            const idCierre = this.dataset.idcierre;
            const response = await fetch(base_url + 'Estacion/getDataCierre', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idUser: idUser, fecha_venta: fechaVenta, idCierre: idCierre })
            });
            const result = await response.json();
            if (result.success) {
                await fntImprimirCierre(result.cierreData);
            } else {
                notifi(result.message, 'error');
            }
        } catch (error) {
            notifi('Error al obtener los datos para imprimir el cierre.', 'error');
        }
    });

    btnImprimirPdf.addEventListener('click', async function () {
        try {
            const fecha = this.dataset.fecha;
            const idUsuario = this.dataset.iduser;
            fntGenerarPDF({ idUser: parseInt(idUsuario), fecha: fecha });
        } catch (error) {
            console.error('Error al generar PDF:', error);
            notifi('Error al generar el PDF de ventas.', 'error');
        }
    });

    /**
     * Configura los controles de búsqueda por fecha (Día o Mes)
     */
    function setupDateSearch() {
        const selectFechaCierre = document.getElementById('selectFechaCierre');
        if (!selectFechaCierre) return;

        const parent = selectFechaCierre.parentNode;

        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'row g-2 align-items-center';

        controlsContainer.innerHTML = `
            <div class="col-auto">
                <select id="searchType" class="form-control form-control-sm" style="min-width: 100px;">
                    <option value="day">Por Día</option>
                    <option value="month">Por Mes</option>
                </select>
            </div>
            <div class="col">
                <input type="date" id="searchDateDay" class="form-control form-control-sm">
                <div id="monthRangeContainer" style="display:none;">
                    <input type="month" id="searchDateMonthStart" class="form-control form-control-sm mr-1" placeholder="Desde">
                    <input type="month" id="searchDateMonthEnd" class="form-control form-control-sm" placeholder="Hasta">
                </div>
            </div>
            <div class="col-auto">
                <button id="btnPrintReport" class="btn btn-danger btn-sm" title="Imprimir Reporte" style="display:none;"><i class="fas fa-file-pdf"></i></button>
            </div>
        `;

        parent.insertBefore(controlsContainer, selectFechaCierre);
        selectFechaCierre.remove();

        const searchType = document.getElementById('searchType');
        const searchDateDay = document.getElementById('searchDateDay');
        const searchDateMonthStart = document.getElementById('searchDateMonthStart');
        const searchDateMonthEnd = document.getElementById('searchDateMonthEnd');
        const monthRangeContainer = document.getElementById('monthRangeContainer');
        const btnPrintReport = document.getElementById('btnPrintReport');

        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');

        searchDateDay.value = `${yyyy}-${mm}-${dd}`;
        searchDateMonthStart.value = `${yyyy}-${mm}`;
        searchDateMonthEnd.value = `${yyyy}-${mm}`;

        loadLitrosPorFecha(searchDateDay.value, 'day');

        searchType.addEventListener('change', function () {
            if (this.value === 'day') {
                searchDateDay.style.display = 'block';
                monthRangeContainer.style.display = 'none';
                btnPrintReport.style.display = 'none';
                loadLitrosPorFecha(searchDateDay.value, 'day');
            } else {
                searchDateDay.style.display = 'none';
                monthRangeContainer.style.display = 'flex';
                btnPrintReport.style.display = 'block';
                loadLitrosPorFecha(searchDateMonthStart.value, 'month', searchDateMonthEnd.value);
            }
        });

        searchDateDay.addEventListener('change', function () {
            if (this.value) loadLitrosPorFecha(this.value, 'day');
        });

        searchDateMonthStart.addEventListener('change', function () {
            if (this.value) {
                if (searchDateMonthEnd.value < this.value) {
                    searchDateMonthEnd.value = this.value;
                }
                loadLitrosPorFecha(this.value, 'month', searchDateMonthEnd.value);
            }
        });

        searchDateMonthEnd.addEventListener('change', function () {
            if (this.value) loadLitrosPorFecha(searchDateMonthStart.value, 'month', this.value);
        });

        btnPrintReport.addEventListener('click', function () {
            const type = searchType.value;
            if (type === 'day') return;

            const fecha = searchDateMonthStart.value;
            const fechaFin = searchDateMonthEnd.value;

            if (fecha) {
                fntGenerarReporteLitros(fecha, type, fechaFin);
            } else {
                notifi("Seleccione una fecha válida.", "warning");
            }
        });
    }

    async function fntGenerarReporteLitros(fecha, type, fechaFin = null) {
        try {
            const response = await fetch(base_url + 'Estacion/generarReporteLitros', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha: fecha, type: type, fechaFin: fechaFin })
            });
            const result = await response.json();
            if (result.success) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = base_url + "data/estacion/reporte_venta.php";
                form.target = '_blank';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reporteData';
                input.value = JSON.stringify(result);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } else {
                notifi(result.message, 'error');
            }
        } catch (error) {
            console.error(error);
            notifi("Error al generar el reporte.", "error");
        }
    }

    /**
     * Función para cargar los litros vendidos en una fecha específica
     */
    async function loadLitrosPorFecha(fecha, type = 'day', fechaFin = null) {
        try {
            const response = await fetch(base_url + 'Estacion/getLitrosPorFecha', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha: fecha, type: type, fechaFin: fechaFin })
            })
            const result = await response.json()
            if (result.success) {
                const totalLitros = parseFloat(result.totalLitros) || 0
                const formattedLitros = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(totalLitros);
                document.getElementById('totalLitrosFecha').textContent = `${formattedLitros} L`
            } else {
                document.getElementById('totalLitrosFecha').textContent = '0,00 L'
                notifi(result.message || 'No hay datos para esta fecha.', 'info')
            }
        } catch (error) {
            console.error('Error al cargar litros por fecha:', error)
            document.getElementById('totalLitrosFecha').textContent = '0,00 L'
            notifi('Error al cargar los litros vendidos.', 'error')
        }
    }

    // Cargar datos al iniciar
    setupDateSearch()
    setupTasaSection()
    loadOpenSales()
    loadInitialData()
})