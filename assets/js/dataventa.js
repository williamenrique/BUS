document.addEventListener('DOMContentLoaded', () => {
    if (typeof initQZTrayConnection === 'function') initQZTrayConnection();

    const API = (window.SITGO_BASE || '/') + 'estacion';
    const $ = id => document.getElementById(id);

    let ventasCierreActual = null;
    let dtCierres = null;
    let dtOpenSales = null;
    let dtVentasCierre = null;
    let filtroFechaActivo = false;

    let tablaVentasCtx = { tipo: null, id_user: null, fecha: null, id_cierre: null };

    // Gráfico de litros por estación
    let chartLitros = null;

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        initFiltrosLitros();
        initChartToggle();
        await cargarEstacionesFiltro();
        await cargarResumenLitros();
        initDataTables();
        bindEventos();
        bindModalPdf();
    })();

    async function fetchJSON(url, opts = {}){
        try{
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            return ct.includes('application/json') ? await r.json() : { ok: false, message: `Error ${r.status}` };
        } catch { return { ok: false, message: 'Error de conexión' }; }
    }

    /* ============================================================
       RESUMEN DE LITROS · Filtros + Tarjetas + Gráfico
       ============================================================ */

    function initFiltrosLitros(){
        const hoy  = new Date();
        const yyyy = hoy.getFullYear();
        const mm   = String(hoy.getMonth() + 1).padStart(2, '0');
        const dd   = String(hoy.getDate()).padStart(2, '0');

        if ($('litrosFecha')) $('litrosFecha').value = `${yyyy}-${mm}-${dd}`;
        if ($('litrosTipo'))  $('litrosTipo').value = 'day';

        // Cambio día/mes → actualizar tipo de input y recargar
        $('litrosTipo')?.addEventListener('change', (e) => {
            const esMes = e.target.value === 'month';
            const inp = $('litrosFecha');
            if (!inp) return;
            if (esMes){
                inp.type = 'month';
                inp.value = `${yyyy}-${mm}`;
            } else {
                inp.type = 'date';
                inp.value = `${yyyy}-${mm}-${dd}`;
            }
            cargarResumenLitros();
        });

        $('litrosFecha')?.addEventListener('change', cargarResumenLitros);
        $('litrosEstacion')?.addEventListener('change', cargarResumenLitros);
        $('btnLitrosActualizar')?.addEventListener('click', cargarResumenLitros);
    }
    /* ============================================================
       CHART COLAPSABLE
       ============================================================ */
    function initChartToggle(){
        const btn = $('btnToggleChart');
        const section = $('litrosChartSection');
        if (!btn || !section) return;

        // Estado inicial: expandido
        section.classList.remove('collapsed');
        btn.setAttribute('aria-expanded', 'true');

        btn.addEventListener('click', () => {
            const colapsado = section.classList.toggle('collapsed');
            btn.setAttribute('aria-expanded', colapsado ? 'false' : 'true');

            // Redibujar el chart al expandir (Chart.js necesita recalcular el tamaño)
            if (!colapsado && chartLitros){
                setTimeout(() => chartLitros.resize(), 50);
            }
        });
    }
    async function cargarEstacionesFiltro(){
        const sel = $('litrosEstacion');
        if (!sel) return;

        const d = await fetchJSON(API + '/estaciones');
        if (!d.ok) return;

        sel.innerHTML = '<option value="0">Todas las estaciones</option>';
        (d.data || []).forEach(e => {
            const o = document.createElement('option');
            o.value = e.id_estacion;
            o.textContent = e.estacion;
            sel.appendChild(o);
        });
    }

    async function cargarResumenLitros(){
        const tipo  = $('litrosTipo')?.value || 'day';
        const fecha = $('litrosFecha')?.value || fechaHoy();
        const idEst = parseInt($('litrosEstacion')?.value || '0');

        if (!fecha){
            toast('Selecciona una fecha.', 'error');
            return;
        }

        const d = await fetchJSON(API + '/resumen-litros', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ tipo, fecha, id_estacion: idEst })
        });

        if (!d.ok){
            toast(d.message || 'Error al cargar litros.', 'error');
            return;
        }

        // Tarjeta 1: total del período
        $('litrosPeriodo').textContent = fmtLitros(d.totalPeriodo) + ' L';

        const fechaLabel = tipo === 'month' ? formatMesLabel(fecha) : formatFecha(fecha);
        const estLabel   = idEst > 0
            ? (d.porEstacion[0]?.estacion || 'Estación seleccionada')
            : 'Todas las estaciones';
        $('litrosPeriodoLabel').textContent = `${fechaLabel} · ${estLabel}`;

        // Tarjeta 2: estación seleccionada
        if (idEst > 0){
            const est = d.porEstacion[0];
            $('litrosEstacionSel').textContent = est ? fmtLitros(est.total_litros) + ' L' : '0.00 L';
            $('litrosEstacionLabel').textContent = est ? est.estacion : 'Sin ventas';
        } else {
            $('litrosEstacionSel').textContent = '—';
            $('litrosEstacionLabel').textContent = 'Selecciona una estación';
        }

        // Tarjeta 3: estaciones con ventas
        const totalEstaciones = (d.porEstacion || []).length;
        $('litrosEstacionesCount').textContent = totalEstaciones;
        $('litrosEstacionesLabel').textContent = totalEstaciones === 1
            ? 'Estación con ventas'
            : 'Estaciones con ventas';

        renderChartLitros(d.porEstacion || []);
    }

    function renderChartLitros(items){
        const canvas = $('litrosPorEstacionChart');
        const empty  = $('litrosChartEmpty');
        if (!canvas) return;

        if (chartLitros){ chartLitros.destroy(); chartLitros = null; }

        if (!items.length){
            canvas.style.display = 'none';
            if (empty) empty.style.display = 'block';
            return;
        }

        canvas.style.display = 'block';
        if (empty) empty.style.display = 'none';

        const labels = items.map(i => i.estacion || 'Sin estación');
        const data   = items.map(i => parseFloat(i.total_litros || 0));
        const colors = labels.map((_, i) => `hsl(${210 + i * 30}, 70%, 55%)`);

        chartLitros = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Litros',
                    data,
                    backgroundColor: colors,
                    borderColor: colors.map(c => c.replace('55%', '45%')),
                    borderWidth: 1,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `${parseFloat(ctx.raw || 0).toLocaleString('es-VE')} L`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: v => v.toLocaleString('es-VE') + ' L' }
                    }
                }
            }
        });
    }

    function formatMesLabel(f){
        if (!f) return '—';
        const [y, m] = f.split('-');
        const meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        return `${meses[parseInt(m) - 1] || m} ${y}`;
    }

    function fechaHoy(){
        const d = new Date();
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    /* ============================================================
       DATATABLES
       ============================================================ */
    function initDataTables(){
        dtOpenSales = new DataTable({
            tbodyId: 'tbodyOpenSales',
            endpoint: API + '/open-sales/listar',
            columns: ['Empleado', 'Fecha', 'Litros', 'Acciones'],
            noDataMessage: 'No hay ventas abiertas',
            searchInputId: 'filtroBuscarOpen',
            limitSelectorId: 'filtroLimitOpen',
            paginationId: 'paginationOpenSales',
            totalId: 'totalOpenSales',
            startId: 'startOpenSales',
            endId: 'endOpenSales',
            renderRow: (v) => `
                <tr>
                    <td>
                        ${esc(v.nombre)} ${esc(v.apellido)}
                        ${v.estacion ? `<span style="color:#64748b; font-size:.78rem;">(${esc(v.estacion)})</span>` : ''}
                    </td>
                    <td>${formatFecha(v.fecha_venta)}</td>
                    <td class="text-right">${fmtLitros(v.total_litros)} L</td>
                    <td class="text-right">
                        <div class="actions">
                            <button class="action-btn" data-action="ver-abiertas" data-user="${v.id_user}" data-fecha="${v.fecha_venta}" title="Ver tickets">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn edit" data-action="pdf-abiertas" data-user="${v.id_user}" data-fecha="${v.fecha_venta}" title="Generar PDF">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn" data-action="cerrar-abiertas" data-user="${v.id_user}" data-fecha="${v.fecha_venta}" title="Cerrar venta">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn delete" data-action="del-abiertas" data-user="${v.id_user}" data-fecha="${v.fecha_venta}" title="Eliminar">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`,
            onDataLoaded: () => { refreshIcons(); bindOpenSalesActions(); }
        });

        dtCierres = new DataTable({
            tbodyId: 'tbodyCierres',
            endpoint: API + '/cierres/listar',
            columns: ['#','Empleado','Tasa','Fecha','Efectivo Bs','P. Venta Bs','Total Bs','Litros','Acciones'],
            noDataMessage: 'Sin cierres registrados',
            searchInputId: 'filtroBuscarCierres',
            limitSelectorId: 'filtroLimitCierres',
            paginationId: 'paginationCierres',
            totalId: 'totalCierres',
            startId: 'startCierres',
            endId: 'endCierres',
            getExtraParams: () => {
                if (!filtroFechaActivo) return {};
                const tipoFecha = $('searchType')?.value || 'day';
                const fecha = tipoFecha === 'month'
                    ? ($('searchDateMonth')?.value || '')
                    : ($('searchDateDay')?.value || '');
                if (!fecha) return {};
                return { fecha, tipoFecha };
            },
            renderRow: (c) => `
                <tr>
                    <td><strong>#${c.id_cierre}</strong></td>
                    <td>
                        ${esc(c.usuario_nombres)} ${esc(c.usuario_apellidos)}
                        ${c.estacion ? `<span style="color:#64748b; font-size:.78rem;">(${esc(c.estacion)})</span>` : ''}
                    </td>
                    <td>${fmtNum(c.tasa_dia, 2)}</td>
                    <td>${formatFecha(c.fecha_cierre)}</td>
                    <td class="text-right">${fmtNum(c.efectivo_bs, 2)}</td>
                    <td class="text-right">${fmtNum(c.debito_bs, 2)}</td>
                    <td class="text-right"><strong>${fmtNum(c.total_bs, 2)}</strong></td>
                    <td class="text-right">${fmtLitros(c.total_litros_vendidos)}</td>
                    <td class="text-right">
                        <div class="actions">
                            <button class="action-btn" data-action="ver" data-id="${c.id_cierre}" data-user="${c.id_user}" data-fecha="${c.fecha_cierre}" title="Ver ventas">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn edit" data-action="pdf-cierre" data-id="${c.id_cierre}" data-user="${c.id_user}" data-fecha="${c.fecha_cierre}" title="Generar PDF">
                                <i data-lucide="file-text" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn delete" data-action="del-cierre" data-id="${c.id_cierre}" data-user="${c.id_user}" data-fecha="${c.fecha_cierre}" title="Eliminar cierre">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`,
            onDataLoaded: () => { refreshIcons(); bindCierresActions(); }
        });

        dtVentasCierre = new DataTable({
            tbodyId: 'tbodyVentasCierre',
            endpoint: API + '/ventas-cierre/listar',
            columns: ['#','Hora','Vehículo','Combustible','Litros','Pago','Monto',''],
            noDataMessage: 'Sin ventas en este cierre',
            searchInputId: 'filtroBuscarVentasCierre',
            limitSelectorId: 'filtroLimitVentasCierre',
            paginationId: 'paginationVentasCierre',
            totalId: 'totalVentasCierre',
            startId: 'startVentasCierre',
            endId: 'endVentasCierre',
            autoLoad: false,
            getExtraParams: () => {
                if (!tablaVentasCtx.id_cierre) return {};
                return { id_cierre: tablaVentasCtx.id_cierre };
            },
            renderRow: (v) => `
                <tr>
                    <td>#${v.id_venta}</td>
                    <td>${esc(v.hora_venta || '')}</td>
                    <td>${esc(v.tipo_vehiculo)}</td>
                    <td>${esc(v.tipo_combustible || '—')}</td>
                    <td class="text-right">${fmtLitros(v.litros)}</td>
                    <td>${esc(v.tipo_pago)}</td>
                    <td class="text-right">${fmtNum(v.monto, 2)}</td>
                    <td class="text-right">
                        <div class="actions">
                            <button class="action-btn" data-action="reimprimir" data-id="${v.id_venta}" data-user="${tablaVentasCtx.id_user || ''}" data-fecha="${tablaVentasCtx.fecha || ''}" title="Reimprimir (COPIA)">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn delete" data-action="eliminar-ticket" data-id="${v.id_venta}" data-user="${tablaVentasCtx.id_user || ''}" data-fecha="${tablaVentasCtx.fecha || ''}" title="Eliminar ticket">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`,
            onDataLoaded: () => { refreshIcons(); bindVentasCierreActions(); }
        });
    }

    function refreshIcons(){
        if (window.lucide && typeof window.lucide.createIcons === 'function'){
            try { window.lucide.createIcons(); } catch(e){ console.error('lucide:', e); }
        }
    }

    function bindCierresActions(){
        const tbody = $('tbodyCierres');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;
        tbody.addEventListener('click', (e) => {
            const b = e.target.closest('[data-action]');
            if (!b) return;
            const { action, id, user, fecha } = b.dataset;
            if (action === 'ver')        verCierre(id, user, fecha);
            if (action === 'del-cierre') eliminarCierre(id, user, fecha);
            if (action === 'pdf-cierre') abrirPdfCierreDesdeFila(id, user, fecha);
        });
    }

    function bindOpenSalesActions(){
        const tbody = $('tbodyOpenSales');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;
        tbody.addEventListener('click', (e) => {
            const b = e.target.closest('[data-action]');
            if (!b) return;
            const { action, user, fecha } = b.dataset;
            if (action === 'ver-abiertas')    verVentasAbiertas(user, fecha);
            if (action === 'pdf-abiertas')    abrirModalPdfAbiertas(user, fecha);
            if (action === 'cerrar-abiertas') cerrarVentaAbierta(user, fecha);
            if (action === 'del-abiertas')    eliminarAbiertas(user, fecha);
        });
    }

    function bindVentasCierreActions(){
        const tbody = $('tbodyVentasCierre');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;
        tbody.addEventListener('click', async (e) => {
            const b = e.target.closest('[data-action]');
            if (!b) return;
            const { action, id, user, fecha } = b.dataset;
            if (action === 'reimprimir')      await reimprimirTicket(id);
            if (action === 'eliminar-ticket') await eliminarTicket(id, user, fecha, b);
        });
    }

    /* ============================================================
       PDF DESDE LA FILA DEL CIERRE
       ============================================================ */
    async function abrirPdfCierreDesdeFila(idCierre, idUser, fecha){
        const dataCierre = await fetchJSON(API + '/data-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: idCierre })
        });

        if (!dataCierre.ok || !dataCierre.cierreData){
            toast(dataCierre.message || 'No se pudo cargar el cierre.', 'error');
            return;
        }

        ventasCierreActual = Object.assign({}, dataCierre.cierreData, {
            usuario_id: idUser,
            id_cierre:  parseInt(idCierre),
        });
        tablaVentasCtx = { tipo: 'cierre', id_user: idUser, fecha, id_cierre: idCierre };

        abrirModalPdf('cierre');
    }

    /* ============================================================
       REIMPRIMIR TICKET
       ============================================================ */
    async function reimprimirTicket(idVenta){
        if (!idVenta){
            toast('No se identificó la venta a reimprimir.', 'error');
            return;
        }

        toast('Preparando ticket para reimpresión…', 'success');

        const t = await fetchJSON(API + '/ticket-by-venta/' + idVenta, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: '{}'
        });

        if (!t.ok || !t.ticketData){
            toast(t.message || 'No se pudo obtener el ticket.', 'error');
            return;
        }

        if (typeof fntImprimirTicket === 'function'){
            try{
                await fntImprimirTicket({ ticketData: t.ticketData, copia: 1 });
                toast('Ticket enviado a la impresora.', 'success');
            } catch(e){
                console.error(e);
                toast('Error al imprimir: ' + e.message, 'error');
            }
        } else {
            toast('Módulo de impresión no cargado.', 'error');
        }
    }

    /* ============================================================
       ELIMINAR TICKET
       ============================================================ */
    async function eliminarTicket(idVenta, idUser, fecha, btnEl){
        const tipoCtx = tablaVentasCtx.tipo;
        const msg = tipoCtx === 'cierre'
            ? `¿Eliminar el ticket #${idVenta}? Se recalcularán los totales del cierre.`
            : `¿Eliminar el ticket #${idVenta}?`;

        const ok = await sitgoConfirm({
            title: 'Eliminar ticket',
            message: msg,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        btnEl.disabled = true;
        try {
            const d = await fetchJSON(API + '/eliminar-venta', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_venta: idVenta, fecha, id_user: idUser })
            });
            if (!d.ok){ toast(d.message || 'Error al eliminar.', 'error'); return; }
            toast(d.message || 'Ticket eliminado.', 'success');

            if (tipoCtx === 'cierre' && tablaVentasCtx.id_cierre){
                dtVentasCierre.refresh();
                const dataCierre = await fetchJSON(API + '/data-cierre', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id_cierre: tablaVentasCtx.id_cierre })
                });
                if (dataCierre.ok && dataCierre.cierreData){
                    ventasCierreActual = Object.assign({}, dataCierre.cierreData, {
                        usuario_id: tablaVentasCtx.id_user,
                        id_cierre:  tablaVentasCtx.id_cierre,
                    });
                }
                dtCierres?.refresh();
            } else if (tipoCtx === 'abiertas'){
                await verVentasAbiertas(idUser, fecha);
                dtOpenSales?.refresh();
            }

            refreshIcons();
        } catch (e) {
            console.error(e);
            toast('Error de conexión.', 'error');
        } finally {
            btnEl.disabled = false;
        }
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEventos(){
        $('searchType')?.addEventListener('change', (e) => {
            const esMes = e.target.value === 'month';
            $('searchDateDay').style.display   = esMes ? 'none' : '';
            $('searchDateMonth').style.display = esMes ? '' : 'none';
            filtroFechaActivo = true;
            dtCierres?.changePage(1);
        });
        $('searchDateDay')?.addEventListener('change', () => { filtroFechaActivo = true; dtCierres?.changePage(1); });
        $('searchDateMonth')?.addEventListener('change', () => { filtroFechaActivo = true; dtCierres?.changePage(1); });

        $('btnImprimirCierre')?.addEventListener('click', async () => {
            if (!ventasCierreActual) return;
            if (typeof fntImprimirCierre === 'function'){
                await fntImprimirCierre(ventasCierreActual);
            }
        });

        $('btnImprimirPdf')?.addEventListener('click', () => {
            if (!ventasCierreActual) return;
            abrirModalPdf('cierre');
        });

        $('btnPdfLitros')?.addEventListener('click', () => {
            abrirModalPdf('litros');
        });
    }

    /* ============================================================
       MODAL SELECTOR DE PDF
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
                const tipo = modal.dataset.tipo;
                cerrarModalPdf();
                if (tipo === 'cierre')   generarPdfCierre(seccion);
                if (tipo === 'abiertas') generarPdfAbiertas(seccion);
                if (tipo === 'litros')   generarPdfLitros();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') cerrarModalPdf();
        });
    }

    function abrirModalPdf(tipo){
        const modal = $('modalPdfSelector');
        if (!modal) return;
        modal.dataset.tipo = tipo;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function abrirModalPdfAbiertas(idUser, fecha){
        tablaVentasCtx = { tipo: 'abiertas', id_user: idUser, fecha, id_cierre: null };
        abrirModalPdf('abiertas');
    }

    function cerrarModalPdf(){
        const modal = $('modalPdfSelector');
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    /* ============================================================
       GENERAR PDF · CIERRE
       ============================================================ */
    async function generarPdfCierre(seccion){
        if (!ventasCierreActual?.id_cierre){
            toast('No hay cierre seleccionado.', 'error');
            return;
        }

        const d = await fetchJSON(API + '/data-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: ventasCierreActual.id_cierre })
        });
        if (!d.ok){
            toast(d.message || 'Error al cargar los datos.', 'error');
            return;
        }

        const cierreData = d.cierreData;
        const ventas     = d.ventas || [];
        const fecha      = cierreData.fecha_cierre || ventasCierreActual.fecha_cierre;

        try {
            await SITGO_PDF.generar(
                'estacion_reporte_dia',
                { cierre: cierreData, ventas, seccion },
                `cierre_${seccion}_${fecha}.pdf`,
                {
                    titulo_documento: 'CIERRE DEL DÍA',
                    doc_color:        '#1e40af',
                    mostrar_header:   false,
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
        }
    }

    /* ============================================================
       GENERAR PDF · ABIERTAS
       ============================================================ */
    async function generarPdfAbiertas(seccion){
        if (!tablaVentasCtx.id_user){ toast('No hay venta seleccionada.', 'error'); return; }

        const { id_user, fecha } = tablaVentasCtx;

        const det = await fetchJSON(API + '/detalle-ventas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, id_user })
        });
        if (!det.ok){
            toast(det.message || 'No hay ventas para generar el PDF.', 'error');
            return;
        }

        const ventas = det.data || [];
        if (!ventas.length){
            toast('No hay ventas para generar el PDF.', 'error');
            return;
        }

        const resumen = calcularResumenDesdeVentas(ventas);

        const cierreData = {
            id_cierre:        null,
            fecha_cierre:     fecha,
            estacion:         '',
            usuario_nombres:  ventas[0]?.operador || '',
            usuario_apellidos:'',
            tasa_dia:         parseFloat(ventas[0]?.tasa_dia || 0),
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

        try {
            await SITGO_PDF.generar(
                'estacion_reporte_dia',
                { cierre: cierreData, ventas, seccion },
                `ventas_abiertas_${seccion}_${fecha}.pdf`,
                {
                    titulo_documento: 'VENTAS ABIERTAS',
                    doc_color:        '#1e40af',
                    mostrar_header:   false,
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
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
            const efectivob = parseFloat(v.effectivob ?? v.efectivob ?? 0);
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
       GENERAR PDF · LITROS
       ============================================================ */
    async function generarPdfLitros(){
        const tipo  = $('litrosTipo')?.value || 'day';
        const fecha = $('litrosFecha')?.value || fechaHoy();
        const idEst = parseInt($('litrosEstacion')?.value || '0');

        if (!fecha){ toast('Selecciona una fecha.', 'error'); return; }

        const d = await fetchJSON(API + '/litros-reporte', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fecha,
                type: tipo,
                fechaFin: null,
                id_estacion: idEst > 0 ? idEst : null
            })
        });
        if (!d.ok){ toast(d.message || 'Error al cargar los datos.', 'error'); return; }

        try {
            await SITGO_PDF.generar(
                'estacion_litros',
                {
                    data: d.data,
                    total: d.total,
                    fecha: d.fecha,
                    type: d.type,
                    fechaFin: d.fechaFin,
                    litrosGasolina: d.litrosGasolina,
                    litrosDiesel: d.litrosDiesel,
                },
                `reporte_litros_${fecha}.pdf`,
                {
                    titulo_documento: 'REPORTE DE LITROS',
                    doc_color: '#1e40af',
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
        }
    }

    /* ============================================================
       ACCIONES CIERRE
       ============================================================ */
    async function verCierre(idCierre, idUser, fecha){
        ventasCierreActual = { fecha_cierre: fecha, usuario_id: idUser, id_cierre: idCierre };
        tablaVentasCtx = { tipo: 'cierre', id_user: idUser, fecha, id_cierre: idCierre };

        const dataCierre = await fetchJSON(API + '/data-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: idCierre })
        });
        if (dataCierre.ok && dataCierre.cierreData){
            ventasCierreActual = Object.assign({}, dataCierre.cierreData, { usuario_id: idUser, id_cierre: idCierre });
        }

        const estNombre = ventasCierreActual?.estacion || '';
        const empleado  = `${ventasCierreActual?.usuario_nombres || ''} ${ventasCierreActual?.usuario_apellidos || ''}`.trim();
        const empleadoTxt = empleado
            ? ` · ${empleado}${estNombre ? ' (' + estNombre + ')' : ''}`
            : (estNombre ? ` · (${estNombre})` : '');

        $('cierreTitle').textContent = `Detalle del cierre #${idCierre} · ${formatFecha(fecha)}${empleadoTxt}`;
        $('ventasCierreSection').style.display = '';
        $('ventasCierreSection').scrollIntoView({ behavior: 'smooth' });
        if (dtVentasCierre) dtVentasCierre.refresh();
    }

    async function verVentasAbiertas(idUser, fecha){
        tablaVentasCtx = { tipo: 'abiertas', id_user: idUser, fecha, id_cierre: null };

        const d = await fetchJSON(API + '/ventas-abiertas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user: idUser, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

        const estNombre = (d.data && d.data[0] && d.data[0].estacion) ? d.data[0].estacion : '';
        const empleado  = (d.data && d.data[0] && d.data[0].operador) ? d.data[0].operador : '';
        const empleadoTxt = empleado
            ? ` · ${empleado}${estNombre ? ' (' + estNombre + ')' : ''}`
            : (estNombre ? ` · (${estNombre})` : '');

        $('cierreTitle').textContent = `Ventas abiertas · ${formatFecha(fecha)}${empleadoTxt}`;
        $('ventasCierreSection').style.display = '';
        $('ventasCierreSection').scrollIntoView({ behavior: 'smooth' });
        const tbody = $('tbodyVentasCierre');
        if (!d.data.length){
            tbody.innerHTML = '<tr><td colspan="8" class="dt-empty">Sin ventas</td></tr>';
            $('startVentasCierre').textContent = '0';
            $('endVentasCierre').textContent   = '0';
            $('totalVentasCierre').textContent = '0';
            $('paginationVentasCierre').innerHTML = '';
        } else {
            tbody.innerHTML = d.data.map(v => `
                <tr>
                    <td>#${v.numero_venta ?? v.id_venta ?? ''}</td>
                    <td>${esc(v.hora_venta || '')}</td>
                    <td>${esc(v.tipo_vehiculo || '')}</td>
                    <td>${esc(v.tipo_combustible || '—')}</td>
                    <td class="text-right">${fmtLitros(v.cantidad_litros ?? v.litros ?? 0)}</td>
                    <td>${esc(v.tipo_pago || '')}</td>
                    <td class="text-right">${fmtNum(v.monto, 2)}</td>
                    <td class="text-right">
                        <div class="actions">
                            <button class="action-btn" data-action="reimprimir" data-id="${v.numero_venta ?? v.id_venta}" title="Reimprimir (COPIA)">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                            <button class="action-btn delete" data-action="eliminar-ticket" data-id="${v.numero_venta ?? v.id_venta}" data-user="${idUser}" data-fecha="${fecha}" title="Eliminar ticket">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
            refreshIcons();
            $('startVentasCierre').textContent = '1';
            $('endVentasCierre').textContent   = String(d.data.length);
            $('totalVentasCierre').textContent = String(d.data.length);
            $('paginationVentasCierre').innerHTML = '';
        }
        bindVentasCierreActions();
    }

    /* ============================================================
       CERRAR VENTA ABIERTA
       ============================================================ */
    async function cerrarVentaAbierta(idUser, fecha){
        const ok = await sitgoConfirm({
            title: 'Cerrar venta abierta',
            message: `¿Cerrar la venta abierta del día ${formatFecha(fecha)}? Se generará un cierre.`,
            okText: 'Sí, cerrar',
            cancelText: 'Cancelar',
            type: 'warning',
            icon: 'lock',
        });
        if (!ok) return;

        try {
            const d = await fetchJSON(API + '/cerrar-turno-pendiente', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha, id_user: idUser })
            });

            if (!d.ok){ toast(d.message || 'Error al cerrar la venta.', 'error'); return; }
            toast(d.message || 'Venta cerrada correctamente.', 'success');

            dtOpenSales?.refresh();
            dtCierres?.refresh();

            if (ventasCierreActual && String(ventasCierreActual.usuario_id) === String(idUser) && ventasCierreActual.fecha_cierre === fecha){
                $('ventasCierreSection').style.display = 'none';
            }
        } catch (e) {
            console.error(e);
            toast('Error al cerrar la venta: ' + e.message, 'error');
        }
    }

    /* ============================================================
       ELIMINAR CIERRE
       ============================================================ */
    async function eliminarCierre(idCierre, idUser, fecha){
        const ok = await sitgoConfirm({
            title: 'Eliminar cierre',
            message: `¿Eliminar el cierre #${idCierre}? Las ventas volverán a "abiertas".`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        const d = await fetchJSON(API + '/eliminar-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: idCierre, id_user: idUser, id_estacion: 0, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast(d.message, 'success');
        dtCierres?.refresh();
        dtOpenSales?.refresh();
        if (tablaVentasCtx.id_cierre === parseInt(idCierre)){
            $('ventasCierreSection').style.display = 'none';
        }
    }

    /* ============================================================
       ELIMINAR ABIERTAS
       ============================================================ */
    async function eliminarAbiertas(idUser, fecha){
        const ok = await sitgoConfirm({
            title: 'Eliminar ventas abiertas',
            message: '¿Eliminar TODAS las ventas abiertas de este usuario/fecha?',
            okText: 'Sí, eliminar todo',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        const d = await fetchJSON(API + '/eliminar-ventas-abiertas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user: idUser, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast(d.message, 'success');
        dtOpenSales?.refresh();
        if (tablaVentasCtx.tipo === 'abiertas' && String(tablaVentasCtx.id_user) === String(idUser) && tablaVentasCtx.fecha === fecha){
            $('ventasCierreSection').style.display = 'none';
        }
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    function formatFecha(f){
        if (!f) return '—';
        const [y,m,d] = f.split('-'); return `${d}/${m}/${y}`;
    }
    function fmtNum(val, decimals = 2){
        const n = parseFloat(val);
        if (!isFinite(n)) return (0).toFixed(decimals).replace('.', ',');
        return n.toLocaleString('es-VE', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
    }
    function fmtLitros(val){ return fmtNum(val, 2); }
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