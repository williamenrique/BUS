document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'movimientos';
    const $ = id => document.getElementById(id);

    let instituciones = [];
    let instSeleccionada = { flota: null, aceite: null, resumen: null, movimientos: null };

    /* Estado */
    let movimientosAll = [];
    let movimientosFiltered = [];
    let movCurrentPage = 1;
    let movPerPage = 10;
    let selectedMov = null;

    let flotaAll = [];
    let flotaStatusActual = null;

    let aceiteAll = [];
    let aceiteStatusActual = null;

    let resumenAll = [];
    let resumenSelected = new Set();

    let estAll = [];
    let estFiltered = [];
    let estCurrentPage = 1;
    let estPerPage = 10;
    let estChart = null;

    /* ============================================================
       FETCH
       ============================================================ */
    async function fetchJSON(url, opts = {}){
        try {
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                return { success: false, message: `Error ${r.status}` };
            }
            return await r.json();
        } catch {
            return { success: false, message: 'Error de conexión' };
        }
    }

    function refreshIcons(){
        if (window.lucide && typeof window.lucide.createIcons === 'function'){
            try { window.lucide.createIcons(); } catch(e){ console.error(e); }
        }
    }

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        await cargarInstituciones();
        configurarFechas();
        await cargarFlota();
        await cargarAceite();
        await cargarResumen();
        await cargarMovimientos();
        await cargarEstacionesSelect();
        await cargarEstacion();
        bindEvents();
        bindModalDetalleEstacion();
        refreshIcons();
    })();

    function configurarFechas(){
        const hoy = new Date();
        const hace30 = new Date();
        hace30.setDate(hoy.getDate() - 30);
        const fmt = d => d.toISOString().split('T')[0];
        $('movFechaInicio').value = fmt(hace30);
        $('movFechaFin').value = fmt(hoy);
        $('estFechaInicio').value = fmt(hace30);
        $('estFechaFin').value = fmt(hoy);
    }

    /* ============================================================
       INSTITUCIONES
       ============================================================ */
    async function cargarInstituciones(){
        const d = await fetchJSON(API + '/instituciones');
        if (!d.success){ console.error('Error instituciones:', d); return; }

        instituciones = d.data || [];

        ['instFlota', 'instAceite', 'instResumen', 'instMovimientos'].forEach(id => {
            const sel = $(id);
            if (!sel) return;
            sel.innerHTML = '';
            instituciones.forEach(i => {
                const opt = document.createElement('option');
                opt.value = i.id_institucion;
                opt.textContent = abreviar(i.nombre);
                opt.title = i.nombre;
                sel.appendChild(opt);
            });
            if (instituciones[0]) sel.value = instituciones[0].id_institucion;
        });

        instSeleccionada.flota = parseInt(instituciones[0]?.id_institucion ?? 1);
        instSeleccionada.aceite = instSeleccionada.flota;
        instSeleccionada.resumen = instSeleccionada.flota;
        instSeleccionada.movimientos = instSeleccionada.flota;
    }

    function abreviar(nombre){
        if (!nombre) return 'N/D';
        if (nombre.length <= 30) return nombre;
        const ignorar = ['de','del','la','el','los','las','y','e','o','a','en','con'];
        const palabras = nombre.split(/\s+/);
        let ini = '';
        palabras.forEach(p => {
            if (!ignorar.includes(p.toLowerCase()) && p.length > 0) ini += p[0].toUpperCase();
        });
        return ini.length >= 3 ? ini : nombre.substring(0, 30) + '…';
    }

    function nombreInstitucion(id){
        const i = instituciones.find(x => parseInt(x.id_institucion) === parseInt(id));
        return i ? i.nombre : '';
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEvents(){
        $('instFlota')?.addEventListener('change', e => {
            instSeleccionada.flota = parseInt(e.target.value);
            flotaStatusActual = null;
            $('flotaTablaWrap').style.display = 'none';
            cargarFlota();
        });
        $('instAceite')?.addEventListener('change', e => {
            instSeleccionada.aceite = parseInt(e.target.value);
            aceiteStatusActual = null;
            $('aceiteTablaWrap').style.display = 'none';
            cargarAceite();
        });
        $('instResumen')?.addEventListener('change', e => {
            instSeleccionada.resumen = parseInt(e.target.value);
            cargarResumen();
        });
        $('instMovimientos')?.addEventListener('change', e => {
            instSeleccionada.movimientos = parseInt(e.target.value);
            cargarMovimientos();
        });

        /* Flota stats */
        document.querySelectorAll('#flotaStats .pub-stat[data-status]').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('#flotaStats .pub-stat').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                flotaStatusActual = card.dataset.status;
                cargarUnidadesPorEstado(flotaStatusActual);
            });
        });
        $('flotaSearch')?.addEventListener('input', renderFlotaTabla);

        /* Aceite stats */
        document.querySelectorAll('.pub-stat[data-aceite]').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.pub-stat[data-aceite]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                aceiteStatusActual = card.dataset.aceite;
                $('btnPdfAceite').disabled = false;
                cargarUnidadesPorAceite(aceiteStatusActual);
            });
        });
        $('aceiteSearch')?.addEventListener('input', renderAceiteTabla);

        $('btnPdfAceite')?.addEventListener('click', () => generarPdfAceite('por_estado'));
        $('btnPdfAceiteCompleto')?.addEventListener('click', () => generarPdfAceite('completo'));

        /* Resumen */
        $('resumenSearch')?.addEventListener('input', renderResumenTabla);
        $('resumenSelectAll')?.addEventListener('change', toggleResumenAll);
        $('resumenSelectAllHeader')?.addEventListener('change', toggleResumenAll);
        $('btnPdfResumen')?.addEventListener('click', generarPdfResumen);

        /* Movimientos */
        $('btnMovFiltrar')?.addEventListener('click', cargarMovimientos);
        $('movSearch')?.addEventListener('input', () => { movCurrentPage = 1; renderMovimientos(); });
        $('movLimit')?.addEventListener('change', e => { movPerPage = parseInt(e.target.value); movCurrentPage = 1; renderMovimientos(); });

        /* Estación */
        $('btnEstFiltrar')?.addEventListener('click', cargarEstacion);
        $('estSearch')?.addEventListener('input', () => { estCurrentPage = 1; renderEstacion(); });
        $('estLimit')?.addEventListener('change', e => { estPerPage = parseInt(e.target.value); estCurrentPage = 1; renderEstacion(); });
        $('estChartType')?.addEventListener('change', renderEstacionChart);
        $('btnPdfEstacion')?.addEventListener('click', generarPdfEstacion);
    }

    /* ============================================================
       FLOTA
       ============================================================ */
    async function cargarFlota(){
        const d = await fetchJSON(API + '/estado-flota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_institucion: instSeleccionada.flota })
        });
        if (!d.success){ console.error('Error estado flota:', d); return; }

        const r = d.data || {};
        $('flotaOperativas').textContent = r.operativas || 0;
        $('flotaInoperativas').textContent = r.inoperativas || 0;
        $('flotaMantenimiento').textContent = r.en_mantenimiento || 0;
        $('flotaCriticas').textContent = r.criticas || 0;
        $('flotaTotal').textContent = r.total || 0;
    }

    async function cargarUnidadesPorEstado(status){
        $('flotaTablaWrap').style.display = 'block';
        $('flotaTablaBody').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/unidades-por-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status, id_institucion: instSeleccionada.flota })
        });
        if (!d.success){ $('flotaTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>'; return; }

        flotaAll = d.data || [];
        renderFlotaTabla();
    }

    function renderFlotaTabla(){
        const q = ($('flotaSearch')?.value || '').toLowerCase().trim();
        const items = flotaAll.filter(u => {
            if (!q) return true;
            return `${u.id_unidad||''} ${u.marca_unidad||''} ${u.modelo_unidad||''} ${u.vim_unidad||''}`.toLowerCase().includes(q);
        });

        if (!items.length){
            $('flotaTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Sin unidades</td></tr>';
            return;
        }

        $('flotaTablaBody').innerHTML = items.map(u => `
            <tr>
                <td><strong>${esc(u.id_unidad || '—')}</strong></td>
                <td>${esc((u.marca_unidad||'') + ' ' + (u.modelo_unidad||''))}</td>
                <td><code style="font-size:.72rem;">${esc(u.vim_unidad || '—')}</code></td>
                <td>${esc(u.transmision || '—')}</td>
                <td>${esc(u.tipo_combustible || '—')}</td>
                <td class="text-right">${fmtNum(u.km_actual, 0)}</td>
                <td class="text-right">${fmtNum(u.ultimo_cambio_aceite, 0)}</td>
            </tr>
        `).join('');
    }

    /* ============================================================
       ACEITE
       ============================================================ */
    async function cargarAceite(){
        const d = await fetchJSON(API + '/estado-aceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_institucion: instSeleccionada.aceite })
        });
        if (!d.success){ console.error('Error estado aceite:', d); return; }

        const r = d.data || {};
        $('aceiteOk').textContent = r.ok || 0;
        $('aceiteProximo').textContent = r.proximo || 0;
        $('aceiteRequerido').textContent = r.requerido || 0;
    }

    async function cargarUnidadesPorAceite(status){
        $('aceiteTablaWrap').style.display = 'block';
        $('aceiteTablaBody').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/unidades-por-estado-aceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status, id_institucion: instSeleccionada.aceite })
        });
        if (!d.success){ $('aceiteTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>'; return; }

        aceiteAll = d.data || [];
        renderAceiteTabla();
    }

    function renderAceiteTabla(){
        const q = ($('aceiteSearch')?.value || '').toLowerCase().trim();
        const items = aceiteAll.filter(u => {
            if (!q) return true;
            return `${u.id_unidad||''} ${u.marca_unidad||''} ${u.modelo_unidad||''} ${u.vim_unidad||''}`.toLowerCase().includes(q);
        });

        if (!items.length){
            $('aceiteTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Sin unidades</td></tr>';
            return;
        }

        $('aceiteTablaBody').innerHTML = items.map(u => {
            const km = parseInt(u.km_actual || 0);
            const uc = parseInt(u.ultimo_cambio_aceite || 0);
            const pc = parseInt(u.proximo_cambio_aceite || 0);
            const rest = pc - km;

            let badge = '<span class="badge badge-success">Bien</span>';
            if (pc <= 0) badge = '<span class="badge badge-secondary">Sin Registro</span>';
            else if (rest <= 0) badge = '<span class="badge badge-danger">Requerido</span>';
            else if (rest <= 1000) badge = '<span class="badge badge-warning">Próximo</span>';

            return `
                <tr>
                    <td><strong>${esc(u.id_unidad || '—')}</strong></td>
                    <td>${esc((u.marca_unidad||'') + ' ' + (u.modelo_unidad||''))}</td>
                    <td><code style="font-size:.72rem;">${esc(u.vim_unidad || '—')}</code></td>
                    <td class="text-right">${fmtNum(km, 0)}</td>
                    <td class="text-right">${fmtNum(uc, 0)}</td>
                    <td class="text-right">${fmtNum(pc, 0)}</td>
                    <td class="text-center">${badge}</td>
                </tr>
            `;
        }).join('');
    }

    /* ============================================================
       RESUMEN FLOTA
       ============================================================ */
    async function cargarResumen(){
        const d = await fetchJSON(API + '/resumen-flota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_institucion: instSeleccionada.resumen })
        });
        if (!d.success){ console.error('Error resumen:', d); return; }

        resumenAll = d.data || [];
        resumenSelected.clear();
        renderResumenTabla();
    }

    function keyResumen(r){ return `${r.marca_modelo}|${r.transmision}|${r.combustible}`; }

    function renderResumenTabla(){
        const q = ($('resumenSearch')?.value || '').toLowerCase().trim();
        const items = resumenAll.filter(r => {
            if (!q) return true;
            return `${r.marca_modelo||''} ${r.transmision||''} ${r.combustible||''}`.toLowerCase().includes(q);
        });

        if (!items.length){
            $('resumenBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Sin datos</td></tr>';
            $('btnPdfResumen').disabled = true;
            actualizarResumenSeleccionados();
            return;
        }

        $('resumenBody').innerHTML = items.map(r => {
            const k = keyResumen(r);
            const checked = resumenSelected.has(k) ? 'checked' : '';
            return `
                <tr data-key="${esc(k)}" style="cursor:pointer;" class="resumen-row ${checked ? 'selected' : ''}">
                    <td onclick="event.stopPropagation();">
                        <input type="checkbox" ${checked} data-key="${esc(k)}" class="resumen-check"
                            style="width:16px; height:16px; accent-color:#2563eb; cursor:pointer;">
                    </td>
                    <td><strong>${esc(r.marca_modelo)}</strong></td>
                    <td>${esc(r.transmision || '—')}</td>
                    <td>${esc(r.combustible || '—')}</td>
                    <td class="text-center">${r.total || 0}</td>
                    <td class="text-center" style="color:#16a34a; font-weight:600;">${r.operativas || 0}</td>
                    <td class="text-center" style="color:#dc2626; font-weight:600;">${r.inoperativas || 0}</td>
                </tr>
            `;
        }).join('');

        // Click en la fila entera → toggle checkbox
        document.querySelectorAll('#resumenBody .resumen-row').forEach(tr => {
            tr.addEventListener('click', () => {
                const k = tr.dataset.key;
                if (resumenSelected.has(k)) resumenSelected.delete(k);
                else resumenSelected.add(k);
                renderResumenTabla();
            });
        });

        // Click directo en el checkbox → toggle (sin doble trigger)
        document.querySelectorAll('#resumenBody .resumen-check').forEach(cb => {
            cb.addEventListener('change', e => {
                const k = e.target.dataset.key;
                if (e.target.checked) resumenSelected.add(k);
                else resumenSelected.delete(k);
                renderResumenTabla();
            });
        });

        actualizarResumenSeleccionados();
    }

    function toggleResumenAll(e){
        const checked = e.target.checked;
        // Sincronizar ambos checkboxes "seleccionar todos"
        if ($('resumenSelectAll')) $('resumenSelectAll').checked = checked;
        if ($('resumenSelectAllHeader')) $('resumenSelectAllHeader').checked = checked;

        if (checked){
            resumenAll.forEach(r => resumenSelected.add(keyResumen(r)));
        } else {
            resumenSelected.clear();
        }
        renderResumenTabla();
    }

    function actualizarResumenSeleccionados(){
        let total = 0, op = 0, inop = 0, crit = 0;
        const seleccionados = [];

        resumenAll.forEach(r => {
            if (resumenSelected.has(keyResumen(r))){
                seleccionados.push(r);
                total += parseInt(r.total || 0);
                op    += parseInt(r.operativas || 0);
                inop  += parseInt(r.inoperativas || 0);
                // crit = 0 (no hay dato de críticas en el modelo)
            }
        });

        // KPIs
        if ($('resumenSelTotal')) $('resumenSelTotal').textContent = total;
        if ($('resumenSelOp'))    $('resumenSelOp').textContent    = op;
        if ($('resumenSelInop'))  $('resumenSelInop').textContent  = inop;
        if ($('resumenSelCrit'))  $('resumenSelCrit').textContent  = crit;
        if ($('resumenSelCount')) $('resumenSelCount').textContent = `${seleccionados.length}`;

        // Lista
        const body = $('resumenSelectedBody');
        if (!body) return;

        if (!seleccionados.length){
            body.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:1rem; color:#94a3b8; font-style:italic;">Selecciona grupos de la tabla</td></tr>';
        } else {
            body.innerHTML = seleccionados.map(r => `
                <tr>
                    <td><strong>${esc(r.marca_modelo)}</strong><br><small style="color:#94a3b8;">${esc(r.transmision || '—')} / ${esc(r.combustible || '—')}</small></td>
                    <td class="text-center">${r.total || 0}</td>
                    <td class="text-center" style="color:#16a34a; font-weight:600;">${r.operativas || 0}</td>
                    <td class="text-center" style="color:#dc2626; font-weight:600;">${r.inoperativas || 0}</td>
                </tr>
            `).join('');
        }

        if ($('btnPdfResumen')) $('btnPdfResumen').disabled = seleccionados.length === 0;

        // Sincronizar checkbox maestro
        const allChecked = resumenAll.length > 0 && seleccionados.length === resumenAll.length;
        if ($('resumenSelectAll')) $('resumenSelectAll').checked = allChecked;
        if ($('resumenSelectAllHeader')) $('resumenSelectAllHeader').checked = allChecked;
    }

    /* ============================================================
       MOVIMIENTOS
       ============================================================ */
    async function cargarMovimientos(){
        $('movBody').innerHTML = '<tr><td colspan="8" class="dt-loading">Cargando...</td></tr>';

        const body = {
            fechaInicio: $('movFechaInicio').value,
            fechaFin: $('movFechaFin').value,
            tipoMovimiento: $('movTipo').value,
            id_institucion: instSeleccionada.movimientos
        };

        const d = await fetchJSON(API + '/movimientos-data', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        if (!d.success){
            $('movBody').innerHTML = '<tr><td colspan="8" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        movimientosAll = d.data || [];
        movCurrentPage = 1;
        renderMovimientos();
    }

    function renderMovimientos(){
        const q = ($('movSearch')?.value || '').toLowerCase().trim();
        movimientosFiltered = movimientosAll.filter(m => {
            if (!q) return true;
            return `${m.tipo||''} ${m.referencia||''} ${m.unidad||''} ${m.operador||''} ${m.mecanico||''}`.toLowerCase().includes(q);
        });

        const total = movimientosFiltered.length;
        const totalPages = Math.max(1, Math.ceil(total / movPerPage));
        if (movCurrentPage > totalPages) movCurrentPage = totalPages;
        const start = (movCurrentPage - 1) * movPerPage;
        const page = movimientosFiltered.slice(start, start + movPerPage);

        if (!page.length){
            $('movBody').innerHTML = '<tr><td colspan="8" class="dt-empty">Sin movimientos</td></tr>';
        } else {
            $('movBody').innerHTML = page.map((m, idx) => `
                <tr data-idx="${start + idx}" style="cursor:pointer;">
                    <td>${start + idx + 1}</td>
                    <td><span class="badge badge-info">${esc(m.tipo)}</span></td>
                    <td>${formatFecha(m.fecha)}</td>
                    <td><code style="font-size:.72rem;">${esc(m.referencia)}</code></td>
                    <td><strong>${esc(m.unidad)}</strong></td>
                    <td>${esc(m.operador || '—')}</td>
                    <td>${esc(m.mecanico || '—')}</td>
                    <td>${esc(m.estado || '—')}</td>
                </tr>
            `).join('');

            document.querySelectorAll('#movBody tr[data-idx]').forEach(tr => {
                tr.addEventListener('click', () => {
                    const idx = parseInt(tr.dataset.idx);
                    const m = movimientosFiltered[idx];
                    if (m) mostrarDetalleMovimiento(m);
                });
            });
        }

        $('movStart').textContent = total ? start + 1 : 0;
        $('movEnd').textContent = Math.min(start + movPerPage, total);
        $('movTotal').textContent = total;
        renderPaginacion();
    }

    function renderPaginacion(){
        const total = movimientosFiltered.length;
        const totalPages = Math.max(1, Math.ceil(total / movPerPage));
        let html = '';
        const btn = (p, icon, disabled, active) => `
            <button class="dt-page-btn ${active?'active':''}" data-page="${p}" ${disabled?'disabled':''}>
                ${icon ? `<i data-lucide="${icon}" class="w-4 h-4"></i>` : p}
            </button>
        `;
        html += btn(1, 'chevrons-left', movCurrentPage === 1, false);
        html += btn(movCurrentPage - 1, 'chevron-left', movCurrentPage === 1, false);
        paginas(movCurrentPage, totalPages).forEach(n => {
            if (n === '...') html += '<span class="dt-page-dots">…</span>';
            else html += btn(n, null, false, n === movCurrentPage);
        });
        html += btn(movCurrentPage + 1, 'chevron-right', movCurrentPage === totalPages, false);
        html += btn(totalPages, 'chevrons-right', movCurrentPage === totalPages, false);
        $('movPagination').innerHTML = html;
        $('movPagination').querySelectorAll('.dt-page-btn').forEach(b => {
            b.addEventListener('click', () => {
                if (b.disabled) return;
                movCurrentPage = parseInt(b.dataset.page);
                renderMovimientos();
            });
        });
        refreshIcons();
    }

    function paginas(current, total){
        if (total <= 7) return Array.from({length: total}, (_, i) => i + 1);
        if (current <= 4) return [1,2,3,4,5,'...',total];
        if (current >= total - 3) return [1,'...',total-4,total-3,total-2,total-1,total];
        return [1,'...',current-1,current,current+1,'...',total];
    }

    function mostrarDetalleMovimiento(m){
        selectedMov = m;
        const d = $('movDetalle');
        if (!d) return;

        const dt = m.tipo;
        const iconMap = { 'Despacho Almacén':'file-text', 'Mantenimiento Flota':'tools', 'Cambio Aceite':'droplet', 'Actualización KM':'gauge' };
        const colorMap = { 'Despacho Almacén':'#2563eb', 'Mantenimiento Flota':'#64748b', 'Cambio Aceite':'#f59e0b', 'Actualización KM':'#8b5cf6' };

        let extra = '';
        if (dt === 'Despacho Almacén' && m.id_despacho){
            extra = `<div style="margin-top:.75rem;"><button class="btn btn-primary btn-sm" id="movPdfDesp"><i data-lucide="file-text" class="w-4 h-4"></i><span>Generar PDF</span></button></div>`;
        }

        d.innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2rem; height:2rem; border-radius:.4rem; background:${colorMap[dt]||'#64748b'}22; color:${colorMap[dt]||'#64748b'};">
                    <i data-lucide="${iconMap[dt]||'circle'}" style="width:16px; height:16px;"></i>
                </span>
                <strong style="font-size:.88rem; color:#0f172a;">${esc(dt)}</strong>
            </div>
            <div style="font-size:.8rem; color:#475569; display:grid; gap:.4rem;">
                <div><strong>Referencia:</strong> <code>${esc(m.referencia)}</code></div>
                <div><strong>Fecha:</strong> ${formatFecha(m.fecha)}</div>
                <div><strong>Unidad:</strong> ${esc(m.unidad)}</div>
                <div><strong>Operador:</strong> ${esc(m.operador || '—')}</div>
                <div><strong>Mecánico:</strong> ${esc(m.mecanico || '—')}</div>
                <div><strong>Despachador:</strong> ${esc(m.despachador || '—')}</div>
                <div><strong>Estado:</strong> ${esc(m.estado || '—')}</div>
                <div style="margin-top:.4rem; padding:.5rem .65rem; background:#fff; border:1px solid #e2e8f0; border-radius:.35rem;"><strong>Observación:</strong> ${esc(m.observacion || 'Sin observaciones')}</div>
            </div>
            ${extra}
        `;
        refreshIcons();
        $('movPdfDesp')?.addEventListener('click', generarPdfDespacho);
    }

    /* ============================================================
       PDF · DESPACHO (orden individual)
       ============================================================ */
    async function generarPdfDespacho(){
        if (!selectedMov?.id_despacho) return;

        const d = await fetchJSON(API + '/detalle-orden', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDespacho: selectedMov.id_despacho })
        });
        if (!d.success){ alert('No se pudo obtener el despacho'); return; }

        const o = d.data;
        await SITGO_PDF.generar(
            'publico_movimientos',
            {
                orden: {
                    id_despacho: o.id_despacho,
                    numero_orden: o.numero_orden,
                    fecha_despacho: o.fecha_despacho,
                    id_unidad: o.id_unidad,
                    marca_unidad: o.marca_unidad,
                    modelo_unidad: o.modelo_unidad,
                    vim_unidad: o.vim_unidad,
                    operador_nombre: o.operador_nombre,
                    mecanico_nombre: o.mecanico_nombre,
                    despachador_nombre: o.despachador_nombre,
                    usuario_registro: o.usuario_registro,
                    observacion: o.observacion,
                    estado_orden: o.estado_orden,
                    articulos: o.productos || [],
                }
            },
            `despacho_${o.id_despacho}.pdf`,
            {
                titulo_documento: 'ORDEN DE DESPACHO',
                doc_color: '#1e40af',
                institucion: {
                    nombre: nombreInstitucion(instSeleccionada.movimientos) || 'SITGO',
                    rif: '',
                },
            }
        );
    }

    /* ============================================================
       ESTACIÓN
       ============================================================ */
    async function cargarEstacionesSelect(){
        const d = await fetchJSON(API + '/ventas-estacion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fechaInicio: $('estFechaInicio').value, fechaFin: $('estFechaFin').value, estacionId: 'todas' })
        });
        if (!d.success) return;

        const estaciones = d.estaciones || [];
        const sel = $('estEstacion');
        sel.innerHTML = '<option value="todas">Todas las estaciones</option>';
        estaciones.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id_estacion;
            opt.textContent = e.estacion;
            sel.appendChild(opt);
        });
    }

    async function cargarEstacion(){
        $('estBody').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/ventas-estacion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fechaInicio: $('estFechaInicio').value,
                fechaFin: $('estFechaFin').value,
                estacionId: $('estEstacion').value
            })
        });
        if (!d.success){
            $('estBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        estAll = d.data || [];
        estCurrentPage = 1;
        renderEstacion();
        renderEstacionChart();
    }

    function renderEstacion(){
        const q = ($('estSearch')?.value || '').toLowerCase().trim();
        estFiltered = estAll.filter(e => {
            if (!q) return true;
            return `${e.estacion||''} ${e.fecha||''}`.toLowerCase().includes(q);
        });

        const total = estFiltered.length;
        const totalPages = Math.max(1, Math.ceil(total / estPerPage));
        if (estCurrentPage > totalPages) estCurrentPage = totalPages;
        const start = (estCurrentPage - 1) * estPerPage;
        const page = estFiltered.slice(start, start + estPerPage);

        if (!page.length){
            $('estBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Sin datos</td></tr>';
        } else {
            $('estBody').innerHTML = page.map(e => `
                <tr>
                    <td>${formatFecha(e.fecha)}</td>
                    <td>${esc(e.estacion)}</td>
                    <td class="text-right"><strong>${fmtNum(e.total_litros, 2)} L</strong></td>
                    <td class="text-right">${e.total_ventas}</td>
                    <td class="text-right"><strong>${fmtNum(e.total_bs, 2)} Bs</strong></td>
                    <td class="text-right">
                        <button class="action-btn" data-est='${esc(JSON.stringify(e)).replace(/'/g, "&#39;")}' title="Ver detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </td>
                </tr>
            `).join('');

            document.querySelectorAll('#estBody [data-est]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const e = JSON.parse(btn.dataset.est.replace(/&#39;/g, "'"));
                    abrirModalDetalleEstacion(e);
                });
            });
        }

        $('estStart').textContent = total ? start + 1 : 0;
        $('estEnd').textContent = Math.min(start + estPerPage, total);
        $('estTotal').textContent = total;
        renderEstPag();
    }

    function renderEstPag(){
        const total = estFiltered.length;
        const totalPages = Math.max(1, Math.ceil(total / estPerPage));
        let html = '';
        const btn = (p, icon, disabled, active) => `
            <button class="dt-page-btn ${active?'active':''}" data-page="${p}" ${disabled?'disabled':''}>
                ${icon ? `<i data-lucide="${icon}" class="w-4 h-4"></i>` : p}
            </button>
        `;
        html += btn(1, 'chevrons-left', estCurrentPage === 1, false);
        html += btn(estCurrentPage - 1, 'chevron-left', estCurrentPage === 1, false);
        paginas(estCurrentPage, totalPages).forEach(n => {
            if (n === '...') html += '<span class="dt-page-dots">…</span>';
            else html += btn(n, null, false, n === estCurrentPage);
        });
        html += btn(estCurrentPage + 1, 'chevron-right', estCurrentPage === totalPages, false);
        html += btn(totalPages, 'chevrons-right', estCurrentPage === totalPages, false);
        $('estPagination').innerHTML = html;
        $('estPagination').querySelectorAll('.dt-page-btn').forEach(b => {
            b.addEventListener('click', () => {
                if (b.disabled) return;
                estCurrentPage = parseInt(b.dataset.page);
                renderEstacion();
            });
        });
        refreshIcons();
    }

    function renderEstacionChart(){
        const canvas = $('estChart');
        const empty = $('estChartEmpty');
        const type = $('estChartType').value;

        if (!estAll.length){
            canvas.style.display = 'none';
            empty.style.display = 'block';
            if (estChart) { estChart.destroy(); estChart = null; }
            return;
        }
        canvas.style.display = 'block';
        empty.style.display = 'none';
        if (estChart) { estChart.destroy(); estChart = null; }

        let labels = [], data = [], label = '', colors = [];

        if (type === 'litros_dia'){
            const map = {};
            estAll.forEach(e => { map[e.fecha] = (map[e.fecha] || 0) + parseFloat(e.total_litros); });
            labels = Object.keys(map).sort();
            data = labels.map(f => map[f]);
            label = 'Litros';
            colors = labels.map(() => 'rgba(37,99,235,.7)');
        } else if (type === 'ventas_dia'){
            const map = {};
            estAll.forEach(e => { map[e.fecha] = (map[e.fecha] || 0) + parseInt(e.total_ventas); });
            labels = Object.keys(map).sort();
            data = labels.map(f => map[f]);
            label = 'Ventas';
            colors = labels.map(() => 'rgba(22,163,74,.7)');
        } else if (type === 'litros_tipo_vehiculo'){
            const map = {};
            estAll.forEach(e => (e.tipos_vehiculo||[]).forEach(t => { map[t.tipo] = (map[t.tipo]||0) + parseFloat(t.litros); }));
            labels = Object.keys(map);
            data = labels.map(l => map[l]);
            label = 'Litros por vehículo';
            colors = labels.map((_, i) => `hsl(${i*60}, 70%, 55%)`);
        } else if (type === 'litros_tipo_combustible'){
            const map = {};
            estAll.forEach(e => {
                if (parseFloat(e.litros_gasolina) > 0) map['Gasolina'] = (map['Gasolina']||0) + parseFloat(e.litros_gasolina);
                if (parseFloat(e.litros_diesel) > 0) map['Diésel'] = (map['Diésel']||0) + parseFloat(e.litros_diesel);
            });
            labels = Object.keys(map);
            data = labels.map(l => map[l]);
            label = 'Litros';
            colors = ['#f59e0b', '#0f172a'];
        } else if (type === 'ventas_vendedor'){
            const map = {};
            estAll.forEach(e => (e.vendedores||[]).forEach(v => { map[v.nombre] = (map[v.nombre]||0) + parseInt(v.ventas); }));
            labels = Object.keys(map);
            data = labels.map(l => map[l]);
            label = 'Ventas';
            colors = labels.map((_, i) => `hsl(${i*40}, 70%, 55%)`);
        } else if (type === 'comparativo_estaciones'){
            const map = {};
            estAll.forEach(e => { map[e.estacion] = (map[e.estacion]||0) + parseFloat(e.total_litros); });
            labels = Object.keys(map);
            data = labels.map(l => map[l]);
            label = 'Litros';
            colors = labels.map((_, i) => `hsl(${i*80}, 70%, 55%)`);
        }

        estChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels,
                datasets: [{ label, data, backgroundColor: colors, borderColor: colors.map(c => c.replace('0.7', '1')), borderWidth: 1 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => label.includes('Litros') ? `${ctx.raw.toLocaleString('es-VE')} L` : `${ctx.raw}`
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: v => label.includes('Litros') ? v.toLocaleString('es-VE') + ' L' : v } }
                }
            }
        });
    }

    /* ============================================================
       MODAL DETALLE ESTACIÓN
       ============================================================ */
    function bindModalDetalleEstacion(){
        const modal = $('modalDetalleEstacion');
        if (!modal) return;
        modal.addEventListener('click', e => {
            if (e.target === modal || e.target.closest('[data-close]')){
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape'){
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
        });
    }

    function abrirModalDetalleEstacion(item){
        $('modalDetalleTitle').textContent = `Ventas · ${formatFecha(item.fecha)} · ${item.estacion}`;
        const ventas = item.ventas || [];

        let html = `
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.5rem; margin-bottom:1rem;">
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:.5rem; padding:.6rem .75rem;">
                    <div style="font-size:.65rem; color:#64748b; text-transform:uppercase; font-weight:700;">Ventas</div>
                    <div style="font-size:1.2rem; font-weight:800; color:#1e40af;">${item.total_ventas}</div>
                </div>
                <div style="background:#dcfce7; border:1px solid #bbf7d0; border-radius:.5rem; padding:.6rem .75rem;">
                    <div style="font-size:.65rem; color:#64748b; text-transform:uppercase; font-weight:700;">Litros</div>
                    <div style="font-size:1.2rem; font-weight:800; color:#166534;">${fmtNum(item.total_litros, 2)} L</div>
                </div>
                <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:.5rem; padding:.6rem .75rem;">
                    <div style="font-size:.65rem; color:#64748b; text-transform:uppercase; font-weight:700;">Total Bs</div>
                    <div style="font-size:1.2rem; font-weight:800; color:#b45309;">${fmtNum(item.total_bs, 2)}</div>
                </div>
            </div>
        `;

        if ((item.vendedores||[]).length){
            html += `<div style="margin-bottom:1rem;"><strong style="font-size:.85rem; color:#0f172a;">Vendedores:</strong><div style="display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.35rem;">`;
            item.vendedores.forEach(v => {
                html += `<span class="badge badge-info">${esc(v.nombre)} · ${v.ventas} ventas (${fmtNum(v.litros, 2)} L)</span>`;
            });
            html += '</div></div>';
        }

        html += `<h4 style="margin:1rem 0 .5rem; font-size:.85rem; font-weight:700;">Detalle de tickets (${ventas.length})</h4>`;
        html += '<div class="table-wrap" style="max-height:400px;"><table class="pub-tabla"><thead><tr><th>Ticket</th><th>Hora</th><th>Vendedor</th><th>Vehículo</th><th>Combustible</th><th class="text-right">Litros</th><th class="text-right">Monto</th></tr></thead><tbody>';

        if (!ventas.length){
            html += '<tr><td colspan="7" class="dt-empty">Sin ventas</td></tr>';
        } else {
            ventas.forEach(v => {
                const simbolo = (v.tipo_pago||'').toLowerCase().includes('divisa') ? '$' : 'Bs';
                html += `
                    <tr>
                        <td><strong>#${v.id_venta}</strong></td>
                        <td>${esc(v.hora_venta || '—')}</td>
                        <td>${esc(v.vendedor)}</td>
                        <td>${esc(v.tipo_vehiculo)}</td>
                        <td>${esc(v.tipo_combustible)}</td>
                        <td class="text-right">${fmtNum(v.litros, 2)}</td>
                        <td class="text-right"><strong>${fmtNum(v.monto, 2)} ${simbolo}</strong></td>
                    </tr>
                `;
            });
        }
        html += '</tbody></table></div>';

        $('modalDetalleBody').innerHTML = html;
        $('modalDetalleEstacion').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    /* ============================================================
       PDF · ACEITE
       ============================================================ */
    async function generarPdfAceite(modo = 'por_estado'){
        let items;
        let filtroTexto;

        if (modo === 'por_estado'){
            if (!aceiteStatusActual){
                alert('Selecciona primero un estado (Bien / Próximo / Requerido)');
                return;
            }
            if (!aceiteAll.length){
                alert('No hay unidades para el estado seleccionado');
                return;
            }
            items = aceiteAll;
            const mapa = { 'ok':'Bien', 'proximo':'Próximo', 'requerido':'Requerido' };
            filtroTexto = mapa[aceiteStatusActual] || aceiteStatusActual;
        } else {
            const d = await fetchJSON(API + '/unidades-por-estado-aceite', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'todos', id_institucion: instSeleccionada.aceite })
            });
            if (!d.success || !d.data || !d.data.length){
                alert('No hay unidades para generar el reporte');
                return;
            }
            items = d.data;
            filtroTexto = 'Completo (todas las unidades)';
        }

        await SITGO_PDF.generar(
            'publico_aceite',
            {
                items,
                filtro: filtroTexto,
                modo,
                institucion_nombre: nombreInstitucion(instSeleccionada.aceite),
            },
            `aceite_${modo === 'completo' ? 'completo' : aceiteStatusActual}_${new Date().toISOString().slice(0,10)}.pdf`,
            {
                titulo_documento: 'REPORTE DE ACEITE',
                doc_color: '#f59e0b',
                institucion: {
                    nombre: nombreInstitucion(instSeleccionada.aceite) || 'SITGO',
                    rif: '',
                },
            }
        );
    }

    /* ============================================================
       PDF · RESUMEN FLOTA (operatividad)
       ============================================================ */
    async function generarPdfResumen(){
        if (resumenSelected.size === 0){
            alert('Selecciona al menos un grupo');
            return;
        }
        const items = resumenAll.filter(r => resumenSelected.has(keyResumen(r))).map(r => ({
            groupName: r.marca_modelo + ' / ' + (r.transmision || '—') + ' / ' + (r.combustible || '—'),
            cantidad: parseInt(r.total || 0),
            operativas: parseInt(r.operativas || 0),
            inoperativas: parseInt(r.inoperativas || 0),
            criticas: 0,
        }));

        await SITGO_PDF.generar(
            'flota_operatividad',
            { summaryData: items },
            `operatividad_${new Date().toISOString().slice(0,10)}.pdf`,
            {
                titulo_documento: 'OPERATIVIDAD DE FLOTA',
                doc_color: '#1e40af',
                institucion: {
                    nombre: nombreInstitucion(instSeleccionada.resumen) || 'SITGO',
                    rif: '',
                },
            }
        );
    }

    /* ============================================================
       PDF · VENTAS ESTACIÓN (con header fijo "ABASTECIMIENTO")
       ============================================================ */
    async function generarPdfEstacion(){
        if (!estAll.length){
            alert('No hay datos para exportar');
            return;
        }

        const sel = $('estEstacion');
        const nombreEst = sel.value === 'todas' ? 'Todas las estaciones' : (sel.options[sel.selectedIndex].text || 'N/D');

        await SITGO_PDF.generar(
            'publico_ventas_estacion',
            {
                data: estAll,
                fechaInicio: $('estFechaInicio').value,
                fechaFin: $('estFechaFin').value,
                estacionNombre: nombreEst,
                institucion_nombre: nombreInstitucion(instSeleccionada.movimientos),
            },
            `ventas_estacion_${$('estFechaInicio').value}_${$('estFechaFin').value}.pdf`,
            {
                titulo_documento: 'VENTAS DE ESTACIÓN',
                doc_color: '#1e40af',
                mostrar_header: false,   // ← única excepción: header fijo "SERVICIO SOCIALISTA DE ABASTECIMIENTO..."
            }
        );
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    function formatFecha(f){
        if (!f) return '—';
        try {
            if (/^\d{4}-\d{2}-\d{2}/.test(f)){
                const d = new Date(f.replace(' ', 'T'));
                if (!isNaN(d.getTime())) return d.toLocaleDateString('es-VE', {day:'2-digit', month:'2-digit', year:'numeric'});
            }
            return f;
        } catch { return f; }
    }
    function fmtNum(v, dec = 2){
        const n = parseFloat(v);
        if (!isFinite(n)) return (0).toFixed(dec).replace('.', ',');
        return n.toLocaleString('es-VE', { minimumFractionDigits: dec, maximumFractionDigits: dec });
    }
    function esc(s){
        if (s == null) return '';
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }
});