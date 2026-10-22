document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'dashboard';
    const API_USERS = (window.SITGO_BASE || '/') + 'usuarios';
    const $ = id => document.getElementById(id);

    let institucionActual = (window.SITGO_INST || 1);
    let dailyChart = null;
    let monthlyChart = null;

    /* Estado flota */
    let flotaStatusActual = null;
    let flotaUnidadesAll = [];
    let sessionsIntervalId = null;

    /* ============================================================
       HELPERS
       ============================================================ */
    async function fetchJSON(url, opts = {}) {
        try {
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return { success: false, message: `Error ${r.status}` };
            }
            return await r.json();
        } catch {
            return { success: false, message: 'Error de conexión' };
        }
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try { window.lucide.createIcons(); } catch(e) { console.error(e); }
        }
    }

    function setText(id, v) {
        const el = $(id);
        if (el) el.textContent = v;
    }

    function fmtNum(v, dec = 2) {
        const n = parseFloat(v);
        if (!isFinite(n)) return (0).toFixed(dec).replace('.', ',');
        return n.toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }

    function fmtFecha(f) {
        if (!f) return '—';
        try {
            const d = new Date(f.replace(' ', 'T'));
            if (!isNaN(d.getTime())) return d.toLocaleDateString('es-VE');
            return f;
        } catch { return f; }
    }

    function getEstadoOrdenBadge(estado) {
        switch (parseInt(estado)) {
            case 1: return '<span class="badge badge-secondary">Requisición</span>';
            case 2: return '<span class="badge badge-warning">Aprobada</span>';
            case 3: return '<span class="badge badge-success">Despachada</span>';
            case 4: return '<span class="badge badge-danger">Rechazada</span>';
            default: return '<span class="badge badge-info">N/A</span>';
        }
    }

    function toast(msg, type = 'success') {
        let c = document.querySelector('.toast-container');
        if (!c) { c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); }
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        await setupInstitucionSelector();

        if ($('sec-flota'))     await loadFlota();
        if ($('sec-almacen'))   await loadAlmacen();
        if ($('sec-compras'))   await loadCompras();
        if ($('sec-bienes'))    await loadBienes();
        if ($('sec-estacion'))  { await loadEstacion(); await populateMonthSelects(); await loadDailySales(); }
        if ($('sec-sesiones'))  {
            await loadActiveSessions();
            // Auto-refresh cada 30s
            sessionsIntervalId = setInterval(loadActiveSessions, 30000);
        }

        bindEvents();
        refreshIcons();
    })();

    function bindEvents() {
        $('dash-institucion')?.addEventListener('change', async e => {
            institucionActual = parseInt(e.target.value, 10);
            sessionStorage.setItem('dash_institucion', institucionActual);

            await Promise.all([
                $('sec-flota')    ? loadFlota()    : null,
                $('sec-almacen')  ? loadAlmacen()  : null,
                $('sec-compras')  ? loadCompras()  : null,
                $('sec-bienes')   ? loadBienes()   : null,
            ]);
        });

        $('btnRefreshSessions')?.addEventListener('click', loadActiveSessions);

        /* Tarjetas de flota clickeables */
        document.querySelectorAll('#flota-stats [data-flota-status]').forEach(card => {
            card.addEventListener('click', () => {
                const status = parseInt(card.dataset.flotaStatus, 10);
                abrirEstadoFlota(status);
            });
        });

        $('btn-volver-resumen')?.addEventListener('click', volverResumenFlota);
        $('flota-estado-search')?.addEventListener('input', renderFlotaEstadoTabla);
    }

    function qInst() { return '?id_institucion=' + institucionActual; }

    /* ============================================================
       SELECTOR DE INSTITUCIÓN
       ============================================================ */
    async function setupInstitucionSelector() {
        const sel = $('dash-institucion');
        if (!sel) return;

        const guardada = parseInt(sessionStorage.getItem('dash_institucion') || '0', 10);
        if (guardada > 0) institucionActual = guardada;

        const d = await fetchJSON(API + '/instituciones');
        if (!d.success || !d.data || !d.data.length) return;

        sel.innerHTML = d.data.map(i =>
            `<option value="${i.id_institucion}">${i.nombre}</option>`
        ).join('');

        if (!d.data.find(x => parseInt(x.id_institucion) === institucionActual)) {
            institucionActual = parseInt(d.data[0].id_institucion, 10);
        }
        sel.value = institucionActual;
        sessionStorage.setItem('dash_institucion', institucionActual);
    }

    /* ============================================================
       FLOTA · Resumen general
       ============================================================ */
    async function loadFlota() {
        const d = await fetchJSON(API + '/operaciones' + qInst());
        if (!d.success) return;

        const o = d.data || {};
        setText('flota-operativas',    o.status?.operativas ?? 0);
        setText('flota-inoperativas',  o.status?.inoperativas ?? 0);
        setText('flota-mantenimiento', o.status?.mantenimiento ?? 0);
        setText('flota-criticas',      o.status?.criticas ?? 0);

        setText('aceite-ok',        o.aceite_status?.ok ?? 0);
        setText('aceite-proximo',   o.aceite_status?.proximo ?? 0);
        setText('aceite-requerido', o.aceite_status?.requerido ?? 0);

        const tbody = $('tabla-resumen-flota');
        if (!tbody) return;

        if (!o.grouped || !o.grouped.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dt-empty">Sin datos</td></tr>';
        } else {
            tbody.innerHTML = o.grouped.map(g => `
                <tr>
                    <td>${g.marca_unidad} ${g.modelo_unidad}</td>
                    <td>${g.transmision || '—'}</td>
                    <td>${g.tipo_combustible || '—'}</td>
                    <td class="text-center">${g.total}</td>
                    <td class="text-center" style="color:#16a34a; font-weight:600;">${g.operativas}</td>
                    <td class="text-center" style="color:#dc2626; font-weight:600;">${g.inoperativas}</td>
                </tr>
            `).join('');
        }
    }

    /* ============================================================
       FLOTA · Unidades por estado (click en tarjetas)
       ============================================================ */
    async function abrirEstadoFlota(status) {
        flotaStatusActual = status;

        // Marcar tarjeta activa
        document.querySelectorAll('#flota-stats [data-flota-status]').forEach(c => {
            c.classList.remove('active');
            c.style.boxShadow = '';
        });
        const card = document.querySelector(`#flota-stats [data-flota-status="${status}"]`);
        if (card) {
            card.classList.add('active');
            card.style.boxShadow = '0 0 0 3px rgba(37,99,235,.25)';
        }

        // Cambiar paneles
        const pResumen = $('flota-panel-resumen');
        const pEstado  = $('flota-panel-estado');
        if (pResumen) pResumen.style.display = 'none';
        if (pEstado)  pEstado.style.display  = 'block';

        const nombreStatus = { 1:'Operativas', 2:'Inoperativas', 3:'En Mantenimiento', 4:'Críticas' };
        setText('flota-estado-nombre', nombreStatus[status] || '—');
        setText('flota-estado-count', 0);

        // Cargar unidades
        const body = $('flota-estado-body');
        if (body) body.innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/operaciones' + qInst());
        if (!d.success) {
            if (body) body.innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        // Reutilizamos getOperacionesData pero NO devuelve unidades individuales.
        // Hacemos una llamada extra al endpoint de unidades por estado (el mismo de /movimientos público).
        const r = await fetchJSON((window.SITGO_BASE || '/') + 'movimientos/unidades-por-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status, id_institucion: institucionActual })
        });

        if (!r.success || !r.data) {
            if (body) body.innerHTML = '<tr><td colspan="7" class="dt-empty">Sin unidades en este estado</td></tr>';
            setText('flota-estado-count', 0);
            return;
        }

        flotaUnidadesAll = r.data;
        setText('flota-estado-count', flotaUnidadesAll.length);
        renderFlotaEstadoTabla();
    }

    function renderFlotaEstadoTabla() {
        const body = $('flota-estado-body');
        if (!body) return;

        const q = ($('flota-estado-search')?.value || '').toLowerCase().trim();
        const items = flotaUnidadesAll.filter(u => {
            if (!q) return true;
            return `${u.id_unidad||''} ${u.marca_unidad||''} ${u.modelo_unidad||''} ${u.vim_unidad||''}`.toLowerCase().includes(q);
        });

        if (!items.length) {
            body.innerHTML = '<tr><td colspan="7" class="dt-empty">Sin unidades</td></tr>';
            return;
        }

        // Base para armar la URL del historial respetando BASE_URL
        const base = (window.SITGO_BASE || '/');

        body.innerHTML = items.map(u => {
            // URL del historial de la unidad (abre en nueva pestaña)
            const urlHistorial = base + 'flota/historial/' + u.id_flota;

            return `
                <tr>
                    <td>
                        <a href="${urlHistorial}"
                           class="unidad-link"
                           title="Ver historial de la unidad"
                           target="_blank" rel="noopener">
                            <strong>${u.id_unidad || '—'}</strong>
                        </a>
                    </td>
                    <td>${(u.marca_unidad || '') + ' ' + (u.modelo_unidad || '')}</td>
                    <td><code style="font-size:.72rem;">${u.vim_unidad || '—'}</code></td>
                    <td>${u.transmision || '—'}</td>
                    <td>${u.tipo_combustible || '—'}</td>
                    <td class="text-right">${fmtNum(u.km_actual, 0)}</td>
                    <td class="text-right">${fmtNum(u.ultimo_cambio_aceite, 0)}</td>
                </tr>
            `;
        }).join('');
    }

    function volverResumenFlota() {
        flotaStatusActual = null;
        flotaUnidadesAll = [];
        if ($('flota-panel-resumen')) $('flota-panel-resumen').style.display = '';
        if ($('flota-panel-estado'))  $('flota-panel-estado').style.display  = 'none';
        if ($('flota-estado-search')) $('flota-estado-search').value = '';

        document.querySelectorAll('#flota-stats [data-flota-status]').forEach(c => {
            c.classList.remove('active');
            c.style.boxShadow = '';
        });
    }

    /* ============================================================
       ALMACÉN
       ============================================================ */
    async function loadAlmacen() {
        const d = await fetchJSON(API + '/almacen' + qInst());
        if (!d.success) return;
        const a = d.data || {};

        setText('almacen-productos-sin-stock',  a.productos_sin_stock ?? 0);
        setText('almacen-productos-stock-bajo', a.productos_stock_bajo ?? 0);
        setText('almacen-total-productos',      a.total_productos ?? 0);
        setText('almacen-ordenes-pendientes',   a.orders_aprobadas?.total_aprobadas ?? 0);
        setText('almacen-ordenes-despacho',     a.orders_despachadas?.total_despachadas ?? 0);

        setText('almacen-lubricantes-actual',   fmtNum(a.consumibles?.mes_actual ?? 0) + ' Lts');
        setText('almacen-lubricantes-anterior', fmtNum(a.consumibles?.mes_anterior ?? 0) + ' Lts');

        await Promise.all([ loadTopProductosAlmacen(), loadUltimasOrdenesAlmacen() ]);
    }

    async function loadTopProductosAlmacen() {
        const tbody = $('tabla-top-productos');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="5" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/almacen/top-productos' + qInst());
        if (!d.success || !d.data || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="dt-empty">Sin productos despachados este mes</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map((p, i) => `
            <tr>
                <td class="text-center">${i + 1}</td>
                <td>${p.producto || '—'}</td>
                <td>${p.enlace_producto || '—'}</td>
                <td>${p.ubicacion || '—'}</td>
                <td class="text-right" style="color:#1e40af; font-weight:700;">${fmtNum(p.total_despachado, 2)} ${p.present_producto || ''}</td>
            </tr>
        `).join('');
    }

    async function loadUltimasOrdenesAlmacen() {
        const tbody = $('tabla-ultimas-ordenes');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/almacen/ultimas-ordenes' + qInst());
        if (!d.success || !d.data || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dt-empty">Sin órdenes registradas</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map(o => `
            <tr>
                <td><strong>#${o.numero_orden || o.id_despacho}</strong></td>
                <td>${fmtFecha(o.fecha_despacho)}</td>
                <td>${o.id_unidad || '—'} ${o.modelo_unidad ? '· ' + o.modelo_unidad : ''}</td>
                <td>${o.operador_nombre || '—'}</td>
                <td class="text-center"><span class="badge badge-info">${o.total_articulos} art.</span></td>
                <td class="text-center">${getEstadoOrdenBadge(o.estado_orden)}</td>
            </tr>
        `).join('');
    }

    /* ============================================================
       COMPRAS
       ============================================================ */
    async function loadCompras() {
        const d = await fetchJSON(API + '/compras' + qInst());
        if (!d.success) return;
        const c = d.data || {};

        setText('compras-requisiciones-pendientes', c.requisiciones_pendientes ?? 0);
        setText('compras-ordenes-aprobadas',         c.ordenes_aprobadas ?? 0);
        setText('compras-articulos-sin-stock',       c.articulos_sin_stock ?? 0);
        setText('compras-articulos-stock-bajo',      c.articulos_stock_bajo ?? 0);
        setText('compras-ordenes-despachadas-mes',   c.ordenes_despachadas_mes ?? 0);
        setText('compras-ordenes-costeadas-mes',     c.ordenes_costeadas_mes ?? 0);
        setText('compras-total-divisa-mes',          '$' + fmtNum(c.total_divisa_mes));
        setText('compras-total-bs-mes',              'Bs. ' + fmtNum(c.total_bs_mes));

        await Promise.all([
            loadTopProductosCosteados(),
            loadUltimasRequisiciones(),
            loadUltimasComprasCosteadas(),
        ]);
    }

    async function loadTopProductosCosteados() {
        const tbody = $('tabla-top-productos-costeados');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/compras/top-productos' + qInst());
        if (!d.success || !d.data || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="dt-empty">Sin productos costeados este mes</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map((p, i) => `
            <tr>
                <td class="text-center">${i + 1}</td>
                <td>${p.producto || '—'}</td>
                <td>${p.proveedor || '—'}</td>
                <td>${p.enlace_producto || '—'}</td>
                <td class="text-center"><span class="badge badge-info">${p.veces_costeadas}</span></td>
                <td class="text-right" style="color:#1e40af; font-weight:700;">$${fmtNum(p.total_divisa)}</td>
                <td class="text-right" style="color:#166534; font-weight:700;">Bs. ${fmtNum(p.total_bs)}</td>
            </tr>
        `).join('');
    }

    async function loadUltimasRequisiciones() {
        const tbody = $('tabla-ultimas-requisiciones');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/compras/ultimas-requisiciones' + qInst());
        if (!d.success || !d.data || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dt-empty">Sin requisiciones</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map(o => `
            <tr>
                <td><strong>#${o.numero_orden || o.id_despacho}</strong></td>
                <td>${fmtFecha(o.fecha_despacho)}</td>
                <td>${o.id_unidad || '—'} ${o.modelo_unidad ? '· ' + o.modelo_unidad : ''}</td>
                <td>${o.solicitante || '—'}</td>
                <td class="text-center"><span class="badge badge-info">${o.total_articulos} art.</span></td>
                <td class="text-center">${getEstadoOrdenBadge(o.estado_orden)}</td>
            </tr>
        `).join('');
    }

    async function loadUltimasComprasCosteadas() {
        const tbody = $('tabla-ultimas-compras-costeadas');
        if (!tbody) return;
        tbody.innerHTML = '<tr><td colspan="6" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/compras/ultimas-costeadas' + qInst());
        if (!d.success || !d.data || !d.data.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="dt-empty">Sin compras costeadas</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.map(o => `
            <tr>
                <td><strong>#${o.numero_orden || o.id_despacho}</strong></td>
                <td>${fmtFecha(o.fecha_despacho)}</td>
                <td>${o.id_unidad || '—'} ${o.modelo_unidad ? '· ' + o.modelo_unidad : ''}</td>
                <td class="text-center"><span class="badge badge-info">${o.articulos_costeados} art.</span></td>
                <td class="text-right" style="color:#1e40af; font-weight:700;">$${fmtNum(o.total_divisa)}</td>
                <td class="text-right" style="color:#166534; font-weight:700;">Bs. ${fmtNum(o.total_bs)}</td>
            </tr>
        `).join('');
    }

    /* ============================================================
       BIENES
       ============================================================ */
    async function loadBienes() {
        const d = await fetchJSON(API + '/bienes' + qInst());
        if (!d.success) return;
        const { summary, recent } = d.data || {};

        setText('bienes-total',       summary?.total_bienes ?? 0);
        setText('bienes-activos',     summary?.total_activos ?? 0);
        setText('bienes-reparacion',  summary?.total_reparacion ?? 0);
        setText('bienes-baja',        summary?.total_baja ?? 0);

        const tbody = $('tabla-bienes-recientes');
        if (!tbody) return;
        if (!recent || !recent.length) {
            tbody.innerHTML = '<tr><td colspan="3" class="dt-empty">Sin bienes recientes</td></tr>';
            return;
        }
        tbody.innerHTML = recent.map(b => `
            <tr>
                <td>${b.descripcion_bien || '—'}</td>
                <td>${b.departamento_bien || '—'}</td>
                <td class="text-center">${b.fecha_adquisicion ? fmtFecha(b.fecha_adquisicion) : '—'}</td>
            </tr>
        `).join('');
    }

    /* ============================================================
       ESTACIÓN
       ============================================================ */
    async function loadEstacion() {
        const d = await fetchJSON(API + '/estacion');
        if (!d.success) return;
        const e = d.data || {};
        setText('totalVentasHoy', e.total_ventas ?? 0);
        setText('totalLitrosHoy', fmtNum(e.total_litros) + ' Lts');
        setText('user_activo',    e.user_activo ?? 0);
    }

    async function loadDailySales() {
        const d = await fetchJSON(API + '/estacion/ventas-dia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha: new Date().toISOString().slice(0,10) })
        });
        if (!d.success) return;

        const items = d.data?.sales_by_user || [];
        const canvas = $('dailySalesChart');
        if (!canvas) return;

        if (dailyChart) { dailyChart.destroy(); dailyChart = null; }
        if (!items.length) return;

        dailyChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: items.map(x => x.usuario || '—'),
                datasets: [{
                    label: 'Litros vendidos',
                    data: items.map(x => parseFloat(x.total_litros || 0)),
                    backgroundColor: 'rgba(37,99,235,.7)',
                    borderColor: '#2563eb',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => v + ' L' } } }
            }
        });
    }

    async function populateMonthSelects() {
        const startSel = $('startMonth');
        const endSel = $('endMonth');
        const btn = $('generateChartBtn');
        if (!startSel || !endSel || !btn) return;

        const d = await fetchJSON(API + '/estacion/meses');
        if (!d.success || !d.data || !d.data.length) return;

        startSel.innerHTML = d.data.map(x => `<option value="${x.mes}">${x.mes}</option>`).join('');
        endSel.innerHTML   = d.data.map(x => `<option value="${x.mes}">${x.mes}</option>`).join('');
        endSel.value = d.data[0].mes;

        btn.addEventListener('click', generateMonthlyChart);
    }

    async function generateMonthlyChart() {
        const start = $('startMonth')?.value;
        const end   = $('endMonth')?.value;
        const wrap  = $('monthlyChartWrap');
        const noData = $('noLitersDataMessage');
        const canvas = $('monthlyLitersChart');

        if (!start || !end) return;
        if (start > end) { alert('La fecha inicial no puede ser mayor que la final.'); return; }

        if (monthlyChart) { monthlyChart.destroy(); monthlyChart = null; }

        const d = await fetchJSON(API + '/estacion/litros-mensuales', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ start_month: start, end_month: end })
        });

        if (!d.success || !d.data || !d.data.length) {
            if (wrap) wrap.style.display = 'none';
            if (noData) { noData.style.display = 'block'; noData.textContent = 'Sin datos para el rango.'; }
            return;
        }

        if (noData) noData.style.display = 'none';
        if (wrap)  wrap.style.display = 'block';

        const labels = d.data.map(x => x.mes_venta);
        const data   = d.data.map(x => parseFloat(x.total_litros || 0));
        const total  = data.reduce((s, v) => s + v, 0);

        monthlyChart = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels.map((l, i) => `${l} · ${fmtNum(data[i])} L`),
                datasets: [{
                    data,
                    backgroundColor: ['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#06B6D4','#F97316'],
                    borderColor: '#fff',
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    title: { display: true, text: `Total: ${fmtNum(total)} L` },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.label} (${((ctx.raw / total) * 100).toFixed(1)}%)`
                        }
                    }
                }
            }
        });
    }

    /* ============================================================
       SESIONES ACTIVAS
       ============================================================ */
    let sessionsHash = '';        // hash de la última data renderizada
    let sessionsFirstLoad = true; // solo el primer load muestra "Cargando..."

    async function loadActiveSessions() {
        const list = $('active-users-list');
        const noMsg = $('no-active-users');
        if (!list) return;

        // Solo mostrar "Cargando..." la primera vez
        if (sessionsFirstLoad) {
            list.innerHTML = '<div class="text-muted" style="padding:1rem; text-align:center; grid-column:1/-1;">Cargando...</div>';
        }

        const d = await fetchJSON(API_USERS + '/sesiones-activas');

        // ¿Sin sesiones?
        if (!d.success || !d.data || !d.data.length) {
            const hash = 'empty';
            // Si ya estaba vacío, no re-renderizar
            if (!sessionsFirstLoad && sessionsHash === hash) return;

            sessionsHash = hash;
            sessionsFirstLoad = false;
            list.innerHTML = '';
            if (noMsg) noMsg.style.display = 'block';
            setText('sessions-count', 0);
            return;
        }

        // Construir un hash ligero con los campos que importan
        // (session_id + status + nick). Si el hash no cambió, no se re-renderiza.
        const hash = d.data.map(s =>
            `${s.session_id}|${s.usuario_status}|${s.usuario_nick}`
        ).join('##');

        if (!sessionsFirstLoad && hash === sessionsHash) {
            // Nada cambió → refresco silencioso, no toca el DOM
            return;
        }

        sessionsHash = hash;
        sessionsFirstLoad = false;

        if (noMsg) noMsg.style.display = 'none';
        setText('sessions-count', d.data.length);

        list.innerHTML = d.data.map(s => {
            const estado = s.usuario_status == 1
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>';

            // Iniciales para fallback
            const iniciales = (s.usuario_nick || '?')
                .substring(0, 2)
                .toUpperCase();

            // Avatar: imagen si existe, si no → div con iniciales
            const avatarHtml = s.imagen_url
                ? `<img src="${s.imagen_url}" alt="${s.usuario_nick}"
                        class="session-avatar"
                        onerror="this.outerHTML='<div class=\\'session-avatar session-avatar-fallback\\'>${iniciales}</div>'">`
                : `<div class="session-avatar session-avatar-fallback">${iniciales}</div>`;

            return `
                <div style="padding:.85rem; border:1px solid #e2e8f0; border-radius:.55rem; background:#fff; display:flex; flex-direction:column; gap:.55rem;">
                    <div style="display:flex; align-items:center; gap:.6rem;">
                        ${avatarHtml}
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; color:#0f172a; font-size:.85rem;">${s.usuario_nick}</div>
                            <div style="font-size:.72rem; color:#64748b;">
                                ${(s.nombres || '') + ' ' + (s.apellidos || '')}
                            </div>
                        </div>
                        ${estado}
                    </div>
                    <div style="font-size:.7rem; color:#64748b; display:grid; gap:.15rem;">
                        <div><i data-lucide="network" style="width:11px; height:11px; display:inline;"></i> IP: ${s.ip_address || '—'}</div>
                        <div><i data-lucide="clock" style="width:11px; height:11px; display:inline;"></i> Inicio: ${s.created_at || '—'}</div>
                    </div>
                    <div style="display:flex; gap:.35rem; margin-top:.25rem;">
                        <button class="btn btn-ghost btn-sm" data-force-logout="${s.usuario_nick}" style="flex:1; justify-content:center; color:#dc2626;">
                            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                            <span>Cerrar sesión</span>
                        </button>
                        <button class="btn btn-ghost btn-sm" data-disable-user="${s.usuario_id}" style="flex:1; justify-content:center; color:#b45309;">
                            <i data-lucide="user-x" class="w-3.5 h-3.5"></i>
                            <span>Deshabilitar</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        refreshIcons();

        // Delegación de eventos (una sola vez por render)
        list.querySelectorAll('[data-force-logout]').forEach(btn => {
            btn.addEventListener('click', () => forceLogout(btn.dataset.forceLogout));
        });
        list.querySelectorAll('[data-disable-user]').forEach(btn => {
            btn.addEventListener('click', () => disableUser(btn.dataset.disableUser));
        });
    }

    /* ============================================================
       MODAL DE CONFIRMACIÓN · SITGO
       Devuelve una Promesa<boolean> — reemplaza al confirm() nativo.
       ============================================================ */
    function sitgoConfirm(options = {}){
        return new Promise((resolve) => {
            const {
                title     = '¿Confirmar acción?',
                message   = 'Esta acción no se puede deshacer.',
                okText    = 'Confirmar',
                cancelText= 'Cancelar',
                type      = 'warning',   // warning | danger | info | success
                icon      = 'alert-triangle'
            } = options;

            const modal    = $('sitgoConfirmModal');
            const iconWrap = $('sitgoConfirmIconWrap');
            const titleEl  = $('sitgoConfirmTitle');
            const msgEl    = $('sitgoConfirmMessage');
            const okBtn    = $('sitgoConfirmOk');
            const cancelBtn= $('sitgoConfirmCancel');
            const okTextEl = $('sitgoConfirmOkText');
            const cancelTextEl = $('sitgoConfirmCancelText');

            // Sin modal en el DOM → fallback al confirm nativo (por seguridad)
            if (!modal){
                resolve(window.confirm(`${title}\n\n${message}`));
                return;
            }

            // Aplicar textos e iconos
            titleEl.textContent  = title;
            msgEl.textContent    = message;
            okTextEl.textContent = okText;
            cancelTextEl.textContent = cancelText;

            // Resetear tipo del icono
            iconWrap.classList.remove('danger','info','success','warning');
            if (type !== 'warning') iconWrap.classList.add(type);

            // Cambiar el icono (Lucide)
            iconWrap.innerHTML = `<i data-lucide="${icon}" class="w-6 h-6"></i>`;
            if (window.lucide && typeof window.lucide.createIcons === 'function'){
                try{ window.lucide.createIcons(); } catch(_) {}
            }

            // Botón OK: si es danger, usar rojo; si no, primary
            okBtn.classList.toggle('btn-danger', type === 'danger');
            okBtn.classList.toggle('btn-primary', type !== 'danger');

            // Mostrar
            modal.classList.add('open');

            // Limpieza + resolver
            const cleanup = (result) => {
                modal.classList.remove('open');
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey);
                resolve(result);
            };

            const onOk     = () => cleanup(true);
            const onCancel = () => cleanup(false);
            const onBackdrop = (e) => {
                if (e.target === modal) cleanup(false);
            };
            const onKey = (e) => {
                if (e.key === 'Escape') cleanup(false);
                if (e.key === 'Enter')  cleanup(true);
            };

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            modal.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey);

            // Foco en Cancelar por defecto (seguridad UX)
            setTimeout(() => cancelBtn.focus(), 50);
        });
    }

    /* ============================================================
       FORZAR LOGOUT
       ============================================================ */
    async function forceLogout(nick) {
        const confirmado = await sitgoConfirm({
            title: 'Cerrar sesión',
            message: `¿Deseas cerrar la sesión de "${nick}"? Se le pedirá iniciar sesión nuevamente.`,
            okText: 'Sí, cerrar sesión',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'log-out',
        });
        if (!confirmado) return;

        const d = await fetchJSON(API_USERS + '/forzar-logout', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nick })
        });

        if (d.success) {
            toast(d.message || 'Sesión cerrada', 'success');
            sessionsHash = '';
            sessionsFirstLoad = true;
            loadActiveSessions();
        } else {
            toast(d.message || 'No se pudo cerrar', 'error');
        }
    }

    /* ============================================================
       DESHABILITAR USUARIO
       ============================================================ */
    async function disableUser(userId) {
            const confirmado = await sitgoConfirm({
                title: 'Deshabilitar usuario',
                message: 'El usuario no podrá iniciar sesión mientras esté deshabilitado. ¿Continuar?',
                okText: 'Sí, deshabilitar',
                cancelText: 'Cancelar',
                type: 'warning',
                icon: 'user-x',
            });
            if (!confirmado) return;

            const d = await fetchJSON(API_USERS + '/cambiar-status/' + userId, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_status: 0 })
            });

            if (d.ok || d.success) {
                toast('Usuario deshabilitado', 'success');
                sessionsHash = '';
                sessionsFirstLoad = true;
                loadActiveSessions();
            } else {
                toast(d.message || 'No se pudo deshabilitar', 'error');
            }
    }
});