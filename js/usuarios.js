document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'usuarios';
    // console.log('📡 API Usuarios =', API);

    const $ = id => document.getElementById(id);
    let dt = null;
    let ssPersonal = null;   // instancia del SearchSelect de personal
        let menusArbol = [];
    let menusSeleccionados = new Set();

    /* ============================================
       RENDER ROW (fila de tabla)
       ============================================ */
    function renderRow(u){
        const active = Number(u.usuario_status) === 1;
        const badge  = active ? 'badge-success' : 'badge-danger';
        const txt    = active ? 'Activo' : 'Inactivo';

        const nombrePersonal = (u.personal_nombre || u.personal_apellido)
            ? `${u.personal_nombre || ''} ${u.personal_apellido || ''}`.trim()
            : '<span class="text-muted">Sin vincular</span>';

        // Avatar: si hay imagen → usarla; si no → iniciales (fallback)
        const avatarUrl = u.usuario_imagen
            ? (window.SITGO_BASE || '/') + 'uploads/storage/' + esc(u.usuario_imagen)
            : null;

        const inicial = (u.usuario_nick || '?').charAt(0).toUpperCase();
        const avatarHtml = avatarUrl
            ? `<img src="${avatarUrl}" alt="Avatar" class="user-avatar-sm" onerror="this.outerHTML='<div class=\\'user-avatar-sm user-avatar-fallback\\'>${inicial}</div>'">`
            : `<div class="user-avatar-sm user-avatar-fallback">${inicial}</div>`;

        return `
            <tr>
                <td>${avatarHtml}</td>
                <td><strong>${esc(u.usuario_nick)}</strong></td>
                <td>${nombrePersonal}</td>
                <td><span class="badge badge-info">${esc(u.rol_nombre || '—')}</span></td>
                <td>${esc(u.departamento_nombre || '—')}</td>
                <td>${esc(u.estacion_nombre || '—')}</td>
                <td><span class="badge ${badge}">${txt}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn" data-action="delimg" data-id="${u.usuario_id}" title="Eliminar imagen">
                            <i data-lucide="image-off" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn" data-action="reset" data-id="${u.usuario_id}" title="Resetear contraseña">
                            <i data-lucide="key-round" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn edit" data-action="editar" data-id="${u.usuario_id}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn toggle" data-action="toggle" data-id="${u.usuario_id}" data-status="${active ? 0 : 1}" title="${active ? 'Desactivar' : 'Activar'}">
                            <i data-lucide="${active ? 'user-x' : 'user-check'}" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${u.usuario_id}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    /* ============================================
       INIT
       ============================================ */
    (async () => {
        try{
            // 1) Inicializar SearchSelect de personal ANTES de cargar datos
            initSearchSelect();

            // 2) Cargar combos (roles, departamentos, estaciones)
            await cargarCombos();

            // 3) Inicializar DataTable
            dt = new DataTable({
                tbodyId: 'tbodyUsuarios',
                endpoint: API + '/listar',
                columns: ['', 'Usuario', 'Personal', 'Rol', 'Departamento', 'Estación', 'Estatus', 'Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationUsuarios',
                totalId: 'totalUsuarios',
                startId: 'startUsuarios',
                endId: 'endUsuarios',
                noDataMessage: 'No hay usuarios registrados',
                getExtraParams: () => ({
                    status: $('filtroStatus')?.value || '',
                    rol: $('filtroRol')?.value || '',
                    departamento: $('filtroDepartamento')?.value || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindForm();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================
       SEARCHSELECT · Personal
       ============================================ */
    function initSearchSelect(){
        ssPersonal = new SearchSelect({
            wrapperId: 'wrapPersonal',
            hiddenInputId: 'usuario_id_personal',
            placeholder: 'Buscar por cédula o nombre...',
            noResultsText: 'Sin coincidencias',
            onChange: () => {
                // El onChange no hace nada especial; solo guardamos el valor.
                // El backend autogenera el nick al guardar.
            }
        });
    }

    /**
     * Recarga las opciones del SearchSelect con el personal disponible.
     */
    function actualizarOpcionesPersonal(items, seleccionado = null){
        const opts = (items || []).map(p => ({
            value: p.id_personal,
            label: `${p.personal_apellido} ${p.personal_nombre} (${p.personal_cedula})`,
            search: `${p.personal_cedula} ${p.personal_nombre} ${p.personal_apellido}`.toLowerCase()
        }));
        ssPersonal.setOptions(opts);
        if (seleccionado) ssPersonal.setValue(seleccionado);
    }

    /* ============================================
       API helper
       ============================================ */
    async function fetchJSON(url, opts = {}){
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, ...opts });
        return r.json();
    }

    /**
     * Carga combos (roles, departamentos, estaciones) y personal disponible.
     */
    async function cargarCombos(personalId = null){
        const url = API + '/combos' + (personalId ? '?personal_id=' + personalId : '');
        const d = await fetchJSON(url);
        if (!d.ok) throw new Error('combos');

        fillSelect($('usuario_rol_id'), d.roles, 'rol_id', 'rol_nombre', 'Seleccione...');
        fillSelect($('usuario_departamento_id'), d.departamentos, 'departamento_id', 'departamento_nombre', 'Seleccione...');
        fillSelect($('filtroRol'), d.roles, 'rol_id', 'rol_nombre', 'Todos los roles');
        fillSelect($('filtroDepartamento'), d.departamentos, 'departamento_id', 'departamento_nombre', 'Todos los departamentos');

        // Estación: usa función especial que respeta el value 0
        fillEstaciones(d.estaciones);

        // Personal → SearchSelect
        actualizarOpcionesPersonal(d.personal, personalId);
    }

    /**
     * Llena el select de estaciones manteniendo la opción "Sin estación (0)".
     */
    function fillEstaciones(items){
        const sel = $('usuario_estacion_id');
        if (!sel) return;
        sel.innerHTML = `<option value="0">Sin estación (0)</option>`;
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it.id_estacion;
            o.textContent = it.estacion;
            sel.appendChild(o);
        });
    }

    /**
     * Rellena un <select> nativo con opciones.
     */
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

    /* ============================================
       EVENTOS GENERALES
       ============================================ */
    function bindEvents(){
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSeccion();
        });

        // Filtros de la tabla
        $('filtroStatus')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroRol')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroDepartamento')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());

        // Preview de imagen
        $('usuario_imagen')?.addEventListener('change', (e) => {
            const f = e.target.files?.[0];
            if (!f) return;
            const url = URL.createObjectURL(f);
            $('previewImg').src = url;
            $('wrapPreview').style.display = '';
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') cerrarFormulario();
        });
    }

    /**
     * Delegación de clics para las acciones de la tabla.
     */
    function bindTableActions(){
        const tbody = $('tbodyUsuarios');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id, status } = btn.dataset;
            if (action === 'editar')   abrirEditar(id);
            if (action === 'eliminar') eliminar(id);
            if (action === 'toggle')   toggleStatus(id, status);
            if (action === 'reset')    resetPassword(id);
            if (action === 'delimg')   eliminarImagen(id);   // 👈 NUEVA
        });
    }

    /* ============================================
       SECCIÓN COLAPSABLE
       ============================================ */
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

    /* ============================================
       CREAR
       ============================================ */
    async function abrirCrear(){
        const f = $('formUsuario');
        f.reset();

        $('usuario_id').value = '';
        $('usuario_status').value = '1';
        $('usuario_rol_id').value = '';
        $('usuario_departamento_id').value = '';
        $('usuario_estacion_id').value = '';

        // Limpiar SearchSelect de personal
        ssPersonal?.clear();

        // Recargar combos actualizados
        await cargarCombos();

        $('wrapPreview').style.display = 'none';

        $('formSectionTitle').textContent = 'Nuevo Usuario';
        $('formSectionSubtitle').textContent = 'Selecciona un personal y completa los datos';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Usuario';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    /* ============================================
       EDITAR
       ============================================ */
    async function abrirEditar(id){
        try{
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const u = d.data;

            // Cargar combos incluyendo el personal ya vinculado
            await cargarCombos(u.usuario_id_personal || null);

            $('usuario_id').value              = u.usuario_id;
            $('usuario_rol_id').value          = u.usuario_rol_id || '';
            $('usuario_departamento_id').value = u.usuario_departamento_id || '';
            $('usuario_estacion_id').value     = u.usuario_estacion_id || '';
            $('usuario_status').value          = u.usuario_status ?? 1;

            if (u.usuario_imagen){
                $('previewImg').src = (window.SITGO_BASE || '/') + 'uploads/storage/' + u.usuario_imagen;
                $('wrapPreview').style.display = '';
            } else {
                $('wrapPreview').style.display = 'none';
            }
                        // Cargar menús del usuario
            $('blockMenus').style.display = '';
            await cargarMenusUsuario(id);

            $('formSectionTitle').textContent = 'Editar Usuario';
            $('formSectionSubtitle').textContent = `Editando a "${u.usuario_nick}"`;
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

    /* ============================================
       GUARDAR
       ============================================ */
    function bindForm(){
        $('formUsuario')?.addEventListener('submit', guardar);
    }

    async function guardar(e){
        e.preventDefault();
        limpiarErrores();

        const form = $('formUsuario');
        const id = $('usuario_id').value;

        const fd = new FormData(form);
                // Incluir menús asignados (solo en edición)
        if (id){
            fd.append('menus_json', JSON.stringify([...menusSeleccionados]));
        }
        const btn = $('btnGuardar');
        
        btn.disabled = true;
        const btnText = btn.querySelector('.btn-text');
        const original = btnText.textContent;
        btnText.textContent = id ? 'Actualizando...' : 'Guardando...';

        try{
            const url = id ? `${API}/actualizar/${id}` : `${API}/guardar`;
            const r = await fetch(url, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const d = await r.json();

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

            // Éxito
            let msg = d.message;
            if (!id && d.nick){
                msg = `Usuario "${d.nick}" creado. Contraseña por defecto: 123456`;
            }
                        // Guardar menús asignados (solo en edición)
            if (id && menusSeleccionados.size >= 0){
                try{
                    await fetchJSON(API + '/menus/guardar/' + id, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ menus: [...menusSeleccionados] })
                    });
                } catch (e){
                    console.warn('Error al guardar menús:', e);
                }
            }

            toast(d.message, 'success');
            form.reset();
            $('usuario_id').value = '';
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

    /* ============================================
       ELIMINAR
       ============================================ */
    async function eliminar(id){
        const u = dt?.getData().find(x => Number(x.usuario_id) === Number(id));
        const nick = u?.usuario_nick || 'este usuario';

        const ok = await sitgoConfirm({
            title: 'Eliminar usuario',
            message: `¿Eliminar a "${nick}"? Esta acción no se puede deshacer.`,
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

    /* ============================================
       TOGGLE STATUS
       ============================================ */
    async function toggleStatus(id, status){
        try{
            const d = await fetchJSON(API + '/cambiar-status/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ usuario_status: Number(status) }),
            });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            dt?.refresh();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================
       RESET PASSWORD
       ============================================ */
    async function resetPassword(id){
        const u = dt?.getData().find(x => Number(x.usuario_id) === Number(id));
        const nick = u?.usuario_nick || 'usuario';

        const nueva = await sitgoPrompt({
            title:       'Resetear contraseña',
            message:     `Nueva contraseña para "${nick}". Debe tener al menos 4 caracteres.`,
            placeholder: 'Nueva contraseña',
            okText:      'Cambiar contraseña',
            cancelText:  'Cancelar',
            type:        'warning',
            icon:        'key-round',
            inputType:   'password',
            minLength:   4,
        });

        if (nueva === null) return;

        try{
            const d = await fetchJSON(API + '/reset-password/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ nueva_password: nueva })
            });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }
        /**
     * Elimina la imagen del usuario y restaura la default.
     */
    async function eliminarImagen(id){
        const u = dt?.getData().find(x => Number(x.usuario_id) === Number(id));
        const nick = u?.usuario_nick || 'usuario';

        const ok = await sitgoConfirm({
            title: 'Eliminar imagen',
            message: `¿Eliminar la imagen de "${nick}"? Se restaurará la predeterminada.`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'image-off',
        });
        if (!ok) return;

        try{
            const d = await fetchJSON(API + '/eliminar-imagen/' + id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            dt?.refresh();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }
    /* ============================================
       HELPERS
       ============================================ */
    function limpiarErrores(){
        const form = $('formUsuario');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function cerrarFormulario(){
        const form = $('formUsuario');
        form.reset();
        $('usuario_id').value = '';
        $('wrapPreview').style.display = 'none';
                $('blockMenus').style.display = 'none';
        menusArbol = [];
        menusSeleccionados.clear();
        ssPersonal?.clear();
        limpiarErrores();

        $('formSectionTitle').textContent = 'Nuevo Usuario';
        $('formSectionSubtitle').textContent = 'Selecciona un personal y completa los datos';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Usuario';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        cerrarSeccion();
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

        /* ============================================================
       MENÚS DEL USUARIO
       ============================================================ */
    async function cargarMenusUsuario(userId){
        const cont = $('menusTree');
        cont.innerHTML = `<div class="menus-asign-loading">Cargando menús...</div>`;

        try{
            const d = await fetchJSON(API + '/menus/' + userId);
            if (!d.ok) throw new Error(d.message);

            menusArbol = d.arbol || [];
            menusSeleccionados = new Set((d.asignados || []).map(Number));

            renderMenusTree();
        } catch (e){
            cont.innerHTML = `<div class="menus-asign-loading" style="color:#dc2626;">
                Error: ${esc(e.message)}
            </div>`;
        }
    }

    function renderMenusTree(){
        const cont = $('menusTree');
        if (!menusArbol.length){
            cont.innerHTML = `<div class="menus-asign-loading">Sin menús registrados.</div>`;
            return;
        }
        cont.innerHTML = `<ul class="menus-tree-list">${menusArbol.map(renderMenuNode).join('')}</ul>`;
        if (window.lucide) lucide.createIcons();

        // Toggle de submenús
        cont.querySelectorAll('[data-toggle-sub]').forEach(btn => {
            btn.addEventListener('click', () => {
                const li = btn.closest('.menus-tree-item');
                li.classList.toggle('collapsed');
                btn.style.transform = li.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(90deg)';
            });
        });

        // Marcar/desmarcar menús
        cont.querySelectorAll('input[type="checkbox"][data-menu-id]').forEach(chk => {
            chk.addEventListener('change', (e) => {
                const mid = Number(e.target.dataset.menuId);
                if (e.target.checked){
                    menusSeleccionados.add(mid);
                } else {
                    menusSeleccionados.delete(mid);
                }
                // Sincronizar hijos
                sincronizarHijos(mid, e.target.checked);
            });
        });
    }

    function renderMenuNode(n){
        const marcado = menusSeleccionados.has(Number(n.menu_id));
        const tieneHijos = n.children && n.children.length > 0;
        const icono = n.menu_icono || 'circle';

        return `
            <li class="menus-tree-item" data-id="${n.menu_id}">
                <div class="menus-tree-row">
                    <div class="menus-tree-left">
                        ${tieneHijos
                            ? `<button type="button" class="menus-tree-toggle" data-toggle-sub="1">
                                 <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                               </button>`
                            : `<span class="menus-tree-spacer"></span>`}
                        <label class="menus-tree-check">
                            <input type="checkbox"
                                   data-menu-id="${n.menu_id}"
                                   ${marcado ? 'checked' : ''}>
                            <span class="menus-tree-icon">
                                <i data-lucide="${esc(icono)}" class="w-3.5 h-3.5"></i>
                            </span>
                            <span class="menus-tree-name">${esc(n.menu_nombre)}</span>
                        </label>
                    </div>
                    ${n.menu_ruta ? `<code class="menus-tree-ruta">/${esc(n.menu_ruta)}</code>` : ''}
                </div>
                ${tieneHijos ? `<ul class="menus-tree-children">${n.children.map(renderMenuNode).join('')}</ul>` : ''}
            </li>`;
    }

    /**
     * Marca/desmarca recursivamente los hijos cuando se cambia el padre.
     */
    function sincronizarHijos(parentId, checked){
        const nodo = buscarNodo(menusArbol, parentId);
        if (!nodo || !nodo.children) return;

        const marcarRec = (n) => {
            const mid = Number(n.menu_id);
            if (checked) menusSeleccionados.add(mid);
            else         menusSeleccionados.delete(mid);

            const chk = document.querySelector(`input[data-menu-id="${mid}"]`);
            if (chk) chk.checked = checked;

            if (n.children) n.children.forEach(marcarRec);
        };

        nodo.children.forEach(marcarRec);

        // Sincronizar el padre con el estado de sus hijos (si algún hijo está marcado, marcar padre)
        sincronizarPadres(parentId);
    }

    function sincronizarPadres(nodoId){
        // Buscar el padre de este nodo
        let padre = null;
        const buscar = (nodos) => {
            for (const n of nodos){
                if (n.children){
                    for (const h of n.children){
                        if (Number(h.menu_id) === Number(nodoId)){
                            padre = n;
                            return true;
                        }
                    }
                    if (buscar(n.children)) return true;
                }
            }
            return false;
        };
        buscar(menusArbol);

        if (padre){
            const hijos = padre.children || [];
            const algunMarcado = hijos.some(h => menusSeleccionados.has(Number(h.menu_id)));
            const chk = document.querySelector(`input[data-menu-id="${padre.menu_id}"]`);
            if (chk) chk.checked = algunMarcado;
            if (algunMarcado) menusSeleccionados.add(Number(padre.menu_id));
            else              menusSeleccionados.delete(Number(padre.menu_id));

            sincronizarPadres(padre.menu_id);
        }
    }

    function buscarNodo(nodos, id){
        for (const n of nodos){
            if (Number(n.menu_id) === Number(id)) return n;
            if (n.children){
                const found = buscarNodo(n.children, id);
                if (found) return found;
            }
        }
        return null;
    }

    // Botones de acciones masivas
    $('btnMenuMarcarTodos')?.addEventListener('click', () => {
        menusSeleccionados = new Set();
        const marcarRec = (nodos) => {
            nodos.forEach(n => {
                menusSeleccionados.add(Number(n.menu_id));
                if (n.children) marcarRec(n.children);
            });
        };
        marcarRec(menusArbol);
        renderMenusTree();
    });

    $('btnMenuLimpiar')?.addEventListener('click', () => {
        menusSeleccionados = new Set();
        renderMenusTree();
    });

    $('btnMenuExpandir')?.addEventListener('click', () => {
        const btn = $('btnMenuExpandir');
        const span = btn.querySelector('span');
        const expandido = span.textContent === 'Colapsar';
        document.querySelectorAll('.menus-tree-item').forEach(li => {
            li.classList.toggle('collapsed', expandido);
            const toggle = li.querySelector('[data-toggle-sub]');
            if (toggle) toggle.style.transform = expandido ? 'rotate(0deg)' : 'rotate(90deg)';
        });
        span.textContent = expandido ? 'Expandir' : 'Colapsar';
    });
});