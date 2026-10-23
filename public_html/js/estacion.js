document.addEventListener('DOMContentLoaded', () => {
    if (typeof initQZTrayConnection === 'function') initQZTrayConnection();

    const API = (window.SITGO_BASE || '/') + 'estacion';
    const $ = id => document.getElementById(id);

    let state = {
        tiposVehiculo: [],
        tiposPago: [],
        tiposCombustible: [],
        tasa: 0,
        userId: 0,
        idEstacion: 0,
        idCierreActual: 0,
        usuarioNombre:   '',
        usuarioApellido: '',
        estacionNombre:  '',
        submitting:      false,   // ← evita doble submit de venta
        cerrando:        false,   // ← evita doble cierre
        actualizandoTasa:false,   // ← evita doble update de tasa
    };

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        await cargar();
        bindEvents();
        bindModalPdf();
        updatePreview();
    })();

    async function fetchJSON(url, opts = {}){
        try{
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                return { ok: false, message: `Error ${r.status}` };
            }
            return await r.json();
        } catch {
            return { ok: false, message: 'Error de conexión' };
        }
    }

    async function cargar(){
        const d = await fetchJSON(API + '/initial-data');
        if (!d.ok){ toast('Error al cargar datos: ' + d.message, 'error'); return; }

        state.tiposVehiculo    = d.tiposVehiculo || [];
        state.tiposPago        = d.tiposPago || [];
        state.tiposCombustible = d.tiposCombustible || [];
        state.userId           = d.idUser;
        state.idEstacion       = d.idEstacion;
        state.usuarioNombre    = d.usuarioNombre   || '';
        state.usuarioApellido  = d.usuarioApellido || '';
        state.estacionNombre   = d.estacionNombre  || '';

        llenar($('tipoVehiculo'), state.tiposVehiculo, 'Seleccione...');
        llenar($('tipoPago'),     state.tiposPago,     'Seleccione...');

        const selComb = $('tipoCombustible');
        selComb.innerHTML = '';
        if (state.tiposCombustible.length){
            state.tiposCombustible.forEach(c => {
                const o = document.createElement('option');
                o.value = c.id;
                o.textContent = c.nombre;
                if (String(c.id) === '1') o.selected = true;
                selComb.appendChild(o);
            });
        }

        if (d.tasa && d.tasa.tasa_dia){
            state.tasa = parseFloat(d.tasa.tasa_dia);
            aplicarTasa(d.tasa);
        }

        renderTickets(d.ultimosTickets || []);
        renderResumen(d.resumen);
        renderPendientes(d.ventasPendientes || []);
        cargarUsuariosEstacion();
    }

    function llenar(sel, items, ph){
        if (!sel) return;
        sel.innerHTML = `<option value="">${ph}</option>`;
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it.id;
            o.textContent = it.nombre;
            sel.appendChild(o);
        });
    }

    function aplicarTasa(tasaObj){
        const tasa = parseFloat(tasaObj.tasa_dia || 0);
        $('tasaDisplay').textContent = tasa.toFixed(2);
        $('tasaInput').value = tasa.toFixed(2);
        state.tasa = tasa;

        if (tasaObj.tasa_update && tasaObj.tasa_update !== '0000-00-00 00:00:00'){
            const fUpd = new Date(tasaObj.tasa_update.replace(/-/g, '/'));
            const opts = { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' };
            $('tasaLastUpdate').textContent = 'Últ. actualización: ' + fUpd.toLocaleDateString('es-VE', opts);

            const hoy = new Date();
            const mismaFecha = fUpd.getFullYear() === hoy.getFullYear()
                && fUpd.getMonth() === hoy.getMonth()
                && fUpd.getDate() === hoy.getDate();

            if (mismaFecha){
                $('tasaInput').style.display = 'none';
                $('tasaDisplay').style.display = '';
                $('btnUpdateTasa').style.display = 'none';
            } else {
                $('tasaInput').style.display = '';
                $('tasaDisplay').style.display = 'none';
                $('btnUpdateTasa').style.display = '';
            }
        } else {
            $('tasaLastUpdate').textContent = 'Aún no se ha actualizado hoy.';
            $('tasaInput').style.display = '';
            $('tasaDisplay').style.display = 'none';
            $('btnUpdateTasa').style.display = '';
        }
    }

    /* ============================================================
       TICKETS / RESUMEN / PENDIENTES
       ============================================================ */
    function renderTickets(tickets){
        const tbody = $('tbodyTickets');
        if (!tickets.length){
            tbody.innerHTML = '<tr><td colspan="5" class="dt-empty">Sin tickets registrados</td></tr>';
            return;
        }
        tbody.innerHTML = tickets.map(t => `
            <tr>
                <td><strong>#${t.id_venta}</strong></td>
                <td>${esc(t.hora_venta || '')}</td>
                <td>${esc(t.tipoVehiculo)} <span style="color:#94a3b8; font-size:.7rem;">· ${esc(t.tipoCombustible || '')}</span></td>
                <td class="text-right">${parseFloat(t.litros).toFixed(2)} L</td>
                <td class="text-right">
                    <button class="action-btn" data-action="print" data-id="${t.id_venta}" data-fecha="${t.fecha_venta}" data-user="${t.id_user}" title="Reimprimir">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>
        `).join('');
        if (window.lucide) lucide.createIcons();

        tbody.querySelectorAll('[data-action="print"]').forEach(btn => {
            btn.addEventListener('click', () => reimprimir(btn.dataset.id, btn.dataset.fecha, btn.dataset.user));
        });
    }

    function renderResumen(r){
        if (!r) return;
        $('totalVehiculos').textContent = r.total_ventas ?? 0;
        $('totalLitros').textContent    = parseFloat(r.total_litros || 0).toFixed(2) + ' L';
        $('totalBolivares').textContent = parseFloat(r.total_bs || 0).toFixed(2) + ' Bs';

        const pagos = [];
        if (parseFloat(r.total_divisa) > 0)   pagos.push(`<li>${icon('dollar-sign')} Divisa: <strong>$${parseFloat(r.total_divisa).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_efectivo) > 0) pagos.push(`<li>${icon('banknote')} Efectivo: <strong>Bs ${parseFloat(r.total_efectivo).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_debito) > 0)   pagos.push(`<li>${icon('credit-card')} Punto: <strong>Bs ${parseFloat(r.total_debito).toFixed(2)}</strong></li>`);
        $('tiposPagosList').innerHTML = pagos.join('');
        $('tiposPagosContainer').style.display = pagos.length ? '' : 'none';

        const veh = (r.tiposVehiculo || []).map(v =>
            `<li>${icon('car')} ${esc(v.tipo_vehiculo)}: <strong>${v.cantidad}</strong></li>`
        );
        $('tiposVehiculosList').innerHTML = veh.join('');
        $('tiposVehiculosContainer').style.display = veh.length ? '' : 'none';

        const comb = (r.tiposCombustible || []).map(c =>
            `<li>${icon('droplet')} ${esc(c.tipo_combustible)}: <strong>${c.cantidad}</strong> venta(s) — <strong>${parseFloat(c.litros).toFixed(2)} L</strong></li>`
        );
        $('tiposCombustibleList').innerHTML = comb.join('');
        $('tiposCombustibleContainer').style.display = comb.length ? '' : 'none';

        $('btnCerrarDia').style.display  = (r.total_ventas > 0) ? '' : 'none';
        $('btnGenerarPDF').style.display = (r.total_ventas > 0) ? '' : 'none';

        if (window.lucide) lucide.createIcons();
    }

    function renderPendientes(ventas){
        const sec = $('cierrePendienteSection');
        const cont = $('cierrePendienteList');
        if (!ventas || !ventas.length){ sec.style.display = 'none'; return; }
        sec.style.display = '';
        cont.innerHTML = ventas.map(v => `
            <div class="estacion-cierre-pendiente-item">
                <strong>Día sin cierre: ${formatFecha(v.fecha_venta)}</strong>
                <div style="display:flex; gap:.35rem;">
                    <button class="btn btn-danger btn-sm" data-action="cerrar-pend" data-fecha="${v.fecha_venta}" data-user="${v.id_user}">Cerrar día</button>
                    <button class="btn btn-ghost btn-sm" data-action="pdf-pend" data-fecha="${v.fecha_venta}" data-user="${v.id_user}">PDF</button>
                </div>
            </div>
        `).join('');

        cont.querySelectorAll('[data-action="cerrar-pend"]').forEach(b => {
            b.addEventListener('click', () => cerrarPendiente(b.dataset.fecha, b.dataset.user));
        });
        cont.querySelectorAll('[data-action="pdf-pend"]').forEach(b => {
            b.addEventListener('click', () => {
                state._pdfFechaPendiente = b.dataset.fecha;
                state._pdfUserPendiente  = b.dataset.user;
                abrirModalPdf('pendiente');
            });
        });
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEvents(){
        $('litros')?.addEventListener('input', calcularMonto);
        $('tipoPago')?.addEventListener('change', calcularMonto);
        $('tasaInput')?.addEventListener('input', calcularMonto);
        $('tipoVehiculo')?.addEventListener('change', updatePreview);
        $('tipoCombustible')?.addEventListener('change', updatePreview);

        $('btnUpdateTasa')?.addEventListener('click', actualizarTasa);
        $('formVenta')?.addEventListener('submit', registrarVenta);
        $('btnCerrarDia')?.addEventListener('click', cerrarDia);

        $('btnGenerarPDF')?.addEventListener('click', () => {
            if (!state.idEstacion){ toast('Sin estación asignada.', 'error'); return; }
            abrirModalPdf('hoy');
        });
                // PDF por fecha
        $('btnPdfPorFecha')?.addEventListener('click', generarPdfPorFecha);
    }

    /* ============================================================
       CÁLCULO
       ============================================================ */
    function calcularMonto(){
        const tp = $('tipoPago')?.value;
        const lts = parseFloat($('litros')?.value) || 0;
        const tasa = parseFloat($('tasaInput')?.value || $('tasaDisplay')?.textContent) || state.tasa;

        let monto = 0;
        if (tp === '1') monto = lts * 0.5;
        else if (tp === '2' || tp === '3') monto = lts * 0.5 * tasa;
        $('monto').value = monto ? monto.toFixed(2) : '';
        updatePreview();
    }

    function updatePreview(){
        const veh  = $('tipoVehiculo')?.selectedOptions[0]?.text || '';
        const pago = $('tipoPago')?.selectedOptions[0]?.text || '';
        const comb = $('tipoCombustible')?.selectedOptions[0]?.text || 'Gasolina';
        const lts  = $('litros')?.value || '';
        const mt   = $('monto')?.value || '';
        const simbolo = $('tipoPago')?.value === '1' ? '$' : 'Bs';

        $('ticketPreview').textContent = `====== ESTACIÓN DE SERVICIO ======

        TICKET DE VENTA
        Fecha: ${new Date().toLocaleString('es-VE')}
        ----------------------------------
        Tipo Pago: ${pago}
        Tipo Vehículo: ${veh}
        Combustible: ${comb}
        Cantidad: ${lts} Litros
        Precio Total: ${mt} ${simbolo}
        =============================
        ¡Gracias por su compra!`;
    }

    /* ============================================================
       ACCIONES
       ============================================================ */
    async function actualizarTasa(){
        if (state.actualizandoTasa) return;

        const tasa = parseFloat($('tasaInput')?.value);
        if (!tasa || tasa <= 0){ toast('Ingresa una tasa válida.', 'error'); return; }

        state.actualizandoTasa = true;
        const btn = $('btnUpdateTasa');
        if (btn) btn.disabled = true;

        try {
            const d = await fetchJSON(API + '/update-tasa', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tasa })
            });
            if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
            toast(d.message, 'success');
            await cargar();
        } finally {
            state.actualizandoTasa = false;
            if (btn) btn.disabled = false;
        }
    }

    /* ============================================================
       REGISTRAR VENTA (con protección anti doble submit)
       ============================================================ */
    async function registrarVenta(e){
        e.preventDefault();

        // 1) Bloqueo si ya hay una venta en proceso
        if (state.submitting) return;

        const payload = {
            tipo_vehiculo:    $('tipoVehiculo').value,
            tipo_combustible: $('tipoCombustible').value || 1,
            litros:           $('litros').value,
            tipo_pago:        $('tipoPago').value,
            monto:            $('monto').value,
            tasa:             $('tasaInput')?.value || state.tasa,
        };

        if (!payload.tipo_vehiculo || !payload.tipo_pago || parseFloat(payload.litros) <= 0){
            toast('Completa todos los campos.', 'error');
            return;
        }

        // 2) Marcar envío y deshabilitar botón
        state.submitting = true;
        const submitBtn = $('formVenta')?.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const d = await fetchJSON(API + '/registrar-venta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok){
                toast(d.message || 'Error', 'error');
                return;
            }

            toast(d.message, 'success');

            // Limpiar form
            $('formVenta').reset();
            $('monto').value = '';
            $('tipoCombustible').value = '1';
            updatePreview();

            // Recargar resumen en paralelo
            cargar().catch(err => console.warn('cargar:', err));

            // Imprimir ticket en paralelo
            if (d.ticketData && typeof fntImprimirTicket === 'function'){
                fntImprimirTicket({ ticketData: d.ticketData, copia: 0 })
                    .catch(err => console.warn('Impresión falló:', err));
            }
        } catch (err) {
            console.error(err);
            toast('Error de conexión.', 'error');
        } finally {
            // 3) Liberar bloqueo SIEMPRE
            state.submitting = false;
            if (submitBtn) submitBtn.disabled = false;
        }
    }

    async function reimprimir(id, fecha, idUser){
        const d = await fetchJSON(API + '/ticket/' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        if (typeof fntImprimirTicket === 'function'){
            try{ await fntImprimirTicket({ ticketData: d.ticketData, copia: 1 }); toast('Ticket enviado.', 'success'); }
            catch(e){ toast('Error: ' + e.message, 'error'); }
        }
    }

    /* ============================================================
       CERRAR DÍA (sin preguntar modo del detallado → siempre 'unificado')
       ============================================================ */
    async function cerrarDia(){
        if (state.cerrando) return;

        const ok = await sitgoConfirm({
            title: 'Cerrar el día',
            message: '¿Deseas cerrar el día actual? No se podrán registrar más ventas para esta fecha. Se generará un cierre con todos los tickets abiertos.',
            okText: 'Sí, cerrar día',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'door-closed',
        });
        if (!ok) return;

        state.cerrando = true;
        const btn = $('btnCerrarDia');
        if (btn) btn.disabled = true;

        try {
            const fecha = fechaHoy();

            // Detallado impreso siempre en modo 'unificado'
            if (typeof fntImprimirDetallado === 'function'){
                const detalle = await fetchJSON(API + '/detalle-ventas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha, id_user: state.userId })
                });
                if (detalle.ok && Array.isArray(detalle.data) && detalle.data.length){
                    try{ await fntImprimirDetallado(detalle.data, 'unificado'); }
                    catch(e){ console.warn('Detallado:', e); }
                }
            }

            const d = await fetchJSON(API + '/cerrar-dia', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha })
            });
            if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

            if (d.idCierre) state.idCierreActual = d.idCierre;

            if (d.dataCierre && typeof fntImprimirCierre === 'function'){
                try{ await fntImprimirCierre(d.dataCierre); } catch(e){ console.warn(e); }
            }

            toast(d.message, 'success');
            await cargar();
        } finally {
            state.cerrando = false;
            if (btn) btn.disabled = false;
        }
    }

    /* ============================================================
       CERRAR DÍA PENDIENTE (sin preguntar modo → siempre 'unificado')
       ============================================================ */
    async function cerrarPendiente(fecha, idUser){
        if (state.cerrando) return;

        const ok = await sitgoConfirm({
            title: 'Cerrar día pendiente',
            message: `¿Deseas cerrar el día ${formatFecha(fecha)}? Se generará un cierre con los tickets abiertos de esa fecha.`,
            okText: 'Sí, cerrar',
            cancelText: 'Cancelar',
            type: 'warning',
            icon: 'calendar-clock',
        });
        if (!ok) return;

        state.cerrando = true;

        try {
            if (typeof fntImprimirDetallado === 'function'){
                const detalle = await fetchJSON(API + '/detalle-ventas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha, id_user: idUser })
                });
                if (detalle.ok && Array.isArray(detalle.data) && detalle.data.length){
                    try{ await fntImprimirDetallado(detalle.data, 'unificado'); }
                    catch(e){ console.warn('Detallado:', e); }
                }
            }

            const d = await fetchJSON(API + '/cerrar-turno-pendiente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha, id_user: idUser })
            });
            if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

            if (d.dataCierre && typeof fntImprimirCierre === 'function'){
                try{ await fntImprimirCierre(d.dataCierre); } catch(e){ console.warn(e); }
            }
            toast(d.message, 'success');
            await cargar();
        } finally {
            state.cerrando = false;
        }
    }

    /* ============================================================
       MODAL PDF
       ============================================================ */
    function bindModalPdf(){
        const modal = $('modalPdfSelector');
        if (!modal) return;

        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close]')){
                cerrarModalPdf();
                return;
            }
            const opt = e.target.closest('.pdf-opt');
            if (opt){
                const seccion = opt.dataset.seccion;
                const tipo = modal.dataset.tipo || 'hoy';
                cerrarModalPdf();
                generarPdfCierre(seccion, tipo);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModalPdf();
        });
    }

    function abrirModalPdf(tipo){
        const modal = $('modalPdfSelector');
        if (!modal) return;
        modal.dataset.tipo = tipo || 'hoy';
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalPdf(){
        const modal = $('modalPdfSelector');
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    /* ============================================================
       GENERAR PDF · unificado para:
         - tipo='hoy'       → cierre/detalle del día del usuario
         - tipo='pendiente' → cierre de un día pendiente
         - tipo='fecha'     → ventas de la estación + fecha (+ usuario)
       El `seccion` viene del modal (gasolina | diesel).
       ============================================================ */
    async function generarPdfCierre(seccion, tipo = 'hoy'){
        // ---------- CASO A: PDF por fecha (estación + fecha + usuario) ----------
        if (tipo === 'fecha'){
            const ctx = state._pdfFechaCtx;
            if (!ctx){ toast('No hay contexto de fecha.', 'error'); return; }

            const btn = $('btnPdfPorFecha');
            if (btn){ btn.disabled = true; btn.style.opacity = '.6'; }

            try {
                const d = await fetchJSON(API + '/ventas-por-fecha', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha: ctx.fecha, id_user: ctx.id_user })
                });

                if (!d.ok){ toast(d.message || 'No se pudo generar el PDF.', 'error'); return; }

                const cierreData = d.cierreData;
                const ventas     = d.ventas || [];

                // Filtrar por combustible según la sección elegida
                const ventasFiltradas = filtrarVentasPorSeccion(ventas, seccion);
                if (!ventasFiltradas.length){
                    toast(`No hay ventas de ${seccion === 'gasolina' ? 'gasolina' : 'diésel'} para esa fecha.`, 'error');
                    return;
                }

                // Recalcular resumen con las ventas filtradas
                const resumenFiltrado = calcularResumenDesdeVentas(ventasFiltradas);
                const cierreFiltrado = {
                    ...cierreData,
                    total_ventas:     resumenFiltrado.totalVentas,
                    total_litros:     resumenFiltrado.totalLitros,
                    total_divisa:     resumenFiltrado.totalDivisa,
                    total_efectivo:   resumenFiltrado.totalEfectivo,
                    total_debito:     resumenFiltrado.totalDebito,
                    total_general_bs: resumenFiltrado.totalGeneralBs,
                    cant_auto:        resumenFiltrado.cantAuto,
                    cant_moto:        resumenFiltrado.cantMoto,
                    cant_camion:      resumenFiltrado.cantCamion,
                    cant_gasolina:    resumenFiltrado.cantGasolina,
                    cant_diesel:      resumenFiltrado.cantDiesel,
                    litros_gasolina:  resumenFiltrado.litrosGasolina,
                    litros_diesel:    resumenFiltrado.litrosDiesel,
                };

                const usuarioTxt = ctx.id_user > 0
                    ? (cierreData.usuario_nombres || 'Usuario')
                    : 'Todos los usuarios';

                await SITGO_PDF.generar(
                    'estacion_reporte_dia',
                    { cierre: cierreFiltrado, ventas: ventasFiltradas, seccion },
                    `reporte_${state.idEstacion}_${ctx.fecha}_${ctx.id_user || 'todos'}_${seccion}.pdf`,
                    {
                        titulo_documento: 'REPORTE DE VENTAS',
                        doc_color:        '#1e40af',
                        mostrar_header:   false,
                        subtitulo:        `Estación: ${cierreData.estacion || '—'} · Fecha: ${formatFecha(ctx.fecha)} · ${usuarioTxt}`,
                    }
                );

                toast('PDF generado.', 'success');
            } catch (e){
                console.error(e);
                toast('Error al generar el PDF: ' + e.message, 'error');
            } finally {
                if (btn){ btn.disabled = false; btn.style.opacity = ''; }
                state._pdfFechaCtx = null;
            }
            return;
        }

        // ---------- CASO B: PDF del día / pendiente (lógica original) ----------
        let fecha = fechaHoy();
        let idUser = state.userId;

        if (tipo === 'pendiente' && state._pdfFechaPendiente){
            fecha = state._pdfFechaPendiente;
            idUser = parseInt(state._pdfUserPendiente) || state.userId;
        }

        let cierreData = null;
        let ventas = null;

        if (state.idCierreActual && tipo !== 'pendiente'){
            const d = await fetchJSON(API + '/data-cierre', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_cierre: state.idCierreActual })
            });
            if (d.ok){
                cierreData = d.cierreData;
                ventas = d.ventas;
            }
        }

        if (!cierreData){
            const det = await fetchJSON(API + '/detalle-ventas', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha, id_user: idUser })
            });
            if (!det.ok){
                toast('No hay ventas para generar el PDF.', 'error');
                return;
            }

            ventas = det.data || [];
            if (!ventas.length){
                toast('No hay ventas para generar el PDF.', 'error');
                return;
            }

            const resumen = calcularResumenDesdeVentas(ventas);

            const nombreVendedor = (idUser === state.userId)
                ? (state.usuarioNombre + ' ' + state.usuarioApellido).trim()
                : '';

            cierreData = {
                id_cierre:        null,
                fecha_cierre:     fecha,
                estacion:         state.estacionNombre,
                usuario_nombres:  nombreVendedor,
                usuario_apellidos:'',
                tasa_dia:         state.tasa,
                total_ventas:     resumen.totalVentas,
                total_litros:     resumen.totalLitros,
                total_divisa:     resumen.totalDivisa,
                total_efectivo:   resumen.totalEfectivo,
                total_debito:     resumen.totalDebito,
                total_general_bs: resumen.totalGeneralBs,
                cant_auto:        resumen.cantAuto,
                cant_moto:        resumen.cantMoto,
                cant_camion:      resumen.cantCamion,
                cant_gasolina:    resumen.cantGasolina,
                cant_diesel:      resumen.cantDiesel,
                litros_gasolina:  resumen.litrosGasolina,
                litros_diesel:    resumen.litrosDiesel,
            };
        }

        // Filtrar por sección (gasolina | diesel) — igual que antes
        const ventasFiltradas = filtrarVentasPorSeccion(ventas, seccion);
        if (!ventasFiltradas.length){
            toast(`No hay ventas de ${seccion === 'gasolina' ? 'gasolina' : 'diésel'}.`, 'error');
            return;
        }

        const resumenFiltrado = calcularResumenDesdeVentas(ventasFiltradas);
        const cierreFiltrado = {
            ...cierreData,
            total_ventas:     resumenFiltrado.totalVentas,
            total_litros:     resumenFiltrado.totalLitros,
            total_divisa:     resumenFiltrado.totalDivisa,
            total_efectivo:   resumenFiltrado.totalEfectivo,
            total_debito:     resumenFiltrado.totalDebito,
            total_general_bs: resumenFiltrado.totalGeneralBs,
            cant_auto:        resumenFiltrado.cantAuto,
            cant_moto:        resumenFiltrado.cantMoto,
            cant_camion:      resumenFiltrado.cantCamion,
            cant_gasolina:    resumenFiltrado.cantGasolina,
            cant_diesel:      resumenFiltrado.cantDiesel,
            litros_gasolina:  resumenFiltrado.litrosGasolina,
            litros_diesel:    resumenFiltrado.litrosDiesel,
        };

        try {
            await SITGO_PDF.generar(
                'estacion_reporte_dia',
                { cierre: cierreFiltrado, ventas: ventasFiltradas, seccion },
                `cierre_${seccion}_${fecha}.pdf`,
                {
                    titulo_documento: 'CIERRE DEL DÍA',
                    doc_color:        '#1e40af',
                    mostrar_header:   false,
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error al generar PDF: ' + e.message, 'error');
        }
    }

    function calcularResumenDesdeVentas(ventas){
        let totalVentas = ventas.length;
        let totalLitros = 0, totalDivisa = 0, totalEfectivo = 0, totalDebito = 0, totalGeneralBs = 0;
        let cantAuto = 0, cantMoto = 0, cantCamion = 0;
        let cantGasolina = 0, cantDiesel = 0;
        let litrosGasolina = 0, litrosDiesel = 0;

        ventas.forEach(v => {
            const litros    = parseFloat(v.cantidad_litros || 0);
            const efectivob = parseFloat(v.efectivob || 0);
            const debito    = parseFloat(v.tarjeta_debito || 0);
            const divisa    = parseFloat(v.monto_divisa || 0);

            totalLitros += litros;
            totalDivisa += divisa;
            totalEfectivo += efectivob;
            totalDebito += debito;
            totalGeneralBs += efectivob + debito;

            const tv = (v.tipo_vehiculo || '').toLowerCase();
            if (tv.includes('moto')) { cantMoto++; }
            else if (tv.includes('camion') || tv.includes('camión')) { cantCamion++; }
            else if (tv.includes('auto') || tv.includes('carro')) { cantAuto++; }

            const tc = (v.tipo_combustible || '').toLowerCase();
            if (tc.includes('gasolina')) { cantGasolina++; litrosGasolina += litros; }
            else if (tc.includes('diesel') || tc.includes('diésel')) { cantDiesel++; litrosDiesel += litros; }
        });

        return {
            totalVentas, totalLitros, totalDivisa, totalEfectivo, totalDebito, totalGeneralBs,
            cantAuto, cantMoto, cantCamion, cantGasolina, cantDiesel, litrosGasolina, litrosDiesel,
        };
    }
        /* ============================================================
       FILTRAR VENTAS POR SECCIÓN (gasolina | diesel)
       ============================================================ */
    function filtrarVentasPorSeccion(ventas, seccion){
        if (!Array.isArray(ventas)) return [];
        if (!seccion) return ventas;

        const objetivo = seccion === 'gasolina' ? 'gasolina' : 'diesel';
        return ventas.filter(v => {
            const tc = (v.tipo_combustible || '').toLowerCase();
            if (objetivo === 'gasolina') return tc.includes('gasolina');
            return tc.includes('diesel') || tc.includes('diésel');
        });
    }
    /* ============================================================
       HELPERS
       ============================================================ */
    function fechaHoy(){
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }
    function formatFecha(f){
        if (!f) return '—';
        const [y,m,d] = f.split('-'); return `${d}/${m}/${y}`;
    }
    function icon(name){
        return `<i data-lucide="${name}" class="w-4 h-4" style="display:inline-block;vertical-align:middle;margin-right:.35rem;"></i>`;
    }
    function esc(s){
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
    function toast(msg, type = 'success'){
        let c = document.querySelector('.toast-container');
        if (!c){ c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); }
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

        /* ============================================================
       PDF POR FECHA · Carga del <select> de usuarios de la estación
       ============================================================ */
    async function cargarUsuariosEstacion(){
        const d = await fetchJSON(API + '/usuarios-por-estacion');
        if (!d.ok) return;

        const sel = $('pdfUsuario');
        if (!sel) return;

        sel.innerHTML = '<option value="0">Todos los usuarios de la estación</option>';
        (d.data || []).forEach(u => {
            const o = document.createElement('option');
            o.value = u.id;
            o.textContent = u.nombre || u.nick;
            sel.appendChild(o);
        });
    }

    /* ============================================================
       PDF POR FECHA · Paso 1: validar y abrir el modal selector
       El modal es el MISMO que el de "PDF del día".
       ============================================================ */
    function generarPdfPorFecha(){
        const fecha  = $('pdfFecha')?.value || '';
        const idUsr  = parseInt($('pdfUsuario')?.value || '0');

        if (!fecha){ toast('Selecciona una fecha.', 'error'); return; }
        if (fecha > fechaHoy()){ toast('No se pueden generar PDF de fechas futuras.', 'error'); return; }
        if (!state.idEstacion){ toast('Sin estación asignada.', 'error'); return; }

        // Guardar contexto para que el modal sepa qué generar
        state._pdfFechaCtx = { fecha, id_user: idUsr };

        // Abrir el mismo modal (tipo='fecha')
        abrirModalPdf('fecha');
    }
});