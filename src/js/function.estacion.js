document.addEventListener('DOMContentLoaded', function () {
    if (typeof initQZTrayConnection === 'function') {
        initQZTrayConnection();
    }

    if (typeof fntFormatBs !== 'function') {
        window.fntFormatBs = function (valor) {
            const num = parseFloat(valor) || 0;
            const partes = num.toFixed(2).split('.');
            const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return entero + ',' + partes[1];
        };
    }

    const ventaForm = document.getElementById('ventaForm')
    const tasaDisplay = document.getElementById('tasaDisplay')
    const tasaInput = document.querySelector('#txtTasa')
    const ltsInput = document.querySelector('#txtLTS')
    const montoInput = document.querySelector('#txtMonto')
    const selectTipoVehiculo = document.getElementById('txtListTipoVehiculo')
    const selectTipoPago = document.getElementById('txtListTipoPago')
    const selectTipoCombustible = document.getElementById('txtListTipoCombustible')
    const ticketPreview = document.getElementById('ticketPreview')
    const btnUpdateTasa = document.getElementById('btnUpdateTasa')
    const totalVehiculosSpan = document.getElementById('totalVehiculos')
    const totalLitrosSpan = document.getElementById('totalLitros')
    const totalBolivaresSpan = document.getElementById('totalBolivares')
    const tiposVehiculosContainer = document.getElementById('tiposVehiculosContainer')
    const tiposVehiculosList = document.getElementById('tiposVehiculosList')
    const tiposPagosContainer = document.getElementById('tiposPagosContainer')
    const tiposPagosList = document.getElementById('tiposPagosList')
    const tiposCombustibleContainer = document.getElementById('tiposCombustibleContainer')
    const tiposCombustibleList = document.getElementById('tiposCombustibleList')
    const btnCerrarDia = document.getElementById('btnCerrarDia')
    const btnGenerarPDF = document.getElementById('btnGenerarPDF')
    const tasaLastUpdateSpan = document.getElementById('tasaLastUpdate');
    const cierrePendienteSection = document.getElementById('cierrePendienteSection')
    const cierrePendienteButtons = document.getElementById('cierrePendienteButtons')

    let ventasDataTable;
    if ($.fn.DataTable.isDataTable('#ventasTable')) {
        ventasDataTable = $('#ventasTable').DataTable();
    } else {
        ventasDataTable = $('#ventasTable').DataTable({
            "dom": 'lfrtip',
            "language": { "url": base_url + "src/plugins/js/es_es.json" }
        })
    }

    async function loadInitialData() {
        try {
            const response = await fetch(base_url + 'Estacion/initialData')
            const data = await response.json()
            if (data.success) {
                selectTipoVehiculo.innerHTML = ''
                data.tiposVehiculo.forEach(v => {
                    const o = document.createElement('option')
                    o.value = v.id_tipo_vehiculo
                    o.textContent = v.nombre
                    selectTipoVehiculo.appendChild(o)
                })
                selectTipoPago.innerHTML = ''
                data.tiposPago.forEach(p => {
                    const o = document.createElement('option')
                    o.value = p.id_tipo_pago
                    o.textContent = p.nombre
                    selectTipoPago.appendChild(o)
                })
                if (selectTipoCombustible && data.tiposCombustible) {
                    selectTipoCombustible.innerHTML = ''
                    data.tiposCombustible.forEach(c => {
                        const o = document.createElement('option')
                        o.value = c.id_tipo_combustible
                        o.textContent = c.nombre
                        selectTipoCombustible.appendChild(o)
                    })
                    if (selectTipoCombustible.querySelector('option[value="1"]')) {
                        selectTipoCombustible.value = '1'
                    } else if (selectTipoCombustible.options.length > 0) {
                        selectTipoCombustible.selectedIndex = 0
                    }
                }

                if (data.tasa) {
                    const tasaFormateada = parseFloat(data.tasa.tasa_dia).toFixed(2);
                    tasaInput.value = tasaFormateada;
                    tasaDisplay.textContent = tasaFormateada;

                    if (data.tasa.tasa_update && data.tasa.tasa_update !== '0000-00-00 00:00:00') {
                        const fechaUpdate = new Date(data.tasa.tasa_update.replace(/-/g, '/'));
                        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                        tasaLastUpdateSpan.textContent = `Última actualización: ${fechaUpdate.toLocaleDateString('es-ES', options)}`;

                        const hoy = new Date();
                        if (fechaUpdate.getFullYear() === hoy.getFullYear() &&
                            fechaUpdate.getMonth() === hoy.getMonth() &&
                            fechaUpdate.getDate() === hoy.getDate()) {
                            tasaInput.style.display = 'none';
                            tasaDisplay.style.display = 'block';
                            btnUpdateTasa.style.display = 'none';
                        } else {
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
                updateVentasTable(data.ultimosTickets)
                updateDailySummary(data.resumen)
                updateVentasPendientes(data.ventasPendientes)
            } else {
                notifi(data.message, 'error')
            }
        } catch (error) {
            notifi('Error al cargar los datos iniciales: ' + error.message, 'error')
        }
    }

    function updateVentasTable(tickets) {
        ventasDataTable.rows().remove().draw();
        tickets.forEach(ticket => {
            ventasDataTable.row.add([
                ticket.id_venta,
                ticket.fecha_venta,
                ticket.tipoVehiculo,
                ticket.litros,
                `<button class="btn btn-info btn-sm print-ticket-btn" data-id="${ticket.id_venta}" data-fecha="${ticket.fecha_venta}" data-iduser="${ticket.id_user}"><i class="fas fa-print"></i></button>`
            ]).draw(false).node();
        })
    }

    function updateDailySummary(resumen) {
        if (resumen) {
            const efectivoMasDivisaBs = (parseFloat(resumen.total_bs) || 0)
            totalVehiculosSpan.textContent = resumen.total_ventas
            totalLitrosSpan.textContent = parseFloat(resumen.total_litros).toFixed(2) + " L"
            totalBolivaresSpan.textContent = fntFormatBs(efectivoMasDivisaBs) + " Bs"

            tiposPagosList.innerHTML = ''
            const pagosExistentes = (resumen.total_divisa > 0) || (resumen.total_efectivo > 0) || (resumen.total_debito > 0)
            if (pagosExistentes) {
                if (resumen.total_divisa > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-dollar-sign text-success mr-2"></i>Divisa: <strong>${fntFormatBs(resumen.total_divisa)} $</strong></li>`
                }
                if (resumen.total_efectivo > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-money-bill-wave text-primary mr-2"></i>Efectivo: <strong>${fntFormatBs(resumen.total_efectivo)} Bs</strong></li>`
                }
                if (resumen.total_debito > 0) {
                    tiposPagosList.innerHTML += `<li class="mb-1"><i class="fas fa-credit-card text-info mr-2"></i>Punto de Venta: <strong>${fntFormatBs(resumen.total_debito)} Bs</strong></li>`
                }
                tiposPagosContainer.style.display = 'block'
            } else {
                tiposPagosContainer.style.display = 'none'
            }

            tiposVehiculosList.innerHTML = ''
            if (resumen.tiposVehiculo && resumen.tiposVehiculo.length > 0) {
                resumen.tiposVehiculo.forEach(item => {
                    let iconClass = "fa-car-side"
                    if (item.tipo_vehiculo.includes('Moto')) iconClass = "fa-motorcycle"
                    else if (item.tipo_vehiculo.includes('Camion')) iconClass = "fa-solid fa-truck"
                    tiposVehiculosList.innerHTML += `<li class="mb-1"><i class="fas ${iconClass} text-secondary mr-2"></i>${item.tipo_vehiculo}: <strong>${item.cantidad}</strong></li>`
                })
                tiposVehiculosContainer.style.display = 'block'
            } else {
                tiposVehiculosContainer.style.display = 'none'
            }

            if (tiposCombustibleList && tiposCombustibleContainer) {
                tiposCombustibleList.innerHTML = ''
                if (resumen.tiposCombustible && resumen.tiposCombustible.length > 0) {
                    resumen.tiposCombustible.forEach(item => {
                        let iconClass = "fa-gas-pump"
                        if (item.tipo_combustible && item.tipo_combustible.toLowerCase().includes('diesel')) {
                            iconClass = "fa-oil-can"
                        }
                        const litros = parseFloat(item.litros || 0).toFixed(2)
                        tiposCombustibleList.innerHTML += `<li class="mb-1"><i class="fas ${iconClass} text-info mr-2"></i>${item.tipo_combustible}: <strong>${item.cantidad}</strong> venta(s) — <strong>${litros} L</strong></li>`
                    })
                    tiposCombustibleContainer.style.display = 'block'
                } else {
                    tiposCombustibleContainer.style.display = 'none'
                }
            }

            if (resumen.total_ventas === 0) {
                btnCerrarDia.style.display = 'none'
                btnGenerarPDF.style.display = 'none'
            } else {
                btnCerrarDia.style.display = 'block'
                btnGenerarPDF.style.display = 'block'
            }
        } else {
            totalVehiculosSpan.textContent = '0';
            totalLitrosSpan.textContent = '0.00 L';
            totalBolivaresSpan.textContent = '0,00 Bs';
            tiposPagosList.innerHTML = '';
            tiposPagosContainer.style.display = 'none';
            tiposVehiculosList.innerHTML = '';
            tiposVehiculosContainer.style.display = 'none';
            if (tiposCombustibleList) tiposCombustibleList.innerHTML = '';
            if (tiposCombustibleContainer) tiposCombustibleContainer.style.display = 'none';
            btnCerrarDia.style.display = 'none';
            btnGenerarPDF.style.display = 'none';
        }
    }

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

    function updateTicketPreview() {
        const tipoVehiculo = selectTipoVehiculo.options[selectTipoVehiculo.selectedIndex]?.text || ''
        const tipoPago = selectTipoPago.options[selectTipoPago.selectedIndex]?.text || ''
        const tipoCombustible = selectTipoCombustible && selectTipoCombustible.options[selectTipoCombustible.selectedIndex]
            ? selectTipoCombustible.options[selectTipoCombustible.selectedIndex].text
            : ''
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
        Combustible: ${tipoCombustible}
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
    if (selectTipoCombustible) {
        selectTipoCombustible.addEventListener('change', updateTicketPreview)
    }

    tasaInput.addEventListener('blur', function () {
        const tasaValue = parseFloat(this.value);
        if (!isNaN(tasaValue)) {
            this.value = tasaValue.toFixed(3);
        }
    });

    ventaForm.addEventListener('submit', async function (e) {
        const submitButton = ventaForm.querySelector('button[type="submit"]');
        e.preventDefault()
        const lts = parseFloat(ltsInput.value)
        if (lts <= 0 || isNaN(lts)) {
            notifi('Por favor, ingrese una cantidad de litros válida.', 'warning')
            return
        }
        const formData = new FormData(ventaForm)
        formData.append('txtListTipoVehiculo', selectTipoVehiculo.value)
        formData.append('txtListTipoPago', selectTipoPago.value)
        formData.append('txtListTipoCombustible', selectTipoCombustible ? selectTipoCombustible.value : '1')
        formData.append('txtLTS', ltsInput.value)
        formData.append('txtMonto', montoInput.value)
        formData.append('txtTasa', tasaInput.value)
        formData.append('action', 'registrarVenta')
        if (submitButton) submitButton.disabled = true;
        try {
            const response = await fetch(base_url + 'Estacion/registrarVenta', {
                method: 'POST',
                body: formData
            })
            const result = await response.json()
            if (result.success) {
                notifi(result.message, 'success')
                if (result.ticketData) {
                    fntImprimirTicket({ ticketData: result.ticketData, copia: 0 });
                }
                ventaForm.reset()
                if (selectTipoCombustible && selectTipoCombustible.querySelector('option[value="1"]')) {
                    selectTipoCombustible.value = '1';
                }
                ticketPreview.textContent = ''
                updateTicketPreview()
                await loadInitialData()
            } else {
                notifi(result.message, 'error')
            }
        } catch (error) {
            Swal.fire('Error en la solicitud: ' + error.message, 'error')
        } finally {
            if (submitButton) submitButton.disabled = false;
        }
    })

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
                    tasaInput.value = nuevaTasa;
                    tasaDisplay.textContent = nuevaTasa;
                    tasaInput.style.display = 'none';
                    tasaDisplay.style.display = 'block';
                    btnUpdateTasa.style.display = 'none';
                    await loadInitialData();
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
     * Cerrar el día actual. Ahora pregunta cómo imprimir el detallado.
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
                    const today = new Date()
                    const day = String(today.getDate()).padStart(2, '0')
                    const month = String(today.getMonth() + 1).padStart(2, '0')
                    const year = today.getFullYear()
                    const fechaCierre = `${year}-${month}-${day}`

                    // 1. Preguntar cómo quiere el detallado (antes de imprimirlo)
                    const modoDetallado = await fntPreguntarModoDetallado();

                    // 2. Obtener y (si aplica) imprimir el detallado
                    const detailedResponse = await fetch(base_url + 'Estacion/getDetalleVentas', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ idUser: userId, fecha_detalle: fechaCierre })
                    });
                    const detailedResult = await detailedResponse.json();
                    if (detailedResult.success && modoDetallado) {
                        await fntImprimirDetallado(detailedResult.ticketData, modoDetallado);
                    }

                    // 3. Realizar el cierre en el servidor
                    const closeResponse = await fetch(base_url + 'Estacion/cerrarDia', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ userId: userId, fecha_cierre: fechaCierre })
                    });
                    const closeResult = await closeResponse.json();

                    if (closeResult.success) {
                        // 4. Imprimir el cierre
                        if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                            await fntImprimirCierre(closeResult.dataCierre);
                        }
                        notifi(closeResult.message, 'success');
                        updateVentasTable([]);
                        updateDailySummary(null);
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

    btnGenerarPDF.addEventListener('click', async () => {
        try {
            const today = new Date()
            const day = String(today.getDate()).padStart(2, '0');
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const year = today.getFullYear();
            const fechaReporte = `${year}-${month}-${day}`;

            if (!userId) { notifi('Error: ID de usuario no definido para generar PDF.', 'error'); return; }

            fntGenerarPDF({ idUser: userId, fecha: fechaReporte });
        } catch (error) {
            notifi('Error al generar el PDF de ventas.', 'error')
        }
    })

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.pdf-btn')
        if (button) {
            const fechaReporte = button.dataset.fecha
            const idUser = button.dataset.iduser
            fntGenerarPDF({ idUser: parseInt(idUser), fecha: fechaReporte });
        }
    })

    document.addEventListener('click', async function (e) {
        // Botón de reimprimir ticket
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
                    result.copia = 1;
                    fntImprimirTicket(result)
                } else {
                    notifi(result.message, 'error')
                }
            } catch (error) {
                notifi('Error al obtener el ticket.', 'error')
            }
        }

        // Cerrar turno pendiente (ahora también pregunta modo detallado)
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
                        // 1. Preguntar modo detallado
                        const modoDetallado = await fntPreguntarModoDetallado();

                        // 2. Obtener e imprimir detallado
                        const detailedResponse = await fetch(base_url + 'Estacion/getDetalleVentas', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ idUser: idUser, fecha_detalle: fechaCierre })
                        });
                        const detailedResult = await detailedResponse.json();
                        if (detailedResult.success && modoDetallado) {
                            await fntImprimirDetallado(detailedResult.ticketData, modoDetallado);
                        }

                        // 3. Cerrar en servidor
                        const closeResponse = await fetch(base_url + 'Estacion/cerrarTurnoPendiente', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ userId: idUser, fecha_cierre: fechaCierre })
                        });
                        const closeResult = await closeResponse.json();

                        if (closeResult.success) {
                            // 4. Imprimir cierre
                            if (typeof fntImprimirCierre === 'function' && closeResult.dataCierre) {
                                await fntImprimirCierre(closeResult.dataCierre);
                            }
                            notifi(closeResult.message, 'success');
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