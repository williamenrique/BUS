// v3.3 - Orden por Total Despachado (desc/asc) + filtros de fecha
(function () {
    'use strict';

    function cuandoListo(fn) {
        let intentos = 0;
        const maxIntentos = 200;
        const intervalo = setInterval(() => {
            intentos++;
            if (document.getElementById('btnGenerarPdfInventario')) {
                clearInterval(intervalo);
                try { fn(); } catch (e) { console.error('init error:', e); }
            } else if (intentos >= maxIntentos) {
                clearInterval(intervalo);
                console.error('❌ No apareció #btnGenerarPdfInventario después de 20s');
            }
        }, 100);
    }

    cuandoListo(function init() {

        const INST = window.SITGO_INST || 1;
        const API  = (window.SITGO_BASE || '/') + 'despachos/i/' + INST + '/historial';
        const $ = id => document.getElementById(id);

        let dt = null;

        // Estado del orden por Total Despachado
        let ordenTotal = 'desc'; // 'desc' | 'asc'

        let timelineState = {
            page: 1,
            limit: 10,
            totalPages: 1,
            productId: null,
            producto: null,
            fechaInicio: '',
            fechaFin: ''
        };

        /* ========================================================
           FILTROS DE FECHA · RESUMEN
           ======================================================== */
        function getRangoResumen() {
            return {
                fechaInicio: $('filtroFechaInicio')?.value || '',
                fechaFin:    $('filtroFechaFin')?.value    || ''
            };
        }

        function validarRango(desde, hasta) {
            if (desde && hasta && desde > hasta) {
                toast('La fecha inicial no puede ser mayor que la final.', 'error');
                return false;
            }
            return true;
        }

        function pintarRangoActivo(idSpan, desde, hasta) {
            const el = $(idSpan);
            if (!el) return;
            if (desde && hasta) {
                el.textContent = `· Rango: ${formatFecha(desde)} al ${formatFecha(hasta)}`;
            } else if (desde) {
                el.textContent = `· Desde ${formatFecha(desde)}`;
            } else if (hasta) {
                el.textContent = `· Hasta ${formatFecha(hasta)}`;
            } else {
                el.textContent = '';
            }
        }

        /* ========================================================
           ORDEN POR TOTAL DESPACHADO
           ======================================================== */
        function aplicarEstadoOrdenUI() {
            const btn = $('btnOrdenarTotal');
            if (!btn) return;
            btn.dataset.orden = ordenTotal;
            btn.title = ordenTotal === 'desc'
                ? 'Ordenado: mayor a menor (clic para invertir)'
                : 'Ordenado: menor a mayor (clic para invertir)';
        }

        $('btnOrdenarTotal')?.addEventListener('click', () => {
            ordenTotal = (ordenTotal === 'desc') ? 'asc' : 'desc';
            aplicarEstadoOrdenUI();
            dt?.changePage(1) || dt?.reload(true);
        });

        aplicarEstadoOrdenUI();

        /* ========================================================
           DATATABLE
           ======================================================== */
        if (typeof DataTable !== 'undefined' && $('tbodyHistorial')) {
            dt = new DataTable({
                tbodyId: 'tbodyHistorial',
                endpoint: API + '/listar',
                columns: ['ID','Artículo','Stock','Total Despachado','Primer Despacho','Último Despacho','Acciones'],
                renderRow: renderResumenRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationHistorial',
                totalId: 'totalHistorial',
                startId: 'startHistorial',
                endId: 'endHistorial',
                noDataMessage: 'No hay productos registrados',
                getExtraParams: () => {
                    const r = getRangoResumen();
                    return {
                        ...r,
                        orden: ordenTotal
                    };
                },
                onDataLoaded: () => {
                    bindTableActions();
                    const r = getRangoResumen();
                    pintarRangoActivo('rangoActivo', r.fechaInicio, r.fechaFin);
                }
            });
        }

        /* ========================================================
           EVENTOS FILTROS · RESUMEN
           ======================================================== */
        function aplicarFiltroResumen() {
            const r = getRangoResumen();
            if (!validarRango(r.fechaInicio, r.fechaFin)) return;
            dt?.changePage(1) || dt?.reload(true);
        }

        $('filtroFechaInicio')?.addEventListener('change', aplicarFiltroResumen);
        $('filtroFechaFin')?.addEventListener('change', aplicarFiltroResumen);

        $('btnLimpiarFechas')?.addEventListener('click', () => {
            if ($('filtroFechaInicio')) $('filtroFechaInicio').value = '';
            if ($('filtroFechaFin'))    $('filtroFechaFin').value    = '';
            pintarRangoActivo('rangoActivo', '', '');
            dt?.changePage(1) || dt?.reload(true);
        });

        /* ========================================================
           EVENTOS FILTROS · TIMELINE
           ======================================================== */
        $('btnTimelineFiltrar')?.addEventListener('click', () => {
            const desde = $('timelineFechaInicio')?.value || '';
            const hasta = $('timelineFechaFin')?.value    || '';
            if (!validarRango(desde, hasta)) return;
            timelineState.fechaInicio = desde;
            timelineState.fechaFin    = hasta;
            timelineState.page = 1;
            cargarTimeline();
        });

        $('btnTimelineLimpiar')?.addEventListener('click', () => {
            if ($('timelineFechaInicio')) $('timelineFechaInicio').value = '';
            if ($('timelineFechaFin'))    $('timelineFechaFin').value    = '';
            timelineState.fechaInicio = '';
            timelineState.fechaFin    = '';
            timelineState.page = 1;
            cargarTimeline();
        });

        /* ========================================================
           BOTONES
           ======================================================== */
        $('btnVolverResumen')?.addEventListener('click', volverResumen);
        $('btnGenerarPdfInventario')?.addEventListener('click', function (e) {
            e.preventDefault();
            generarPdfGlobal();
        });
        $('btnGenerarPdfProducto')?.addEventListener('click', function (e) {
            e.preventDefault();
            generarPdfProducto();
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('.historial-producto-link');
            if (link){
                e.preventDefault();
                abrirTimeline(parseInt(link.dataset.id));
            }
        });

        /* ========================================================
           AUTO-ABRIR TIMELINE SI VIENE CON #producto-{id}
           ======================================================== */
        function autoAbrirDesdeHash(){
            const hash = window.location.hash || '';
            const m = hash.match(/^#producto-(\d+)$/);
            if (m && m[1]) {
                setTimeout(() => abrirTimeline(parseInt(m[1])), 300);
            }
        }
        autoAbrirDesdeHash();

        window.addEventListener('hashchange', () => {
            const m = (window.location.hash || '').match(/^#producto-(\d+)$/);
            if (m && m[1]) abrirTimeline(parseInt(m[1]));
        });

        /* ========================================================
           RENDER RESUMEN
           ======================================================== */
        function renderResumenRow(p){
            const stock = Number(p.stock_actual || 0);
            const total = Number(p.total_despachado || 0);
            const present = p.present_producto || 'UND';

            let stockBadge;
            if (stock <= 0){
                stockBadge = `<span class="badge badge-danger">Sin stock</span>`;
            } else if (stock < 10){
                stockBadge = `<span class="badge badge-warning">${stock.toLocaleString('es-VE')} ${esc(present)}</span>`;
            } else {
                stockBadge = `<span class="badge badge-success">${stock.toLocaleString('es-VE')} ${esc(present)}</span>`;
            }

            const totalBadge = total > 0
                ? `<span class="badge badge-info">${total.toLocaleString('es-VE')}</span>`
                : `<span class="text-muted">—</span>`;

            return `
                <tr>
                    <td><strong>#${p.id_producto}</strong></td>
                    <td>
                        <a href="#" class="historial-producto-link"
                           data-id="${p.id_producto}"
                           style="color:#1d4ed8; font-weight:600; text-decoration:none;">
                            ${esc(p.producto)}
                        </a>
                        <div style="font-size:.7rem; color:#94a3b8; margin-top:.15rem;">
                            ${esc(p.enlace_producto || '')} · ${esc(p.ubicacion || '')}
                        </div>
                    </td>
                    <td class="text-right">${stockBadge}</td>
                    <td class="text-right">${totalBadge}</td>
                    <td>${p.primer_despacho ? formatFecha(p.primer_despacho) : '<span class="text-muted">—</span>'}</td>
                    <td>${p.ultimo_despacho ? formatFecha(p.ultimo_despacho) : '<span class="text-muted">—</span>'}</td>
                    <td class="text-right">
                        <button class="action-btn edit" data-action="ver" data-id="${p.id_producto}" title="Ver historial">
                            <i data-lucide="history" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>`;
        }

        function bindTableActions(){
            const tbody = $('tbodyHistorial');
            if (!tbody || tbody._bound) return;
            tbody._bound = true;

            tbody.addEventListener('click', e => {
                const btn = e.target.closest('[data-action]');
                if (!btn) return;
                if (btn.dataset.action === 'ver'){
                    abrirTimeline(parseInt(btn.dataset.id));
                }
            });
        }

        function mostrarResumen(){
            if ($('vistaResumen'))    $('vistaResumen').style.display = '';
            if ($('vistaTimeline'))   $('vistaTimeline').style.display = 'none';
        }

        function mostrarTimeline(){
            if ($('vistaResumen'))    $('vistaResumen').style.display = 'none';
            if ($('vistaTimeline'))   $('vistaTimeline').style.display = '';
        }

        function volverResumen(){
            mostrarResumen();
            timelineState.productId = null;
            timelineState.producto = null;
            timelineState.fechaInicio = '';
            timelineState.fechaFin = '';
            if ($('timelineFechaInicio')) $('timelineFechaInicio').value = '';
            if ($('timelineFechaFin'))    $('timelineFechaFin').value    = '';
            pintarRangoActivo('timelineRangoActivo', '', '');
            if ($('timelineContainer')) $('timelineContainer').innerHTML = '';
            if (window.location.hash) {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            }
        }

        /* ========================================================
           TIMELINE
           ======================================================== */
        async function abrirTimeline(idProducto){
            timelineState.productId = idProducto;
            timelineState.page = 1;
            timelineState.fechaInicio = '';
            timelineState.fechaFin = '';
            if ($('timelineFechaInicio')) $('timelineFechaInicio').value = '';
            if ($('timelineFechaFin'))    $('timelineFechaFin').value    = '';

            mostrarTimeline();
            $('timelineTitle').textContent = 'Cargando...';
            $('timelineProductoInfo').innerHTML = '';
            $('timelineContainer').innerHTML = `
                <div class="historial-loading">
                    <div class="dt-loading">Cargando historial...</div>
                </div>`;
            $('timelinePaginationWrap').style.display = 'none';
            pintarRangoActivo('timelineRangoActivo', '', '');

            window.scrollTo({ top: 0, behavior: 'smooth' });

            await cargarTimeline();
        }

        async function cargarTimeline(){
            const payload = {
                limit:  timelineState.limit,
                offset: (timelineState.page - 1) * timelineState.limit,
                fechaInicio: timelineState.fechaInicio || '',
                fechaFin:    timelineState.fechaFin    || ''
            };

            try{
                const d = await fetchJSON(`${API}/detalle/${timelineState.productId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!d.ok) throw new Error(d.message);

                timelineState.producto = d.producto;
                const items = d.data || [];
                const total = d.total || 0;
                timelineState.totalPages = Math.max(1, Math.ceil(total / timelineState.limit));

                $('timelineTitle').textContent = d.producto.producto;

                $('timelineProductoInfo').innerHTML = `
                    <div class="historial-producto-item">
                        <span class="historial-producto-label">Stock actual</span>
                        <strong>${Number(d.producto.stock_actual).toLocaleString('es-VE')} ${esc(d.producto.present_producto || '')}</strong>
                    </div>
                    <div class="historial-producto-item">
                        <span class="historial-producto-label">Total despachado</span>
                        <strong style="color:#1d4ed8;">${Number(d.producto.total_despachado).toLocaleString('es-VE')}</strong>
                    </div>
                    <div class="historial-producto-item">
                        <span class="historial-producto-label">Total despachos</span>
                        <strong>${total}</strong>
                    </div>
                    <div class="historial-producto-item">
                        <span class="historial-producto-label">Presentación</span>
                        <strong>${esc(d.producto.present_producto || '—')}</strong>
                    </div>
                `;

                pintarRangoActivo('timelineRangoActivo', timelineState.fechaInicio, timelineState.fechaFin);

                if (!items.length){
                    $('timelineContainer').innerHTML = `
                        <div class="historial-empty">
                            ${iconSvg('inbox', 'w-16 h-16 mx-auto', '#cbd5e1')}
                            <h4>Sin despachos registrados</h4>
                            <p>${(timelineState.fechaInicio || timelineState.fechaFin)
                                ? 'No hay despachos en el rango de fechas seleccionado.'
                                : 'Este producto aún no ha sido despachado.'}</p>
                        </div>`;
                    $('timelinePaginationWrap').style.display = 'none';
                    if (window.lucide) lucide.createIcons();
                    return;
                }

                $('timelineContainer').innerHTML = `<div class="timeline">${items.map(renderEvento).join('')}</div>`;

                const start = (timelineState.page - 1) * timelineState.limit + 1;
                const end   = Math.min(timelineState.page * timelineState.limit, total);
                $('startTimeline').textContent = start;
                $('endTimeline').textContent   = end;
                $('totalTimeline').textContent = total;
                $('timelinePaginationWrap').style.display = '';
                renderPaginacionTimeline(timelineState.page, timelineState.totalPages);

                if (window.lucide) lucide.createIcons();

            } catch (e){
                console.error(e);
                $('timelineContainer').innerHTML = `
                    <div class="historial-empty">
                        <p style="color:#dc2626;">Error: ${esc(e.message)}</p>
                    </div>`;
            }
        }

        function renderEvento(ev){
            const fecha = formatFecha(ev.fecha_despacho);
            const unidadTxt = ev.id_unidad || '—';
            const modelo = [ev.marca_unidad, ev.modelo_unidad].filter(Boolean).join(' ');
            const cant = Number(ev.cant_despacho || 0);

            return `
                <div class="timeline-item">
                    <div class="timeline-dot dot-blue">
                        ${iconSvg('truck', 'w-4 h-4')}
                    </div>
                    <div class="timeline-card">
                        <div class="timeline-card-head">
                            <div>
                                <span class="timeline-tag tag-blue">Despacho #${ev.numero_orden || ev.id_despacho}</span>
                                <h4 class="timeline-title">${esc(unidadTxt)}${modelo ? ' · ' + esc(modelo) : ''}</h4>
                            </div>
                            <span class="timeline-date">
                                ${iconSvg('calendar', 'w-3.5 h-3.5')}
                                ${esc(fecha)}
                            </span>
                        </div>

                        <div class="timeline-body">
                            <p style="font-size:1.05rem; margin:.25rem 0;">
                                <strong style="color:#1d4ed8;">${cant.toLocaleString('es-VE')}</strong>
                                <span style="color:#64748b; font-size:.85rem;">unidades despachadas</span>
                            </p>
                            ${ev.observacion ? `<p class="timeline-note">${esc(ev.observacion)}</p>` : ''}
                            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem; margin-top:.5rem; font-size:.75rem; color:#64748b;">
                                <div><strong>Operador:</strong> ${esc(ev.operador_nombre || '—')}</div>
                                <div><strong>Mecánico:</strong> ${esc(ev.mecanico_nombre || '—')}</div>
                                <div><strong>Despachador:</strong> ${esc(ev.despachador_nombre || '—')}</div>
                            </div>
                        </div>
                    </div>
                </div>`;
        }

        /* ========================================================
           PDF GLOBAL (tabla de historial completa)
           ======================================================== */
        async function generarPdfGlobal(){
            const btn = $('btnGenerarPdfInventario');
            if (btn) btn.disabled = true;

            try {
                const q = $('filtroBuscar')?.value || '';
                const r = getRangoResumen();

                let allItems = [];
                let offset = 0;
                const limit = 100;
                let total = null;
                let intentos = 0;
                const maxIntentos = 50;

                while (intentos < maxIntentos) {
                    intentos++;

                    const params = new URLSearchParams({
                        q,
                        limit,
                        offset,
                        fechaInicio: r.fechaInicio,
                        fechaFin:    r.fechaFin,
                        orden:       ordenTotal
                    });

                    const resp = await fetch(`${API}/listar?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const ct = resp.headers.get('content-type') || '';
                    if (!ct.includes('application/json')) {
                        toast('Error: el servidor no devolvió JSON.', 'error');
                        return;
                    }

                    const d = await resp.json();
                    if (!d.ok) {
                        toast(d.message || 'Error al cargar los productos.', 'error');
                        return;
                    }

                    let batch = d.data;
                    if (batch && !Array.isArray(batch) && typeof batch === 'object') {
                        const aplanado = [];
                        Object.values(batch).forEach(sub => {
                            if (Array.isArray(sub)) aplanado.push(...sub);
                        });
                        batch = aplanado;
                    }
                    if (!Array.isArray(batch)) batch = [];

                    if (total === null) {
                        total = d.totalFiltrados ?? d.total ?? batch.length;
                    }

                    allItems = allItems.concat(batch);
                    offset += limit;

                    if (batch.length < limit || offset >= total) break;
                }

                if (!allItems.length) {
                    toast('No hay productos para generar el PDF.', 'error');
                    return;
                }

                const productos = allItems
                    .filter(p => p && typeof p === 'object')
                    .map(p => ({
                        id_producto:      p.id_producto ?? '',
                        producto:         p.producto ?? '',
                        present_producto: p.present_producto ?? '',
                        stock_actual:     p.stock_actual ?? p.cant_producto ?? 0,
                        total_despachado: p.total_despachado ?? 0,
                        primer_despacho:  p.primer_despacho ?? '',
                        ultimo_despacho:  p.ultimo_despacho ?? '',
                    }));

                if (!productos.length) {
                    toast('Los productos no tienen el formato esperado.', 'error');
                    return;
                }

                await SITGO_PDF.generar(
                    'productos_historial',
                    {
                        title: 'Historial de Productos',
                        mode: 'resumen',
                        data: productos,
                        fechaInicio: r.fechaInicio,
                        fechaFin: r.fechaFin,
                    },
                    `historial_productos_${new Date().toISOString().slice(0,10)}.pdf`,
                    {
                        titulo_documento: 'HISTORIAL DE PRODUCTOS',
                        doc_color:        '#1e40af',
                    }
                );

            } catch (e) {
                console.error(e);
                toast('Error de conexión al generar el PDF.', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        /* ========================================================
           PDF INDIVIDUAL (timeline de un producto)
           ======================================================== */
        async function generarPdfProducto(){
            if (!timelineState.productId) {
                toast('Selecciona un producto primero.', 'error');
                return;
            }

            const btn = $('btnGenerarPdfProducto');
            if (btn) btn.disabled = true;

            try {
                const payload = {
                    limit: 9999,
                    offset: 0,
                    fechaInicio: timelineState.fechaInicio || '',
                    fechaFin:    timelineState.fechaFin    || ''
                };
                const d = await fetchJSON(`${API}/detalle/${timelineState.productId}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!d.ok) {
                    toast(d.message || 'Error al cargar los despachos.', 'error');
                    return;
                }

                const items = d.data || [];
                if (!items.length) {
                    toast('No hay despachos para generar el PDF.', 'error');
                    return;
                }

                await SITGO_PDF.generar(
                    'productos_historial',
                    {
                        title: `Historial de ${timelineState.producto?.producto || 'Producto'}`,
                        mode: 'detalle',
                        producto: {
                            id_producto:      timelineState.productId,
                            producto:         timelineState.producto?.producto || '—',
                            present_producto: timelineState.producto?.present_producto || '—',
                            stock_actual:     timelineState.producto?.stock_actual || 0,
                            total_despachado: timelineState.producto?.total_despachado || 0,
                        },
                        data: items,
                        fechaInicio: timelineState.fechaInicio || '',
                        fechaFin:    timelineState.fechaFin    || '',
                    },
                    `historial_${(timelineState.producto?.producto || 'producto').replace(/\s+/g, '_')}_${new Date().toISOString().slice(0,10)}.pdf`,
                    {
                        titulo_documento: 'HISTORIAL DE PRODUCTO',
                        doc_numero:       `#${String(timelineState.productId).padStart(4, '0')}`,
                        doc_color:        '#1e40af',
                    }
                );
            } catch (e) {
                console.error(e);
                toast('Error de conexión al generar el PDF.', 'error');
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        /* ========================================================
           PAGINACIÓN TIMELINE
           ======================================================== */
        function renderPaginacionTimeline(current, total){
            const cont = $('paginationTimeline');
            let html = '';

            const btn = (page, icon, disabled, extra = '') => `
                <button class="dt-page-btn ${extra}" data-page="${page}" ${disabled ? 'disabled' : ''}>
                    ${icon ? `<i data-lucide="${icon}" class="w-4 h-4"></i>` : page}
                </button>`;

            html += btn(1, 'chevrons-left', current === 1);
            html += btn(current - 1, 'chevron-left', current === 1);

            getPageNumbers(current, total).forEach(n => {
                if (n === '...'){
                    html += `<span class="dt-page-dots">…</span>`;
                } else {
                    html += btn(n, null, false, n === current ? 'active' : '');
                }
            });

            html += btn(current + 1, 'chevron-right', current === total);
            html += btn(total, 'chevrons-right', current === total);

            cont.innerHTML = html;

            cont.querySelectorAll('.dt-page-btn').forEach(b => {
                b.addEventListener('click', () => {
                    if (b.disabled) return;
                    const p = parseInt(b.dataset.page);
                    if (p >= 1 && p <= timelineState.totalPages){
                        timelineState.page = p;
                        cargarTimeline();
                        $('timelineWrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });

            if (window.lucide) lucide.createIcons();
        }

        function getPageNumbers(current, total){
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
            if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
            return [1, '...', current - 1, current, current + 1, '...', total];
        }

        /* ========================================================
           HELPERS
           ======================================================== */
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
                return `${parts[2]}/${parts[1]}/${parts[0]}`;
            } catch { return f; }
        }

        function iconSvg(name, cls = '', color = ''){
            const style = color ? `style="color:${color};"` : '';
            return `<i data-lucide="${esc(name)}" class="${cls}" ${style}></i>`;
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
})();