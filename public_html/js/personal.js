document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'personal';
    const $ = id => document.getElementById(id);

    let dt = null;   // instancia del DataTable

    /* ============================================
       RENDER ROW
       ============================================ */
    function renderRow(p) {
        const active = Number(p.personal_status) === 1;
        const badge  = active ? 'badge-success' : 'badge-danger';
        const txt    = active ? 'Activo' : 'Inactivo';
        const nombre = `${p.personal_nombre || ''} ${p.personal_apellido || ''}`.trim();

        const depto = (p.departamento_nombre && p.departamento_nombre.trim() !== '')
            ? esc(p.departamento_nombre)
            : '<span class="text-muted">Sin departamento</span>';

        return `
            <tr>
                <td>${esc(p.personal_cedula)}</td>
                <td><strong>${esc(nombre)}</strong></td>
                <td>${esc(p.cargo_nombre || '—')}</td>
                <td>${depto}</td>
                <td>${esc(p.personal_tlf || '—')}</td>
                <td>${esc(p.personal_email || '—')}</td>
                <td><span class="badge ${badge}">${txt}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn edit" data-action="editar" data-id="${p.id_personal}" title="Editar">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn toggle" data-action="toggle" data-id="${p.id_personal}" data-status="${active ? 0 : 1}" title="${active ? 'Desactivar' : 'Activar'}">
                            <i data-lucide="${active ? 'user-x' : 'user-check'}" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${p.id_personal}" title="Eliminar">
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
        try {
            await cargarCombos();

            dt = new DataTable({
                tbodyId: 'tbodyPersonal',
                endpoint: API + '/listar',
                columns: ['Cédula', 'Nombre', 'Cargo', 'Departamento', 'Teléfono', 'Email', 'Estatus', 'Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationPersonal',
                totalId: 'totalPersonal',
                startId: 'startPersonal',
                endId: 'endPersonal',
                noDataMessage: 'No hay personal registrado',
                getExtraParams: () => ({
                    status: $('filtroStatus')?.value || '',
                    departamento: $('filtroDepartamento')?.value || ''
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindForm();
        } catch (e) {
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================
       API helper
       ============================================ */
    async function fetchJSON(url, opts = {}) {
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            ...opts
        });
        return r.json();
    }

    async function cargarCombos() {
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) throw new Error('combos');
        fillSelect($('personal_cargo'), d.cargos, 'id_cargo', 'cargo', 'Seleccione...');
        fillSelect($('personal_departamento'), d.departamentos, 'departamento_id', 'departamento_nombre', 'Sin departamento');
        fillSelect($('filtroDepartamento'), d.departamentos, 'departamento_id', 'departamento_nombre', 'Todos los departamentos');
    }

    function fillSelect(sel, items, vKey, lKey, placeholder) {
        if (!sel) return;
        sel.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(it => {
            const o = document.createElement('option');
            o.value = it[vKey];
            o.textContent = it[lKey];
            sel.appendChild(o);
        });
    }

    /* ============================================
       EVENTOS
       ============================================ */
    function bindEvents() {
        $('btnNuevo')?.addEventListener('click', abrirCrear);
        $('btnCancelar')?.addEventListener('click', cerrarFormulario);
        $('toggleForm')?.addEventListener('click', toggleSeccion);
        $('btnToggleForm')?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSeccion();
        });

        // Filtros que disparan al DataTable
        $('filtroStatus')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroDepartamento')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());

        // Escape cierra formulario
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') cerrarFormulario();
        });
    }

    function bindTableActions() {
        // Delegación en el tbody — se ejecuta cada vez que el DataTable re-renderiza
        const tbody = $('tbodyPersonal');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id, status } = btn.dataset;
            if (action === 'editar')   abrirEditar(id);
            if (action === 'eliminar') eliminar(id);
            if (action === 'toggle')   toggleStatus(id, status);
        });
    }

    /* ============================================
       SECCIÓN COLAPSABLE
       ============================================ */
    function toggleSeccion() {
        const s = $('seccionForm');
        s.classList.toggle('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = s.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(180deg)';
    }
    function abrirSeccion() {
        $('seccionForm').classList.remove('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = 'rotate(180deg)';
    }
    function cerrarSeccion() {
        $('seccionForm').classList.add('collapsed');
        const t = $('btnToggleForm');
        if (t) t.style.transform = 'rotate(0deg)';
    }

    /* ============================================
       FORMULARIO · CREAR
       ============================================ */
    function abrirCrear() {
        const f = $('formPersonal');
        f.reset();
        $('id_personal').value = '';
        $('personal_status').value = '1';
        $('personal_departamento').value = '';
        $('personal_cargo').value = '';

        $('personal_cedula').readOnly = false;
        $('personal_cedula').classList.remove('input-locked');

        $('formSectionTitle').textContent = 'Nuevo Personal';
        $('formSectionSubtitle').textContent = 'Completa los datos para crear un nuevo registro';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Personal';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        limpiarErrores();
        abrirSeccion();
        $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => $('personal_cedula')?.focus(), 300);
    }

    /* ============================================
       FORMULARIO · EDITAR
       ============================================ */
    async function abrirEditar(id) {
        try {
            const d = await fetchJSON(API + '/obtener/' + id);
            if (!d.ok) throw new Error(d.message);
            const p = d.data;

            $('id_personal').value           = p.id_personal;
            $('personal_cedula').value       = p.personal_cedula || '';
            $('personal_nombre').value       = p.personal_nombre || '';
            $('personal_apellido').value     = p.personal_apellido || '';
            $('personal_cargo').value        = p.personal_cargo || '';
            $('personal_departamento').value = p.personal_departamento || '';
            $('personal_tlf').value          = p.personal_tlf || '';
            $('personal_email').value        = p.personal_email || '';
            $('personal_direccion').value    = p.personal_direccion || '';
            $('personal_status').value       = p.personal_status ?? 1;

            $('personal_cedula').readOnly = true;
            $('personal_cedula').classList.add('input-locked');

            $('formSectionTitle').textContent = 'Editar Personal';
            $('formSectionSubtitle').textContent =
                `Editando a ${p.personal_nombre} ${p.personal_apellido} · Cédula ${p.personal_cedula}`;
            $('formSectionIconWrap').innerHTML = '<i data-lucide="pencil" class="w-5 h-5"></i>';
            if (window.lucide) lucide.createIcons();

            $('btnNuevoText').textContent = 'Cancelar edición';
            $('btnGuardar').querySelector('.btn-text').textContent = 'Actualizar';

            limpiarErrores();
            abrirSeccion();
            $('seccionForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
            setTimeout(() => $('personal_nombre')?.focus(), 300);
        } catch (e) {
            toast('Error al cargar: ' + e.message, 'error');
        }
    }

    /* ============================================
       FORMULARIO · GUARDAR
       ============================================ */
    function bindForm() {
        $('formPersonal')?.addEventListener('submit', guardar);
    }

    async function guardar(e) {
        e.preventDefault();
        limpiarErrores();

        const form = $('formPersonal');
        const id = $('id_personal').value;
        const payload = Object.fromEntries(new FormData(form).entries());
        if (id) delete payload.personal_cedula;

        const btn = $('btnGuardar');
        btn.disabled = true;
        const btnText = btn.querySelector('.btn-text');
        const original = btnText.textContent;
        btnText.textContent = id ? 'Actualizando...' : 'Guardando...';

        try {
            const url = id ? `${API}/actualizar/${id}` : `${API}/guardar`;
            const d = await fetchJSON(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload),
            });

            if (!d.ok) {
                if (d.errors) {
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
            $('id_personal').value = '';
            cerrarFormulario();
            dt?.refresh();
        } catch (err) {
            console.error(err);
            toast('Error de conexión', 'error');
        } finally {
            btn.disabled = false;
            btnText.textContent = original;
        }
    }

    /* ============================================
       ELIMINAR / TOGGLE
       ============================================ */
    async function eliminar(id) {
        const p = dt?.getData().find(x => Number(x.id_personal) === Number(id));
        const nombre = p ? `${p.personal_nombre} ${p.personal_apellido}` : 'este registro';

        const ok = await sitgoConfirm({
            title: 'Eliminar personal',
            message: `¿Eliminar a "${nombre}"? Esta acción no se puede deshacer.`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        try {
            const d = await fetchJSON(API + '/eliminar/' + id, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            dt?.refresh();
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        }
    }

    async function toggleStatus(id, status) {
        try {
            const d = await fetchJSON(API + '/cambiar-status/' + id, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ personal_status: Number(status) }),
            });
            if (!d.ok) throw new Error(d.message);
            toast(d.message, 'success');
            dt?.refresh();
        } catch (e) {
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================
       HELPERS
       ============================================ */
    function limpiarErrores() {
        const form = $('formPersonal');
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error, select.error, textarea.error')
            .forEach(el => el.classList.remove('error'));
    }

    function cerrarFormulario() {
        const form = $('formPersonal');
        form.reset();
        $('id_personal').value = '';
        $('personal_cedula').readOnly = false;
        $('personal_cedula').classList.remove('input-locked');
        limpiarErrores();

        $('formSectionTitle').textContent = 'Nuevo Personal';
        $('formSectionSubtitle').textContent = 'Completa los datos para crear un nuevo registro';
        $('formSectionIconWrap').innerHTML = '<i data-lucide="user-plus" class="w-5 h-5"></i>';
        if (window.lucide) lucide.createIcons();

        $('btnNuevoText').textContent = 'Nuevo Personal';
        $('btnGuardar').querySelector('.btn-text').textContent = 'Guardar';

        cerrarSeccion();
    }

    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toast(msg, type = 'success') {
        let c = document.querySelector('.toast-container');
        if (!c) {
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