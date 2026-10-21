document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'movimientos';
    const $ = id => document.getElementById(id);

    let instituciones = [];
    let instSeleccionada = { flota: null, aceite: null, resumen: null, movimientos: null };

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

        if ($('movFechaInicio')) $('movFechaInicio').value = fmt(hace30);
        if ($('movFechaFin'))    $('movFechaFin').value    = fmt(hoy);
        if ($('estFechaInicio')) $('estFechaInicio').value = fmt(hace30);
        if ($('estFechaFin'))    $('estFechaFin').value    = fmt(hoy);
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

        instSeleccionada.flota        = parseInt(instituciones[0]?.id_institucion ?? 1);
        instSeleccionada.aceite       = instSeleccionada.flota;
        instSeleccionada.resumen      = instSeleccionada.flota;
        instSeleccionada.movimientos  = instSeleccionada.flota;
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
            if ($('flotaTablaWrap')) $('flotaTablaWrap').style.display = 'none';
            cargarFlota();
        });
        $('instAceite')?.addEventListener('change', e => {
            instSeleccionada.aceite = parseInt(e.target.value);
            aceiteStatusActual = null;
            if ($('aceiteTablaWrap')) $('aceiteTablaWrap').style.display = 'none';
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

        /* Nav interno con offset */
        document.querySelectorAll('.publico-nav-links a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                const href = link.getAttribute('href');
                const target = document.querySelector(href);
                if (!target) return;
                e.preventDefault();

                const nav = document.querySelector('.publico-nav');
                const navHeight = nav ? nav.offsetHeight : 0;
                const extraOffset = 12;
                const y = target.getBoundingClientRect().top + window.pageYOffset - navHeight - extraOffset;

                window.scrollTo({ top: y, behavior: 'smooth' });

                document.querySelectorAll('.publico-nav-links a').forEach(a => a.classList.remove('active'));
                link.classList.add('active');
            });
        });

        /* Flota */
        document.querySelectorAll('#flotaStats .pub-stat[data-status]').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('#flotaStats .pub-stat').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                flotaStatusActual = card.dataset.status;
                cargarUnidadesPorEstado(flotaStatusActual);
            });
        });
        $('flotaSearch')?.addEventListener('input', renderFlotaTabla);

        /* Aceite */
        document.querySelectorAll('.pub-stat[data-aceite]').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.pub-stat[data-aceite]').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                aceiteStatusActual = card.dataset.aceite;
                if ($('btnPdfAceite')) $('btnPdfAceite').disabled = false;
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
        $('movTipo')?.addEventListener('change', () => {
            movCurrentPage = 1;
            cargarMovimientos();
        });
        $('btnMovFiltrar')?.addEventListener('click', () => {
            movCurrentPage = 1;
            cargarMovimientos();
        });
        $('movFechaInicio')?.addEventListener('change', () => {
            $('btnMovFiltrar')?.classList.add('has-changes');
        });
        $('movFechaFin')?.addEventListener('change', () => {
            $('btnMovFiltrar')?.classList.add('has-changes');
        });
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
        if ($('flotaOperativas'))   $('flotaOperativas').textContent   = r.operativas || 0;
        if ($('flotaInoperativas')) $('flotaInoperativas').textContent = r.inoperativas || 0;
        if ($('flotaMantenimiento')) $('flotaMantenimiento').textContent = r.en_mantenimiento || 0;
        if ($('flotaCriticas'))     $('flotaCriticas').textContent     = r.criticas || 0;
        if ($('flotaTotal'))        $('flotaTotal').textContent        = r.total || 0;
    }

    async function cargarUnidadesPorEstado(status){
        if ($('flotaTablaWrap')) $('flotaTablaWrap').style.display = 'block';
        if ($('flotaTablaBody')) $('flotaTablaBody').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/unidades-por-estado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status, id_institucion: instSeleccionada.flota })
        });
        if (!d.success){
            if ($('flotaTablaBody')) $('flotaTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        flotaAll = d.data || [];
        renderFlotaTabla();
    }

    function renderFlotaTabla(){
        if (!$('flotaTablaBody')) return;
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
        if ($('aceiteOk'))        $('aceiteOk').textContent        = r.ok || 0;
        if ($('aceiteProximo'))   $('aceiteProximo').textContent   = r.proximo || 0;
        if ($('aceiteRequerido')) $('aceiteRequerido').textContent = r.requerido || 0;
    }

    async function cargarUnidadesPorAceite(status){
        if ($('aceiteTablaWrap')) $('aceiteTablaWrap').style.display = 'block';
        if ($('aceiteTablaBody')) $('aceiteTablaBody').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/unidades-por-estado-aceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status, id_institucion: instSeleccionada.aceite })
        });
        if (!d.success){
            if ($('aceiteTablaBody')) $('aceiteTablaBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        aceiteAll = d.data || [];
        renderAceiteTabla();
    }

    function renderAceiteTabla(){
        if (!$('aceiteTablaBody')) return;
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
        if (!$('resumenBody')) return;
        const q = ($('resumenSearch')?.value || '').toLowerCase().trim();
        const items = resumenAll.filter(r => {
            if (!q) return true;
            return `${r.marca_modelo||''} ${r.transmision||''} ${r.combustible||''}`.toLowerCase().includes(q);
        });

        if (!items.length){
            $('resumenBody').innerHTML = '<tr><td colspan="7" class="dt-empty">Sin datos</td></tr>';
            if ($('btnPdfResumen')) $('btnPdfResumen').disabled = true;
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

        document.querySelectorAll('#resumenBody .resumen-row').forEach(tr => {
            tr.addEventListener('click', () => {
                const k = tr.dataset.key;
                if (resumenSelected.has(k)) resumenSelected.delete(k);
                else resumenSelected.add(k);
                renderResumenTabla();
            });
        });

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
            }
        });

        if ($('resumenSelTotal')) $('resumenSelTotal').textContent = total;
        if ($('resumenSelOp'))    $('resumenSelOp').textContent    = op;
        if ($('resumenSelInop'))  $('resumenSelInop').textContent  = inop;
        if ($('resumenSelCrit'))  $('resumenSelCrit').textContent  = crit;
        if ($('resumenSelCount')) $('resumenSelCount').textContent = `${seleccionados.length}`;

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

        const allChecked = resumenAll.length > 0 && seleccionados.length === resumenAll.length;
        if ($('resumenSelectAll')) $('resumenSelectAll').checked = allChecked;
        if ($('resumenSelectAllHeader')) $('resumenSelectAllHeader').checked = allChecked;
    }

    /* ============================================================
       MOVIMIENTOS · CARGA
       ============================================================ */
    async function cargarMovimientos(){
        if (!$('movBody')) return;
        $('movBody').innerHTML = '<tr><td colspan="8" class="dt-loading">Cargando...</td></tr>';

        const body = {
            fechaInicio: $('movFechaInicio')?.value || '',
            fechaFin: $('movFechaFin')?.value || '',
            tipoMovimiento: $('movTipo')?.value || 'todos',
            id_institucion: instSeleccionada.movimientos
        };

        const d = await fetchJSON(API + '/movimientos-data', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });

        $('btnMovFiltrar')?.classList.remove('has-changes');

        if (!d.success){
            $('movBody').innerHTML = '<tr><td colspan="8" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        movimientosAll = d.data || [];
        movCurrentPage = 1;
        renderMovimientos();
    }

    function renderMovimientos(){
        if (!$('movBody')) return;

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
            $('movBody').innerHTML = page.map((m, idx) => {
                const realIdx = start + idx;

                let refHtml = `<code style="font-size:.72rem;">${esc(m.referencia)}</code>`;
                if (m.id_despacho){
                    refHtml = `<a href="javascript:void(0)" data-mov-action="detalle" data-mov-idx="${realIdx}" style="color:#1d4ed8; font-weight:600; text-decoration:none;"><i data-lucide="file-text" class="w-3 h-3" style="display:inline; vertical-align:middle;"></i> ${esc(m.referencia)}</a>`;
                } else if (m.id_aceite){
                    refHtml = `<a href="javascript:void(0)" data-mov-action="detalle" data-mov-idx="${realIdx}" style="color:#b45309; font-weight:600; text-decoration:none;"><i data-lucide="droplet" class="w-3 h-3" style="display:inline; vertical-align:middle;"></i> ${esc(m.referencia)}</a>`;
                } else if (m.id_mantenimiento){
                    refHtml = `<a href="javascript:void(0)" data-mov-action="detalle" data-mov-idx="${realIdx}" style="color:#334155; font-weight:600; text-decoration:none;"><i data-lucide="tools" class="w-3 h-3" style="display:inline; vertical-align:middle;"></i> ${esc(m.referencia)}</a>`;
                } else if (m.id_kilometraje){
                    refHtml = `<a href="javascript:void(0)" data-mov-action="detalle" data-mov-idx="${realIdx}" style="color:#6d28d9; font-weight:600; text-decoration:none;"><i data-lucide="gauge" class="w-3 h-3" style="display:inline; vertical-align:middle;"></i> ${esc(m.referencia)}</a>`;
                }

                let unidadHtml = `<strong>${esc(m.unidad || '—')}</strong>`;
                if (m.id_flota){
                    unidadHtml = `<a href="javascript:void(0)" data-mov-action="historial" data-mov-idx="${realIdx}" style="color:#1d4ed8; font-weight:700; text-decoration:none;"><i data-lucide="bus" class="w-3 h-3" style="display:inline; vertical-align:middle;"></i> ${esc(m.unidad)}</a>`;
                }

                return `
                    <tr data-idx="${realIdx}" style="cursor:pointer;">
                        <td>${realIdx + 1}</td>
                        <td><span class="badge badge-info">${esc(m.tipo)}</span></td>
                        <td>${formatFecha(m.fecha)}</td>
                        <td>${refHtml}</td>
                        <td>${unidadHtml}</td>
                        <td>${esc(m.operador || '—')}</td>
                        <td>${esc(m.mecanico || '—')}</td>
                        <td>${esc(m.estado || '—')}</td>
                    </tr>
                `;
            }).join('');

            document.querySelectorAll('#movBody [data-mov-action]').forEach(a => {
                a.addEventListener('click', e => {
                    e.stopPropagation();
                    const action = a.dataset.movAction;
                    const idx = parseInt(a.dataset.movIdx);
                    const m = movimientosFiltered[idx];
                    if (!m) return;
                    if (action === 'detalle')   mostrarDetalleMovimiento(m);
                    if (action === 'historial') mostrarHistorialUnidad(m);
                });
            });

            document.querySelectorAll('#movBody tr[data-idx]').forEach(tr => {
                tr.addEventListener('click', e => {
                    if (e.target.closest('[data-mov-action]')) return;
                    const idx = parseInt(tr.dataset.idx);
                    const m = movimientosFiltered[idx];
                    if (m) mostrarDetalleMovimiento(m);
                });
            });
        }

        if ($('movStart')) $('movStart').textContent = total ? start + 1 : 0;
        if ($('movEnd'))   $('movEnd').textContent   = Math.min(start + movPerPage, total);
        if ($('movTotal')) $('movTotal').textContent = total;
        renderPaginacion();
        refreshIcons();
    }

    function renderPaginacion(){
        if (!$('movPagination')) return;
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

    /* ============================================================
       PANEL · DETALLE SEGÚN TIPO
       ============================================================ */
    function mostrarCargandoPanel(){
        if (!$('movDetalle')) return;
        $('movDetalle').innerHTML = `
            <div style="text-align:center; padding:3rem 1rem; color:#94a3b8;">
                <div class="dt-loading" style="font-size:.9rem;">Cargando detalle...</div>
            </div>`;
    }

    async function mostrarDetalleMovimiento(m){
        if (!$('movDetalle')) return;
        selectedMov = m;
        mostrarCargandoPanel();

        const tipo = m.tipo;
        if (tipo === 'Despacho Almacén' && m.id_despacho){
            await panelDespacho(m);
        } else if (tipo === 'Cambio Aceite' && m.id_aceite){
            await panelAceite(m);
        } else if (tipo === 'Mantenimiento Flota' && m.id_mantenimiento){
            await panelMantenimiento(m);
        } else if (tipo === 'Actualización KM' && m.id_kilometraje){
            await panelKilometraje(m);
        } else {
            renderPanelBasico(m);
        }
        refreshIcons();
    }

    function renderPanelBasico(m){
        if (!$('movDetalle')) return;
        const iconMap = { 'Despacho Almacén':'file-text', 'Mantenimiento Flota':'tools', 'Cambio Aceite':'droplet', 'Actualización KM':'gauge' };
        const colorMap = { 'Despacho Almacén':'#2563eb', 'Mantenimiento Flota':'#64748b', 'Cambio Aceite':'#f59e0b', 'Actualización KM':'#8b5cf6' };
        const dt = m.tipo;

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:${colorMap[dt]||'#64748b'}22; color:${colorMap[dt]||'#64748b'};">
                    <i data-lucide="${iconMap[dt]||'circle'}" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">${esc(dt)}</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(m.referencia)}</code>
                </div>
            </div>
            <div style="font-size:.82rem; color:#475569; display:grid; gap:.5rem;">
                <div><strong>Fecha:</strong> ${formatFecha(m.fecha)}</div>
                <div><strong>Unidad:</strong> ${esc(m.unidad || '—')}</div>
                <div><strong>Estado:</strong> ${esc(m.estado || '—')}</div>
                <div style="margin-top:.4rem; padding:.65rem .85rem; background:#fff; border:1px solid #e2e8f0; border-radius:.4rem;"><strong>Observación:</strong><br>${esc(m.observacion || 'Sin observaciones')}</div>
            </div>
        `;
    }

    async function panelDespacho(m){
        const d = await fetchJSON(API + '/detalle-orden', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idDespacho: m.id_despacho })
        });
        if (!d.success){ renderPanelBasico(m); return; }

        const o = d.data;
        const arts = o.productos || [];

        let artHtml = '';
        if (arts.length){
            artHtml = arts.map(a => `
                <tr>
                    <td class="text-upper" style="font-size:.75rem;">${esc(a.producto || '')}</td>
                    <td class="text-center" style="font-size:.75rem; font-weight:600;">${esc(a.cant_despacho || '')}</td>
                </tr>
            `).join('');
        } else {
            artHtml = '<tr><td colspan="2" class="text-center" style="color:#94a3b8; padding:.75rem;">Sin artículos</td></tr>';
        }

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:#2563eb22; color:#2563eb;">
                    <i data-lucide="file-text" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">Orden de Despacho</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(m.referencia)}</code>
                </div>
            </div>

            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:.5rem; padding:.65rem .85rem; margin-bottom:.75rem;">
                <div style="font-size:.65rem; color:#1e40af; text-transform:uppercase; font-weight:700; margin-bottom:.3rem;">Unidad</div>
                <strong style="font-size:.9rem; color:#0f172a;">${esc(o.id_unidad || '—')}</strong>
                <div style="font-size:.75rem; color:#475569;">${esc(o.marca_unidad || '')} ${esc(o.modelo_unidad || '')}</div>
                <div style="font-size:.72rem; color:#64748b; margin-top:.2rem;"><strong>VIN:</strong> <code>${esc(o.vim_unidad || '—')}</code></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.75rem; font-size:.75rem;">
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Operador</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(o.operador_nombre || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Mecánico</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(o.mecanico_nombre || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem; grid-column:1/-1;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Despachador</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(o.despachador_nombre || '—')}</div>
                </div>
            </div>

            ${o.observacion ? `<div style="background:#f8fafc; border-left:3px solid #cbd5e1; padding:.5rem .75rem; border-radius:.25rem; margin-bottom:.75rem; font-size:.78rem; color:#475569; font-style:italic;">${esc(o.observacion)}</div>` : ''}

            <div style="margin-bottom:.75rem;">
                <div style="font-size:.75rem; font-weight:700; color:#334155; margin-bottom:.35rem;">Artículos despachados (${arts.length})</div>
                <div style="max-height:180px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:.35rem;">
                    <table class="pub-tabla" style="font-size:.75rem;">
                        <thead><tr><th>Producto</th><th class="text-center" style="width:70px;">Cant.</th></tr></thead>
                        <tbody>${artHtml}</tbody>
                    </table>
                </div>
            </div>

            <button class="btn btn-primary btn-sm" id="movPdfDesp" style="width:100%; justify-content:center;">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Generar PDF de la Orden</span>
            </button>
        `;

        $('movPdfDesp')?.addEventListener('click', generarPdfDespacho);
    }

    async function panelAceite(m){
        const d = await fetchJSON(API + '/detalle-aceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idAceite: m.id_aceite, id_institucion: instSeleccionada.movimientos })
        });
        if (!d.success){ renderPanelBasico(m); return; }

        const a = d.data;
        const kmCambio = parseInt(a.kilometraje_cambio || 0);
        const kmAnterior = parseInt(a.kilometraje_anterior || 0);
        const kmProximo = parseInt(a.kilometraje_proximo_cambio || 0);
        const diff = kmProximo > 0 && kmCambio > 0 ? kmProximo - kmCambio : 0;

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:#f59e0b22; color:#f59e0b;">
                    <i data-lucide="droplet" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">Cambio de Aceite</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(m.referencia)}</code>
                </div>
            </div>

            <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:.5rem; padding:.65rem .85rem; margin-bottom:.75rem;">
                <div style="font-size:.65rem; color:#b45309; text-transform:uppercase; font-weight:700; margin-bottom:.3rem;">Unidad</div>
                <strong style="font-size:.9rem; color:#0f172a;">${esc(a.id_unidad || '—')}</strong>
                <div style="font-size:.72rem; color:#64748b; margin-top:.2rem;"><strong>VIN:</strong> <code>${esc(a.vim_unidad || '—')}</code></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.75rem; font-size:.75rem;">
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Fecha cambio</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${formatFecha(a.fecha_cambio)}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Responsable</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem; font-size:.72rem;">${esc(a.responsable_nombre || 'Sistema')}</div>
                </div>
            </div>

            <div style="font-size:.75rem; font-weight:700; color:#334155; margin-bottom:.35rem;">Kilometrajes</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.4rem; margin-bottom:.75rem;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">KM Anterior</div>
                    <div style="font-size:.95rem; font-weight:800; color:#475569;">${kmAnterior ? kmAnterior.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">KM Cambio</div>
                    <div style="font-size:.95rem; font-weight:800; color:#b45309;">${kmCambio ? kmCambio.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Próximo</div>
                    <div style="font-size:.95rem; font-weight:800; color:#1e40af;">${kmProximo ? kmProximo.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:${diff <= 0 ? '#fee2e2' : (diff <= 1000 ? '#fef3c7' : '#dcfce7')}; border:1px solid ${diff <= 0 ? '#fecaca' : (diff <= 1000 ? '#fde68a' : '#bbf7d0')}; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Restantes</div>
                    <div style="font-size:.95rem; font-weight:800; color:${diff <= 0 ? '#991b1b' : (diff <= 1000 ? '#b45309' : '#166534')};">${diff > 0 ? diff.toLocaleString('es-VE') : '0'}</div>
                </div>
            </div>

            ${a.observaciones ? `<div style="background:#f8fafc; border-left:3px solid #f59e0b; padding:.5rem .75rem; border-radius:.25rem; font-size:.78rem; color:#475569; font-style:italic;"><strong>Observaciones:</strong><br>${esc(a.observaciones)}</div>` : ''}
        `;
    }

    async function panelMantenimiento(m){
        const d = await fetchJSON(API + '/detalle-mantenimiento', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idMantenimiento: m.id_mantenimiento, id_institucion: instSeleccionada.movimientos })
        });
        if (!d.success){ renderPanelBasico(m); return; }

        const x = d.data;
        const tipo = (x.tipo_mantenimiento === 'c' || x.tipo_mantenimiento === 'C') ? 'Correctivo' : 'Preventivo';

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:#64748b22; color:#64748b;">
                    <i data-lucide="tools" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">Mantenimiento ${tipo}</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(m.referencia)}</code>
                </div>
            </div>

            <div style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:.5rem; padding:.65rem .85rem; margin-bottom:.75rem;">
                <div style="font-size:.65rem; color:#475569; text-transform:uppercase; font-weight:700; margin-bottom:.3rem;">Unidad</div>
                <strong style="font-size:.9rem; color:#0f172a;">${esc(x.id_unidad || '—')}</strong>
                <div style="font-size:.72rem; color:#64748b; margin-top:.2rem;"><strong>VIN:</strong> <code>${esc(x.vim_unidad || '—')}</code></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.75rem; font-size:.75rem;">
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Entrada</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${formatFecha(x.fecha_entrada)}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Salida</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${x.fecha_salida ? formatFecha(x.fecha_salida) : 'Pendiente'}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Operador</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(x.operardor_unidad || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Mecánico</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(x.nomb_mecanico || '—')}</div>
                </div>
            </div>

            ${x.diagnostico ? `<div style="background:#f8fafc; border-left:3px solid #64748b; padding:.5rem .75rem; border-radius:.25rem; margin-bottom:.5rem; font-size:.78rem;"><strong>Diagnóstico:</strong><br>${esc(x.diagnostico)}</div>` : ''}
            ${x.recomendacion ? `<div style="background:#f8fafc; border-left:3px solid #16a34a; padding:.5rem .75rem; border-radius:.25rem; margin-bottom:.5rem; font-size:.78rem;"><strong>Recomendación:</strong><br>${esc(x.recomendacion)}</div>` : ''}
            ${x.obsOperador ? `<div style="background:#f8fafc; border-left:3px solid #3b82f6; padding:.5rem .75rem; border-radius:.25rem; margin-bottom:.5rem; font-size:.78rem;"><strong>Obs. Operador:</strong><br>${esc(x.obsOperador)}</div>` : ''}
            ${x.obsSupervisor ? `<div style="background:#f8fafc; border-left:3px solid #8b5cf6; padding:.5rem .75rem; border-radius:.25rem; margin-bottom:.5rem; font-size:.78rem;"><strong>Obs. Supervisor:</strong><br>${esc(x.obsSupervisor)}</div>` : ''}
        `;
    }

    async function panelKilometraje(m){
        const d = await fetchJSON(API + '/detalle-kilometraje', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idKilometraje: m.id_kilometraje, id_institucion: instSeleccionada.movimientos })
        });
        if (!d.success){ renderPanelBasico(m); return; }

        const x = d.data;
        const kmActual = parseInt(x.kilometraje_actual || 0);
        const kmAnterior = parseInt(x.kilometraje_anterior || 0);
        const recorrido = kmActual > 0 && kmAnterior > 0 ? kmActual - kmAnterior : 0;

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:#8b5cf622; color:#8b5cf6;">
                    <i data-lucide="gauge" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">Actualización de KM</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(m.referencia)}</code>
                </div>
            </div>

            <div style="background:#ede9fe; border:1px solid #ddd6fe; border-radius:.5rem; padding:.65rem .85rem; margin-bottom:.75rem;">
                <div style="font-size:.65rem; color:#6d28d9; text-transform:uppercase; font-weight:700; margin-bottom:.3rem;">Unidad</div>
                <strong style="font-size:.9rem; color:#0f172a;">${esc(x.id_unidad || '—')}</strong>
                <div style="font-size:.72rem; color:#64748b; margin-top:.2rem;"><strong>VIN:</strong> <code>${esc(x.vim_unidad || '—')}</code></div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-bottom:.75rem; font-size:.75rem;">
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Fecha</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${formatFecha(x.fecha_actualizacion)}</div>
                </div>
                <div style="background:#f8fafc; padding:.5rem .65rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Registrado por</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem; font-size:.72rem;">${esc(x.responsable_nombre || 'Sistema')}</div>
                </div>
            </div>

            <div style="font-size:.75rem; font-weight:700; color:#334155; margin-bottom:.35rem;">Lecturas de kilometraje</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:.4rem;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Anterior</div>
                    <div style="font-size:.9rem; font-weight:800; color:#475569;">${kmAnterior ? kmAnterior.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:#ede9fe; border:1px solid #ddd6fe; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Actual</div>
                    <div style="font-size:.9rem; font-weight:800; color:#6d28d9;">${kmActual ? kmActual.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:#dcfce7; border:1px solid #bbf7d0; border-radius:.4rem; padding:.5rem; text-align:center;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Recorrido</div>
                    <div style="font-size:.9rem; font-weight:800; color:#166534;">${recorrido ? recorrido.toLocaleString('es-VE') : '—'}</div>
                </div>
            </div>
        `;
    }

    /* ============================================================
       PANEL · HISTORIAL DE UNIDAD
       ============================================================ */
    async function mostrarHistorialUnidad(m){
        if (!$('movDetalle')) return;
        if (!m.id_flota){ mostrarDetalleMovimiento(m); return; }
        mostrarCargandoPanel();

        const d = await fetchJSON(API + '/historial-unidad', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ idFlota: m.id_flota, id_institucion: instSeleccionada.movimientos })
        });
        if (!d.success){
            $('movDetalle').innerHTML = `<div style="padding:1rem; color:#dc2626; font-size:.85rem;">${esc(d.message || 'Error al cargar historial')}</div>`;
            return;
        }

        const unidad = d.data.unidad || {};
        const historial = d.data.historial || [];
        const counts = d.data.counts || { despacho:0, aceite:0, mantenimiento:0, status:0 };

        const km = parseInt(unidad.km_actual || 0);
        const ultCambio = parseInt(unidad.ultimo_cambio_aceite || 0);
        const proxCambio = ultCambio > 0 ? ultCambio + 5000 : 0;
        const faltan = proxCambio > 0 ? proxCambio - km : 0;

        const tipoIcon = { despacho:'file-text', aceite:'droplet', mantenimiento:'tools', status:'refresh-cw' };
        const tipoColor = { despacho:'#2563eb', aceite:'#f59e0b', mantenimiento:'#64748b', status:'#8b5cf6' };
        const tipoLabel = { despacho:'Despacho', aceite:'Cambio de Aceite', mantenimiento:'Mantenimiento', status:'Cambio de Estado' };

        let timelineHtml = '';
        if (!historial.length){
            timelineHtml = '<div style="text-align:center; padding:2rem 1rem; color:#94a3b8; font-style:italic;">Sin eventos registrados</div>';
        } else {
            timelineHtml = historial.map(ev => {
                const color = tipoColor[ev.tipo] || '#64748b';
                const icon = tipoIcon[ev.tipo] || 'circle';
                const label = tipoLabel[ev.tipo] || ev.tipo;

                let detalle = ev.descripcion || ev.detalles || '';
                try {
                    const det = typeof ev.detalles === 'string' ? JSON.parse(ev.detalles) : ev.detalles;
                    if (ev.tipo === 'aceite' && det){
                        detalle = `Cambio a ${parseInt(det.kilometraje_cambio||0).toLocaleString('es-VE')} KM (anterior: ${parseInt(det.kilometraje_anterior||0).toLocaleString('es-VE')})`;
                    }
                } catch(_) {}

                return `
                    <div style="display:flex; gap:.65rem; padding:.65rem 0; border-bottom:1px solid #f1f5f9;">
                        <div style="flex-shrink:0; width:1.75rem; height:1.75rem; border-radius:50%; background:${color}22; color:${color}; display:flex; align-items:center; justify-content:center;">
                            <i data-lucide="${icon}" style="width:14px; height:14px;"></i>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="display:flex; justify-content:space-between; align-items:baseline; gap:.4rem; flex-wrap:wrap;">
                                <strong style="font-size:.78rem; color:${color};">${esc(label)} #${ev.id_evento}</strong>
                                <span style="font-size:.68rem; color:#94a3b8;">${formatFecha(ev.fecha)}</span>
                            </div>
                            <div style="font-size:.75rem; color:#475569; margin-top:.2rem; line-height:1.4;">${detalle}</div>
                            <div style="font-size:.68rem; color:#94a3b8; margin-top:.15rem;"><i data-lucide="user" style="width:10px; height:10px; display:inline;"></i> ${esc(ev.usuario || 'Sistema')}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        $('movDetalle').innerHTML = `
            <div style="display:flex; align-items:center; gap:.5rem; margin-bottom:1rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0;">
                <span style="display:inline-flex; align-items:center; justify-content:center; width:2.25rem; height:2.25rem; border-radius:.45rem; background:#1e40af22; color:#1e40af;">
                    <i data-lucide="bus" style="width:18px; height:18px;"></i>
                </span>
                <div>
                    <strong style="font-size:.95rem; color:#0f172a; display:block;">Hoja de Vida</strong>
                    <code style="font-size:.72rem; color:#64748b;">${esc(unidad.id_unidad || '—')}</code>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.4rem; margin-bottom:.75rem; font-size:.72rem;">
                <div style="background:#f8fafc; padding:.45rem .6rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Marca / Modelo</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(unidad.marca || '')} ${esc(unidad.modelo || '')}</div>
                </div>
                <div style="background:#f8fafc; padding:.45rem .6rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">KM Actual</div>
                    <div style="color:#1e40af; font-weight:700; margin-top:.15rem;">${km.toLocaleString('es-VE')}</div>
                </div>
                <div style="background:#f8fafc; padding:.45rem .6rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Próx. Cambio</div>
                    <div style="color:#b45309; font-weight:700; margin-top:.15rem;">${proxCambio ? proxCambio.toLocaleString('es-VE') : '—'}</div>
                </div>
                <div style="background:${faltan <= 0 ? '#fee2e2' : (faltan <= 1000 ? '#fef3c7' : '#dcfce7')}; padding:.45rem .6rem; border-radius:.35rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Faltan</div>
                    <div style="color:${faltan <= 0 ? '#991b1b' : (faltan <= 1000 ? '#b45309' : '#166534')}; font-weight:700; margin-top:.15rem;">${faltan > 0 ? faltan.toLocaleString('es-VE') : '0'}</div>
                </div>
            </div>

            <div style="display:flex; gap:.35rem; flex-wrap:wrap; margin-bottom:.75rem; font-size:.68rem;">
                <span style="background:#eff6ff; color:#1e40af; padding:.2rem .5rem; border-radius:999px; font-weight:600;">Despachos: ${counts.despacho||0}</span>
                <span style="background:#fef3c7; color:#b45309; padding:.2rem .5rem; border-radius:999px; font-weight:600;">Aceite: ${counts.aceite||0}</span>
                <span style="background:#f1f5f9; color:#475569; padding:.2rem .5rem; border-radius:999px; font-weight:600;">Mant.: ${counts.mantenimiento||0}</span>
                <span style="background:#ede9fe; color:#6d28d9; padding:.2rem .5rem; border-radius:999px; font-weight:600;">Estados: ${counts.status||0}</span>
            </div>

            <div style="font-size:.75rem; font-weight:700; color:#334155; margin-bottom:.35rem;">Historial de eventos (${historial.length})</div>
            <div style="max-height:350px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:.4rem; padding:0 .75rem;">
                ${timelineHtml}
            </div>
        `;

        refreshIcons();
    }

    /* ============================================================
       PDF · DESPACHO
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
        const sel = $('estEstacion');
        if (!sel) return;

        const d = await fetchJSON(API + '/ventas-estacion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fechaInicio: $('estFechaInicio')?.value || '',
                fechaFin: $('estFechaFin')?.value || '',
                estacionId: 'todas'
            })
        });
        if (!d.success) return;

        const estaciones = d.estaciones || [];
        sel.innerHTML = '<option value="todas">Todas las estaciones</option>';
        estaciones.forEach(e => {
            const opt = document.createElement('option');
            opt.value = e.id_estacion;
            opt.textContent = e.estacion;
            sel.appendChild(opt);
        });
    }

    async function cargarEstacion(){
        const bodyEl = $('estBody');
        if (!bodyEl) return;

        bodyEl.innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando...</td></tr>';

        const d = await fetchJSON(API + '/ventas-estacion', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fechaInicio: $('estFechaInicio')?.value || '',
                fechaFin: $('estFechaFin')?.value || '',
                estacionId: $('estEstacion')?.value || 'todas'
            })
        });
        if (!d.success){
            bodyEl.innerHTML = '<tr><td colspan="7" class="dt-empty">Error al cargar</td></tr>';
            return;
        }

        estAll = d.data || [];
        estCurrentPage = 1;
        renderEstacion();
        renderEstacionChart();
    }

    function renderEstacion(){
        if (!$('estBody')) return;

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

        if ($('estStart')) $('estStart').textContent = total ? start + 1 : 0;
        if ($('estEnd'))   $('estEnd').textContent   = Math.min(start + estPerPage, total);
        if ($('estTotal')) $('estTotal').textContent = total;
        renderEstPag();
    }

    function renderEstPag(){
        if (!$('estPagination')) return;
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
        if (!canvas) return;

        const type = $('estChartType')?.value || 'litros_dia';

        if (!estAll.length){
            canvas.style.display = 'none';
            if (empty) empty.style.display = 'block';
            if (estChart) { estChart.destroy(); estChart = null; }
            return;
        }
        canvas.style.display = 'block';
        if (empty) empty.style.display = 'none';

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
                if (parseFloat(e.litros_diesel) > 0)   map['Diésel']   = (map['Diésel']||0)   + parseFloat(e.litros_diesel);
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
        if (!$('modalDetalleBody')) return;
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
            { items, filtro: filtroTexto, modo },
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
       PDF · OPERATIVIDAD FLOTA
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
       PDF · VENTAS ESTACIÓN
       ============================================================ */
    async function generarPdfEstacion(){
        if (!estAll.length){
            alert('No hay datos para exportar');
            return;
        }

        const sel = $('estEstacion');
        const nombreEst = (sel?.value === 'todas' || !sel) ? 'Todas las estaciones' : (sel.options[sel.selectedIndex].text || 'N/D');

        await SITGO_PDF.generar(
            'publico_ventas_estacion',
            {
                data: estAll,
                fechaInicio: $('estFechaInicio')?.value || '',
                fechaFin: $('estFechaFin')?.value || '',
                estacionNombre: nombreEst,
            },
            `ventas_estacion_${$('estFechaInicio')?.value || ''}_${$('estFechaFin')?.value || ''}.pdf`,
            {
                titulo_documento: 'VENTAS DE ESTACIÓN',
                doc_color: '#1e40af',
                mostrar_header: false,
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