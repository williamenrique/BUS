console.log('✅ menu.js cargado');

document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'menu';
    const $ = id => document.getElementById(id);

    let combos = { padres: [], usuarios: [], departamentos: [] };
    let menusFlat = [];
    let menuActual = null;

    /* ============================================================
       INIT
       ============================================================ */
    (async () => {
        try{
            await cargarCombos();
            await cargarArbol();
            bindEvents();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================================
       API
       ============================================================ */
    async function fetchJSON(url, opts = {}){
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            ...opts
        });
        return r.json();
    }

    async function cargarCombos(){
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) throw new Error('combos');
        combos = d;

        fillSelect($('menu_padre_id'), d.padres, 'menu_id', 'menu_nombre', '— Ninguno (raíz) —');
        fillSelect($('selUsuarioAdd'), d.usuarios, 'usuario_id', u => {
            const nombre = `${u.personal_nombre || ''} ${u.personal_apellido || ''}`.trim();
            return nombre ? `${u.usuario_nick} · ${nombre}` : u.usuario_nick;
        }, 'Seleccione usuario...');
        fillSelect($('selDeptoAdd'), d.departamentos, 'departamento_id', 'departamento_nombre', 'Seleccione departamento...');
    }

    function fillSelect(sel, items, vKey, lKey, placeholder){
        if (!sel) return;
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it[vKey];
            o.textContent = typeof lKey === 'function' ? lKey(it) : it[lKey];
            sel.appendChild(o);
        });
    }

    /* ============================================================
       CARGAR ÁRBOL Y APLANAR
       ============================================================ */
    async function cargarArbol(){
        const d = await fetchJSON(API + '/arbol');
        if (!d.ok) throw new Error(d.message);

        // Aplanar el árbol en lista con padre_id y nivel
        menusFlat = [];
        const recorrer = (nodos, nivel = 0, padreNombre = null) => {
            nodos.forEach(n => {
                menusFlat.push({ ...n, _nivel: nivel, _padreNombre: padreNombre });
                if (n.children && n.children.length){
                    recorrer(n.children, nivel + 1, n.menu_nombre);
                }
            });
        };
        recorrer(d.data);

        renderGrid();
    }

    /* ============================================================
       RENDER GRID DE CARDS
       ============================================================ */
    function renderGrid(){
        const grid = $('gridMenus');
        const q = ($('filtroMenu')?.value || '').toLowerCase().trim();
        const est = $('filtroEstadoMenu')?.value || '';
        const tipo = $('filtroTipoMenu')?.value || '';

        // Solo mostramos menús PADRE en el grid (los hijos van dentro del modal)
        let lista = menusFlat.filter(m => m._nivel === 0);

        if (q) lista = lista.filter(m => (m.menu_nombre || '').toLowerCase().includes(q) || (m.menu_ruta || '').toLowerCase().includes(q));
        if (est !== '') lista = lista.filter(m => String(m.menu_estado) === est);
        if (tipo === 'link')  lista = lista.filter(m => !tieneHijos(m));
        if (tipo === 'grupo') lista = lista.filter(m => tieneHijos(m));

        if (!lista.length){
            grid.innerHTML = `
                <div class="menu-empty">
                    <i data-lucide="inbox" class="w-16 h-16 mx-auto" style="color:#cbd5e1;"></i>
                    <h4>Sin menús</h4>
                    <p>No se encontraron menús con los filtros seleccionados.</p>
                </div>`;
            if (window.lucide) lucide.createIcons();
            return;
        }

        grid.innerHTML = lista.map(renderCard).join('');
        bindCardActions();
        if (window.lucide) lucide.createIcons();
    }

    function tieneHijos(m){
        return menusFlat.some(x => x.menu_padre_id == m.menu_id);
    }

    function getHijos(menuId){
        return menusFlat.filter(x => x.menu_padre_id == menuId);
    }

    function renderCard(m){
        const hijos = getHijos(m.menu_id);
        const esGrupo = hijos.length > 0;
        const activo = Number(m.menu_estado) === 1;
        const padreNombre = m._padreNombre ? `<span class="menu-card-padre">· ${esc(m._padreNombre)}</span>` : '';

        const badgeTipo = esGrupo
            ? `<span class="menu-badge badge-grupo">${iconSvg('folder-tree')} ${hijos.length} submenú${hijos.length !== 1 ? 's' : ''}</span>`
            : `<span class="menu-badge badge-link">${iconSvg('link')} Link directo</span>`;

        const badgeEstado = activo
            ? `<span class="menu-badge badge-activo">${iconSvg('check')} Activo</span>`
            : `<span class="menu-badge badge-inactivo">${iconSvg('x')} Inactivo</span>`;

        const submenuList = esGrupo
            ? `<div class="menu-card-submenus">
                 <div class="menu-card-submenus-head">Submenús</div>
                 <ul>
                    ${hijos.map(h => `
                        <li>
                            <span class="menu-card-sub-icon">
                                ${iconSvg(h.menu_icono || 'circle')}
                            </span>
                            <span class="menu-card-sub-name">${esc(h.menu_nombre)}</span>
                            <code class="menu-card-sub-ruta">/${esc(h.menu_ruta || '—')}</code>
                            ${Number(h.menu_estado) === 0 ? '<span class="menu-sub-off">OFF</span>' : ''}
                        </li>
                    `).join('')}
                 </ul>
               </div>`
            : '';

        return `
            <div class="menu-card ${activo ? '' : 'menu-card-off'}" data-id="${m.menu_id}">
                <div class="menu-card-header">
                    <span class="menu-card-icon">
                        ${iconSvg(m.menu_icono || 'circle')}
                    </span>
                    <div class="menu-card-title">
                        <strong>${esc(m.menu_nombre)}</strong>
                        ${padreNombre}
                    </div>
                    <div class="menu-card-badges">
                        ${badgeTipo}
                        ${badgeEstado}
                    </div>
                </div>

                <div class="menu-card-body">
                    ${!esGrupo ? `
                        <div class="menu-card-info">
                            <span class="menu-card-info-label">Ruta</span>
                            <code>/${esc(m.menu_ruta || '—')}</code>
                        </div>
                    ` : ''}

                    <div class="menu-card-meta">
                        <span>
                            ${iconSvg('hash')} Orden ${m.menu_orden}
                        </span>
                        <span>
                            ${iconSvg('layers')} ${esc(m.menu_scope || 'general')}
                        </span>
                        ${m.usuarios_count > 0 ? `<span>${iconSvg('users')} ${m.usuarios_count} usuarios</span>` : ''}
                        ${m.deptos_count > 0 ? `<span>${iconSvg('building-2')} ${m.deptos_count} deptos</span>` : ''}
                    </div>

                    ${submenuList}
                </div>

                <div class="menu-card-actions">
                    <button class="action-btn edit" data-action="editar" data-id="${m.menu_id}" title="Editar">
                        ${iconSvg('pencil')}
                    </button>
                    ${esGrupo ? `
                        <button class="action-btn group" data-action="submenus" data-id="${m.menu_id}" title="Gestionar submenús">
                            ${iconSvg('folder-tree')}
                        </button>
                    ` : `
                        <button class="action-btn group" data-action="submenus" data-id="${m.menu_id}" title="Convertir en grupo / añadir submenús">
                            ${iconSvg('folder-plus')}
                        </button>
                    `}
                    <button class="action-btn toggle" data-action="estado" data-id="${m.menu_id}" data-estado="${activo ? 0 : 1}" title="${activo ? 'Desactivar' : 'Activar'}">
                        ${iconSvg(activo ? 'eye-off' : 'eye')}
                    </button>
                    <button class="action-btn delete" data-action="eliminar" data-id="${m.menu_id}" title="Eliminar">
                        ${iconSvg('trash-2')}
                    </button>
                </div>
            </div>`;
    }

    function iconSvg(name){
        return `<i data-lucide="${esc(name)}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i>`;
    }

    /* ============================================================
       EVENTOS CARDS
       ============================================================ */
    function bindCardActions(){
        document.querySelectorAll('#gridMenus [data-action]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const { action, id, estado } = btn.dataset;
                if (action === 'editar')   abrirEditar(id);
                if (action === 'submenus') abrirModalSubmenus(id);
                if (action === 'estado')   cambiarEstado(id, estado);
                if (action === 'eliminar') eliminarMenu(id);
            });
        });
    }

    /* ============================================================
       EVENTOS FORM
       ============================================================ */
    function bindEvents(){
        $('btnNuevoMenu')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => { e.stopPropagation(); toggleSeccion(); });

        $('formMenu')?.addEventListener('submit', guardar);

        // Tipo: link vs grupo (oculta/muestra ruta)
        document.querySelectorAll('input[name="menu_tipo"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                document.querySelectorAll('.menu-tipo-opt').forEach(o => o.classList.remove('active'));
                e.target.closest('.menu-tipo-opt').classList.add('active');
                actualizarTipoForm(e.target.value);
            });
        });

        // Preview de icono
        $('menu_icono')?.addEventListener('input', (e) => {
            const name = e.target.value.trim();
            $('iconPreview').innerHTML = name
                ? `<i data-lucide="${esc(name)}" class="w-4 h-4"></i>`
                : `<i data-lucide="circle" class="w-4 h-4"></i>`;
            if (window.lucide) lucide.createIcons();
        });

        // Botones asignar
        $('btnAddUsuario')?.addEventListener('click', agregarUsuario);
        $('btnAddDepto')?.addEventListener('click', agregarDepto);

        // Filtros
        $('filtroMenu')?.addEventListener('input', renderGrid);
        $('filtroEstadoMenu')?.addEventListener('change', renderGrid);
        $('filtroTipoMenu')?.addEventListener('change', renderGrid);
        $('btnExpandirTodo')?.addEventListener('click', toggleExpandirTodo);

        // Modal submenú
        $('formSubmenu')?.addEventListener('submit', guardarSubmenu);
        $('btnSmCancelar')?.addEventListener('click', limpiarFormSubmenu);

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape'){ cerrarFormulario(); cerrarModalSubmenu(); }
        });
    }

    function actualizarTipoForm(tipo){
        const esGrupo = tipo === 'grupo';
        $('rutaGroup').style.display = esGrupo ? 'none' : '';
        $('rutaReq').style.display = esGrupo ? 'none' : '';
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
    async function abrirCrear(){
        const f = $('formMenu');
        f.reset();
        $('menu_id').value = '';
        $('menu_padre_id').value = '';
        $('menu_estado').value = '1';
        $('menu_orden').value = '0';
        $('menu_scope').value = 'general';
        $('iconPreview').innerHTML = `<i data-lucide="circle" class="w-4 h-4"></i>`;
        if (window.lucide) lucide.createIcons();

        menuActual = null;
        $('blockUsuarios').style.display = 'none';
        $('blockDeptos').style.display = 'none';

        // Modo link por defecto
        document.querySelector('input[name="menu_tipo"][value="link"]').checked = true;
        document.querySelectorAll('.menu-tipo-opt').forEach(o => o.classList.remove('active'));
        document.querySelector('.menu-tipo-opt[data-tipo="link"]').classList.add('active');
        actualizarTipoForm('link');

        await cargarCombos();

        $('formSectionTitle').textContent = 'Nuevo Menú';
        $('formSectionSubtitle').textContent = 'Configura los datos básicos. Los submenús se gestionan aparte.';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="menu" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoMenuText').textContent = 'Nuevo Menú';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => $('menu_nombre')?.focus(), 300);
    }

    /* ============================================================
       EDITAR
       ============================================================ */
    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const m = d.data;
            menuActual = m;

            $('menu_id').value = m.menu_id;
            $('menu_nombre').value = m.menu_nombre || '';
            $('menu_icono').value = m.menu_icono || '';
            $('menu_ruta').value = m.menu_ruta || '';
            $('menu_orden').value = m.menu_orden || 0;
            $('menu_scope').value = m.menu_scope || 'general';
            $('menu_estado').value = m.menu_estado ?? 1;

            $('iconPreview').innerHTML = m.menu_icono
                ? `<i data-lucide="${esc(m.menu_icono)}" class="w-4 h-4"></i>`
                : `<i data-lucide="circle" class="w-4 h-4"></i>`;
            if (window.lucide) lucide.createIcons();

            // Detectar tipo: si tiene hijos → grupo, si no → link
            const hijos = getHijos(m.menu_id);
            const tipo = hijos.length > 0 ? 'grupo' : 'link';
            document.querySelector(`input[name="menu_tipo"][value="${tipo}"]`).checked = true;
            document.querySelectorAll('.menu-tipo-opt').forEach(o => o.classList.remove('active'));
            document.querySelector(`.menu-tipo-opt[data-tipo="${tipo}"]`).classList.add('active');
            actualizarTipoForm(tipo);

            // Cargar combos y excluir el propio menú
            const combosData = await fetchJSON(API + '/combos');
            const padres = combosData.padres.filter(p => Number(p.menu_id) !== Number(id));
            fillSelect($('menu_padre_id'), padres, 'menu_id', 'menu_nombre', '— Ninguno (raíz) —');
            $('menu_padre_id').value = m.menu_padre_id || '';

            $('formSectionTitle').textContent = 'Editar Menú';
            $('formSectionSubtitle').textContent = `Editando "${m.menu_nombre}"`;
            $('formSectionIconWrap').innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i>';
            if (window.lucide) lucide.createIcons();

            $('btnNuevoMenuText').textContent = 'Cancelar edición';
            $('btnGuardar').querySelector('.btn-text').textContent = 'Actualizar';

            // Mostrar bloques de asignación
            $('blockUsuarios').style.display = '';
            $('blockDeptos').style.display = '';
            await cargarUsuarios(id);
            await cargarDeptos(id);

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
    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formMenu');
        const id = $('menu_id').value;
        const payload = Object.fromEntries(new FormData(form).entries());
        const tipo = payload.menu_tipo;
        delete payload.menu_tipo;

        // Si es grupo, ruta vacía
        if (tipo === 'grupo'){
            payload.menu_ruta = '';
        }

        // Validaciones cliente
        if (!payload.menu_nombre?.trim()){
            form.querySelector('[data-error="menu_nombre"]').textContent = 'El nombre es obligatorio.';
            return;
        }
        if (tipo === 'link' && !payload.menu_ruta?.trim()){
            form.querySelector('[data-error="menu_ruta"]').textContent = 'La ruta es obligatoria para menús de link.';
            return;
        }

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
            await cargarArbol();
            await cargarCombos();

            if (!id){
                cerrarFormulario();
            } else {
                abrirEditar(id);
            }
        } catch (err){
            console.error(err);
            toast('Error de conexión', 'error');
        } finally{
            btn.disabled = false;
            btnText.textContent = original;
        }
    }

    /* ============================================================
       ELIMINAR / ESTADO
       ============================================================ */
    async function eliminarMenu(id){
        const m = menusFlat.find(x => x.menu_id == id);
        const hijos = getHijos(id);
        const msg = hijos.length
            ? `¿Eliminar "${m?.menu_nombre}"? Se eliminarán también ${hijos.length} submenú(s).`
            : `¿Eliminar "${m?.menu_nombre}"?`;
        if (!confirm(msg)) return;

        try{
            const d = await fetchJSON(API + '/eliminar/' + id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            await cargarArbol();
            await cargarCombos();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    async function cambiarEstado(id, estado){
        try{
            const d = await fetchJSON(API + '/cambiar-estado/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ menu_estado: Number(estado) })
            });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            await cargarArbol();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================================
       MODAL SUBMENÚS
       ============================================================ */
    async function abrirModalSubmenus(parentId){
        const parent = menusFlat.find(x => x.menu_id == parentId);
        if (!parent) return;

        $('sm_menu_id').value = parentId;
        $('submenuMenuNombre').textContent = parent.menu_nombre;

        // Si el padre tenía ruta (era link), sugerir quitarla (pasa a ser grupo)
        if (parent.menu_ruta){
            console.log('Padre tenía ruta:', parent.menu_ruta, '→ al añadir submenú puedes dejarla o quitarla editando el padre.');
        }

        limpiarFormSubmenu();
        await cargarSubmenus(parentId);

        $('modalSubmenu').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    window.cerrarModalSubmenu = function(){
        $('modalSubmenu').classList.remove('open');
        document.body.style.overflow = '';
        limpiarFormSubmenu();
    };

    async function cargarSubmenus(parentId){
        const lista = $('listaSubmenus');
        const hijos = getHijos(parentId);

        if (!hijos.length){
            lista.innerHTML = `<li class="text-muted" style="padding:1rem; text-align:center;">Sin submenús todavía.</li>`;
            return;
        }

        lista.innerHTML = hijos.map(h => `
            <li class="submenu-list-item">
                <div class="submenu-list-info">
                    <span class="submenu-list-icon">${iconSvg(h.menu_icono || 'circle')}</span>
                    <div>
                        <strong>${esc(h.menu_nombre)}</strong>
                        <code>/${esc(h.menu_ruta || '—')}</code>
                        ${Number(h.menu_estado) === 0 ? '<span class="menu-sub-off">OFF</span>' : ''}
                    </div>
                </div>
                <div class="submenu-list-actions">
                    <button class="action-btn edit" data-action="sm-editar" data-id="${h.menu_id}" title="Editar">
                        ${iconSvg('pencil')}
                    </button>
                    <button class="action-btn delete" data-action="sm-eliminar" data-id="${h.menu_id}" title="Eliminar">
                        ${iconSvg('trash-2')}
                    </button>
                </div>
            </li>
        `).join('');

        if (window.lucide) lucide.createIcons();

        lista.querySelectorAll('[data-action="sm-editar"]').forEach(b => {
            b.addEventListener('click', () => cargarSubmenuParaEditar(b.dataset.id));
        });
        lista.querySelectorAll('[data-action="sm-eliminar"]').forEach(b => {
            b.addEventListener('click', () => eliminarSubmenu(b.dataset.id));
        });
    }

    function cargarSubmenuParaEditar(id){
        const h = menusFlat.find(x => x.menu_id == id);
        if (!h) return;

        $('sm_id').value = h.menu_id;
        $('sm_nombre').value = h.menu_nombre || '';
        $('sm_icono').value = h.menu_icono || '';
        $('sm_ruta').value = h.menu_ruta || '';
        $('sm_orden').value = h.menu_orden || 0;
        $('sm_estado').value = h.menu_estado ?? 1;

        $('submenuFormTitle').textContent = 'Editar Submenú';
        $('btnSmGuardarText').textContent = 'Actualizar';

        // Scroll al form
        $('formSubmenu').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function limpiarFormSubmenu(){
        $('formSubmenu').reset();
        $('sm_id').value = '';
        $('sm_orden').value = '0';
        $('sm_estado').value = '1';
        $('submenuFormTitle').textContent = 'Nuevo Submenú';
        $('btnSmGuardarText').textContent = 'Guardar';
    }

    async function guardarSubmenu(e){
        e.preventDefault();

        const id = $('sm_id').value;
        const parentId = $('sm_menu_id').value;
        const payload = {
            menu_padre_id: Number(parentId),
            menu_nombre: $('sm_nombre').value.trim(),
            menu_icono: $('sm_icono').value.trim(),
            menu_ruta: $('sm_ruta').value.trim(),
            menu_orden: Number($('sm_orden').value) || 0,
            menu_estado: Number($('sm_estado').value),
            menu_scope: 'general',
            menu_es_desplegable: 0,
        };

        if (!payload.menu_nombre || !payload.menu_ruta){
            toast('Nombre y ruta son obligatorios.', 'error');
            return;
        }

        try{
            const url = id ? `${API}/actualizar/${id}` : `${API}/guardar`;
            const d = await fetchJSON(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (!d.ok){ toast(d.message || 'Error', 'error'); return; }

            toast(d.message, 'success');
            limpiarFormSubmenu();
            await cargarArbol();
            await cargarSubmenus(parentId);
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    async function eliminarSubmenu(id){
        if (!confirm('¿Eliminar este submenú?')) return;
        try{
            const d = await fetchJSON(API + '/eliminar/' + id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');

            const parentId = $('sm_menu_id').value;
            await cargarArbol();
            await cargarSubmenus(parentId);

            // Si era el último submenú, cerrar modal
            const hijos = getHijos(parentId);
            if (!hijos.length){
                window.cerrarModalSubmenu();
            }
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================================
       USUARIOS ASIGNADOS
       ============================================================ */
    async function cargarUsuarios(menuId){
        const d = await fetchJSON(API + '/usuarios/' + menuId);
        const lista = $('listaUsuarios');
        if (!d.ok || !d.data.length){
            lista.innerHTML = `<li class="text-muted" style="padding:.5rem; text-align:center;">Sin usuarios asignados.</li>`;
            return;
        }
        lista.innerHTML = d.data.map(u => {
            const nombre = `${u.personal_nombre || ''} ${u.personal_apellido || ''}`.trim();
            return `
                <li class="menu-lista-item">
                    <div>
                        <strong>${esc(u.usuario_nick)}</strong>
                        ${nombre ? `<span class="text-muted"> · ${esc(nombre)}</span>` : ''}
                    </div>
                    <button type="button" class="action-btn delete" data-user-id="${u.usuario_id}" title="Quitar">
                        ${iconSvg('x')}
                    </button>
                </li>`;
        }).join('');
        if (window.lucide) lucide.createIcons();

        lista.querySelectorAll('[data-user-id]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const uid = btn.dataset.userId;
                const rr = await fetchJSON(API + '/usuarios/quitar/' + menuId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario_id: uid })
                });
                if (rr.ok){ toast(rr.message, 'success'); cargarUsuarios(menuId); cargarArbol(); }
                else toast(rr.message, 'error');
            });
        });
    }

    async function agregarUsuario(){
        if (!menuActual){ toast('Guarda el menú primero', 'error'); return; }
        const uid = $('selUsuarioAdd').value;
        if (!uid){ toast('Selecciona un usuario', 'error'); return; }

        const d = await fetchJSON(API + '/usuarios/asignar/' + menuActual.menu_id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ usuario_id: uid })
        });
        if (!d.ok){ toast(d.message, 'error'); return; }
        toast(d.message, 'success');
        $('selUsuarioAdd').value = '';
        cargarUsuarios(menuActual.menu_id);
        cargarArbol();
    }

    /* ============================================================
       DEPARTAMENTOS ASIGNADOS
       ============================================================ */
    async function cargarDeptos(menuId){
        const d = await fetchJSON(API + '/departamentos/' + menuId);
        const lista = $('listaDeptos');
        if (!d.ok || !d.data.length){
            lista.innerHTML = `<li class="text-muted" style="padding:.5rem; text-align:center;">Sin departamentos asignados.</li>`;
            return;
        }
        lista.innerHTML = d.data.map(dp => `
            <li class="menu-lista-item">
                <div><strong>${esc(dp.departamento_nombre)}</strong></div>
                <button type="button" class="action-btn delete" data-depto-id="${dp.departamento_id}" title="Quitar">
                    ${iconSvg('x')}
                </button>
            </li>
        `).join('');
        if (window.lucide) lucide.createIcons();

        lista.querySelectorAll('[data-depto-id]').forEach(btn => {
            btn.addEventListener('click', async () => {
                const did = btn.dataset.deptoId;
                const rr = await fetchJSON(API + '/departamentos/quitar/' + menuId, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ departamento_id: did })
                });
                if (rr.ok){ toast(rr.message, 'success'); cargarDeptos(menuId); cargarArbol(); }
                else toast(rr.message, 'error');
            });
        });
    }

    async function agregarDepto(){
        if (!menuActual){ toast('Guarda el menú primero', 'error'); return; }
        const did = $('selDeptoAdd').value;
        if (!did){ toast('Selecciona un departamento', 'error'); return; }

        const d = await fetchJSON(API + '/departamentos/asignar/' + menuActual.menu_id, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ departamento_id: did })
        });
        if (!d.ok){ toast(d.message, 'error'); return; }
        toast(d.message, 'success');
        $('selDeptoAdd').value = '';
        cargarDeptos(menuActual.menu_id);
        cargarArbol();
    }

    /* ============================================================
       EXPANDIR TODO
       ============================================================ */
    function toggleExpandirTodo(){
        // No aplica porque cada card muestra sus submenús siempre. Solo cambia el texto.
        const btn = $('btnExpandirTodo');
        const span = btn.querySelector('span');
        const actual = span.textContent;
        span.textContent = actual === 'Expandir' ? 'Colapsar' : 'Expandir';

        document.querySelectorAll('.menu-card-submenus').forEach(el => {
            el.style.display = span.textContent === 'Colapsar' ? '' : 'none';
        });
    }

    /* ============================================================
       CERRAR FORM
       ============================================================ */
    function cerrarFormulario(){
        const form = $('formMenu');
        form.reset();
        $('menu_id').value = '';
        menuActual = null;

        $('blockUsuarios').style.display = 'none';
        $('blockDeptos').style.display = 'none';

        $('iconPreview').innerHTML = `<i data-lucide="circle" class="w-4 h-4"></i>`;
        if (window.lucide) lucide.createIcons();

        limpiarErrores();

        $('formSectionTitle').textContent = 'Nuevo Menú';
        $('formSectionSubtitle').textContent = 'Configura los datos básicos. Los submenús se gestionan aparte.';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="menu" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoMenuText').textContent = 'Nuevo Menú';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        cerrarSeccion();
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    function limpiarErrores(){
        const form = $('formMenu');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error').forEach(el => el.classList.remove('error'));
    }

    function esc(s){
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
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