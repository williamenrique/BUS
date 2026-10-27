document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'productos/i/' + INST;
    const $ = id => document.getElementById(id);

    let dt = null;
    let combos = { enlaces: [], proveedores: [], ubicaciones: [] };

    // SearchSelects
    let ssEnlace         = null;
    let ssUbicacion      = null;
    let ssProveedor      = null;
    let ssFiltroEnlace   = null;
    let ssFiltroUbicacion= null;

    /* ============================================================
       RENDER ROW
       ============================================================ */
    function renderRow(p){
        const cant = Number(p.cant_producto || 0);
        const present = p.present_producto || 'UND';

        let stockBadge;
        if (cant <= 0){
            stockBadge = `<span class="badge badge-danger">Sin stock</span>`;
        } else if (cant < 10){
            stockBadge = `<span class="badge badge-warning">${cant.toLocaleString('es-VE')} ${esc(present)}</span>`;
        } else {
            stockBadge = `<span class="badge badge-success">${cant.toLocaleString('es-VE')} ${esc(present)}</span>`;
        }

        return `
            <tr>
                <td><strong>#${p.id_producto}</strong></td>
                <td>
                    <strong>${esc(p.producto)}</strong>
                    ${p.tag_producto == 2 ? '<span class="badge badge-info" style="margin-left:.35rem; font-size:.65rem;">REP.</span>' : ''}
                </td>
                <td>${esc(p.enlace_producto || '—')}</td>
                <td>${esc(p.empresa_proveedor || '—')}</td>
                <td>${esc(p.ubicacion || '—')}</td>
                <td class="text-center">${stockBadge}</td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn" data-action="stock" data-id="${p.id_producto}" data-producto="${esc(p.producto)}" data-cant="${cant}" title="Agregar stock">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn edit" data-action="editar" data-id="${p.id_producto}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${p.id_producto}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
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
            initSearchSelects();
            await cargarCombos();

            dt = new DataTable({
                tbodyId: 'tbodyProductos',
                endpoint: API + '/listar',
                columns: ['#','Producto','Tipo','Proveedor','Ubicación','Stock','Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationProductos',
                totalId: 'totalProductos',
                startId: 'startProductos',
                endId: 'endProductos',
                noDataMessage: 'No hay productos registrados',
                getExtraParams: () => ({
                    enlace:    ssFiltroEnlace ? ssFiltroEnlace.getValue() : '',
                    ubicacion: ssFiltroUbicacion ? ssFiltroUbicacion.getValue() : '',
                    stock:     $('filtroStock')?.value || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            actualizarBotonPdf();   // ← NUEVO: estado inicial del botón
            bindForm();
            bindStockForm();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       SEARCHSELECTs
       ============================================================ */
    function initSearchSelects(){
        ssEnlace = new SearchSelect({
            wrapperId: 'wrapEnlace',
            hiddenInputId: 'id_enlace_producto',
            placeholder: 'Seleccione tipo...',
            noResultsText: 'Sin tipos'
        });

        ssUbicacion = new SearchSelect({
            wrapperId: 'wrapUbicacion',
            hiddenInputId: 'id_ubicacion',
            placeholder: 'Seleccione ubicación...',
            noResultsText: 'Sin ubicaciones'
        });

        ssProveedor = new SearchSelect({
            wrapperId: 'wrapProveedor',
            hiddenInputId: 'id_proveedor',
            placeholder: 'Seleccione proveedor...',
            noResultsText: 'Sin proveedores'
        });

        ssFiltroEnlace = new SearchSelect({
            wrapperId: 'wrapFiltroEnlace',
            hiddenInputId: 'filtroEnlace',
            placeholder: 'Todos los tipos',
            noResultsText: 'Sin tipos',
            onChange: () => dt?.changePage(1) || dt?.reload()
        });

        ssFiltroUbicacion = new SearchSelect({
            wrapperId: 'wrapFiltroUbicacion',
            hiddenInputId: 'filtroUbicacion',
            placeholder: 'Todas las ubicaciones',
            noResultsText: 'Sin ubicaciones',
            onChange: () => dt?.changePage(1) || dt?.reload()
        });
    }

    /* ============================================================
       API
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

    async function cargarCombos(){
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) throw new Error('combos');
        combos = d;

        const enlacesOpts = (d.enlaces || []).map(e => ({
            value: e.id,
            label: e.nombre,
            search: e.nombre.toLowerCase()
        }));
        ssEnlace.setOptions(enlacesOpts);
        ssFiltroEnlace.setOptions(enlacesOpts);

        const ubicacionesOpts = (d.ubicaciones || []).map(u => ({
            value: u.id,
            label: u.nombre,
            search: u.nombre.toLowerCase()
        }));
        ssUbicacion.setOptions(ubicacionesOpts);
        ssFiltroUbicacion.setOptions(ubicacionesOpts);

        ssProveedor.setOptions((d.proveedores || []).map(p => ({
            value: p.id,
            label: p.nombre,
            search: p.nombre.toLowerCase()
        })));
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    function bindEvents(){
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });
        $('btnPdfPorEstado')?.addEventListener('click', generarPdfPorEstado);

        $('filtroStock')?.addEventListener('change', () => {
            actualizarBotonPdf();
            dt?.changePage(1) || dt?.reload();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape'){ cerrarFormulario(); cerrarModalStock(); }
        });
    }

    function bindTableActions(){
        const tbody = $('tbodyProductos');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id, producto, cant } = btn.dataset;
            if (action === 'editar')   abrirEditar(id);
            if (action === 'eliminar') eliminar(id);
            if (action === 'stock')    abrirModalStock(id, producto, cant);
        });
    }

    /* ============================================================
       SECCIÓN COLAPSABLE
       ============================================================ */
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

    /* ============================================================
       CREAR
       ============================================================ */
    function abrirCrear(){
        const f = $('formProducto');
        f.reset();
        $('id_producto').value = '';
        $('present_producto').value = 'UNIDAD';
        $('tag_producto').value = '1';
        $('cant_producto').value = 0;

        ssEnlace.clear();
        ssUbicacion.clear();
        ssProveedor.clear();

        $('cantidadGroup').style.display = '';

        $('formSectionTitle').textContent = 'Nuevo Producto';
        $('formSectionSubtitle').textContent = 'Completa los datos del producto';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="package" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Producto';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => $('producto')?.focus(), 300);
    }

    /* ============================================================
       EDITAR
       ============================================================ */
    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const p = d.data;

            $('id_producto').value             = p.id_producto;
            $('producto').value                = p.producto || '';
            $('present_producto').value        = p.present_producto || 'UNIDAD';
            $('tag_producto').value            = p.tag_producto || '1';

            ssEnlace.setValue(p.id_enlace_producto);
            ssUbicacion.setValue(p.id_ubicacion);
            ssProveedor.setValue(p.id_proveedor);

            $('cantidadGroup').style.display = 'none';

            $('formSectionTitle').textContent = 'Editar Producto';
            $('formSectionSubtitle').textContent = `Editando: ${p.producto}`;
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

    /* ============================================================
       GUARDAR
       ============================================================ */
    function bindForm(){
        $('formProducto')?.addEventListener('submit', guardar);
    }

    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formProducto');
        const id = $('id_producto').value;
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
            $('id_producto').value = '';
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

    /* ============================================================
       MODAL · AGREGAR STOCK
       ============================================================ */
    function abrirModalStock(id, producto, cantActual){
        $('stock_id_producto').value = id;
        $('stock_producto_label').textContent = producto;
        $('stock_actual_label').textContent = Number(cantActual || 0).toLocaleString('es-VE');
        $('stock_cantidad').value = '';
        $('modalStock').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => $('stock_cantidad')?.focus(), 100);
    }

    window.cerrarModalStock = function(){
        $('modalStock').classList.remove('open');
        document.body.style.overflow = '';
    };

    function bindStockForm(){
        $('formStock')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = $('stock_id_producto').value;
            const cantidad = parseFloat($('stock_cantidad').value);

            if (!id || !cantidad || cantidad <= 0){
                toast('Ingresa una cantidad válida.', 'error');
                return;
            }

            try{
                const d = await fetchJSON(`${API}/agregar-stock/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cantidad })
                });

                if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

                toast(d.message, 'success');
                window.cerrarModalStock();
                dt?.refresh();
            } catch (err){
                console.error(err);
                toast('Error de conexión', 'error');
            }
        });
    }

    /* ============================================================
       ELIMINAR
       ============================================================ */
    async function eliminar(id){
        const p = dt?.getData().find(x => Number(x.id_producto) === Number(id));
        const nombre = p?.producto || 'este producto';

        const ok = await sitgoConfirm({
            title: 'Eliminar producto',
            message: `¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`,
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

    /* ============================================================
       HELPERS
       ============================================================ */
    function limpiarErrores(){
        const form = $('formProducto');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function cerrarFormulario(){
        const form = $('formProducto');
        form.reset();
        $('id_producto').value = '';
        $('cantidadGroup').style.display = '';

        ssEnlace?.clear();
        ssUbicacion?.clear();
        ssProveedor?.clear();

        limpiarErrores();

        $('formSectionTitle').textContent = 'Nuevo Producto';
        $('formSectionSubtitle').textContent = 'Completa los datos del producto';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="package" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Producto';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        cerrarSeccion();
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
       PDF · PRODUCTOS POR ESTADO DE STOCK
       Si el select filtroStock tiene valor (sin/bajo/ok), el PDF
       se genera SOLO para ese estado. Si está vacío, los 3 grupos.
       ============================================================ */
    async function generarPdfPorEstado(){
        const btn = $('btnPdfPorEstado');
        const original = btn.innerHTML;

        const filtroStock = $('filtroStock')?.value || '';

        // Mapeo filtro → nombre del grupo
        const mapaGrupo = {
            'sin': 'Sin stock',
            'bajo': 'Stock bajo',
            'ok': 'Con stock',
        };
        const grupoSeleccionado = mapaGrupo[filtroStock] || '';

        btn.disabled = true;
        btn.innerHTML = 'Generando...';

        try{
            const r = await fetch(`${API}/pdf-por-estado`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                throw new Error('El servidor no devolvió JSON.');
            }
            const d = await r.json();
            if (!d.ok){
                toast(d.message || 'No se pudo generar el PDF.', 'error');
                return;
            }

            // Filtrar grupos si el usuario seleccionó un estado
            let grupos = d.grupos || {};
            let conteos = d.conteos || {};

            if (grupoSeleccionado){
                grupos = { [grupoSeleccionado]: grupos[grupoSeleccionado] || [] };
                conteos = {
                    sin_stock: grupoSeleccionado === 'Sin stock'  ? (d.conteos?.sin_stock || 0) : 0,
                    bajo:      grupoSeleccionado === 'Stock bajo' ? (d.conteos?.bajo || 0)      : 0,
                    ok:        grupoSeleccionado === 'Con stock'  ? (d.conteos?.ok || 0)        : 0,
                    total:     (d.conteos?.[
                        grupoSeleccionado === 'Sin stock'  ? 'sin_stock' :
                        grupoSeleccionado === 'Stock bajo' ? 'bajo' : 'ok'
                    ] || 0),
                    filtro:    grupoSeleccionado,
                };
            }

            const titulo = grupoSeleccionado
                ? `INVENTARIO · ${grupoSeleccionado.toUpperCase()}`
                : 'INVENTARIO POR ESTADO DE STOCK';

            await SITGO_PDF.generar(
                'productos_inventario',
                {
                    title: titulo,
                    data: grupos,
                    mode: 'por_estado',
                    conteos: conteos,
                },
                `productos_${grupoSeleccionado ? grupoSeleccionado.replace(/\s+/g, '_').toLowerCase() : 'por_estado'}_${new Date().toISOString().slice(0,10)}.pdf`,
                {
                    titulo_documento: titulo,
                    doc_color: '#1e40af',
                }
            );
        } catch(e){
            console.error(e);
            toast('Error al generar el PDF: ' + e.message, 'error');
        } finally{
            btn.disabled = false;
            btn.innerHTML = original;
            if (window.lucide) lucide.createIcons();
        }
    }

    /* ============================================================
       Actualiza el texto y color del botón PDF por Estado
       según el filtro de stock seleccionado
       ============================================================ */
    function actualizarBotonPdf(){
        const btn = $('btnPdfPorEstado');
        const txt = $('btnPdfPorEstadoText');
        if (!btn || !txt) return;

        const filtro = $('filtroStock')?.value || '';

        // Reset clases
        btn.classList.remove('btn-primary', 'btn-danger', 'btn-warning', 'btn-success');

        const map = {
            ''    : { texto: 'PDF por Estado',     clase: 'btn-primary' },
            'sin' : { texto: 'PDF · Sin stock',    clase: 'btn-danger'  },
            'bajo': { texto: 'PDF · Stock bajo',   clase: 'btn-warning' },
            'ok'  : { texto: 'PDF · Con stock',    clase: 'btn-success' },
        };

        const cfg = map[filtro] || map[''];
        txt.textContent = cfg.texto;
        btn.classList.add(cfg.clase);
    }
});