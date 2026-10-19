console.log('✅ estacion.js cargado');

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
    };

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        await cargar();
        bindEvents();
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

        // Selects
        llenar($('tipoVehiculo'), state.tiposVehiculo, 'Seleccione...');
        llenar($('tipoPago'),     state.tiposPago,     'Seleccione...');

        // Combustibles
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

        // Tasa
        if (d.tasa && d.tasa.tasa_dia){
            state.tasa = parseFloat(d.tasa.tasa_dia);
            aplicarTasa(d.tasa);
        }

        // Render
        renderTickets(d.ultimosTickets || []);
        renderResumen(d.resumen);
        renderPendientes(d.ventasPendientes || []);
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

        // Pagos
        const pagos = [];
        if (parseFloat(r.total_divisa) > 0)   pagos.push(`<li>${icon('dollar-sign')} Divisa: <strong>$${parseFloat(r.total_divisa).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_efectivo) > 0) pagos.push(`<li>${icon('banknote')} Efectivo: <strong>Bs ${parseFloat(r.total_efectivo).toFixed(2)}</strong></li>`);
        if (parseFloat(r.total_debito) > 0)   pagos.push(`<li>${icon('credit-card')} Punto: <strong>Bs ${parseFloat(r.total_debito).toFixed(2)}</strong></li>`);
        $('tiposPagosList').innerHTML = pagos.join('');
        $('tiposPagosContainer').style.display = pagos.length ? '' : 'none';

        // Vehículos
        const veh = (r.tiposVehiculo || []).map(v =>
            `<li>${icon('car')} ${esc(v.tipo_vehiculo)}: <strong>${v.cantidad}</strong></li>`
        );
        $('tiposVehiculosList').innerHTML = veh.join('');
        $('tiposVehiculosContainer').style.display = veh.length ? '' : 'none';

        // Combustibles
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
            b.addEventListener('click', () => generarPDF(b.dataset.fecha, b.dataset.user));
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
        $('btnGenerarPDF')?.addEventListener('click', () => generarPDF(fechaHoy(), state.userId));
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

    async function registrarVenta(e){
        e.preventDefault();

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

        const d = await fetchJSON(API + '/registrar-venta', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast(d.message, 'success');

        if (d.ticketData && typeof fntImprimirTicket === 'function'){
            try{ await fntImprimirTicket({ ticketData: d.ticketData, copia: 0 }); }
            catch(e){ console.warn('Impresión falló:', e); }
        }

        $('formVenta').reset();
        $('monto').value = '';
        $('tipoCombustible').value = '1';
        updatePreview();
        await cargar();
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

    async function cerrarDia(){
        if (!confirm('¿Cerrar el día actual? No se podrán registrar más ventas para esta fecha.')) return;

        const fecha = fechaHoy();

        // Imprimir detallado (con modo)
        if (typeof fntPreguntarModoDetallado === 'function'){
            const modo = await fntPreguntarModoDetallado();
            if (modo){
                const detalle = await fetchJSON(API + '/detalle-ventas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha, id_user: state.userId })
                });
                if (detalle.ok && typeof fntImprimirDetallado === 'function'){
                    try{ await fntImprimirDetallado(detalle.data, modo); } catch(e){ console.warn(e); }
                }
            }
        }

        const d = await fetchJSON(API + '/cerrar-dia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        if (d.dataCierre && typeof fntImprimirCierre === 'function'){
            try{ await fntImprimirCierre(d.dataCierre); } catch(e){ console.warn(e); }
        }

        toast(d.message, 'success');
        await cargar();
    }

    async function cerrarPendiente(fecha, idUser){
        if (!confirm(`¿Cerrar el día ${formatFecha(fecha)}?`)) return;

        if (typeof fntPreguntarModoDetallado === 'function'){
            const modo = await fntPreguntarModoDetallado();
            if (modo){
                const detalle = await fetchJSON(API + '/detalle-ventas', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ fecha, id_user: idUser })
                });
                if (detalle.ok && typeof fntImprimirDetallado === 'function'){
                    try{ await fntImprimirDetallado(detalle.data, modo); } catch(e){ console.warn(e); }
                }
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
    }

    async function generarPDF(fecha, idUser){
        const d = await fetchJSON(API + '/reporte-pdf', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user: idUser })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        if (typeof fntGenerarPDF === 'function'){
            fntGenerarPDF({ idUser, fecha });
        } else {
            toast('Datos preparados.', 'success');
        }
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
});