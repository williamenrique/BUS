console.log('✅ despachos.js cargado');

document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'despachos/i/' + INST;
    const $ = id => document.getElementById(id);

    let dt = null;
    let combos = { flota: [], operadores: [], mecanicos: [], despachadores: [], articulos: [] };
    let articulosAgregados = [];
    let despachoActual = null; // Para el botón PDF

    function renderRow(d){
        return `
            <tr>
                <td><strong>#${d.numero_orden || d.id_despacho}</strong></td>
                <td>${formatFecha(d.fecha_despacho)}</td>
                <td>
                    <strong>${esc(d.id_unidad || '—')}</strong>
                    <div style="font-size:.7rem; color:#94a3b8;">
                        ${esc(d.marca_unidad || '')} ${esc(d.modelo_unidad || '')}
                    </div>
                </td>
                <td>${esc(d.operador_nombre || '—')}</td>
                <td class="text-center">
                    <span class="badge badge-info">${d.total_articulos} art.</span>
                </td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn" data-action="ver" data-id="${d.id_despacho}" title="Ver detalle">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn edit" data-action="editar" data-id="${d.id_despacho}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${d.id_despacho}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    (async () => {
        try{
            await cargarCombos();
            await cargarStats();

            $('fecha_despacho').valueAsDate = new Date();

            dt = new DataTable({
                tbodyId: 'tbodyDespachos',
                endpoint: API + '/listar',
                columns: ['#','Fecha','Unidad','Operador','Artículos','Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationDespachos',
                totalId: 'totalDespachos',
                startId: 'startDespachos',
                endId: 'endDespachos',
                noDataMessage: 'No hay despachos registrados',
                getExtraParams: () => ({
                    fecha: $('filtroFecha')?.value || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindForm();
            bindArticuloForm();
            bindEliminarForm();
        } catch (e){
            console.error(e);
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

        $('id_flota').innerHTML = '<option value="">Seleccione...</option>';
        d.flota.forEach(f => {
            const o = document.createElement('option');
            o.value = f.id_flota;
            o.textContent = `${f.id_unidad} - ${f.modelo_unidad || ''}`;
            o.dataset.info = JSON.stringify(f);
            $('id_flota').appendChild(o);
        });

        fillPersonal($('operador_id'),    d.operadores);
        fillPersonal($('mecanico_id'),    d.mecanicos);
        fillPersonal($('despachador_id'), d.despachadores);

        $('sel_articulo').innerHTML = '<option value="">Seleccione...</option>';
        d.articulos.forEach(a => {
            const o = document.createElement('option');
            o.value = a.id_producto;
            o.textContent = `${a.producto} (${a.cant_producto} ${a.present_producto || ''})`;
            o.dataset.stock = a.cant_producto;
            o.dataset.producto = a.producto;
            $('sel_articulo').appendChild(o);
        });
    }

    function fillPersonal(sel, items){
        sel.innerHTML = '<option value="">Seleccione...</option>';
        items.forEach(p => {
            const o = document.createElement('option');
            o.value = p.id_personal;
            o.textContent = `${p.personal_cedula} - ${p.personal_nombre} ${p.personal_apellido}`;
            sel.appendChild(o);
        });
    }

    async function cargarStats(){
        try{
            const d = await fetchJSON(API + '/stats');
            if (!d.ok) return;
            const { actual, meta, porcentaje } = d.data;
            $('progressPercentage').textContent = porcentaje + '%';
            $('progressBarFill').style.width = porcentaje + '%';
            $('progressCurrent').textContent = `${actual} de ${meta} órdenes`;
        } catch {}
    }

    function bindEvents(){
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });

        $('id_flota')?.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            if (!opt || !opt.dataset.info){
                $('unidadInfo').style.display = 'none';
                return;
            }
            const info = JSON.parse(opt.dataset.info);
            $('info_id_unidad').textContent = info.id_unidad || '—';
            $('info_vim_unidad').textContent = info.vim_unidad || '—';
            $('info_marca').textContent = info.marca_unidad || '—';
            $('info_modelo').textContent = info.modelo_unidad || '—';
            $('unidadInfo').style.display = '';
        });

        $('filtroFecha')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());

        // Botón PDF dentro del modal de detalle
        $('btnImprimirPdfDetalle')?.addEventListener('click', generarPdfDetalle);

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape'){ cerrarFormulario(); cerrarModalDespacho('modalDetalle'); cerrarModalDespacho('modalEliminar'); }
        });
    }

    function bindTableActions(){
        const tbody = $('tbodyDespachos');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id } = btn.dataset;
            if (action === 'ver')      verDespacho(id);
            if (action === 'editar')   abrirEditar(id);
            if (action === 'eliminar') abrirEliminar(id);
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

    function bindArticuloForm(){
        $('sel_articulo')?.addEventListener('change', (e) => {
            const opt = e.target.selectedOptions[0];
            const stock = opt?.dataset.stock || 0;
            $('stock_disponible').textContent = stock;
        });

        $('btnAddArticulo')?.addEventListener('click', agregarArticulo);
    }

    function agregarArticulo(){
        const sel = $('sel_articulo');
        const opt = sel.selectedOptions[0];
        const cant = parseFloat($('inp_cantidad').value);

        if (!opt || !opt.value){
            toast('Selecciona un artículo.', 'error');
            return;
        }
        if (!cant || cant <= 0){
            toast('Ingresa una cantidad válida.', 'error');
            return;
        }

        const stockDisp = parseFloat(opt.dataset.stock || 0);
        if (cant > stockDisp){
            toast(`Stock insuficiente. Disponible: ${stockDisp}`, 'error');
            return;
        }

        const idProd = parseInt(opt.value);
        if (articulosAgregados.some(a => a.id_producto === idProd)){
            toast('Este artículo ya está agregado.', 'error');
            return;
        }

        articulosAgregados.push({
            id_producto: idProd,
            producto: opt.dataset.producto,
            cantidad: cant
        });

        renderArticulos();
        $('inp_cantidad').value = '';
        sel.value = '';
        $('stock_disponible').textContent = '0';
    }

    function renderArticulos(){
        const tbody = $('tbodyArticulos');

        if (!articulosAgregados.length){
            tbody.innerHTML = `<tr><td colspan="4" class="dt-empty">No hay artículos agregados</td></tr>`;
            return;
        }

        tbody.innerHTML = articulosAgregados.map((a, i) => `
            <tr>
                <td>COD ${String(a.id_producto).padStart(4, '0')}</td>
                <td>${esc(a.producto)}</td>
                <td class="text-right">${a.cantidad}</td>
                <td class="text-right">
                    <button type="button" class="action-btn delete" data-idx="${i}" title="Quitar">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        if (window.lucide) lucide.createIcons();

        tbody.querySelectorAll('[data-idx]').forEach(btn => {
            btn.addEventListener('click', () => {
                articulosAgregados.splice(parseInt(btn.dataset.idx), 1);
                renderArticulos();
            });
        });
    }

    function abrirCrear(){
        const f = $('formDespacho');
        f.reset();
        $('id_despacho').value = '';
        $('fecha_despacho').valueAsDate = new Date();
        $('unidadInfo').style.display = 'none';
        articulosAgregados = [];
        renderArticulos();

        $('formSectionTitle').textContent = 'Nueva Orden de Despacho';
        $('formSectionSubtitle').textContent = 'Selecciona la unidad, personal y artículos';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="truck" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nueva Orden';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Generar Orden';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const o = d.data;

            $('id_despacho').value = o.id_despacho;
            $('id_flota').value = o.id_flota || '';
            $('fecha_despacho').value = o.fecha_despacho || '';
            $('operador_id').value = o.operador_id || '';
            $('mecanico_id').value = o.mecanico_id || '';
            $('despachador_id').value = o.despachador_id || '';
            $('observacion').value = o.observacion || '';

            $('id_flota').dispatchEvent(new Event('change'));

            articulosAgregados = (o.articulos || []).map(a => ({
                id_producto: parseInt(a.id_producto),
                producto: a.producto,
                cantidad: parseFloat(a.cant_despacho)
            }));
            renderArticulos();

            $('formSectionTitle').textContent = 'Editar Despacho';
            $('formSectionSubtitle').textContent = `Editando orden #${o.numero_orden || o.id_despacho}`;
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
        $('formDespacho')?.addEventListener('submit', guardar);
    }

    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formDespacho');
        const id = $('id_despacho').value;

        if (!articulosAgregados.length){
            toast('Debes agregar al menos un artículo.', 'error');
            return;
        }

        const payload = {
            id_flota:         $('id_flota').value,
            fecha_despacho:   $('fecha_despacho').value,
            operador_id:      $('operador_id').value,
            mecanico_id:      $('mecanico_id').value,
            despachador_id:   $('despachador_id').value,
            observacion:      $('observacion').value,
            articulos:        articulosAgregados
        };

        const btn = $('btnGuardar');
        btn.disabled = true;
        const btnText = btn.querySelector('.btn-text');
        const original = btnText.textContent;
        btnText.textContent = id ? 'Actualizando...' : 'Generando...';

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
            $('id_despacho').value = '';
            articulosAgregados = [];
            cerrarFormulario();
            dt?.refresh();
            cargarCombos();
            cargarStats();
        } catch (err){
            console.error(err);
            toast('Error de conexión', 'error');
        } finally{
            btn.disabled = false;
            btnText.textContent = original;
        }
    }

    async function verDespacho(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);

            if (!d || !d.ok){
                toast(d?.message || 'No se pudo cargar el despacho.', 'error');
                return;
            }

            const o = d.data;
            if (!o){
                toast('El despacho no tiene datos.', 'error');
                return;
            }

            despachoActual = o;
            const arts = Array.isArray(o.articulos) ? o.articulos : [];

            // Remover modal previo
            document.getElementById('__modalDetalleDinamico')?.remove();

            // Crear modal desde cero (todo inline, no depende del CSS)
            const wrap = document.createElement('div');
            wrap.id = '__modalDetalleDinamico';
            wrap.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;overflow-y:auto;';

            const artsHtml = arts.length
                ? arts.map(a => `
                    <tr>
                        <td style="padding:.6rem .8rem; border-bottom:1px solid #f1f5f9;">${esc(a.producto || '—')}</td>
                        <td style="padding:.6rem .8rem; border-bottom:1px solid #f1f5f9; text-align:right;">${a.cant_despacho ?? '—'} ${esc(a.present_producto || '')}</td>
                        <td style="padding:.6rem .8rem; border-bottom:1px solid #f1f5f9;">${esc(a.ubicacion || '—')}</td>
                    </tr>`).join('')
                : '<tr><td colspan="3" style="padding:1rem; text-align:center; color:#94a3b8;">Sin artículos</td></tr>';

            wrap.innerHTML = `
                <div style="background:#fff;border-radius:14px;width:100%;max-width:720px;max-height:90vh;display:flex;flex-direction:column;box-shadow:0 25px 50px rgba(0,0,0,.3);overflow:hidden;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.5rem;border-bottom:1px solid #e2e8f0;flex-shrink:0;">
                        <h3 style="margin:0;font-size:1.125rem;font-weight:700;color:#0f172a;">
                            Despacho #${o.numero_orden || o.id_despacho || id}
                        </h3>
                        <button type="button" data-close="1" style="background:transparent;border:none;font-size:1.5rem;line-height:1;color:#94a3b8;cursor:pointer;padding:0 .25rem;">&times;</button>
                    </div>

                    <div style="padding:1.5rem;overflow-y:auto;flex:1;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.25rem;">
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Fecha</p><strong>${formatFecha(o.fecha_despacho)}</strong></div>
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Unidad</p><strong>${esc(o.id_unidad || '—')}</strong></div>
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Operador</p><strong>${esc(o.operador_nombre || '—')}</strong></div>
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Mecánico</p><strong>${esc(o.mecanico_nombre || '—')}</strong></div>
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Despachador</p><strong>${esc(o.despachador_nombre || '—')}</strong></div>
                            <div><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Artículos</p><strong>${arts.length}</strong></div>
                            <div style="grid-column:1 / -1;"><p style="font-size:.7rem;text-transform:uppercase;color:#94a3b8;margin:0;">Creado por</p><strong>${esc(o.usuario_registro || ('Usuario #' + (o.user_id || '—')))}</strong></div>
                        </div>

                        ${o.observacion ? `<div style="background:#f8fafc;border-left:3px solid #cbd5e1;padding:.65rem .85rem;border-radius:.35rem;margin-bottom:1rem;font-size:.85rem;color:#475569;font-style:italic;">${esc(o.observacion)}</div>` : ''}

                        <h4 style="font-size:.9rem;font-weight:600;color:#334155;margin:1rem 0 .5rem;">Artículos despachados</h4>
                        <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                            <thead>
                                <tr>
                                    <th style="background:#f8fafc;text-align:left;padding:.6rem .8rem;font-size:.7rem;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;">Artículo</th>
                                    <th style="background:#f8fafc;text-align:right;padding:.6rem .8rem;font-size:.7rem;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;">Cantidad</th>
                                    <th style="background:#f8fafc;text-align:left;padding:.6rem .8rem;font-size:.7rem;text-transform:uppercase;color:#475569;border-bottom:1px solid #e2e8f0;">Ubicación</th>
                                </tr>
                            </thead>
                            <tbody>${artsHtml}</tbody>
                        </table>
                    </div>

                    <div style="display:flex;justify-content:flex-end;gap:.75rem;padding:1rem 1.5rem;border-top:1px solid #e2e8f0;flex-shrink:0;">
                        <button type="button" data-close="1" class="btn btn-ghost">Cerrar</button>
                        <button type="button" id="__btnPdfDetalle" class="btn btn-primary">Imprimir PDF</button>
                    </div>
                </div>
            `;

            wrap.addEventListener('click', (e) => { if (e.target === wrap) wrap.remove(); });
            wrap.querySelectorAll('[data-close]').forEach(b => b.addEventListener('click', () => wrap.remove()));
            wrap.querySelector('#__btnPdfDetalle').addEventListener('click', async (e) => {
                const b = e.currentTarget;
                b.disabled = true;
                try {
                    await SITGO_PDF.generar(
                        'despachos_orden',
                        { despacho: despachoActual },
                        `orden_despacho_${despachoActual.numero_orden || despachoActual.id_despacho}.pdf`,
                        {
                            titulo_documento: 'ORDEN DE DESPACHO',
                            doc_numero: `#${String(despachoActual.numero_orden || despachoActual.id_despacho).padStart(6, '0')}`,
                            doc_color: '#1e40af',
                        }
                    );
                } finally {
                    b.disabled = false;
                }
            });

            const onEsc = (e) => { if (e.key === 'Escape'){ wrap.remove(); document.removeEventListener('keydown', onEsc); } };
            document.addEventListener('keydown', onEsc);

            document.body.appendChild(wrap);
            document.body.style.overflow = 'hidden';
            console.log('✅ Modal dinámico insertado:', document.getElementById('__modalDetalleDinamico') ? 'SÍ' : 'NO');

        } catch (e){
            console.error('verDespacho excepción:', e);
            toast('Error: ' + e.message, 'error');
        }
    }
    async function generarPdfDetalle(){
        if (!despachoActual) return;

        const btn = $('btnImprimirPdfDetalle');
        btn.disabled = true;

        try {
            await SITGO_PDF.generar(
                'despachos_orden',
                { despacho: despachoActual },
                `orden_despacho_${despachoActual.numero_orden || despachoActual.id_despacho}.pdf`,
                {
                    titulo_documento: 'ORDEN DE DESPACHO',
                    doc_numero:       `#${String(despachoActual.numero_orden || despachoActual.id_despacho).padStart(6, '0')}`,
                    doc_color:        '#1e40af',
                }
            );
        } finally {
            btn.disabled = false;
        }
    }

    function abrirEliminar(id){
        const d = dt?.getData().find(x => Number(x.id_despacho) === Number(id));
        $('del_id_despacho').value = id;
        $('del_numero_orden').textContent = d?.numero_orden || id;
        $('del_motivo').value = '';
        $('modalEliminar').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function bindEliminarForm(){
        $('formEliminar')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = $('del_id_despacho').value;
            const motivo = $('del_motivo').value.trim();

            if (!motivo){ toast('Escribe un motivo.', 'error'); return; }

            try{
                const d = await fetchJSON(`${API}/eliminar/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ motivo })
                });

                if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

                toast(d.message, 'success');
                cerrarModalDespacho('modalEliminar');
                dt?.refresh();
                cargarCombos();
                cargarStats();
            } catch (err){
                toast('Error de conexión', 'error');
            }
        });
    }

    window.cerrarModalDespacho = function(idModal){
        const m = $(idModal);
        if (!m) return;
        m.classList.remove('open');
        m.style.display = 'none';
        document.body.style.overflow = '';
    };

    function limpiarErrores(){
        const form = $('formDespacho');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function cerrarFormulario(){
        const form = $('formDespacho');
        form.reset();
        $('id_despacho').value = '';
        $('fecha_despacho').valueAsDate = new Date();
        $('unidadInfo').style.display = 'none';
        articulosAgregados = [];
        renderArticulos();
        limpiarErrores();

        $('formSectionTitle').textContent = 'Nueva Orden de Despacho';
        $('formSectionSubtitle').textContent = 'Selecciona la unidad, personal y artículos';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="truck" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nueva Orden';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Generar Orden';

        cerrarSeccion();
    }

    function formatFecha(f){
        if (!f) return '—';
        try{
            const parts = f.split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
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