document.addEventListener('DOMContentLoaded', () => {
    if (typeof initQZTrayConnection === 'function') initQZTrayConnection();

    const API = (window.SITGO_BASE || '/') + 'estacion';
    const $ = id => document.getElementById(id);

    let ventasCierreActual = null;
    let dtCierres = null;
    let dtOpenSales = null;
    let dtVentasCierre = null;
    let filtroFechaActivo = false;
    let ventaAbiertaActual = null;   // { id_user, fecha }

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        await cargarLitrosTotales();
        await cargarFechaPorDefecto();
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
       CARGA INICIAL
       ============================================================ */
    async function cargarLitrosTotales(){
        const litros = await fetchJSON(API + '/litros-totales');
        if (litros.ok) $('totalLitrosSistema').textContent = fmtLitros(litros.total) + ' L';
    }

    async function cargarFechaPorDefecto(){
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        $('searchDateDay').value = `${yyyy}-${mm}-${dd}`;
        $('searchDateMonth').value = `${yyyy}-${mm}`;
        await cargarLitrosFecha();
    }

    async function cargarLitrosFecha(){
        const type = $('searchType').value;
        const fecha = type === 'month' ? $('searchDateMonth').value : $('searchDateDay').value;
        if (!fecha) return;
        const d = await fetchJSON(API + '/litros-por-fecha', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, type })
        });
        if (d.ok) $('totalLitrosFecha').textContent = fmtLitros(d.total) + ' L';
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
                    <td>${esc(v.nombre)} ${esc(v.apellido)}</td>
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
                    <td>${esc(c.usuario_nombres)} ${esc(c.usuario_apellidos)}</td>
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
                if (!ventasCierreActual?.id_cierre) return {};
                return { id_cierre: ventasCierreActual.id_cierre };
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
                        <button class="action-btn" data-action="print" data-id="${v.id_venta}" data-user="${v.id_user}" data-fecha="${v.fecha_venta}" title="Reimprimir">
                            <i data-lucide="printer" class="w-4 h-4"></i>
                        </button>
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
            const b = e.target.closest('[data-action="print"]');
            if (!b) return;
            const { id, user, fecha } = b.dataset;
            const t = await fetchJSON(API + '/ticket/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ fecha, id_user: user })
            });
            if (t.ok && typeof fntImprimirTicket === 'function'){
                await fntImprimirTicket({ ticketData: t.ticketData, copia: 1 });
            }
        });
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEventos(){
        $('searchType')?.addEventListener('change', (e) => {
            const esMes = e.target.value === 'month';
            $('searchDateDay').style.display   = esMes ? 'none' : '';
            $('searchDateMonth').style.display = esMes ? '' : 'none';
            cargarLitrosFecha();
            filtroFechaActivo = true;
            dtCierres?.changePage(1);
        });
        $('searchDateDay')?.addEventListener('change', () => { cargarLitrosFecha(); filtroFechaActivo = true; dtCierres?.changePage(1); });
        $('searchDateMonth')?.addEventListener('change', () => { cargarLitrosFecha(); filtroFechaActivo = true; dtCierres?.changePage(1); });

        $('btnImprimirCierre')?.addEventListener('click', async () => {
            if (!ventasCierreActual) return;
            if (typeof fntImprimirCierre === 'function'){
                await fntImprimirCierre(ventasCierreActual);
            }
        });

        $('btnImprimirPdf')?.addEventListener('click', () => {
            if (!ventasCierreActual) return;
            ventaAbiertaActual = null;
            abrirModalPdf('cierre');
        });

        $('btnPdfLitros')?.addEventListener('click', () => {
            ventaAbiertaActual = null;
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
        ventaAbiertaActual = { id_user: idUser, fecha };
        abrirModalPdf('abiertas');
    }

    function cerrarModalPdf(){
        const modal = $('modalPdfSelector');
        if (!modal) return;
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    /* ============================================================
       GENERAR PDF · CIERRE CERRADO
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
       GENERAR PDF · VENTAS ABIERTAS (Sistema puede)
       ============================================================ */
    async function generarPdfAbiertas(seccion){
        if (!ventaAbiertaActual){ toast('No hay venta seleccionada.', 'error'); return; }

        const { id_user, fecha } = ventaAbiertaActual;

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
            estacion:         '',    // el template lo resuelve; en admin no hay station específica
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
       GENERAR PDF · LITROS
       ============================================================ */
    async function generarPdfLitros(){
        const type = $('searchType')?.value || 'day';
        const fecha = type === 'month' ? $('searchDateMonth')?.value : $('searchDateDay')?.value;
        if (!fecha){ toast('Selecciona una fecha.', 'error'); return; }

        const d = await fetchJSON(API + '/litros-reporte', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fecha, type, fechaFin: null })
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
        const dataCierre = await fetchJSON(API + '/data-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: idCierre })
        });
        if (dataCierre.ok && dataCierre.cierreData){
            ventasCierreActual = Object.assign({}, dataCierre.cierreData, { usuario_id: idUser, id_cierre: idCierre });
        }
        $('cierreTitle').textContent = `Detalle del cierre #${idCierre} · ${formatFecha(fecha)}`;
        $('ventasCierreSection').style.display = '';
        $('ventasCierreSection').scrollIntoView({ behavior: 'smooth' });
        if (dtVentasCierre) dtVentasCierre.refresh();
    }

    async function verVentasAbiertas(idUser, fecha){
        const d = await fetchJSON(API + '/ventas-abiertas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user: idUser, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        $('cierreTitle').textContent = `Ventas abiertas · ${formatFecha(fecha)}`;
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
                    <td>#${v.numero_venta}</td>
                    <td>${esc(v.hora_venta || '')}</td>
                    <td>${esc(v.tipo_vehiculo)}</td>
                    <td>${esc(v.tipo_combustible || '—')}</td>
                    <td class="text-right">${fmtLitros(v.cantidad_litros)}</td>
                    <td>${esc(v.tipo_pago)}</td>
                    <td class="text-right">${fmtNum(v.monto, 2)}</td>
                    <td class="text-right"></td>
                </tr>
            `).join('');
            refreshIcons();
            $('startVentasCierre').textContent = '1';
            $('endVentasCierre').textContent   = String(d.data.length);
            $('totalVentasCierre').textContent = String(d.data.length);
            $('paginationVentasCierre').innerHTML = '';
        }
    }

    /* ============================================================
       CERRAR VENTA ABIERTA (admin)
       ============================================================ */
    async function cerrarVentaAbierta(idUser, fecha){
        if (!confirm(`¿Cerrar la venta abierta del día ${formatFecha(fecha)}? Se generará un cierre.`)) return;

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

            // Si la venta cerrada es la que estábamos viendo, refrescar el detalle
            if (ventasCierreActual && String(ventasCierreActual.usuario_id) === String(idUser) && ventasCierreActual.fecha_cierre === fecha){
                $('ventasCierreSection').style.display = 'none';
            }
        } catch (e) {
            console.error(e);
            toast('Error al cerrar la venta: ' + e.message, 'error');
        }
    }

    async function eliminarCierre(idCierre, idUser, fecha){
        if (!confirm(`¿Eliminar el cierre #${idCierre}? Las ventas volverán a "abiertas".`)) return;
        const d = await fetchJSON(API + '/eliminar-cierre', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_cierre: idCierre, id_user: idUser, id_estacion: 0, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast(d.message, 'success');
        dtCierres?.refresh();
        dtOpenSales?.refresh();
    }

    async function eliminarAbiertas(idUser, fecha){
        if (!confirm('¿Eliminar TODAS las ventas abiertas de este usuario/fecha?')) return;
        const d = await fetchJSON(API + '/eliminar-ventas-abiertas', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_user: idUser, fecha })
        });
        if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
        toast(d.message, 'success');
        dtOpenSales?.refresh();
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