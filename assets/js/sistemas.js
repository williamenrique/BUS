document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'sistemas/i/' + INST;
    const $    = id => document.getElementById(id);

    let dt = null;
    let combos = { departamentos: [], personal: [] };
    let fichaActualId = null;
    let fichaActualData = null;

    let ssEqDepto       = null;
    let ssEqResponsable = null;
    let ssFiltroDepto   = null;
    let ssMantEquipo    = null;
    let ssMantTecnico   = null;

    const STATUS_MAP = {
        'ACTIVO':           { text: 'Activo',           color: 'badge-success' },
        'EN_MANTENIMIENTO': { text: 'En mantenimiento', color: 'badge-warning' },
        'INACTIVO':         { text: 'Inactivo',         color: 'badge-secondary' },
        'BAJA':             { text: 'Baja',             color: 'badge-danger' },
    };

    const TIPO_INT_MAP = {
        'SOPORTE':                    { text: 'Soporte',       color: 'badge-info' },
        'MANTENIMIENTO_PREVENTIVO': { text: 'Preventivo',    color: 'badge-success' },
        'MANTENIMIENTO_CORRECTIVO': { text: 'Correctivo',    color: 'badge-warning' },
        'REEMPLAZO':                  { text: 'Reemplazo',     color: 'badge-danger' },
    };

    function renderRow(eq) {
        const st = STATUS_MAP[String(eq.status_equipo || '').toUpperCase()] || {
            text: eq.status_equipo || '—',
            color: 'badge-secondary'
        };

        const specs = [eq.marca, eq.modelo, eq.procesador, eq.ram_gb, eq.almacenamiento]
            .filter(Boolean).join(' · ') || '—';
        const red = [eq.nombre_host, eq.direccion_ip].filter(Boolean).join(' / ') || '—';

        const totalInt    = Number(eq.total_intervenciones || 0);
        const abiertasInt = Number(eq.intervenciones_abiertas || 0);

        let badgeInterv = `<span class="badge badge-secondary">0</span>`;
        if (totalInt > 0 && abiertasInt > 0) {
            badgeInterv = `
                <span class="badge badge-warning" title="${abiertasInt} intervención(es) abierta(s) de ${totalInt}">
                    ${abiertasInt} ABIERTA${abiertasInt > 1 ? 'S' : ''}
                </span>`;
        } else if (totalInt > 0) {
            badgeInterv = `
                <span class="badge badge-success" title="${totalInt} intervención(es) cerrada(s)">
                    ${totalInt} · CERRADA${totalInt > 1 ? 'S' : ''}
                </span>`;
        }

        return `
            <tr>
                <td><strong>${esc(eq.codigo_equipo)}</strong><br><small class="text-muted">${esc(eq.tipo_equipo || '')}</small></td>
                <td title="${esc(specs)}"><div>${esc(specs)}</div><small class="text-muted">SN: ${esc(eq.serial_equipo || '—')}</small></td>
                <td>${esc(eq.departamento_nombre || '—')}</td>
                <td>${esc(eq.responsable_nombre || '—')}</td>
                <td>${esc(red)}</td>
                <td class="text-center">${badgeInterv}</td>
                <td class="text-center"><span class="badge ${st.color}">${st.text}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn" data-action="ficha" data-id="${eq.id_equipo}" title="Ficha técnica e historial">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn" data-action="mant" data-id="${eq.id_equipo}" title="Registrar soporte / mantenimiento / reemplazo">
                            <i data-lucide="wrench" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn edit" data-action="editar" data-id="${eq.id_equipo}" title="Editar ficha">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${eq.id_equipo}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    (async () => {
        try {
            initSearchSelects();
            await cargarCombos();

            if (typeof DataTable === 'undefined') {
                throw new Error('DataTable no está cargado.');
            }

            dt = new DataTable({
                tbodyId: 'tbodyEquipos',
                endpoint: API + '/listar',
                columns: ['Código', 'Equipo / Specs', 'Departamento', 'Responsable', 'Red / Host', 'Interv.', 'Estatus', 'Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationEquipos',
                totalId: 'totalEquipos',
                startId: 'startEquipos',
                endId: 'endEquipos',
                noDataMessage: 'No hay equipos registrados',
                getExtraParams: () => ({
                    departamento: ssFiltroDepto ? ssFiltroDepto.getValue() : '',
                    tipo: $('filtroTipo')?.value || '',
                    status: $('filtroStatus')?.value || '',
                    fecha_desde: $('filtroFechaDesde')?.value || '',
                    fecha_hasta: $('filtroFechaHasta')?.value || ''
                }),
                onDataLoaded: (res) => {
                    bindTableActions();
                    cargarComboEquipos();
                    const btnPdf = $('btnExportarTablaPdf');
                    const sinDatos = Number(res?.totalFiltrados ?? res?.data?.length ?? 0) === 0;
                    if (btnPdf) {
                        btnPdf.disabled = sinDatos;
                        btnPdf.style.opacity = sinDatos ? '0.5' : '1';
                        btnPdf.style.cursor  = sinDatos ? 'not-allowed' : 'pointer';
                    }
                }
            });

            bindEvents();
            bindForms();
        } catch (e) {
            console.error('❌ Error al inicializar sistemas:', e);
            notifyToast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    function initSearchSelects() {
        ssEqDepto = new SearchSelect({
            wrapperId: 'wrapEqDepto',
            hiddenInputId: 'eq_departamento_id',
            placeholder: 'Seleccione departamento...',
            noResultsText: 'Sin departamentos'
        });

        ssEqResponsable = new SearchSelect({
            wrapperId: 'wrapEqResponsable',
            hiddenInputId: 'eq_responsable_id',
            placeholder: 'Seleccione responsable...',
            noResultsText: 'Sin personal'
        });

        ssFiltroDepto = new SearchSelect({
            wrapperId: 'wrapFiltroDepto',
            hiddenInputId: 'filtroDepartamento',
            placeholder: 'Todos los departamentos',
            noResultsText: 'Sin departamentos',
            onChange: () => dt?.changePage(1) || dt?.reload()
        });

        ssMantEquipo = new SearchSelect({
            wrapperId: 'wrapMantEquipo',
            hiddenInputId: 'mant_id_equipo',
            placeholder: 'Seleccione equipo...',
            noResultsText: 'Sin equipos'
        });

        ssMantTecnico = new SearchSelect({
            wrapperId: 'wrapMantTecnico',
            hiddenInputId: 'mant_tecnico_id',
            placeholder: 'Seleccione técnico...',
            noResultsText: 'Sin personal'
        });
    }

    async function fetchJSON(url, opts = {}) {
        if (typeof window.fetchSafe === 'function') {
            return window.fetchSafe(url, opts);
        }
        const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, ...opts });
        return r.json();
    }

    async function cargarCombos() {
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) throw new Error(d.message || 'Error al cargar combos');
        combos = d;

        const deptosOpts = (d.departamentos || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        }));
        ssEqDepto?.setOptions(deptosOpts);
        ssFiltroDepto?.setOptions(deptosOpts);

        const persOpts = (d.personal || []).map(it => ({
            value: it.id,
            label: it.nombre,
            search: it.nombre.toLowerCase()
        }));
        ssEqResponsable?.setOptions(persOpts);
        ssMantTecnico?.setOptions(persOpts);
    }

    async function cargarComboEquipos() {
        const d = await fetchJSON(`${API}/listar?limit=200&offset=0`);
        if (!d.ok || !Array.isArray(d.data)) return;
        ssMantEquipo?.setOptions(d.data.map(eq => ({
            value: eq.id_equipo,
            label: `${eq.codigo_equipo} — ${[eq.marca, eq.modelo].filter(Boolean).join(' ') || eq.tipo_equipo}`,
            search: `${eq.codigo_equipo} ${eq.marca || ''} ${eq.modelo || ''} ${eq.serial_equipo || ''}`.toLowerCase()
        })));
    }

    function bindEvents() {
        $('btnReporteGlobalPdf')?.addEventListener('click', exportarPdfGlobal);
        $('btnNuevoEquipo')?.addEventListener('click', abrirCrearEquipo);
        $('btnCancelarEquipo')?.addEventListener('click', cerrarFormularioEquipo);
        $('toggleFormEquipo')?.addEventListener('click', toggleSeccionEquipo);
        $('btnToggleFormEquipo')?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSeccionEquipo();
        });

        $('filtroTipo')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroStatus')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroFechaDesde')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroFechaHasta')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());

        $('btnLimpiarFiltros')?.addEventListener('click', () => {
            ssFiltroDepto?.clear();
            if ($('filtroTipo'))       $('filtroTipo').value = '';
            if ($('filtroStatus'))     $('filtroStatus').value = '';
            if ($('filtroFechaDesde')) $('filtroFechaDesde').value = '';
            if ($('filtroFechaHasta')) $('filtroFechaHasta').value = '';
            if ($('filtroBuscar'))     $('filtroBuscar').value = '';
            if (dt) {
                dt.state.search = '';
                dt.state.page = 1;
                dt.reload(true);
            }
        });

        $('btnReporteGlobalPdf')?.addEventListener('click', () => exportarPdfGlobal('btnReporteGlobalPdf'));
        $('btnExportarTablaPdf')?.addEventListener('click', () => exportarPdfGlobal('btnExportarTablaPdf'));

        $('btnNuevaIntervencionTop')?.addEventListener('click', () => abrirModalMantenimiento(null));
        $('btnAgregarMantDesdeFicha')?.addEventListener('click', () => abrirModalMantenimiento(fichaActualId));

        $('btnCerrarFicha')?.addEventListener('click', cerrarModalFicha);
        $('btnPdfFicha')?.addEventListener('click', exportarPdfFicha);

        $('btnCerrarModalMant')?.addEventListener('click', cerrarModalMantenimiento);
        $('btnCancelarModalMant')?.addEventListener('click', cerrarModalMantenimiento);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cerrarModalMantenimiento();
                cerrarModalFicha();
                cerrarFormularioEquipo();
            }
        });
    }

    function bindTableActions() {
        const tbody = $('tbodyEquipos');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id } = btn.dataset;
            if (action === 'ficha')    abrirModalFicha(id);
            if (action === 'mant')     abrirModalMantenimiento(id);
            if (action === 'editar')   abrirEditarEquipo(id);
            if (action === 'eliminar') eliminarEquipo(id);
        });
    }

    function toggleSeccionEquipo() {
        const s = $('seccionFormEquipo');
        s?.classList.toggle('collapsed');
        const t = $('btnToggleFormEquipo');
        if (t) t.style.transform = s?.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    function abrirSeccionEquipo() {
        $('seccionFormEquipo')?.classList.remove('collapsed');
        const t = $('btnToggleFormEquipo');
        if (t) t.style.transform = 'rotate(180deg)';
    }

    function cerrarSeccionEquipo() {
        $('seccionFormEquipo')?.classList.add('collapsed');
        const t = $('btnToggleFormEquipo');
        if (t) t.style.transform = 'rotate(0deg)';
    }

    function abrirCrearEquipo() {
        const f = $('formEquipo');
        f?.reset();
        $('id_equipo').value = '';
        $('status_equipo').value = 'ACTIVO';
        $('tipo_equipo').value = 'PC ESCRITORIO';
        ssEqDepto?.clear();
        ssEqResponsable?.clear();
        limpiarErrores(f);

        $('formEquipoTitle').textContent = 'Nueva Ficha Técnica de Equipo';
        $('formEquipoSubtitle').textContent = 'Completa los datos de hardware, red y asignación del equipo';
        $('btnNuevoEquipoText').textContent = 'Nuevo Equipo';
        $('btnGuardarEquipo').querySelector('.btn-text').textContent = 'Guardar';

        abrirSeccionEquipo();
        $('seccionFormEquipo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function abrirEditarEquipo(id) {
        try {
            const d = await fetchJSON(`${API}/obtener/${id}`);
            if (!d.ok) throw new Error(d.message);
            const eq = d.data;
            const f  = $('formEquipo');
            f?.reset();
            limpiarErrores(f);

            $('id_equipo').value         = eq.id_equipo;
            $('codigo_equipo').value     = eq.codigo_equipo || '';
            $('tipo_equipo').value       = eq.tipo_equipo || 'PC ESCRITORIO';
            $('marca').value             = eq.marca || '';
            $('modelo').value            = eq.modelo || '';
            $('serial_equipo').value     = eq.serial_equipo || '';
            $('fecha_adquisicion').value = eq.fecha_adquisicion || '';
            $('procesador').value        = eq.procesador || '';
            $('ram_gb').value            = eq.ram_gb || '';
            $('almacenamiento').value    = eq.almacenamiento || '';
            $('sistema_operativo').value = eq.sistema_operativo || '';
            $('nombre_host').value       = eq.nombre_host || '';
            $('direccion_ip').value      = eq.direccion_ip || '';
            $('direccion_mac').value     = eq.direccion_mac || '';
            $('status_equipo').value     = eq.status_equipo || 'ACTIVO';
            $('observaciones').value     = eq.observaciones || '';

            ssEqDepto?.setValue(eq.departamento_id || '');
            ssEqResponsable?.setValue(eq.responsable_id || '');

            $('formEquipoTitle').textContent = 'Editar Ficha Técnica';
            $('formEquipoSubtitle').textContent = `Editando equipo ${eq.codigo_equipo}`;
            $('btnNuevoEquipoText').textContent = 'Cancelar edición';
            $('btnGuardarEquipo').querySelector('.btn-text').textContent = 'Actualizar';

            abrirSeccionEquipo();
            $('seccionFormEquipo')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (e) {
            notifyToast('Error al cargar equipo: ' + e.message, 'error');
        }
    }

    function cerrarFormularioEquipo() {
        const f = $('formEquipo');
        f?.reset();
        $('id_equipo').value = '';
        ssEqDepto?.clear();
        ssEqResponsable?.clear();
        limpiarErrores(f);
        $('btnNuevoEquipoText').textContent = 'Nuevo Equipo';
        $('btnGuardarEquipo').querySelector('.btn-text').textContent = 'Guardar';
        cerrarSeccionEquipo();
    }

    function bindForms() {
        $('formEquipo')?.addEventListener('submit', guardarEquipo);
        $('formMantenimiento')?.addEventListener('submit', guardarMantenimiento);
    }

    async function guardarEquipo(e) {
        e.preventDefault();
        const form = $('formEquipo');
        limpiarErrores(form);

        const id = $('id_equipo').value;
        const payload = Object.fromEntries(new FormData(form).entries());

        const btn = $('btnGuardarEquipo');
        btn.disabled = true;
        const txt = btn.querySelector('.btn-text');
        const orig = txt.textContent;
        txt.textContent = id ? 'Actualizando...' : 'Guardando...';

        try {
            const url = id ? `${API}/actualizar/${id}` : `${API}/guardar`;
            const d = await fetchJSON(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok) {
                if (d.errors) {
                    mostrarErrores(form, d.errors);
                } else {
                    notifyToast(d.message || 'Error al guardar equipo', 'error');
                }
                return;
            }

            notifyToast(d.message, 'success');
            cerrarFormularioEquipo();
            dt?.refresh();
        } catch (err) {
            console.error(err);
            notifyToast('Error de conexión', 'error');
        } finally {
            btn.disabled = false;
            txt.textContent = orig;
        }
    }

    async function eliminarEquipo(id) {
        const eq = dt?.getData().find(x => Number(x.id_equipo) === Number(id));
        const label = eq ? `${eq.codigo_equipo} (${eq.tipo_equipo})` : `equipo #${id}`;

        const ok = await sitgoConfirm({
            title: 'Eliminar ficha de equipo',
            message: `¿Eliminar ${label} y su historial de mantenimiento? Esta acción no se puede deshacer.`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        try {
            const d = await fetchJSON(`${API}/eliminar/${id}`, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            notifyToast(d.message, 'success');
            dt?.refresh();
        } catch (e) {
            notifyToast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================================
       FICHA TÉCNICA + BITÁCORA
       ============================================================ */
    async function abrirModalFicha(id) {
        fichaActualId = Number(id);
        $('modalFichaEquipo').style.display = 'block';
        $('tbodyFichaMant').innerHTML = '<tr><td colspan="7" class="dt-loading">Cargando ficha...</td></tr>';

        try {
            const d = await fetchJSON(`${API}/ficha/${id}`);
            if (!d.ok) throw new Error(d.message);
            fichaActualData = d;
            const eq = d.equipo;

            $('fichaTitulo').textContent = `Ficha Técnica · ${eq.codigo_equipo} (${eq.tipo_equipo})`;
            $('fichaSubtitulo').textContent = `${eq.departamento_nombre || 'Sin departamento'} · Responsable: ${eq.responsable_nombre || 'Sin asignar'}`;

            $('fichaDetalleGrid').innerHTML = `
                <div><strong>Marca / Modelo:</strong><br>${esc([eq.marca, eq.modelo].filter(Boolean).join(' ') || '—')}</div>
                <div><strong>Serial:</strong><br>${esc(eq.serial_equipo || '—')}</div>
                <div><strong>Procesador:</strong><br>${esc(eq.procesador || '—')}</div>
                <div><strong>RAM / Almacenamiento:</strong><br>${esc([eq.ram_gb, eq.almacenamiento].filter(Boolean).join(' · ') || '—')}</div>
                <div><strong>Sistema Operativo:</strong><br>${esc(eq.sistema_operativo || '—')}</div>
                <div><strong>Host / IP / MAC:</strong><br>${esc([eq.nombre_host, eq.direccion_ip, eq.direccion_mac].filter(Boolean).join(' / ') || '—')}</div>
                <div><strong>Adquisición:</strong><br>${esc(eq.fecha_adquisicion || '—')}</div>
                <div><strong>Estatus:</strong><br>${esc(eq.status_equipo || '—')}</div>
            `;

            renderTablaMantenimientos(d.mantenimientos || []);
            if (window.lucide) lucide.createIcons();
        } catch (e) {
            notifyToast('Error al cargar ficha: ' + e.message, 'error');
        }
    }

function renderTablaMantenimientos(rows) {
        const tbody = $('tbodyFichaMant');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="7" class="dt-empty">Sin intervenciones registradas</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(m => {
            const tp = TIPO_INT_MAP[m.tipo_intervencion] || { text: m.tipo_intervencion, color: 'badge-secondary' };
            const estado = String(m.estado_intervencion || 'CERRADA').toUpperCase();
            const esAbierta = estado !== 'CERRADA';
            const badgeEstado = estado === 'CERRADA'
                ? 'badge-success'
                : (estado === 'EN_PROCESO' ? 'badge-info' : 'badge-warning');

            const rep = m.componente_nuevo
                ? `<strong>${esc(m.tipo_componente || 'COMP.')}:</strong> ${esc(m.componente_anterior || 'N/A')} → <strong>${esc(m.componente_nuevo)}</strong>`
                : '<span class="text-muted">—</span>';

            const btnCerrar = esAbierta
                ? `<button type="button" class="action-btn" data-close-mant="${m.id_mantenimiento}" title="Cerrar intervención">
                       <i data-lucide="check-circle-2" class="w-4 h-4" style="color:#16a34a;"></i>
                   </button>`
                : '';

            return `
                <tr>
                    <td>${esc(m.fecha_intervencion)}</td>
                    <td><span class="badge ${tp.color}">${tp.text}</span></td>
                    <td><strong>${esc(m.diagnostico)}</strong><br><small>${esc(m.accion_realizada)}</small></td>
                    <td>${rep}</td>
                    <td>${esc(m.tecnico_nombre || '—')}</td>
                    <td><span class="badge ${badgeEstado}">${esc(estado)}</span></td>
                    <td class="text-right">
                        <div class="actions">
                            ${btnCerrar}
                            <button type="button" class="action-btn edit" data-edit-mant="${m.id_mantenimiento}" title="Editar / Cambiar estado">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button type="button" class="action-btn delete" data-del-mant="${m.id_mantenimiento}" title="Eliminar intervención">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        tbody.querySelectorAll('[data-close-mant]').forEach(btn => {
            btn.addEventListener('click', () => cerrarIntervencionRapido(btn.dataset.closeMant));
        });
        tbody.querySelectorAll('[data-edit-mant]').forEach(btn => {
            btn.addEventListener('click', () => editarModalMantenimiento(btn.dataset.editMant));
        });
        tbody.querySelectorAll('[data-del-mant]').forEach(btn => {
            btn.addEventListener('click', () => eliminarMantenimiento(btn.dataset.delMant));
        });
    }

    function cerrarModalFicha() {
        $('modalFichaEquipo').style.display = 'none';
        fichaActualId = null;
        fichaActualData = null;
    }

    /* ============================================================
       MODAL MANTENIMIENTO / SOPORTE / REEMPLAZO
       ============================================================ */
    async function abrirModalMantenimiento(idEquipoPreseleccionado = null) {
        await cargarComboEquipos();
        const f = $('formMantenimiento');
        f?.reset();
        if ($('id_mantenimiento')) $('id_mantenimiento').value = '';
        limpiarErrores(f);
        $('fecha_intervencion').value = new Date().toISOString().slice(0, 10);
        $('tipo_intervencion').value  = 'MANTENIMIENTO_PREVENTIVO';
        $('estado_intervencion').value = 'CERRADA';
        $('btnGuardarMant').querySelector('.btn-text').textContent = 'Guardar intervención';
        ssMantTecnico?.clear();

        if (idEquipoPreseleccionado) {
            ssMantEquipo?.setValue(String(idEquipoPreseleccionado));
        } else {
            ssMantEquipo?.clear();
        }

        $('modalMantenimiento').style.display = 'block';
    }

    async function editarModalMantenimiento(idMant) {
        try {
            await cargarComboEquipos();
            const d = await fetchJSON(`${API}/mantenimiento/obtener/${idMant}`);
            if (!d.ok) throw new Error(d.message);
            const m = d.data;

            const f = $('formMantenimiento');
            f?.reset();
            limpiarErrores(f);

            if ($('id_mantenimiento')) $('id_mantenimiento').value = m.id_mantenimiento;
            ssMantEquipo?.setValue(String(m.id_equipo || ''));
            ssMantTecnico?.setValue(String(m.tecnico_id || ''));

            $('tipo_intervencion').value   = m.tipo_intervencion || 'MANTENIMIENTO_PREVENTIVO';
            $('fecha_intervencion').value  = m.fecha_intervencion || '';
            $('fecha_proximo_mant').value  = m.fecha_proximo_mant || '';
            $('estado_intervencion').value = m.estado_intervencion || 'CERRADA';
            $('solicitante_nombre').value  = m.solicitante_nombre || '';
            $('falla_reportada').value     = m.falla_reportada || '';
            $('diagnostico').value         = m.diagnostico || '';
            $('accion_realizada').value    = m.accion_realizada || '';
            $('tipo_componente').value     = m.tipo_componente || '';
            $('componente_anterior').value = m.componente_anterior || '';
            $('componente_nuevo').value    = m.componente_nuevo || '';
            $('serial_nuevo').value        = m.serial_nuevo || '';
            $('mant_observaciones').value  = m.observaciones || '';
            $('status_equipo_post').value  = '';

            $('btnGuardarMant').querySelector('.btn-text').textContent = 'Actualizar intervención';
            $('modalMantenimiento').style.display = 'block';
        } catch (e) {
            notifyToast('Error al cargar intervención: ' + e.message, 'error');
        }
    }

    async function cerrarIntervencionRapido(idMant) {
        let nota = '';
        if (typeof window.sitgoPrompt === 'function') {
            const res = await window.sitgoPrompt({
                title: 'Cerrar intervención',
                message: 'Opcional: escribe una nota o solución final de cierre (puedes dejarlo vacío):',
                placeholder: 'EJ. VERIFICADO Y OPERATIVO',
                okText: 'Cerrar intervención',
                cancelText: 'Cancelar'
            });
            if (res === null) return;
            nota = res;
        } else {
            const ok = await sitgoConfirm({
                title: 'Cerrar intervención',
                message: '¿Marcar esta intervención como CERRADA?',
                okText: 'Sí, cerrar',
                cancelText: 'Cancelar',
                type: 'primary',
                icon: 'check-circle-2'
            });
            if (!ok) return;
        }

        try {
            const d = await fetchJSON(`${API}/mantenimiento/cerrar/${idMant}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nota_cierre: nota,
                    activar_equipo: true
                })
            });
            if (!d.ok) throw new Error(d.message);
            notifyToast(d.message, 'success');
            dt?.refresh();
            if (fichaActualId) await abrirModalFicha(fichaActualId);
        } catch (e) {
            notifyToast('Error al cerrar: ' + e.message, 'error');
        }
    }

    function cerrarModalMantenimiento() {
        $('modalMantenimiento').style.display = 'none';
    }

    async function guardarMantenimiento(e) {
        e.preventDefault();
        const form = $('formMantenimiento');
        limpiarErrores(form);

        const idMant = $('id_mantenimiento')?.value || '';
        const payload = Object.fromEntries(new FormData(form).entries());
        const btn = $('btnGuardarMant');
        btn.disabled = true;
        const txt = btn.querySelector('.btn-text');
        const orig = txt.textContent;
        txt.textContent = idMant ? 'Actualizando...' : 'Guardando...';

        try {
            const url = idMant
                ? `${API}/mantenimiento/actualizar/${idMant}`
                : `${API}/mantenimiento/guardar`;

            const d = await fetchJSON(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok) {
                if (d.errors) {
                    mostrarErrores(form, d.errors);
                } else {
                    notifyToast(d.message || 'Error al registrar intervención', 'error');
                }
                return;
            }

            notifyToast(d.message, 'success');
            cerrarModalMantenimiento();
            dt?.refresh();
            if (fichaActualId && Number(payload.id_equipo) === Number(fichaActualId)) {
                await abrirModalFicha(fichaActualId);
            }
        } catch (err) {
            console.error(err);
            notifyToast('Error de conexión', 'error');
        } finally {
            btn.disabled = false;
            txt.textContent = orig;
        }
    }

    async function eliminarMantenimiento(idMant) {
        const ok = await sitgoConfirm({
            title: 'Eliminar intervención',
            message: '¿Deseas eliminar este registro de soporte/mantenimiento?',
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        try {
            const d = await fetchJSON(`${API}/mantenimiento/eliminar/${idMant}`, { method: 'POST' });
            if (!d.ok) throw new Error(d.message);
            notifyToast(d.message, 'success');
            dt?.refresh();
            if (fichaActualId) await abrirModalFicha(fichaActualId);
        } catch (e) {
            notifyToast('Error: ' + e.message, 'error');
        }
    }

    async function exportarPdfFicha() {
        if (!fichaActualData || typeof window.SITGO_PDF?.generar !== 'function') {
            notifyToast('No hay ficha cargada o el generador PDF no está disponible.', 'error');
            return;
        }
        try {
            await window.SITGO_PDF.generar(
                'sistemas_ficha',
                fichaActualData,
                `ficha_${fichaActualData.equipo?.codigo_equipo || 'equipo'}.pdf`,
                {
                    titulo_documento: 'FICHA TÉCNICA Y MANTENIMIENTO DE EQUIPO',
                    subtitulo: fichaActualData.equipo?.codigo_equipo || '',
                }
            );
        } catch (e) {
            notifyToast('Error al generar PDF: ' + e.message, 'error');
        }
    }
    async function exportarPdfGlobal(btnId = 'btnExportarTablaPdf') {
        if (typeof window.SITGO_PDF?.generar !== 'function') {
            notifyToast('El generador PDF no está disponible.', 'error');
            return;
        }

        const btn = $(btnId);
        if (btn) btn.disabled = true;

        try {
            const params = new URLSearchParams({
                q: dt?.state?.search || ($('filtroBuscar')?.value.trim() || ''),
                departamento: ssFiltroDepto ? ssFiltroDepto.getValue() : '',
                tipo: $('filtroTipo')?.value || '',
                status: $('filtroStatus')?.value || '',
                fecha_desde: $('filtroFechaDesde')?.value || '',
                fecha_hasta: $('filtroFechaHasta')?.value || ''
            });

            const res = await fetchJSON(`${API}/reporte-global?${params.toString()}`);
            if (!res.ok || !res.data) {
                throw new Error(res.message || 'No se pudo obtener información para el reporte.');
            }

            const equiposFiltrados = Array.isArray(res.data.equipos) ? res.data.equipos : [];
            if (equiposFiltrados.length === 0) {
                notifyToast('No hay registros con los filtros actuales para exportar a PDF.', 'warning');
                return;
            }

            await window.SITGO_PDF.generar(
                'sistemas_global',
                res.data,
                `reporte_sistemas_${new Date().toISOString().slice(0, 10)}.pdf`,
                {
                    titulo_documento: 'REPORTE DE SISTEMAS · EQUIPOS Y MANTENIMIENTO',
                    subtitulo: `Institución #${INST}`,
                }
            );
        } catch (e) {
            notifyToast('Error al generar PDF: ' + e.message, 'error');
        } finally {
            if (btn) btn.disabled = false;
        }
    }
    function mostrarErrores(form, errors) {
        if (!form || !errors) return;
        Object.entries(errors).forEach(([field, msg]) => {
            const span = form.querySelector(`[data-error="${field}"]`);
            const el   = form.querySelector(`[name="${field}"]`);
            if (span) span.textContent = msg;
            if (el) el.classList.add('error');
        });
    }

    function limpiarErrores(form) {
        if (!form) return;
        form.querySelectorAll('.error').forEach(el => { el.textContent = ''; });
        form.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
    }

    function notifyToast(msg, type = 'success') {
        if (typeof window.sitgoToast === 'function') {
            window.sitgoToast(msg, type);
            return;
        }
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

    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
});