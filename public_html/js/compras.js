document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'compras/i/' + INST;
    const $ = id => document.getElementById(id);

    let dtPendientes = null;
    let dtCosteadas = null;
    let flotaCache = [];

    /* ============================================================
       HELPER · Refrescar iconos Lucide de forma robusta
       ============================================================ */
    function refreshIcons(){
        if (window.lucide && typeof window.lucide.createIcons === 'function'){
            try { window.lucide.createIcons(); } catch(e){ console.error('lucide:', e); }
            return;
        }
        let tries = 0;
        const t = setInterval(() => {
            tries++;
            if (window.lucide && typeof window.lucide.createIcons === 'function'){
                clearInterval(t);
                try { window.lucide.createIcons(); } catch(e){ console.error('lucide:', e); }
            } else if (tries > 50){
                clearInterval(t);
                console.warn('Lucide no se cargó en 5s');
            }
        }, 100);
    }

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        try{
            await cargarFlota();

            dtPendientes = crearDataTablePendientes();
            cargarBadgePendientes();

            bindTabs();
            bindEventosPendientes();
            bindEventosCosteadas();
            bindEventosReportes();
            bindModales();
            bindPdfDetalle();
            bindPdfCosteadas();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       TABS
       ============================================================ */
    function bindTabs(){
        document.querySelectorAll('.compras-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.tab;
                document.querySelectorAll('.compras-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                $('tabPendientes').style.display = target === 'pendientes' ? '' : 'none';
                $('tabCosteadas').style.display   = target === 'costeadas'  ? '' : 'none';
                $('tabReportes').style.display    = target === 'reportes'   ? '' : 'none';

                if (target === 'costeadas' && !dtCosteadas){
                    dtCosteadas = crearDataTableCosteadas();
                }
            });
        });
    }

    /* ============================================================
       DATATABLE · PENDIENTES
       ============================================================ */
    function crearDataTablePendientes(){
        return new DataTable({
            tbodyId: 'tbodyPendientes',
            endpoint: API + '/pendientes',
            columns: ['#','Fecha','Unidad','Artículos','Unidades','Acciones'],
            renderRow: renderPendienteRow,
            searchInputId: 'filtroBuscarPend',
            limitSelectorId: 'filtroLimitPend',
            paginationId: 'paginationPendientes',
            totalId: 'totalPendientes',
            startId: 'startPendientes',
            endId: 'endPendientes',
            noDataMessage: 'No hay despachos pendientes de costeo',
            onDataLoaded: () => {
                refreshIcons();
                bindTableActions();
            }
        });
    }

    function renderPendienteRow(d){
        return `
            <tr>
                <td><strong>#${d.numero_orden || d.id_despacho}</strong></td>
                <td>${formatFecha(d.fecha_despacho)}</td>
                <td>
                    <strong>${esc(d.id_unidad || '—')}</strong>
                    <div style="font-size:.7rem; color:#94a3b8;">${esc(d.marca_unidad || '')} ${esc(d.modelo_unidad || '')}</div>
                </td>
                <td class="text-center">
                    <span class="badge badge-warning">${d.articulos_pendientes} art.</span>
                </td>
                <td class="text-center">${Number(d.total_unidades || 0).toLocaleString('es-VE')}</td>
                <td class="text-right">
                    <button class="btn-costear" data-action="asignar" data-id="${d.id_despacho}" title="Costear esta orden">
                        <i data-lucide="dollar-sign"></i>
                        <span>Costear</span>
                    </button>
                </td>
            </tr>`;
    }

    /* ============================================================
       DATATABLE · COSTEADAS
       ============================================================ */
    function crearDataTableCosteadas(){
        return new DataTable({
            tbodyId: 'tbodyCosteadas',
            endpoint: API + '/costeadas',
            columns: ['#','Fecha','Unidad','Artículos','Total $','Total Bs','Acciones'],
            renderRow: renderCosteadaRow,
            searchInputId: 'filtroBuscarCost',
            limitSelectorId: 'filtroLimitCost',
            paginationId: 'paginationCosteadas',
            totalId: 'totalCosteadas',
            startId: 'startCosteadas',
            endId: 'endCosteadas',
            noDataMessage: 'No hay despachos costeados',
            getExtraParams: () => ({
                fecha_desde: $('filtroCostoDesde')?.value || '',
                fecha_hasta: $('filtroCostoHasta')?.value || ''
            }),
            onDataLoaded: () => {
                refreshIcons();
                bindTableActionsCosteadas();
            }
        });
    }

    function renderCosteadaRow(d){
        return `
            <tr>
                <td><strong>#${d.numero_orden || d.id_despacho}</strong></td>
                <td>${formatFecha(d.fecha_despacho)}</td>
                <td>
                    <strong>${esc(d.id_unidad || '—')}</strong>
                    <div style="font-size:.7rem; color:#94a3b8;">${esc(d.marca_unidad || '')} ${esc(d.modelo_unidad || '')}</div>
                </td>
                <td class="text-center">
                    <span class="badge badge-success">${d.articulos_costeados} art.</span>
                </td>
                <td class="text-right">$ ${Number(d.total_divisa || 0).toFixed(2)}</td>
                <td class="text-right">Bs ${Number(d.total_bs || 0).toFixed(2)}</td>
                <td class="text-right">
                    <button class="action-btn edit" data-action="ver" data-id="${d.id_despacho}" title="Ver detalle">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>`;
    }

    /* ============================================================
       BADGE PENDIENTES
       ============================================================ */
    async function cargarBadgePendientes(){
        try{
            const d = await fetchJSON(API + '/pendientes?limit=1');
            if (d.ok){
                $('badgePendientes').textContent = d.totalFiltrados || d.total || 0;
            }
        } catch {}
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEventosPendientes(){
        // Se maneja en bindTableActions()
    }

    function bindEventosCosteadas(){
        $('btnFiltrarCosteadas')?.addEventListener('click', () => {
            if (dtCosteadas) dtCosteadas.changePage(1);
        });

        $('btnLimpiarCosteadas')?.addEventListener('click', () => {
            $('filtroCostoDesde').value = '';
            $('filtroCostoHasta').value = '';
            if (dtCosteadas) dtCosteadas.changePage(1);
        });
    }

    function bindEventosReportes(){
        $('repUnidad')?.addEventListener('change', actualizarConteoReporte);
        $('repDesde')?.addEventListener('change', actualizarConteoReporte);
        $('repHasta')?.addEventListener('change', actualizarConteoReporte);

        $('btnGenerarReporte')?.addEventListener('click', () => {
            toast('Generación de PDF se implementará próximamente.', 'error');
        });
    }

    async function actualizarConteoReporte(){
        const unidad = $('repUnidad')?.value || '';
        const desde  = $('repDesde')?.value || '';
        const hasta  = $('repHasta')?.value || '';
        const div    = $('repConteo');
        const btn    = $('btnGenerarReporte');

        if (!unidad){
            div.textContent = 'Selecciona una unidad para ver las órdenes disponibles.';
            btn.disabled = true;
            return;
        }
        if (!desde || !hasta){
            div.textContent = 'Selecciona un rango de fechas.';
            btn.disabled = true;
            return;
        }

        try{
            const d = await fetchJSON(API + '/conteo-ordenes', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_flota: unidad, fecha_desde: desde, fecha_hasta: hasta })
            });

            if (!d.ok) throw new Error(d.message);

            if (d.total > 0){
                div.innerHTML = `<span style="color:#16a34a;">✓ Se encontraron <strong>${d.total}</strong> órdenes para este reporte.</span>`;
                btn.disabled = false;
            } else {
                div.innerHTML = `<span style="color:#d97706;">⚠ No hay órdenes para esta unidad en el rango de fechas.</span>`;
                btn.disabled = true;
            }
        } catch (e){
            div.textContent = 'Error al consultar.';
            btn.disabled = true;
        }
    }

    function bindModales(){
        $('formCosto')?.addEventListener('submit', guardarCosto);
        $('costo_tasa')?.addEventListener('input', recalcularMontosCosto);

        $('btnAnularDetalle')?.addEventListener('click', anularCosto);
    }

    /* ============================================================
       ACCIONES · PENDIENTES
       ============================================================ */
    function bindTableActions(){
        const tbody = $('tbodyPendientes');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            if (btn.dataset.action === 'asignar') abrirModalCosto(btn.dataset.id);
        });
    }

    /* ============================================================
       ACCIONES · COSTEADAS
       ============================================================ */
    function bindTableActionsCosteadas(){
        const tbody = $('tbodyCosteadas');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            if (btn.dataset.action === 'ver') abrirModalDetalle(btn.dataset.id);
        });
    }

    /* ============================================================
       MODAL ASIGNAR COSTO
       ============================================================ */
    let articulosCosto = [];

    async function abrirModalCosto(id){
        try{
            const d = await fetchJSON(API + '/articulos/' + id);
            if (!d.ok) throw new Error(d.message);

            $('costo_id_despacho').value = id;
            $('costo_info_numero').textContent = '#' + (d.info.numero_orden || id);
            $('costo_info_unidad').textContent = `${d.info.id_unidad} - ${d.info.modelo_unidad || ''}`;
            $('costo_info_fecha').textContent = formatFecha(d.info.fecha_despacho);
            $('costo_tasa').value = '';

            articulosCosto = d.articulos || [];

            renderTablaCosto();
            $('modalCosto').classList.add('open');
            document.body.style.overflow = 'hidden';
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    function renderTablaCosto(){
        const tbody = $('tbodyArticulosCosto');

        if (!articulosCosto.length){
            tbody.innerHTML = `<tr><td colspan="5" class="dt-empty">No hay artículos pendientes</td></tr>`;
            return;
        }

        tbody.innerHTML = articulosCosto.map((a, i) => `
            <tr data-idx="${i}" data-cantidad="${a.cant_despacho}">
                <td>${esc(a.producto)}</td>
                <td class="text-right">${Number(a.cant_despacho).toLocaleString('es-VE')} ${esc(a.present_producto || '')}</td>
                <td class="text-right" style="width:140px;">
                    <input type="number" class="monto-unit" data-idx="${i}" min="0" step="0.01" placeholder="0.00">
                </td>
                <td class="text-right monto-total-usd" style="width:120px; font-weight:600;">$ 0.00</td>
                <td class="text-right monto-total-bs" style="width:120px; color:#64748b;">Bs 0.00</td>
            </tr>
        `).join('');

        tbody.querySelectorAll('.monto-unit').forEach(inp => {
            inp.addEventListener('input', recalcularMontosCosto);
        });

        refreshIcons();
    }

    function recalcularMontosCosto(){
        const tasa = parseFloat($('costo_tasa').value) || 0;

        document.querySelectorAll('#tbodyArticulosCosto tr[data-idx]').forEach(row => {
            const cant = parseFloat(row.dataset.cantidad) || 0;
            const unitVal = parseFloat(row.querySelector('.monto-unit').value) || 0;
            const totalUsd = cant * unitVal;
            const totalBs = totalUsd * tasa;

            row.querySelector('.monto-total-usd').textContent = '$ ' + totalUsd.toFixed(2);
            row.querySelector('.monto-total-bs').textContent = 'Bs ' + totalBs.toFixed(2);
        });
    }

    async function guardarCosto(e){
        e.preventDefault();

        const id = $('costo_id_despacho').value;
        const tasa = parseFloat($('costo_tasa').value) || 0;

        if (tasa <= 0){
            toast('Ingresa una tasa válida mayor a cero.', 'error');
            return;
        }

        const articulos = [];
        document.querySelectorAll('#tbodyArticulosCosto tr[data-idx]').forEach(row => {
            const idx = parseInt(row.dataset.idx);
            const cant = parseFloat(row.dataset.cantidad) || 0;
            const unitVal = parseFloat(row.querySelector('.monto-unit').value) || 0;

            if (unitVal > 0){
                articulos.push({
                    id: articulosCosto[idx].id_compra_pendiente,
                    monto: (unitVal * cant).toFixed(2)
                });
            }
        });

        if (!articulos.length){
            toast('Ingresa al menos un monto.', 'error');
            return;
        }

        try{
            const d = await fetchJSON(API + '/asignar-costo', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_despacho: parseInt(id),
                    tasa,
                    articulos
                })
            });

            if (!d.ok) throw new Error(d.message);

            toast(d.message, 'success');
            cerrarModalCosto();
            dtPendientes?.refresh();
            if (dtCosteadas) dtCosteadas.refresh();
            cargarBadgePendientes();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    window.cerrarModalCosto = function(){
        $('modalCosto').classList.remove('open');
        document.body.style.overflow = '';
    };

    /* ============================================================
       MODAL DETALLE
       ============================================================ */
    let detalleActual = null;

    async function abrirModalDetalle(id){
        try{
            const d = await fetchJSON(API + '/detalle/' + id);
            if (!d.ok) throw new Error(d.message);

            detalleActual = { id, ...d };

            $('det_info_numero').textContent = '#' + (d.info.numero_orden || id);
            $('det_info_unidad').textContent = `${d.info.id_unidad} - ${d.info.modelo_unidad || ''}`;
            $('det_info_fecha').textContent = formatFecha(d.info.fecha_despacho);

            const tbody = $('tbodyDetalle');
            tbody.innerHTML = (d.articulos || []).map(a => `
                <tr>
                    <td>${esc(a.producto)}</td>
                    <td class="text-right">${Number(a.cant_despacho).toLocaleString('es-VE')}</td>
                    <td class="text-right">${Number(a.tasa_dia).toFixed(2)}</td>
                    <td class="text-right">$ ${Number(a.monto_divisa).toFixed(2)}</td>
                    <td class="text-right">Bs ${Number(a.monto_bs).toFixed(2)}</td>
                </tr>
            `).join('') || '<tr><td colspan="5" class="dt-empty">Sin artículos</td></tr>';

            $('tfootDetalle').innerHTML = `
                <tr>
                    <td colspan="3" class="text-right">TOTALES:</td>
                    <td class="text-right">$ ${Number(d.total_divisa).toFixed(2)}</td>
                    <td class="text-right">Bs ${Number(d.total_bs).toFixed(2)}</td>
                </tr>
            `;

            $('modalDetalle').classList.add('open');
            document.body.style.overflow = 'hidden';

            refreshIcons();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    async function anularCosto(){
        if (!detalleActual) return;
        if (!confirm('¿Anular el costeo? La orden volverá a "Pendientes".')) return;

        try{
            const d = await fetchJSON(API + '/anular/' + detalleActual.id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);

            toast(d.message, 'success');
            cerrarModalDetalle();
            dtPendientes?.refresh();
            if (dtCosteadas) dtCosteadas.refresh();
            cargarBadgePendientes();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    window.cerrarModalDetalle = function(){
        $('modalDetalle').classList.remove('open');
        document.body.style.overflow = '';
    };

    /* ============================================================
       PDF · ORDEN COSTEADA (individual)
       ============================================================ */
    function bindPdfDetalle(){
        $('btnPdfDetalle')?.addEventListener('click', async () => {
            if (!detalleActual) return;

            const btn = $('btnPdfDetalle');
            const original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Generando...';

            try{
                const o = detalleActual;
                const numero = o.info?.numero_orden || o.id;

                await SITGO_PDF.generar(
                    'compras_orden_costeada',
                    { orden: o },
                    `orden_costeada_${numero}.pdf`,
                    {
                        titulo_documento: 'ORDEN COSTEADA',
                        doc_numero:       '#' + String(numero).padStart(6, '0'),
                        doc_color:        '#1e40af',
                    }
                );
            } catch(e){
                console.error(e);
                toast('Error al generar el PDF: ' + e.message, 'error');
            } finally{
                btn.disabled = false;
                btn.innerHTML = original;
                refreshIcons();
            }
        });
    }

    /* ============================================================
       PDF · COSTEADAS FILTRADAS (lote)
       ============================================================ */
    function bindPdfCosteadas(){
        $('btnPdfCosteadas')?.addEventListener('click', generarPdfCosteadas);
    }

    async function generarPdfCosteadas(){
        const btn = $('btnPdfCosteadas');
        if (!btn) return;

        const desde = $('filtroCostoDesde')?.value || '';
        const hasta = $('filtroCostoHasta')?.value || '';

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Generando...';

        try{
            const q = $('filtroBuscarCost')?.value || '';
            const params = new URLSearchParams({
                q,
                limit: 5000,
                offset: 0,
                fecha_desde: desde,
                fecha_hasta: hasta,
            });

            const r = await fetch(`${API}/costeadas?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                throw new Error('El servidor no devolvió JSON.');
            }

            const d = await r.json();
            if (!d.ok) throw new Error(d.message || 'Error al cargar las costeadas.');

            let ordenes = d.data || [];
            if (!Array.isArray(ordenes)) ordenes = [];

            if (!ordenes.length){
                toast('No hay costeadas en el rango seleccionado.', 'error');
                return;
            }

            await SITGO_PDF.generar(
                'compras_costeadas_filtrado',
                {
                    fecha_desde: desde,
                    fecha_hasta: hasta,
                    ordenes,
                },
                `compras_costeadas_${desde || 'inicio'}_${hasta || 'hoy'}.pdf`,
                {
                    titulo_documento: 'COMPRAS COSTEADAS',
                    doc_color:        '#1e40af',
                }
            );

            toast('PDF generado correctamente.', 'success');
        } catch(e){
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
        } finally{
            btn.disabled = false;
            btn.innerHTML = original;
            refreshIcons();
        }
    }

    /* ============================================================
       FLOTA
       ============================================================ */
    async function cargarFlota(){
        const d = await fetchJSON(API + '/flota');
        if (!d.ok) throw new Error('flota');
        flotaCache = d.data || [];

        const sel = $('repUnidad');
        if (!sel) return;
        sel.innerHTML = '<option value="">Seleccione una unidad</option>';
        flotaCache.forEach(f => {
            const o = document.createElement('option');
            o.value = f.id_flota;
            o.textContent = `${f.id_unidad} - ${f.modelo_unidad || ''}`;
            sel.appendChild(o);
        });
    }

    /* ============================================================
       HELPERS
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

    function formatFecha(f){
        if (!f) return '—';
        try{
            const parts = f.split('-');
            if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
            return f;
        } catch { return f; }
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