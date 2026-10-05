document.addEventListener('DOMContentLoaded', function () {
    // Iniciar la conexión con QZ Tray al cargar la página
    if (typeof initQZTrayConnection === 'function') {
        initQZTrayConnection();
    }
    const ventaForm = document.getElementById('ventaForm')
    const tasaDisplay = document.getElementById('tasaDisplay')
    const tasaInput = document.querySelector('#txtTasa')
    const ltsInput = document.querySelector('#txtLTS')
    const montoInput = document.querySelector('#txtMonto')
    const selectTipoVehiculo = document.getElementById('txtListTipoVehiculo')
    const selectTipoPago = document.getElementById('txtListTipoPago')
    const ticketPreview = document.getElementById('ticketPreview')
    const btnUpdateTasa = document.getElementById('btnUpdateTasa')
    const ventasTableBody = document.querySelector('#ventasTable tbody')
    const totalVehiculosSpan = document.getElementById('totalVehiculos')
    const totalLitrosSpan = document.getElementById('totalLitros')
    const totalBolivaresSpan = document.getElementById('totalBolivares')
    const totalDivisasSpan = document.getElementById('totalDivisas')
    const tiposVehiculosContainer = document.getElementById('tiposVehiculosContainer')
    const tiposVehiculosList = document.getElementById('tiposVehiculosList')
    const tiposPagosContainer = document.getElementById('tiposPagosContainer')
    const tiposPagosList = document.getElementById('tiposPagosList')
    const btnCerrarDia = document.getElementById('btnCerrarDia')
    const btnGenerarPDF = document.getElementById('btnGenerarPDF')
    const tasaLastUpdateSpan = document.getElementById('tasaLastUpdate');
    const cierrePendienteSection = document.getElementById('cierrePendienteSection')
    const cierrePendienteButtons = document.getElementById('cierrePendienteButtons')
    // Inicializar DataTables
    let ventasDataTable;
    if ($.fn.DataTable.isDataTable('#ventasTable')) {
        ventasDataTable = $('#ventasTable').DataTable();
    } else {
        ventasDataTable = $('#ventasTable').DataTable({
            "dom": 'lfrtip',
            "language": {
                "url": base_url + "src/plugins/js/es_es.json"
            }
        })
    }

    /**
     * Carga los datos iniciales de la estación: tipos de vehículo, pago, tasa, ventas y resúmenes.
     */
    async function loadInitialData() {
        try {
            const response = await fetch(base_url + 'Estacion/initialData')
            const data = await response.json()
            if (data.success) {
                // Cargar tipos de vehículo
                selectTipoVehiculo.innerHTML = ''
                data.tiposVehiculo.forEach(vehiculo => {
                    const option = document.createElement('option')
                    option.value = vehiculo.id_tipo_vehiculo
                    option.textContent = vehiculo.nombre
                    selectTipoVehiculo.appendChild(option)
                })
                // Cargar tipos de pago
                selectTipoPago.innerHTML = ''
                data.tiposPago.forEach(pago => {
                    const option = document.createElement('option')
                    option.value = pago.id_tipo_pago
                    option.textContent = pago.nombre
                    selectTipoPago.appendChild(option)
                })
                // Cargar la tasa del día
                if (data.tasa) {
                    const tasaFormateada = parseFloat(data.tasa.tasa_dia).toFixed(2);
                    tasaInput.value = tasaFormateada;
                    tasaDisplay.textContent = tasaFormateada;

                    if (data.tasa.tasa_update && data.tasa.tasa_update !== '0000-00-00 00:00:00') {
                        const fechaUpdate = new Date(data.tasa.tasa_update.replace(/-/g, '/')); // Mejor compatibilidad
                        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                        tasaLastUpdateSpan.textContent = `Última actualización: ${fechaUpdate.toLocaleDateString('es-ES', options)}`;

                        const hoy = new Date();
                        if (fechaUpdate.getFullYear() === hoy.getFullYear() &&
                            fechaUpdate.getMonth() === hoy.getMonth() &&
                            fechaUpdate.getDate() === hoy.getDate()) {
                            // Tasa actualizada hoy: Ocultar input, mostrar span y ocultar botón
                            tasaInput.style.display = 'none';
                            tasaDisplay.style.display = 'block';
                            btnUpdateTasa.style.display = 'none';
                        } else {
                            // Tasa no actualizada: Mostrar input, ocultar span y mostrar botón
                            tasaInput.style.display = 'block';
                            tasaDisplay.style.display = 'none';
                            btnUpdateTasa.style.display = 'block';
                        }
                    } else {
                        tasaLastUpdateSpan.textContent = 'Aún no se ha actualizado hoy.';
                        tasaInput.style.display = 'block';
                        tasaDisplay.style.display = 'none';
                        btnUpdateTasa.style.display = 'block';
                    }
                }
                // Cargar tickets recientes
                updateVentasTable(data.ultimosTickets)
                // Cargar resumen de ventas
                updateDailySummary(data.resumen)
                // Cargar ventas pendientes
                updateVentasPendientes(data.ventasPendientes)
            } else {
                notifi(data.message, 'error')
            }
        } catch (error) {
            notifi('Error al cargar los datos iniciales: ' + error.message, 'error')
        }
    }

    // --- Funciones de Actualización de la UI ---

    /**
     * Actualiza la tabla de ventas recientes con nuevos datos.
     * @param {Array} tickets - Un array de objetos de ticket.
     */
    function updateVentasTable(tickets) {
        ventasDataTable.rows().remove().draw(); // Borrado más explícito de la tabla
        tickets.forEach(ticket => {
            const rowNode = ventasDataTable.row.add([
                ticket.id_venta,
                ticket.fecha_venta,
                ticket.tipoVehiculo,
                ticket.litros,
                `<button class="btn btn-info btn-sm print-ticket-btn" data-id="${ticket.id_venta}" data-fecha="${ticket.fecha_venta}" data-iduser="${ticket.id_user}"><i class="fas fa-print"></i></button>`
            ]).draw(false).node();
        })
    }

    /**
     * Actualiza las tarjetas de resumen diario con las estadísticas de ventas del día.
     * @param {Object} resumen - Objeto con los totales y desgloses del día.
     */
    function updateDailySummary(resumen) {
        if (resumen) {
            // Calcular el total de efectivo en bolívares (Efectivo Bs + Divisa convertida)
            const efectivoMasDivisaBs = (parseFloat(resumen.total_bs) || 0)
            // Calcular el Total General
            totalVehiculosSpan.textContent = resumen.total_ventas
            totalLitrosSpan.textContent = parseFloat(resumen.total_litros).toFixed(2) + " L"
            totalBolivaresSpan.textContent = efectivoMasDivisaBs.toFixed(2) + " Bs"
            // Actualizar Tipos de Pago y mostrar solo si hay datos
            tiposPagosList.innerHTML = ''
            const pagosExistentes = (resumen.total_divisa > 0) || (resumen.total_efectivo > 0) || (resumen.total_debito > 0)
            if (pagosExistentes) {
                if (resumen.total_divisa > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-dollar-sign text-success mr-2"></i>Divisa: <strong>${parseFloat(resumen.total_divisa).toFixed(2)} $</strong></li>`
                }
                if (resumen.total_efectivo > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-money-bill-wave text-primary mr-2"></i>Efectivo: <strong>${parseFloat(resumen.total_efectivo).toFixed(2)} Bs</strong></li>`
                }
                if (resumen.total_debito > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-credit-card text-info mr-2"></i>Punto de Venta: <strong>${parseFloat(resumen.total_debito).toFixed(2)} Bs</strong></li>`
                }
                tiposPagosContainer.style.display = 'block'
            } else {
                tiposPagosContainer.style.display = 'none'
            }
            // Actualizar Tipos de Vehículo y mostrar si hay datos
            tiposVehiculosList.innerHTML = ''
            if (resumen.tiposVehiculo && resumen.tiposVehiculo.length > 0) {
                resumen.tiposVehiculo.forEach(item => {
                    let iconClass = "fa-car-side" // Ícono por defecto
                    if (item.tipo_vehiculo.includes('Moto')) {
                        iconClass = "fa-motorcycle"
                    } else if (item.tipo_vehiculo.includes('Camion')) {
                        iconClass = "fa-solid fa-truck"
                    }
                    tiposVehiculosList.innerHTML += `<li class="mb-1"><i class="fas ${iconClass} text-secondary mr-2"></i>${item.tipo_vehiculo}: <strong>${item.cantidad}</strong></li>`
                })
                tiposVehiculosContainer.style.display = 'block'
            } else {
                tiposVehiculosContainer.style.display = 'none'
            }
            // Ocultar o mostrar los botones si no hay ventas
            if (resumen.total_ventas === 0) {
                btnCerrarDia.style.display = 'none'
                btnGenerarPDF.style.display = 'none'
            } else {
                btnCerrarDia.style.display = 'block'
                btnGenerarPDF.style.display = 'block'
            }
        } else {
            // Si el resumen es nulo (después de un cierre), reiniciar todo a cero.
            totalVehiculosSpan.textContent = '0';
            totalLitrosSpan.textContent = '0.00 L';
            totalBolivaresSpan.textContent = '0.00 Bs';
            tiposPagosList.innerHTML = '';
            tiposPagosContainer.style.display = 'none';
            tiposVehiculosList.innerHTML = '';
            tiposVehiculosContainer.style.display = 'none';
            btnCerrarDia.style.display = 'none';
            btnGenerarPDF.style.display = 'none';
        }
    }

    /**
     * Muestra u oculta la sección de cierres pendientes y genera los botones correspondientes.
     * @param {Array} ventas - Un array de objetos de ventas pendientes de cierre.
     */
    function updateVentasPendientes(ventas) {
        if (ventas && ventas.length > 0) {
            cierrePendienteSection.style.display = 'block'
            cierrePendienteButtons.innerHTML = ''
            ventas.forEach(venta => {
                cierrePendienteButtons.innerHTML += `
                    <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                        <span class="font-weight-bold text-danger">Día sin cierre: ${venta.fecha_venta}</span>
                        <button class="btn btn-danger btn-sm close-pending-btn" data-fecha="${venta.fecha_venta}" data-iduser="${venta.id_user}">Cerrar Día</button>
                        <button class="btn btn-warning btn-sm pdf-btn" data-fecha="${venta.fecha_venta}" data-iduser="${venta.id_user}">PDF</button>
                    </div>
                `
            })
        } else {
            cierrePendienteSection.style.display = 'none'
        }
    }

    /**
     * Carga y actualiza únicamente la sección de ventas pendientes.
     */
    async function reloadPendingSales() {
        try {
            const response = await fetch(base_url + 'Estacion/initialData'); // Reutilizamos el endpoint
            const data = await response.json();
            if (data.success) {
                // Solo actualizamos la parte de ventas pendientes
                updateVentasPendientes(data.ventasPendientes);
            } else {
                // Si falla, al menos limpiamos la sección para evitar datos incorrectos
                cierrePendienteSection.style.display = 'none';
                cierrePendienteButtons.innerHTML = '';
            }
        } catch (error) {
            console.error('Error al recargar ventas pendientes:', error);
            notifi('No se pudo actualizar la lista de cierres pendientes.', 'error');
        }
    }

    /**
     * Calcula el monto a pagar en función de los litros, la tasa y el tipo de pago seleccionado.
     */
    function calcularMonto() {
        const selectedOption = selectTipoPago.options[selectTipoPago.selectedIndex]
        const tasa = parseFloat(tasaInput.value) || 0
        const lts = parseFloat(ltsInput.value) || 0
        const calcularMonto = {
            '1': () => lts * 0.5,
            '2': () => lts * 0.5 * tasa,
            '3': () => lts * 0.5 * tasa
        }
        const resultado = calcularMonto[selectedOption.value]?.()
        if (resultado !== undefined) {
            montoInput.value = Number(resultado.toFixed(2))
        }
        updateTicketPreview()
    }

    /**
     * Actualiza el área de vista previa del ticket con los datos actuales del formulario.
     */
    function updateTicketPreview() {
        const tipoVehiculo = selectTipoVehiculo.options[selectTipoVehiculo.selectedIndex]?.text || ''
        const tipoPago = selectTipoPago.options[selectTipoPago.selectedIndex]?.text || ''
        const cantidad = ltsInput.value
        const precioTotal = montoInput.value
        const tipoPagoId = selectTipoPago.value
        const simbolo = tipoPagoId === '1' ? '$' : 'BS'
        const fecha = new Date().toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })
        const previewText = `
        ====== ESTACIÓN DE SERVICIO ======

        TICKET DE VENTA
        Fecha: ${fecha}
        ----------------------------------
        Tipo Pago: ${tipoPago}
        Tipo Vehículo: ${tipoVehiculo}
        Cantidad: ${cantidad} Litros
        Precio Total: ${precioTotal} ${simbolo}
        =============================
        ¡Gracias por su compra!
        `
        ticketPreview.textContent = previewText
    }

    // --- Event Listeners ---

    selectTipoPago.addEventListener('change', calcularMonto)
    ltsInput.addEventListener('input', calcularMonto)
    tasaInput.addEventListener('input', calcularMonto)
    selectTipoVehiculo.addEventListener('change', updateTicketPreview)

    // Formatear la tasa a dos decimales cuando el usuario deja el campo
    tasaInput.addEventListener('blur', function () {
        const tasaValue = parseFloat(this.value);
        if (!isNaN(tasaValue)) {
            this.value = tasaValue.toFixed(3);
        }
    });

    /**
     * Maneja el envío del formulario de registro de venta.
     * Usa FormData para enviar los datos, compatible con el backend que espera $_POST.
     */
    ventaForm.addEventListener('submit', async function (e) {
        // --- INICIO DE LA MODIFICACIÓN ---
        const submitButton = ventaForm.querySelector('button[type="submit"]');
        // --- FIN DE LA MODIFICACIÓN ---
        e.preventDefault()
        const lts = parseFloat(ltsInput.value)
        if (lts <= 0 || isNaN(lts)) {
            notifi('Por favor, ingrese una cantidad de litros válida.', 'warning')
            return
        }
        const formData = new FormData(ventaForm)
        formData.append('txtListTipoVehiculo', selectTipoVehiculo.value)
        formData.append('txtListTipoPago', selectTipoPago.value)
        formData.append('txtLTS', ltsInput.value)
        formData.append('txtMonto', montoInput.value)
        formData.append('txtTasa', tasaInput.value)
        formData.append('action', 'registrarVenta')
        // --- INICIO DE LA MODIFICACIÓN ---
        // Deshabilitar el botón para evitar doble clic
        if (submitButton) submitButton.disabled = true;
        // --- FIN DE LA MODIFICACIÓN ---
        try {
            const response = await fetch(base_url + 'Estacion/registrarVenta', {
                method: 'POST',
                body: formData
            })
            const result = await response.json()
            if (result.success) {
                notifi(result.message, 'success')
                // Asegurarnos de que tenemos los datos del ticket para imprimir
                if (result.ticketData) {
                    // Llamar a la función de impresión con los datos recibidos del servidor
                    fntImprimirTicket({ ticketData: result.ticketData, copia: 0 }); // 0 para original
                }
                // Limpiar formulario y actualizar UI
                ventaForm.reset()
                ticketPreview.textContent = ''
                updateTicketPreview()
                await loadInitialData()
            } else {
                notifi(result.message, 'error')
            }
        } catch (error) {
            Swal.fire('Error en la solicitud: ' + error.message, 'error')
        } finally {
            // --- INICIO DE LA MODIFICACIÓN ---
            // Volver a habilitar el botón en cualquier caso (éxito, error o excepción)
            if (submitButton) submitButton.disabled = false;
            // --- FIN DE LA MODIFICACIÓN ---
        }
    })

    /**
     * Maneja el clic en el botón para actualizar la tasa de cambio.
     */
    btnUpdateTasa.addEventListener('click', async function () {
        const valorInput = tasaInput.value;
        if (valorInput && parseFloat(valorInput) > 0) {
            const nuevaTasa = parseFloat(valorInput).toFixed(2);
            try {
                const response = await fetch(base_url + 'Estacion/updateTasa', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ tasa: nuevaTasa })
                })
                const result = await response.json()
                if (result.success) {
                    notifi(result.message, 'success')

                    // Actualizar UI: Ocultar input, mostrar span con nuevo valor
                    tasaInput.value = nuevaTasa;
                    tasaDisplay.textContent = nuevaTasa;
                    tasaInput.style.display = 'none';
                    tasaDisplay.style.display = 'block';
                    btnUpdateTasa.style.display = 'none';

                    await loadInitialData(); // Recargamos los datos para mostrar la nueva fecha de actualización
                } else {
                    notifi(result.message, 'error')
                }
            } catch (error) {
                notifi('Error al actualizar la tasa.', 'error')
            }
        } else {
            notifi('Ingrese una tasa válida.', 'warning')
        }
    })

    /**
     * Maneja el clic en el botón para cerrar las ventas del día actual.
     */
    btnCerrarDia.addEventListener('click', async function () {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esto cerrará el día y no se podrán registrar más ventas para esta fecha.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cerrar día',
            cancelButtonText: 'Cancelar'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    // Generar la fecha actual en formato 'd-m-y'
                    // --- INICIO DE LA CORRECCIÓN ---
                    const today = new Date()
                    const day = String(today.getDate()).padStart(2, '0')
                    const month = String(today.getMonth() + 1).padStart(2, '0')
                    const year = today.getFullYear()
                    const fechaCierre = `${year}-${month}-${day}` // Formato YYYY-MM-DD
                    // --- FIN DE LA CORRECCIÓN ---

                    // 1. Obtener e imprimir el reporte detallado PRIMERO
                    const detailedResponse = await fetch(base_url + 'Estacion/getDetalleVentas', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ idUser: userId, fecha_detalle: fechaCierre })
                    });
                    const detailedResult = await detailedResponse.json();
                    if (detailedResult.success) {
                        await fntImprimirDetallado(detailedResult.ticketData);
                    }

                    // 2. Realizar el cierre en el servidor DESPUÉS de imprimir el detallado.
                    const closeResponse = await fetch(base_url + 'Estacion/cerrarDia', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId, fecha_cierre: fechaCierre })
                    });
                    const closeResult = await closeResponse.json();

                    if (closeResult.success) {
                        // 3. Imprimir el reporte de cierre con los datos de la respuesta.
                        if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                            await fntImprimirCierre(closeResult.dataCierre);
                        }
                        notifi(closeResult.message, 'success');

                        // Limpiar la tabla de tickets y reiniciar el resumen diario a cero.
                        updateVentasTable([]);
                        updateDailySummary(null);
                        // Recargamos todos los datos. El backend ya no devolverá tickets para el día cerrado.
                        await loadInitialData();
                    } else {
                        notifi(closeResult.message, 'error')
                    }
                } catch (error) {
                    notifi('Error al cerrar el día.', 'error')
                }
            }
        })
    })

    /**
     * Maneja el clic en el botón para generar el reporte PDF del día actual.
     */
    btnGenerarPDF.addEventListener('click', async () => {
        try {
            const today = new Date()
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            // --- INICIO DE LA CORRECCIÓN ---
            const year = today.getFullYear();
            const fechaReporte = `${year}-${month}-${day}`; // Formato YYYY-MM-DD

            if (!userId) { notifi('Error: ID de usuario no definido para generar PDF.', 'error'); return; } // Asegurarse de que userId esté definido

            // Llama directamente a fntGenerarPDF, que ahora maneja el SweetAlert y la llamada fetch al controlador
            fntGenerarPDF({ idUser: userId, fecha: fechaReporte });
        } catch (error) {
            notifi('Error al generar el PDF de ventas.', 'error')
        }
    })

    /**
     * Delegación de eventos para botones dinámicos (PDF pendiente).
     */
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.pdf-btn')
        if (button) {
            const fechaReporte = button.dataset.fecha
            const idUser = button.dataset.iduser
            // Llama directamente a fntGenerarPDF, que ahora maneja el SweetAlert y la llamada fetch al controlador
            fntGenerarPDF({ idUser: parseInt(idUser), fecha: fechaReporte });
        }
    })

    document.addEventListener('click', async function (e) {
        // Botón de reimprimir ticket desde la tabla
        if (e.target.closest('.print-ticket-btn')) {
            try {
                const idVenta = e.target.closest('.print-ticket-btn').dataset.id
                const dataFecha = e.target.closest('.print-ticket-btn').dataset.fecha
                const idUser = e.target.closest('.print-ticket-btn').dataset.iduser

                const response = await fetch(base_url + 'Estacion/getTicket', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idVenta: idVenta, idUser: idUser, fechaTicket: dataFecha })
                })
                const result = await response.json()
                if (result.success) {
                    result.copia = 1; // Marcar como copia
                    fntImprimirTicket(result)
                } else {
                    notifi(result.message, 'error')
                }
            } catch (error) {
                notifi('Error al obtener el ticket.', 'error')
            }
        }

        // Botón para cerrar un día pendiente
        if (e.target.closest('.close-pending-btn')) {
            const fechaCierre = e.target.closest('.close-pending-btn').dataset.fecha
            const idUser = e.target.closest('.close-pending-btn').dataset.iduser
            Swal.fire({
                title: '¿Deseas cerrar las ventas de este día?',
                text: `Esto generará el reporte de cierre para el día ${fechaCierre}.`,
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
                            body: JSON.stringify({ idUser: idUser, fecha_detalle: fechaCierre })
                        });
                        const detailedResult = await detailedResponse.json();
                        if (detailedResult.success) {
                            await fntImprimirDetallado(detailedResult.ticketData);
                        }

                        // 2. Realizar el cierre en el servidor DESPUÉS de imprimir el detallado.
                        const closeResponse = await fetch(base_url + 'Estacion/cerrarTurnoPendiente', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ userId: idUser, fecha_cierre: fechaCierre })
                        });
                        const closeResult = await closeResponse.json();

                        if (closeResult.success) {
                            // 3. Imprimir el reporte de cierre con los datos de la respuesta.
                            if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                                await fntImprimirCierre(closeResult.dataCierre); // Imprime el cierre
                            }
                            notifi(closeResult.message, 'success');
                            // Recargamos todos los datos para limpiar la UI y actualizar la lista de pendientes.
                            await loadInitialData();
                        } else {
                            notifi(result.message, 'error')
                        }
                    } catch (error) {
                        notifi('Error al cerrar el turno pendiente.', 'error')
                    }
                }
            })
        }
    })

    loadInitialData()
    updateTicketPreview()
})