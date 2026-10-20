// console.log('✅ bienes.js cargado. SITGO_BASE =', window.SITGO_BASE);
// console.log('📌 INST:', window.SITGO_INST);

document.addEventListener('DOMContentLoaded', () => {
    // console.log('🔄 DOMContentLoaded en bienes.js');

    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'bienes/i/' + INST;
    // console.log('📡 API =', API);

    const $ = id => document.getElementById(id);

    let dt = null;
    let combos = { departamentos: [], grupos: [], subgrupos: [], secciones: [] };

    const STATUS_MAP = {
        '1': { text: 'Activo',        color: 'badge-success' },
        '2': { text: 'Inactivo',      color: 'badge-secondary' },
        '3': { text: 'En reparación', color: 'badge-warning' },
        '4': { text: 'Dado de baja',  color: 'badge-danger' },
        'EN USO':       { text: 'En uso',       color: 'badge-success' },
        'DAÑADO':       { text: 'Dañado',       color: 'badge-danger' },
        'EXTRAVIADO':   { text: 'Extraviado',   color: 'badge-warning' },
        'FALTANTE POR UBICAR': { text: 'Faltante por ubicar', color: 'badge-info' },
    };

    function renderRow(b){
        const st = STATUS_MAP[String(b.status_bien)] || { text: b.status_bien || '—', color: 'badge-secondary' };
        const desc = b.descripcion_bien || '—';
        const descCorta = desc.length > 60 ? desc.substring(0, 60) + '…' : desc;

        return `
            <tr>
                <td><strong>#${b.id_bien}</strong></td>
                <td title="${esc(desc)}">${esc(descCorta)}</td>
                <td>${esc(b.departamento_bien || '—')}</td>
                <td>${esc(b.grupo || '—')}</td>
                <td>${esc(b.subgrupo || '—')}</td>
                <td>${esc(b.seccion_id || '—')}</td>
                <td>${b.fecha_adquisicion ? formatFecha(b.fecha_adquisicion) : '—'}</td>
                <td class="text-center"><span class="badge ${st.color}">${st.text}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn edit" data-action="editar" data-id="${b.id_bien}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${b.id_bien}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    (async () => {
        try{
            // console.log('⏳ Cargando combos...');
            await cargarCombos();
            // console.log('✅ Combos cargados');

            if (typeof DataTable === 'undefined'){
                throw new Error('DataTable no está cargado. Revisa footer.php');
            }

            dt = new DataTable({
                tbodyId: 'tbodyBienes',
                endpoint: API + '/listar',
                columns: ['#','Descripción','Departamento','Grupo','Subgrupo','Sección','Adquisición','Estatus','Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationBienes',
                totalId: 'totalBienes',
                startId: 'startBienes',
                endId: 'endBienes',
                noDataMessage: 'No hay bienes registrados',
                getExtraParams: () => ({
                    departamento: $('filtroDepartamento')?.value || '',
                    grupo:        $('filtroGrupo')?.value        || '',
                    status:       $('filtroStatus')?.value       || ''
                }),
                onDataLoaded: () => {
                    // console.log('📊 Tabla cargada');
                    bindTableActions();
                }
            });

            bindEvents();
            bindForm();
            // console.log('✅ Módulo Bienes inicializado');
        } catch (e){
            console.error('❌ Error al inicializar bienes:', e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    async function fetchJSON(url, opts = {}){
        try{
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                console.error('Respuesta no-JSON:', url, r.status);
                return { ok: false, message: `Error ${r.status}`, type: 'unknown' };
            }
            return await r.json();
        } catch (e){
            console.error('fetchJSON error:', e);
            return { ok: false, message: 'Error de conexión', type: 'network' };
        }
    }

    async function cargarCombos(){
        const d = await fetchJSON(API + '/combos');
        if (!d.ok){
            console.error('Error combos:', d);
            throw new Error(d.message || 'combos');
        }
        combos = d;

        fillSelect($('bien_depatamento_id'), d.departamentos, 'id', 'nombre', 'Seleccione...');
        fillSelect($('grupo_id'),            d.grupos,        'id', 'nombre', 'Seleccione...');
        fillSelect($('subgrupo_id'),         d.subgrupos,     'id', 'nombre', 'Seleccione...');
        fillSelect($('seccion_id'),          d.secciones,     'id', 'nombre', 'Seleccione...');

        fillSelect($('filtroDepartamento'), d.departamentos, 'id', 'nombre', 'Todos los departamentos');
        fillSelect($('filtroGrupo'),        d.grupos,        'id', 'nombre', 'Todos los grupos');
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

    function bindEvents(){
        // dentro de bindEvents()
        $('btnPdfBienes')?.addEventListener('click', () => generarPdfBienes(false));
        $('btnPdfBienesDepto')?.addEventListener('click', () => generarPdfBienes(true));
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });

        $('filtroDepartamento')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroGrupo')?.addEventListener('change',        () => dt?.changePage(1) || dt?.reload());
        $('filtroStatus')?.addEventListener('change',       () => dt?.changePage(1) || dt?.reload());

        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarFormulario(); });
    }
    /**
     * Genera el PDF de bienes.
     * @param {boolean} soloDepartamento — si true, exige que haya un departamento filtrado
     */
    async function generarPdfBienes(soloDepartamento) {
        const deptoId = $('filtroDepartamento')?.value || '';

        if (soloDepartamento && !deptoId) {
            toast('Selecciona un departamento en el filtro para generar este PDF.', 'error');
            return;
        }

        const btn = soloDepartamento ? $('btnPdfBienesDepto') : $('btnPdfBienes');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Generando...';

        try {
            // 1) Traer TODOS los bienes que cumplen los filtros actuales (sin paginar)
            const params = new URLSearchParams({
                q: $('filtroBuscar')?.value || '',
                limit: 9999,
                offset: 0,
                departamento: deptoId,
                grupo: $('filtroGrupo')?.value || '',
                status: $('filtroStatus')?.value || '',
            });

            const r = await fetch(`${API}/listar?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                throw new Error('El servidor no devolvió JSON.');
            }
            const d = await r.json();
            if (!d.ok) throw new Error(d.message || 'Error al cargar los bienes.');

            let items = d.data || [];
            if (!Array.isArray(items)) items = [];

            if (!items.length) {
                toast('No hay bienes para generar el PDF.', 'error');
                return;
            }

            // 2) Título del subtítulo según modo
            let subtitulo = 'Inventario general';
            if (soloDepartamento) {
                const opt = $('filtroDepartamento').selectedOptions[0];
                subtitulo = 'Departamento: ' + (opt?.textContent || '—');
            } else if (deptoId) {
                const opt = $('filtroDepartamento').selectedOptions[0];
                subtitulo = 'Filtrado por: ' + (opt?.textContent || '—');
            }

            // 3) Llamar al servicio de PDF
            await SITGO_PDF.generar(
                'bienes_reporte',
                { items },                            // $items en el template
                `bienes_${soloDepartamento ? 'depto' : 'general'}_${new Date().toISOString().slice(0,10)}.pdf`,
                {
                    titulo_documento: 'REPORTE DE BIENES',
                    doc_color: '#1e40af',
                    subtitulo,
                    agrupar_por_departamento: !soloDepartamento, // general = agrupado; depto = plano
                    // institucion_id lo resuelve PdfService a partir de la sesión
                }
            );

            toast('PDF generado correctamente.', 'success');
        } catch (e) {
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
            if (window.lucide) lucide.createIcons();
        }
    }
    function bindTableActions(){
        const tbody = $('tbodyBienes');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id } = btn.dataset;
            if (action === 'editar')   abrirEditar(id);
            if (action === 'eliminar') eliminar(id);
        });
    }

    function toggleSeccion(){
        const s = $('seccionForm');
        s.classList.toggle('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = s.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(180deg)';
    }
    function abrirSeccion(){
        $('seccionForm').classList.remove('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = 'rotate(180deg)';
    }
    function cerrarSeccion(){
        $('seccionForm').classList.add('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = 'rotate(0deg)';
    }

    function abrirCrear(){
        const f = $('formBien');
        f.reset();
        $('id_bien').value = '';
        $('bien_depatamento_id').value = '';
        $('grupo_id').value = '';
        $('subgrupo_id').value = '';
        $('seccion_id').value = '';
        $('status_bien').value = '1';

        $('formSectionTitle').textContent = 'Nuevo Bien';
        $('formSectionSubtitle').textContent = 'Completa los datos del bien';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="package" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Bien';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => $('bien_depatamento_id')?.focus(), 300);
    }

    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const b = d.data;

            $('id_bien').value              = b.id_bien;
            $('bien_depatamento_id').value  = b.bien_depatamento_id || '';
            $('grupo_id').value             = b.grupo_id || '';
            $('subgrupo_id').value          = b.subgrupo_id || '';
            $('seccion_id').value           = b.seccion_id || '';
            $('fecha_adquisicion').value    = b.fecha_adquisicion || '';
            $('descripcion_bien').value     = b.descripcion_bien || '';
            $('status_bien').value          = b.status_bien || '1';

            $('formSectionTitle').textContent = 'Editar Bien';
            $('formSectionSubtitle').textContent = `Editando bien #${b.id_bien}`;
            $('formSectionIconWrap').innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i>';
            if (window.lucide) lucide.createIcons();

            $('btnNuevoText').textContent = 'Cancelar edición';
            $('btnGuardar').querySelector('.btn-text').textContent = 'Actualizar';

            limpiarErrores();
            abrirSeccion();
            $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e){
            toast('Error al cargar: ' + e.message, 'error');
        }
    }

    function bindForm(){
        $('formBien')?.addEventListener('submit', guardar);
    }

    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formBien');
        const id = $('id_bien').value;
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
                        const input   = form.querySelector(`[name="${f}"]`);
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
            $('id_bien').value = '';
            cerrarFormulario();
            dt?.refresh();
        } catch (err){
            console.error(err);
            toast('Error de conexión', 'error');
        } finally{
            btn.disabled = false;
            btnText.textContent = original;
        }
    }

    async function eliminar(id){
        const b = dt?.getData().find(x => Number(x.id_bien) === Number(id));
        const desc = b?.descripcion_bien || 'este bien';
        if (!confirm(`¿Eliminar "${desc.substring(0, 50)}..."? Esta acción no se puede deshacer.`)) return;

        try{
            const d = await fetchJSON(API + '/eliminar/' + id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            dt?.refresh();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    function limpiarErrores(){
        const form = $('formBien');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function cerrarFormulario(){
        const form = $('formBien');
        form.reset();
        $('id_bien').value = '';
        limpiarErrores();

        $('formSectionTitle').textContent = 'Nuevo Bien';
        $('formSectionSubtitle').textContent = 'Completa los datos del bien';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="package" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Bien';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        cerrarSeccion();
    }

    function formatFecha(f){
        if (!f) return '';
        try{
            if (/^\d{4}-\d{2}-\d{2}$/.test(f)){
                const d = new Date(f + 'T00:00:00');
                return d.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
            }
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