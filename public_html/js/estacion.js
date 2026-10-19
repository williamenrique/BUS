console.log('✅ estacion.js cargado');

document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'estacion';
    const $ = id => document.getElementById(id);

    let state = {
        tiposVehiculo: [],
        tiposPago: [],
        tasa: 0,
        userId: 0,
        idEstacion: 0,
        esSistema: false,
    };

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        try{
            await cargar();
            bindEvents();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       API
       ============================================================ */
    async function fetchJSON(url, opts = {}){
        try{
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                return { ok: false, message: `Error ${r.status}`, type: 'unknown' };
            }
            return await r.json();
        } catch {
            return { ok: false, message: 'Error de conexión', type: 'network' };
        }
    }

    async function cargar(){
        const d = await fetchJSON(API + '/initial-data');
        if (!d.ok) throw new Error(d.message || 'initial-data');

        state.tiposVehiculo = d.tiposVehiculo || [];
        state.tiposPago     = d.tiposPago || [];
        state.userId        = d.idUser;
        state.idEstacion    = d.idEstacion;
        state.esSistema     = !!d.esSistema;

        // Tasa
        if (d.tasa && d.tasa.tasa_dia){
            state.tasa = parseFloat(d.tasa.tasa_dia);
            aplicarTasa(d.tasa);
        } else {
            $('tasaDisplay').textContent = '0.00';
            $('tasaInput').style.display = '';
            $('btnUpdateTasa').style.display = '';
            $('tasaLastUpdate').textContent = 'Sin actualizar';
        }

        // Selects
        llenarSelect($('tipoVehiculo'), d.tiposVehiculo, 'id', 'nombre', 'Seleccione...');
        llenarSelect($('tipoPago'),     d.tiposPago,     'id', 'nombre', 'Seleccione...');

        // Tickets
        renderTickets(d.ultimosTickets || []);

        // Resumen
        renderResumen(d.resumen);

        // Cierres pendientes
        renderPendientes(d.ventasPendientes || []);
    }

    function llenarSelect(sel, items, vKey, lKey, ph){
        if (!sel) return;
        sel.innerHTML = `<option value="">${ph}</option>`;
        (items || []).forEach(it => {
            const o = document.createElement('option');
            o.value = it[vKey];
            o.textContent = it[lKey];
            sel.appendChild(o);
        });
    }

    function aplicarTasa(tasaObj){
        const tasa = parseFloat(tasaObj.tasa_dia || 0);
        $('tasaDisplay').textContent = tasa.toFixed(2);
        $('tasaInput').value = tasa.toFixed(2);
        state.tasa = tasa;

        if (tasaObj.tasa_update && tasaObj.tasa_update !== '0000-00-00 00:00:00'){
            const fechaUpdate = new Date(tasaObj.tasa_update.replace(/-/g, '/'));
            const opts = { day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit' };
            $('tasaLastUpdate').textContent = 'Últ. actualización: ' + fechaUpdate.toLocaleDateString('es-VE', opts);

            const hoy = new Date();
            const mismaFecha = fechaUpdate.getFullYear() === hoy.getFullYear()
                && fechaUpdate.getMonth() === hoy.getMonth()
                && fechaUpdate.getDate() === hoy.getDate();

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
       RENDER TICKETS
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
                <td>${esc(t.tipoVehiculo || '')}</td>
                <td class="text-right">${parseFloat(t.litros || 0).toFixed(2)}</td>
                <td class="text-right">
                    <button class="action-btn" data-action="print" data-id="${t.id_venta}" data-fecha="${t.fecha_venta}" data-user="${t.id_user}" title="Reimprimir">
                        <i data-lucide="printer" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        if (window.lucide) lucide.createIcons();

        tbody.querySelectorAll('[data-action="print"]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.id;
                const fecha = btn.dataset.fecha;
                const idUser = btn.dataset.user;
                await reimprimirTicket(id, fecha, idUser);
            });
        });
    }

    /* ============================================================
       RENDER RESUMEN
       ============================================================ */
    function renderResumen(r){
        if (!r){ return; }

        $('totalVehiculos').textContent = r.total_ventas ?? 0;
        $('totalLitros').textContent    = parseFloat(r.total_litros || 0).toFixed(2) + ' L';
        $('totalBolivares').textContent = parseFloat(r.total_bs || 0).toFixed(2) + ' Bs';

        const pagos = [];
        if (parseFloat(r.total_divisa) > 0)
            pagos.push(`<li>${iconHtml('dollar-sign')} Divisa: <strong>$${parseFloat(r.total_divisa).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_efectivo) > 0)
            pagos.push(`<li>${iconHtml('banknote')} Efectivo: <strong>Bs ${parseFloat(r.total_efectivo).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_debito) > 0)
            pagos.push(`<li>${iconHtml('credit-card')} Punto de venta: <strong>Bs ${parseFloat(r.total_debito).toFixed(2)}</strong></li>`);

        $('tiposPagosList').innerHTML = pagos.join('');
        $('tiposPagosContainer').style.display = pagos.length ? '' : 'none';

        const veh = (r.tiposVehiculo || []).map(v =>
            `<li>${iconHtml('car')} ${esc(v.tipo_vehiculo)}: <strong>${v.cantidad}</strong></li>`
        );
        $('tiposVehiculosList').innerHTML = veh.join('');
        $('tiposVehiculosContainer').style.display = veh.length ? '' : 'none';

        $('btnCerrarDia').style.display  = (r.total_ventas > 0) ? '' : 'none';
        $('btnGenerarPDF').style.display = (r.total_ventas > 0) ? '' : 'none';

        if (window.lucide) lucide.createIcons();
    }

    /* ============================================================
       RENDER PENDIENTES
       ============================================================ */
    function renderPendientes(ventas){
        const sec = $('cierrePendienteSection');
        const cont = $('cierrePendienteList');

        if (!ventas || !ventas.length){
            sec.style.display = 'none';
            return;
        }

        sec.style.display = '';
        cont.innerHTML = ventas.map(v => `
            <div class="estacion-cierre-pendiente-item">
                <strong>Día sin cierre: ${formatFecha(v.fecha_venta)}</strong>
                <div style="display:flex; gap:.35rem;">
                    <button class="btn btn-danger btn-sm" data-action="cerrar-pendiente"
                            data-fecha="${v.fecha_venta}" data-user="${v.id_user}">
                        Cerrar día
                    </button>
                    <button class="btn btn-ghost btn-sm" data-action="pdf-pendiente"
                            data-fecha="${v.fecha_venta}" data-user="${v.id_user}">
                        PDF
                    </button>
                </div>
            </div>
        `).join('');

        cont.querySelectorAll('[data-action="cerrar-pendiente"]').forEach(btn => {
            btn.addEventListener('click', () => cerrarPendiente(btn.dataset.fecha, btn.dataset.user));
        });
        cont.querySelectorAll('[data-action="pdf-pendiente"]').forEach(btn => {
            btn.addEventListener('click', () => generarPDFDia(btn.dataset.user, btn.dataset.fecha));
        });
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEvents(){
        $('litros')?.addEventListener('input', calcularMonto);
        $('tipoPago')?.addEventListener('change', calcularMonto);
        $('tasaInput')?.addEventListener('input', calcularMonto);

        $('btnUpdateTasa')?.addEventListener('click', actualizarTasa);
        $('formVenta')?.addEventListener('submit', registrarVenta);
        $('btnCerrarDia')?.addEventListener('click', cerrarDia);
        $('btnGenerarPDF')?.addEventListener('click', () => generarPDFDia(state.userId, fechaHoy()));
    }

    /* ============================================================
       CALCULAR MONTO
       ============================================================ */
    function calcularMonto(){
        const tp = $('tipoPago')?.value;
        const lts = parseFloat($('litros')?.value) || 0;
        const tasa = parseFloat($('tasaInput')?.value || $('tasaDisplay')?.textContent) || state.tasa;

        let monto = 0;
        if (tp === '1') monto = lts * 0.5;                // Divisa
        else if (tp === '2' || tp === '3') monto = lts * 0.5 * tasa; // Efectivo / Punto (Bs)
        $('monto').value = monto ? monto.toFixed(2) : '';
    }

    /* ============================================================
       ACTUALIZAR TASA
       ============================================================ */
    async function actualizarTasa(){
        const tasa = parseFloat($('tasaInput')?.value);
        if (!tasa || tasa <= 0){ toast('Ingresa una tasa válida.', 'error'); return; }

        const d = await fetchJSON(API + '/update-tasa', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tasa })
        });

        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        toast(d.message, 'success');
        await cargar();
    }

    /* ============================================================
       REGISTRAR VENTA
       ============================================================ */
    async function registrarVenta(e){
        e.preventDefault();

        const payload = {
            tipo_vehiculo: $('tipoVehiculo').value,
            litros:        $('litros').value,
            tipo_pago:     $('tipoPago').value,
            monto:         $('monto').value,
            tasa:          $('tasaInput')?.value || state.tasa,
            id_estacion:   state.idEstacion,
        };

        if (!payload.tipo_vehiculo || !payload.tipo_pago || parseFloat(payload.litros) <= 0){
            toast('Completa todos los campos.', 'error');
            return;
        }

        const d = await fetchJSON(API + '/registrar-venta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        toast(d.message, 'success');

        // Imprimir ticket
        if (d.ticketData){
            try{ await imprimirTicket(d.ticketData, 0); } catch(err){ console.warn('Impresión falló:', err); }
        }

        $('formVenta').reset();
        $('monto').value = '';
        await cargar();
    }

    /* ============================================================
       REIMPRIMIR
       ============================================================ */
    async function reimprimirTicket(id, fecha, idUser){
        const d = await fetchJSON(API + '/ticket/' + id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });

        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        try{ await imprimirTicket(d.ticketData, 1); toast('Ticket enviado a impresión.', 'success'); }
        catch(err){ toast('Error al imprimir: ' + err.message, 'error'); }
    }

    /* ============================================================
       CERRAR DÍA
       ============================================================ */
    async function cerrarDia(){
        if (!confirm('¿Cerrar el día actual? No se podrán registrar más ventas para esta fecha.')) return;

        const fecha = fechaHoy();

        // 1. Imprimir detalle antes de cerrar
        const detalle = await fetchJSON(API + '/detalle-ventas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: state.userId })
        });
        if (detalle.ok && detalle.data){
            try{ await imprimirDetallado(detalle.data); } catch(err){ console.warn(err); }
        }

        // 2. Cerrar
        const d = await fetchJSON(API + '/cerrar-dia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_estacion: state.idEstacion })
        });

        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        // 3. Imprimir cierre
        if (d.dataCierre){
            try{ await imprimirCierre(d.dataCierre); } catch(err){ console.warn(err); }
        }

        toast(d.message, 'success');
        await cargar();
    }

    async function cerrarPendiente(fecha, idUser){
        if (!confirm(`¿Cerrar el día ${formatFecha(fecha)}?`)) return;

        const detalle = await fetchJSON(API + '/detalle-ventas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });
        if (detalle.ok && detalle.data){
            try{ await imprimirDetallado(detalle.data); } catch(err){ console.warn(err); }
        }

        const d = await fetchJSON(API + '/cerrar-turno-pendiente', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        if (d.dataCierre){
            try{ await imprimirCierre(d.dataCierre); } catch(err){ console.warn(err); }
        }

        toast(d.message, 'success');
        await cargar();
    }

    async function generarPDFDia(idUser, fecha){
        const d = await fetchJSON(API + '/reporte-pdf', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast('Datos preparados. Genera el PDF con tu driver.', 'success');
        // Aquí llamas tu generación de PDF
        // generarPDF(d.data);
    }

    /* ============================================================
       IMPRESIÓN — STUBS
       ============================================================
       Aquí conectas tu aplicación de terceros para la impresora térmica.
       Ejemplo con QZ Tray:
       
       async function imprimirTicket(ticket, copia){
           const texto = formatearTicket(ticket, copia);
           await qz.print(configTermica, texto);
       }
       ============================================================ */
    async function imprimirTicket(ticket, copia = 0){
        console.log('[PRINT TICKET]', { ticket, copia });
        const texto = formatearTicketTexto(ticket, copia);
        // TODO: conectar QZ Tray u otra librería
        // await qz.print(config, texto);
        // Por ahora solo lo mostramos en consola
        console.log(texto);
    }

    async function imprimirDetallado(ventas){
        console.log('[PRINT DETALLADO]', ventas);
        // TODO: conectar impresora
    }

    async function imprimirCierre(cierre){
        console.log('[PRINT CIERRE]', cierre);
        // TODO: conectar impresora
    }

    function formatearTicketTexto(t, copia){
        const simbolo = (t.id_tipo_pago == 1) ? '$' : 'Bs';
        return `
================================
  ESTACIÓN DE SERVICIO
  ${copia ? '*** COPIA ***' : ''}
================================
Ticket #: ${t.id_venta}
Fecha: ${t.fecha_venta} ${t.hora_venta || ''}
--------------------------------
Tipo: ${t.tipoVehiculo || ''}
Pago: ${t.tipoPago || ''}
Litros: ${t.litros}
Monto: ${simbolo} ${t.monto}
Tasa: ${t.tasa_dia}
--------------------------------
¡Gracias por su compra!
================================
        `.trim();
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    function fechaHoy(){
        const d = new Date();
        const dd = String(d.getDate()).padStart(2, '0');
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const yyyy = d.getFullYear();
        return `${yyyy}-${mm}-${dd}`;
    }

    function formatFecha(f){
        if (!f) return '—';
        const [y, m, d] = f.split('-');
        return `${d}/${m}/${y}`;
    }

    function iconHtml(name){
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
});