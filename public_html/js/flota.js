console.log('✅ flota.js cargado. SITGO_BASE =', window.SITGO_BASE);

document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'flota/i/' + INST;
    const BASE = (window.SITGO_BASE || '/');
    const $ = id => document.getElementById(id);

    let dt = null;
    let allUnidades = [];

    /* ============================================================
       MAPA DE ESTADOS
       ============================================================ */
    const STATUS_MAP = {
        0: { text: 'Desincorporada',     color: 'badge-secondary' },
        1: { text: 'Operativa',          color: 'badge-success'   },
        2: { text: 'Inoperativa',        color: 'badge-danger'    },
        3: { text: 'Mantenimiento',      color: 'badge-warning'   },
        4: { text: 'Por Desincorporar',  color: 'badge-info'      },
        5: { text: 'Crítica',            color: 'badge-danger'    },
    };

    /* ============================================================
       RENDER ROW
       ============================================================ */
    function renderRow(u){
        const st = STATUS_MAP[u.status_unidad] || { text: '—', color: 'badge-secondary' };
        return `
            <tr>
                <td>
                    <a href="${BASE}flota/i/${INST}/historial/${u.id_flota}"
                       class="font-semibold" style="color:#1d4ed8;">
                        ${esc(u.id_unidad)}
                    </a>
                </td>
                <td>${esc(u.marca_unidad || '—')}</td>
                <td>${esc(u.modelo_unidad || '—')}</td>
                <td>${esc(u.vim_unidad || '—')}</td>
                <td>${esc(u.tipo_combustible || '—')}</td>
                <td>${esc(u.transmision || '—')}</td>
                <td class="text-center"><span class="badge ${st.color}">${st.text}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn edit" data-action="editar" data-id="${u.id_flota}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn toggle" data-action="status" data-id="${u.id_flota}" title="Cambiar estado">
                            <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        try{
            await cargarCombos();
            await cargarTodas();

            dt = new DataTable({
                tbodyId: 'tbodyFlota',
                endpoint: API + '/listar',
                columns: ['ID','Marca','Modelo','VIN','Combustible','Transmisión','Estado','Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationFlota',
                totalId: 'totalFlota',
                startId: 'startFlota',
                endId: 'endFlota',
                noDataMessage: 'No hay unidades registradas',
                getExtraParams: () => ({
                    status: $('filtroStatus')?.value || '',
                    marca:  $('filtroMarca')?.value  || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindForm();
            initReporte();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       API
       ============================================================ */
    async function fetchJSON(url, opts = {}){
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, ...opts });
        return r.json();
    }

    async function cargarCombos(){
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) throw new Error('combos');

        fillSelect($('id_marca'), d.marcas, 'id_marca', 'marca_unidad', 'Seleccione...');
        fillSelect($('id_modelo'), d.modelos, 'id_modelo', 'modelo_unidad', 'Seleccione...');
        fillSelect($('filtroMarca'), d.marcas, 'id_marca', 'marca_unidad', 'Todas las marcas');
    }

    async function cargarTodas(){
        const d = await fetchJSON(API + '/todas');
        if (d.ok) allUnidades = d.data || [];
    }

    function fillSelect(sel, items, vKey, lKey, placeholder){
        if (!sel) return;
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it[vKey];
            o.textContent = it[lKey];
            sel.appendChild(o);
        });
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEvents(){
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', () => toggleSeccion('seccionForm', 'btnToggleForm'));
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion('seccionForm', 'btnToggleForm'); });

        $('filtroStatus')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroMarca')?.addEventListener('change',  () => dt?.changePage(1) || dt?.reload());

        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarFormulario(); });
    }

    function bindTableActions(){
        const tbody = $('tbodyFlota');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id } = btn.dataset;
            if (action === 'editar') abrirEditar(id);
            if (action === 'status') abrirStatus(id);
        });
    }

    /* ============================================================
       SECCIÓN COLAPSABLE
       ============================================================ */
    function toggleSeccion(idSeccion, idBtn){
        const s = $(idSeccion);
        s.classList.toggle('collapsed');
        const t = $(idBtn);
        if (t) t.style.transform = s.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(180deg)';
    }
    function abrirSeccion(idSeccion, idBtn){
        $(idSeccion).classList.remove('collapsed');
        const t = $(idBtn);
        if (t) t.style.transform = 'rotate(180deg)';
    }
    function cerrarSeccion(idSeccion, idBtn){
        $(idSeccion).classList.add('collapsed');
        const t = $(idBtn);
        if (t) t.style.transform = 'rotate(0deg)';
    }

    /* ============================================================
       CREAR
       ============================================================ */
    function abrirCrear(){
        const f = $('formFlota');
        f.reset();
        $('id_flota').value = '';
        $('id_marca').value = '';
        $('id_modelo').value = '';

        $('formSectionTitle').textContent = 'Nueva Unidad';
        $('formSectionSubtitle').textContent = 'Completa los datos de la unidad';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="bus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nueva Unidad';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion('seccionForm', 'btnToggleForm');
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => $('id_unidad')?.focus(), 300);
    }

    /* ============================================================
       EDITAR
       ============================================================ */
    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const u = d.data;

            $('id_flota').value        = u.id_flota;
            $('id_unidad').value       = u.id_unidad || '';
            $('vim_unidad').value      = u.vim_unidad || '';
            $('id_marca').value        = u.id_marca || '';
            $('id_modelo').value       = u.id_modelo || '';
            $('fecha_creacion').value  = u.fecha_creacion || '';
            $('cap_pasajero').value    = u.cap_pasajero || '';
            $('tipo_combustible').value = u.tipo_combustible || 'N/A';
            $('transmision').value     = u.transmision || 'N/A';

            $('formSectionTitle').textContent = 'Editar Unidad';
            $('formSectionSubtitle').textContent = `Editando "${u.id_unidad}"`;
            $('formSectionIconWrap').innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i>';
            if (window.lucide) lucide.createIcons();

            $('btnNuevoText').textContent = 'Cancelar edición';
            $('btnGuardar').querySelector('.btn-text').textContent = 'Actualizar';

            limpiarErrores();
            abrirSeccion('seccionForm', 'btnToggleForm');
            $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e){
            toast('Error al cargar: ' + e.message, 'error');
        }
    }

    /* ============================================================
       CAMBIAR STATUS
       ============================================================ */
    async function abrirStatus(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const u = d.data;

            const actual = STATUS_MAP[u.status_unidad]?.text || '—';

            const opciones = Object.entries(STATUS_MAP)
                .filter(([k]) => Number(k) !== Number(u.status_unidad))
                .map(([k, v]) => `<option value="${k}">${v.text}</option>`)
                .join('');

            const html = `
                <p>Unidad: <strong>${esc(u.id_unidad)}</strong></p>
                <p class="mb-3">Estado actual: <strong>${actual}</strong></p>
                <div class="form-group">
                    <label>Nuevo estado</label>
                    <select id="statusNuevo">${opciones}</select>
                </div>
                <div class="form-group">
                    <label>Motivo del cambio</label>
                    <textarea id="statusMotivo" rows="3" placeholder="Describe el motivo..."></textarea>
                </div>
            `;
            abrirModal('Cambiar estado de unidad', html, async () => {
                const st = $('statusNuevo').value;
                const motivo = ($('statusMotivo').value || '').trim();
                if (!motivo) { toast('Debes indicar el motivo', 'error'); return false; }

                const r = await fetchJSON(API + '/cambiar-status/' + id, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status_unidad: Number(st), motivo })
                });
                if (!r.ok) { toast(r.message || 'Error', 'error'); return false; }
                toast(r.message, 'success');
                dt?.refresh();
                cargarTodas();
                return true;
            });
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================================
       GUARDAR
       ============================================================ */
    function bindForm(){
        $('formFlota')?.addEventListener('submit', guardar);
    }

    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formFlota');
        const id = $('id_flota').value;
        const payload = Object.fromEntries(new FormData(form).entries());

        const btn = $('btnGuardar');
        btn.disabled = true;
        const btnText = btn.querySelector('.btn-text');
        const original = btnText.textContent;
        btnText.textContent = id ? 'Actualizando...' : 'Guardando...';

        try{
            const url = id ? `${API}/actualizar/${id}` : `${API}/guardar`;
            const d = await fetchJSON(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok){
                if (d.errors){
                    Object.entries(d.errors).forEach(([f, m]) => {
                        const errSpan = form.querySelector(`[data-error="${f}"]`);
                        const input = form.querySelector(`[name="${f}"]`);
                        if (errSpan) errSpan.textContent = m;
                        if (input) input.classList.add('error');
                    });
                } else {
                    toast(d.message || 'Error al guardar', 'error');
                }
                return;
            }

            toast(d.message, 'success');
            form.reset();
            $('id_flota').value = '';
            cerrarFormulario();
            dt?.refresh();
            cargarTodas();
        } catch (err){
            console.error(err);
            toast('Error de conexión', 'error');
        } finally{
            btn.disabled = false;
            btnText.textContent = original;
        }
    }

    function cerrarFormulario(){
        const form = $('formFlota');
        form.reset();
        $('id_flota').value = '';
        limpiarErrores();

        $('formSectionTitle').textContent = 'Nueva Unidad';
        $('formSectionSubtitle').textContent = 'Completa los datos de la unidad';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="bus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nueva Unidad';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';
        cerrarSeccion('seccionForm', 'btnToggleForm');
    }

    /* ============================================================
       REPORTE DE OPERATIVIDAD
       ============================================================ */
    function initReporte(){
        $('toggleReporte')?.addEventListener('click', () => toggleSeccion('seccionReporte', 'btnToggleReporte'));
        $('btnToggleReporte')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion('seccionReporte', 'btnToggleReporte'); });
        $('filtroGrupos')?.addEventListener('input', filtrarGrupos);
        $('btnLimpiarReporte')?.addEventListener('click', limpiarReporte);
        $('btnGenerarPDF')?.addEventListener('click', () => {
            toast('Generación de PDF se implementará en la siguiente fase.', 'error');
        });

        pintarGrupos();
    }

    function pintarGrupos(){
        const cont = $('reporteFiltros');
        if (!cont) return;

        if (!allUnidades.length){
            cont.innerHTML = '<p class="text-muted text-center py-4">No hay unidades para mostrar.</p>';
            return;
        }

        const grupos = {};
        allUnidades.forEach(u => {
            const modelo  = u.modelo_unidad || 'Sin Modelo';
            const comb    = u.tipo_combustible || 'No especificado';
            const trans   = u.transmision || 'No especificada';
            const key = `${modelo} / ${comb} / ${trans}`;
            (grupos[key] = grupos[key] || []).push(u);
        });

        const html = Object.entries(grupos).map(([nombre, lista]) => {
            const ids = lista.map(u => u.id_flota);
            return `
                <label class="reporte-grupo" data-name="${esc(nombre.toLowerCase())}">
                    <input type="checkbox"
                           class="reporte-check"
                           data-ids='${JSON.stringify(ids)}'
                           data-name="${esc(nombre)}">
                    <span class="reporte-grupo-name">${esc(nombre)}</span>
                    <span class="reporte-grupo-count">${lista.length}</span>
                </label>
            `;
        }).join('');

        cont.innerHTML = html;

        cont.querySelectorAll('.reporte-check').forEach(chk => {
            chk.addEventListener('change', actualizarPreview);
        });
    }

    function filtrarGrupos(){
        const q = ($('filtroGrupos').value || '').toLowerCase().trim();
        document.querySelectorAll('.reporte-grupo').forEach(g => {
            const name = g.dataset.name || '';
            g.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
    }

    function actualizarPreview(){
        const checks = document.querySelectorAll('.reporte-check:checked');
        const tbody  = $('tbodyResumen');
        const tabla  = $('tablaResumen');
        const placeholder = $('placeholderReporte');

        if (!checks.length){
            tabla.style.display = 'none';
            placeholder.style.display = '';
            $('totalOperativas').textContent     = '0';
            $('totalInoperativas').textContent   = '0';
            $('totalCriticas').textContent       = '0';
            $('totalSeleccionadas').textContent  = '0';
            $('btnGenerarPDF').disabled = true;
            return;
        }

        placeholder.style.display = 'none';
        tabla.style.display = '';
        $('btnGenerarPDF').disabled = false;

        let totOp = 0, totInop = 0, totCrit = 0, totCant = 0;
        let html = '';

        checks.forEach(chk => {
            const ids = JSON.parse(chk.dataset.ids);
            const nombre = chk.dataset.name;

            let cant = 0, op = 0, inop = 0, crit = 0;
            ids.forEach(id => {
                const u = allUnidades.find(x => x.id_flota == id);
                if (!u) return;
                cant++;
                const st = STATUS_MAP[u.status_unidad]?.text || '';
                if (st === 'Operativa')   op++;
                if (st === 'Inoperativa') inop++;
                if (st === 'Crítica')     crit++;
            });

            totCant += cant; totOp += op; totInop += inop; totCrit += crit;

            html += `
                <tr>
                    <td>${esc(nombre)}</td>
                    <td class="text-center"><strong>${cant}</strong></td>
                    <td class="text-center" style="color:#166534;">${op}</td>
                    <td class="text-center" style="color:#b45309;">${inop}</td>
                    <td class="text-center" style="color:#dc2626;">${crit}</td>
                </tr>`;
        });

        tbody.innerHTML = html;
        $('totalOperativas').textContent    = totOp;
        $('totalInoperativas').textContent  = totInop;
        $('totalCriticas').textContent      = totCrit;
        $('totalSeleccionadas').textContent = totCant;
    }

    function limpiarReporte(){
        document.querySelectorAll('.reporte-check:checked').forEach(c => c.checked = false);
        actualizarPreview();
        $('filtroGrupos').value = '';
        filtrarGrupos();
    }

    /* ============================================================
       MODAL GENÉRICO
       ============================================================ */
    function abrirModal(titulo, contenidoHTML, onGuardar){
        const modal = document.getElementById('modalGenerico');
        const body  = document.getElementById('modalGenericoBody');
        const title = document.getElementById('modalGenericoTitle');
        const btnOk = document.getElementById('modalGenericoOk');

        title.textContent = titulo;
        body.innerHTML = contenidoHTML;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        const nuevo = btnOk.cloneNode(true);
        btnOk.parentNode.replaceChild(nuevo, btnOk);

        nuevo.addEventListener('click', async () => {
            const ok = await onGuardar();
            if (ok) cerrarModalGenerico();
        });
    }
    function cerrarModalGenerico(){
        const modal = document.getElementById('modalGenerico');
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
    window.cerrarModalGenerico = cerrarModalGenerico;

    /* ============================================================
       HELPERS
       ============================================================ */
    function limpiarErrores(){
        const form = $('formFlota');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function esc(s){
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function toast(msg, type = 'success'){
        let c = document.querySelector('.toast-container');
        if (!c){
            c = document.createElement('div');
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }
});