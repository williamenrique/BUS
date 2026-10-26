document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'bienes/i/' + INST;

    const $ = id => document.getElementById(id);

    let dt = null;
    let combos = { departamentos: [], grupos: [], subgrupos: [], secciones: [] };

    // SearchSelects
    let ssDepto         = null;
    let ssGrupo         = null;
    let ssSubgrupo      = null;
    let ssSeccion       = null;
    let ssFiltroDepto   = null;
    let ssFiltroGrupo   = null;

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
            initSearchSelects();
            await cargarCombos();

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
                    departamento: ssFiltroDepto ? ssFiltroDepto.getValue() : '',
                    grupo:        ssFiltroGrupo ? ssFiltroGrupo.getValue() : '',
                    status:       $('filtroStatus')?.value || ''
                }),
                onDataLoaded: () => {
                    bindTableActions();
                }
            });

            bindEvents();
            bindForm();
        } catch (e){
            console.error('❌ Error al inicializar bienes:', e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       SEARCHSELECTs
       ============================================================ */
    function initSearchSelects(){
        ssDepto = new SearchSelect({
            wrapperId: 'wrapDepto',
            hiddenInputId: 'bien_depatamento_id',
            placeholder: 'Seleccione departamento...',
            noResultsText: 'Sin departamentos'
        });

        ssGrupo = new SearchSelect({
            wrapperId: 'wrapGrupo',
            hiddenInputId: 'grupo_id',
            placeholder: 'Seleccione grupo...',
            noResultsText: 'Sin grupos'
        });

        ssSubgrupo = new SearchSelect({
            wrapperId: 'wrapSubgrupo',
            hiddenInputId: 'subgrupo_id',
            placeholder: 'Seleccione subgrupo...',
            noResultsText: 'Sin subgrupos'
        });

        ssSeccion = new SearchSelect({
            wrapperId: 'wrapSeccion',
            hiddenInputId: 'seccion_id',
            placeholder: 'Seleccione sección...',
            noResultsText: 'Sin secciones'
        });

        ssFiltroDepto = new SearchSelect({
            wrapperId: 'wrapFiltroDepto',
            hiddenInputId: 'filtroDepartamento',
            placeholder: 'Todos los departamentos',
            noResultsText: 'Sin departamentos',
            onChange: () => dt?.changePage(1) || dt?.reload()
        });

        ssFiltroGrupo = new SearchSelect({
            wrapperId: 'wrapFiltroGrupo',
            hiddenInputId: 'filtroGrupo',
            placeholder: 'Todos los grupos',
            noResultsText: 'Sin grupos',
            onChange: () => dt?.changePage(1) || dt?.reload()
        });
    }

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

        const deptosOpts = (d.departamentos || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        }));
        ssDepto.setOptions(deptosOpts);
        ssFiltroDepto.setOptions(deptosOpts);

        const gruposOpts = (d.grupos || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        }));
        ssGrupo.setOptions(gruposOpts);
        ssFiltroGrupo.setOptions(gruposOpts);

        ssSubgrupo.setOptions((d.subgrupos || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        })));

        ssSeccion.setOptions((d.secciones || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        })));
    }

    function bindEvents(){
        $('btnPdfBienes')?.addEventListener('click', () => generarPdfBienes(false));
        $('btnPdfBienesDepto')?.addEventListener('click', () => generarPdfBienes(true));
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });

        $('filtroStatus')?.addEventListener('change',       () => dt?.changePage(1) || dt?.reload());

        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarFormulario(); });
        $('btnQrBienes')?.addEventListener('click', generarQrBienes);
        $('qrCerrar')?.addEventListener('click', () => {
            $('panelQr').style.display = 'none';
            $('qrCanvas').innerHTML = '';
        });
    }

    async function generarPdfBienes(soloDepartamento) {
        const deptoId = ssFiltroDepto ? ssFiltroDepto.getValue() : '';

        if (soloDepartamento && !deptoId) {
            toast('Selecciona un departamento en el filtro para generar este PDF.', 'error');
            return;
        }

        const btn = soloDepartamento ? $('btnPdfBienesDepto') : $('btnPdfBienes');
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = 'Generando...';

        try {
            const params = new URLSearchParams({
                q: $('filtroBuscar')?.value || '',
                limit: 9999,
                offset: 0,
                departamento: deptoId,
                grupo: ssFiltroGrupo ? ssFiltroGrupo.getValue() : '',
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

            let subtitulo = 'Inventario general';
            if (soloDepartamento) {
                const opt = ssFiltroDepto ? ssFiltroDepto.getSelectedLabel() : '—';
                subtitulo = 'Departamento: ' + (opt || '—');
            } else if (deptoId) {
                const opt = ssFiltroDepto ? ssFiltroDepto.getSelectedLabel() : '—';
                subtitulo = 'Filtrado por: ' + (opt || '—');
            }

            await SITGO_PDF.generar(
                'bienes_reporte',
                { items },
                `bienes_${soloDepartamento ? 'depto' : 'general'}_${new Date().toISOString().slice(0,10)}.pdf`,
                {
                    titulo_documento: 'REPORTE DE BIENES',
                    doc_color: '#1e40af',
                    subtitulo,
                    agrupar_por_departamento: !soloDepartamento,
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
        $('status_bien').value = '1';

        ssDepto?.clear();
        ssGrupo?.clear();
        ssSubgrupo?.clear();
        ssSeccion?.clear();

        $('formSectionTitle').textContent = 'Nuevo Bien';
        $('formSectionSubtitle').textContent = 'Completa los datos del bien';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="package" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Bien';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const b = d.data;

            $('id_bien').value              = b.id_bien;
            $('fecha_adquisicion').value    = b.fecha_adquisicion || '';
            $('descripcion_bien').value     = b.descripcion_bien || '';
            $('status_bien').value          = b.status_bien || '1';

            ssDepto.setValue(b.bien_depatamento_id);
            ssGrupo.setValue(b.grupo_id || '');
            ssSubgrupo.setValue(b.subgrupo_id || '');
            ssSeccion.setValue(b.seccion_id || '');

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

        const ok = await sitgoConfirm({
            title: 'Eliminar bien',
            message: `¿Eliminar "${desc.substring(0, 60)}${desc.length > 60 ? '…' : ''}"? Esta acción no se puede deshacer.`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

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

        ssDepto?.clear();
        ssGrupo?.clear();
        ssSubgrupo?.clear();
        ssSeccion?.clear();

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

    /* ============================================================
       QR · Genera QR del departamento seleccionado
       ============================================================ */
    function generarQrBienes() {
        const deptoId = ssFiltroDepto ? ssFiltroDepto.getValue() : '';

        if (!deptoId) {
            toast('Selecciona un departamento en el filtro para generar el QR.', 'error');
            return;
        }

        if (typeof QRCode === 'undefined') {
            toast('La librería QRCode no está cargada. Revisa footer.php.', 'error');
            return;
        }

        const nombre = ssFiltroDepto ? ssFiltroDepto.getSelectedLabel() : 'Departamento';

        const url = (window.SITGO_BASE || '/')
                  + 'bienes-publico/i/' + INST
                  + '?depto=' + encodeURIComponent(deptoId);

        const canvasWrap = $('qrCanvas');
        canvasWrap.innerHTML = '';

        try {
            new QRCode(canvasWrap, {
                text: url,
                width: 200,
                height: 200,
                colorDark: '#0f172a',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });

            setTimeout(() => {
                const img = canvasWrap.querySelector('img') || canvasWrap.querySelector('canvas');
                let dataUrl = '';

                if (img) {
                    if (img.tagName === 'IMG') {
                        dataUrl = img.src;
                    } else if (img.tagName === 'CANVAS') {
                        dataUrl = img.toDataURL('image/png');
                    }
                }

                $('qrDeptoNombre').textContent = nombre;
                $('qrUrl').textContent = url;

                const aDesc = $('qrDescargar');
                if (aDesc) {
                    aDesc.href = dataUrl || '#';
                    const safe = nombre.replace(/[^A-Za-z0-9_\-]/g, '_');
                    aDesc.download = `QR_${safe}.png`;
                    aDesc.style.display = dataUrl ? '' : 'none';
                }

                const aAbrir = $('qrAbrir');
                if (aAbrir) aAbrir.href = url;

                $('panelQr').style.display = '';
                $('panelQr').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (window.lucide) lucide.createIcons();
            }, 120);

            toast('QR generado correctamente.', 'success');
        } catch (e) {
            console.error(e);
            toast('No se pudo generar el QR: ' + e.message, 'error');
        }
    }
});