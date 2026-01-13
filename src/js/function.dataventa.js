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

    // Función para cargar todos los datos iniciales
    async function loadInitialData() {
        // Cargar total de litros del sistema
        try {
            const response = await fetch(base_url + 'Estacion/getLitrosTotales')
            const result = await response.json()
            if (result.success) {
                totalLitrosSistemaSpan.textContent = `${parseFloat(result.totalLitros).toFixed(2)} L`
            }
        } catch (error) {
            console.error('Error al cargar total de litros:', error)
        }
        // Cargar historial de cierres
        try {
            const response = await fetch(base_url + 'Estacion/getHistorialCierres')
            const result = await response.json()
            // Con el cambio en el backend, 'data' siempre existirá si 'success' es true.
            if (result.success && Array.isArray(result.data)) {
                renderCierresTable(result.data); // result.data será [] si no hay cierres
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

            // Comprobación robusta: Asegurarse de que result.data es un array y tiene elementos
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
                        <button class="btn btn-success btn-sm close-sale-btn" data-fecha="${sale.fecha_venta}" data-iduser="${sale.id_user}">
                            Cerrar Venta
                        </button>
                        <button class="btn btn-info btn-sm print-pdf-btn" data-id="${sale.id_cierre}" data-iduser="${sale.id_user}" data-fecha="${sale.fecha_venta}">
                            Imprimir PDF
                        </button>
                    </td>
                </tr>
            `
        })
        openSalesTableBody.innerHTML = html
    }
    // Event listener para los botones de ventas abiertas
    openSalesTableBody.addEventListener('click', async function (e) {
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
                        // 1. Obtener e imprimir el reporte detallado PRIMERO.
                        const detailedResponse = await fetch(base_url + 'Estacion/getDetalleVentas', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idUser: userId, fecha_detalle: fechaVenta })
                        });
                        const detailedResult = await detailedResponse.json();
                        if (detailedResult.success) {
                            await fntImprimirDetallado(detailedResult.ticketData);
                        }

                        // 2. Realizar el cierre en el servidor DESPUÉS de imprimir el detallado.
                        const closeResponse = await fetch(base_url + 'Estacion/cerrarTurnoPendiente', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ userId: userId, fecha_cierre: fechaVenta })
                        });
                        const closeResult = await closeResponse.json();

                        if (closeResult.success) {
                            // 3. Imprimir el reporte de cierre con los datos de la respuesta.
                            if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                                await fntImprimirCierre(closeResult.dataCierre);
                            }
                            // Recargar ventas abiertas y datos iniciales
                            loadOpenSales()
                            loadInitialData()

                        } else {
                            notifi(result.message, 'error')
                        }
                    } catch (error) {
                        console.error('Error al cerrar venta:', error)
                        notifi('Error al cerrar la venta', 'error')
                    }
                }
            })
        }
        if (e.target.classList.contains('print-pdf-btn')) {
            try {
                // Obtener los datos directamente del botón que se hizo clic
                const fechaVenta = e.target.dataset.fecha;
                const userId = e.target.dataset.iduser; // Asegúrate de que este atributo existe en el botón
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

            // Establecer un título especial para indicar que es una venta en curso
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
                    // Desplazarse a la sección de detalles
                    ventasCierreSection.scrollIntoView({ behavior: 'smooth' });
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                console.error('Error al cargar tickets abiertos:', error);
                notifi('Error al cargar los tickets.', 'error');
            }
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
                // Cambia la URL para que apunte a tu archivo local
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
                    "data": "total_litros_vendidos", "render": function (data) {
                        return `${parseFloat(data).toFixed(2)} L`;
                    }
                },
                {
                    "data": null,
                    "render": function (data, type, row) {
                        return `<button class="btn btn-link btn-sm show-ventas-btn" data-id="${row.id_cierre}" data-iduser="${row.id_user}" data-fecha="${row.fecha_cierre}">Ver Ventas</button>`;
                    }
                }
            ],
            responsive: false, // Desactivado para permitir el scroll horizontal del contenedor
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50],
            order: [[3, 'desc']], // Ordenar por fecha (columna 4) descendente
            columnDefs: [
                { orderable: false, targets: [8] }, // Hacer que la columna de acciones no sea ordenable
            ]
        });
    }
    // Función para renderizar la lista de ventas de un cierre
    function renderVentasList(data) {
        // Limpiar el contenedor principal
        ventasCierreList.innerHTML = '';

        // Actualizar títulos y manejar el caso sin datos
        if (data.length > 0) {
            document.getElementById('fechaCierre').textContent = data[0].fecha_venta;
            document.getElementById('nameEmp').textContent = data[0].empleado;
            // Asignar datos a los botones de acción
            btnImprimirCierre.dataset.fecha = data[0].fecha_venta;
            // --- INICIO DE LA CORRECCIÓN ---
            btnImprimirCierre.dataset.idcierre = cierreIdTitle.textContent; // Guardamos el ID del cierre en el botón
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

        // Crear la estructura de la tabla dinámicamente
        const tableHTML = `
            <table id="ventasCierreTable" class="table table-bordered table-striped table-sm w-100">
                <thead>
                    <tr>
                        <th># Venta</th>
                        <th>Hora</th>
                        <th>Tipo Vehículo</th>
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

        // Destruir DataTable si ya existe para evitar conflictos
        if (tableVentasCierre) {
            tableVentasCierre.destroy();
        }

        // Inicializar la DataTable en la tabla recién creada
        tableVentasCierre = $('#ventasCierreTable').DataTable({
            "data": data,
            "columns": [
                { "data": "numero_venta" },
                { "data": "hora_venta" },
                { "data": "tipo_vehiculo" },
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
                        // Se combinan ambos botones en un solo return
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
        if (e.target.classList.contains('show-ventas-btn')) {
            const idCierre = e.target.dataset.id
            const iduser = e.target.dataset.iduser
            cierreIdTitle.textContent = idCierre
            const fechaCierre = e.target.dataset.fecha
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
                    fntImprimirTicket({ ticketData: result.ticketData, copia: 1 }); // 1 para marcar como copia
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
                            // Recargar la lista de ventas después de la eliminación
                            const idCierre = cierreIdTitle.textContent

                            // Verificar si estamos en una venta abierta (EN CURSO) o un cierre
                            if (idCierre === "EN CURSO") {
                                // Recargar usando el endpoint de ventas abiertas
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
                                // Recargar usando el endpoint de cierres (lógica original)
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
                    // Mover la recarga de datos aquí para que se ejecute siempre después de la operación
                    loadInitialData();
                }
            })
        }
    })

    // Event listener para el botón de imprimir
    btnImprimirCierre.addEventListener('click', async function () {
        try {
            const idUser = this.dataset.iduser;
            const fechaVenta = this.dataset.fecha;
            const idCierre = this.dataset.idcierre; // Obtenemos el ID del cierre desde el botón
            const response = await fetch(base_url + 'Estacion/getDataCierre', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ idUser: idUser, fecha_venta: fechaVenta, idCierre: idCierre }) // Enviamos los 3 parámetros
            });
            const result = await response.json();
            if (result.success) {
                await fntImprimirCierre(result.cierreData); // Usar result.cierreData
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
     * Función para cargar dinámicamente las fechas disponibles con ventas
     * y mostrar el total de litros vendidos al seleccionar una fecha
     */
    async function loadFechasConVentas() {
        try {
            const response = await fetch(base_url + 'Estacion/getFechasConVentas')
            const result = await response.json()
            if (result.success && result.fechas.length > 0) {
                const selectFecha = document.getElementById('selectFechaCierre')
                selectFecha.innerHTML = '' // Limpiar opciones existentes
                // Agregar opción por defecto
                const defaultOption = document.createElement('option')
                defaultOption.value = ''
                defaultOption.textContent = 'Selecciona una fecha'
                defaultOption.disabled = true
                defaultOption.selected = true
                selectFecha.appendChild(defaultOption)
                // Agregar todas las fechas disponibles
                result.fechas.forEach(fecha => {
                    const option = document.createElement('option')
                    option.value = fecha.fecha_venta
                    // Formatear la fecha para mostrarla en formato más legible
                    const [day, month, year] = fecha.fecha_venta.split('-')
                    const dateObj = new Date(`20${year}`, month - 1, day)
                    const formattedDate = dateObj.toLocaleDateString('es-ES', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    })
                    option.textContent = formattedDate
                    option.dataset.originalDate = fecha.fecha_venta // Guardar fecha original
                    selectFecha.appendChild(option)
                })
                // Agregar event listener para cuando se seleccione una fecha
                selectFecha.addEventListener('change', async function () {
                    const fechaSeleccionada = this.options[this.selectedIndex].dataset.originalDate
                    if (fechaSeleccionada) {
                        await loadLitrosPorFecha(fechaSeleccionada)
                    } else {
                        document.getElementById('totalLitrosFecha').textContent = '0 L'
                    }
                })
            } else {
                notifi('No se encontraron fechas con ventas registradas.', 'info')
            }
        } catch (error) {
            console.error('Error al cargar las fechas:', error)
            notifi('Error al cargar las fechas disponibles.', 'error')
        }
        // Cargar ventas abiertas
        await loadOpenSales()
    }
    /**
     * Función para cargar los litros vendidos en una fecha específica
     * @param {string} fecha - Fecha en formato dd-mm-yy
     */
    async function loadLitrosPorFecha(fecha) {
        try {
            const response = await fetch(base_url + 'Estacion/getLitrosPorFecha', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha: fecha })
            })
            const result = await response.json()
            if (result.success) {
                const totalLitros = parseFloat(result.totalLitros) || 0
                document.getElementById('totalLitrosFecha').textContent = `${totalLitros.toFixed(2)} L`
                // Mostrar notificación de éxito
                notifi(`Total de litros vendidos: ${totalLitros.toFixed(2)} L`, 'success')
            } else {
                document.getElementById('totalLitrosFecha').textContent = '0 L'
                notifi(result.message || 'No hay datos para esta fecha.', 'info')
            }
        } catch (error) {
            console.error('Error al cargar litros por fecha:', error)
            document.getElementById('totalLitrosFecha').textContent = '0 L'
            notifi('Error al cargar los litros vendidos.', 'error')
        }
    }
    // Llamar a la función para cargar las fechas cuando el DOM esté listo
    // Cargar datos al iniciar
    loadFechasConVentas()
    loadInitialData()
})