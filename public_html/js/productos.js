document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'productos/i/' + INST;
    const $ = id => document.getElementById(id);

    let dt = null;
    let combos = { enlaces: [], proveedores: [], ubicaciones: [] };

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
                    enlace:    $('filtroEnlace')?.value    || '',
                    ubicacion: $('filtroUbicacion')?.value || '',
                    stock:     $('filtroStock')?.value     || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindForm();
            bindStockForm();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

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

        fillSelect($('id_enlace_producto'), d.enlaces, 'id', 'nombre', 'Seleccione...');
        fillSelect($('id_ubicacion'),       d.ubicaciones, 'id', 'nombre', 'Seleccione...');
        fillSelect($('id_proveedor'),       d.proveedores, 'id', 'nombre', 'Seleccione...');

        fillSelect($('filtroEnlace'),    d.enlaces, 'id', 'nombre', 'Todos los tipos');
        fillSelect($('filtroUbicacion'), d.ubicaciones, 'id', 'nombre', 'Todas las ubicaciones');
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
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });

        $('filtroEnlace')?.addEventListener('change',    () => dt?.changePage(1) || dt?.reload());
        $('filtroUbicacion')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroStock')?.addEventListener('change',     () => dt?.changePage(1) || dt?.reload());

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
        $('id_enlace_producto').value = '';
        $('id_ubicacion').value = '';
        $('id_proveedor').value = '';
        $('present_producto').value = 'UNIDAD';
        $('tag_producto').value = '1';
        $('cant_producto').value = 0;

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
            $('id_enlace_producto').value      = p.id_enlace_producto || '';
            $('id_ubicacion').value            = p.id_ubicacion || '';
            $('id_proveedor').value            = p.id_proveedor || '';
            $('present_producto').value        = p.present_producto || 'UNIDAD';
            $('tag_producto').value            = p.tag_producto || '1';

            // En edición NO se cambia el stock desde aquí
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
        if (!confirm(`¿Eliminar "${nombre}"? Esta acción no se puede deshacer.`)) return;

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
});