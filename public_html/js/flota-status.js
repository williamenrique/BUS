document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'flota/status/i/' + (window.SITGO_INST || 1);
    const $ = id => document.getElementById(id);

    let dt = null;

    /* ============================================
       HELPERS
       ============================================ */
    async function fetchJSON(url, opts = {}) {
        try {
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                ...opts
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return { ok: false, message: `Error ${r.status}` };
            }
            return await r.json();
        } catch {
            return { ok: false, message: 'Error de conexión' };
        }
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try { window.lucide.createIcons(); } catch(e) { console.error(e); }
        }
    }

    function toast(msg, type = 'success') {
        let c = document.querySelector('.toast-container');
        if (!c) { c = document.createElement('div'); c.className = 'toast-container'; document.body.appendChild(c); }
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }

    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ============================================
       Badge de estado
       ------------------------------------------------------------
       - Devuelve el badge con el color según el idstatus.
       - Si la fila tiene id_flota, el badge es clickeable y abre
         el historial de la unidad filtrado por tipo=status.
       ============================================ */
    function badgeStatus(idstatus, idFlota) {
        let label, clase;
        switch (parseInt(idstatus)) {
            case 1:  label = 'Activo';           clase = 'badge-success'; break;
            case 0:  label = 'Inactivo';         clase = 'badge-danger';  break;
            case 2:  label = 'En Mantenimiento'; clase = 'badge-warning'; break;
            case 4:
            case 5:  label = 'Crítico';          clase = 'badge-danger';  break;
            default: label = 'Desconocido';      clase = 'badge-info';    break;
        }

        // Si no hay id_flota → devolvemos el badge estático
        if (!idFlota) {
            return `<span class="badge ${clase}">${label}</span>`;
        }

        // Badge clickeable → abre el historial filtrado por tipo=status
        const inst = window.SITGO_INST || 1;
        const base = (window.SITGO_BASE || '/');
        const url  = `${base}flota/i/${inst}/historial/${idFlota}?tipo=status`;

        return `
            <a href="${url}"
               class="badge ${clase} badge-link"
               title="Ver historial de cambios de estado de esta unidad"
               target="_blank" rel="noopener">
                ${label}
                <i data-lucide="external-link" class="badge-link-icon"></i>
            </a>
        `;
    }

    function fmtFecha(f) {
        if (!f) return '—';
        try {
            const d = new Date(f.replace(' ', 'T'));
            if (!isNaN(d.getTime())) {
                return d.toLocaleDateString('es-VE') + ' ' + d.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
            }
            return f;
        } catch { return f; }
    }

    /* ============================================
       RENDER ROW
       ============================================ */
    function renderRow(r) {
        const inst   = window.SITGO_INST || 1;
        const base   = (window.SITGO_BASE || '/');
        const urlHist = `${base}flota/i/${inst}/historial/${r.id_flota}`;

        return `
            <tr>
                <td>
                    <a href="${urlHist}"
                       class="unidad-link"
                       title="Ver historial completo de la unidad"
                       target="_blank" rel="noopener">
                        <strong>${esc(r.id_unidad || '—')}</strong>
                    </a>
                </td>
                <td>${esc((r.marca_unidad || '') + ' ' + (r.modelo_unidad || ''))}</td>
                <td><code style="font-size:.72rem;">${esc(r.vim_unidad || '—')}</code></td>
                <td>${badgeStatus(r.idstatus, r.id_flota)}</td>
                <td>${r.textCambio ? esc(r.textCambio) : '<span class="text-muted">Sin motivo</span>'}</td>
                <td>${esc(r.usuario_nombre || 'Sistema')}</td>
                <td class="text-center">${esc(fmtFecha(r.fechaCambio))}</td>
            </tr>
        `;
    }

    /* ============================================
       INIT
       ============================================ */
    (async () => {
        try {
            await cargarCombos();

            dt = new DataTable({
                tbodyId: 'tbodyStatus',
                endpoint: API + '/listar',
                columns: ['Unidad', 'Marca/Modelo', 'VIN', 'Estado', 'Motivo', 'Usuario', 'Fecha'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationStatus',
                totalId: 'totalStatus',
                startId: 'startStatus',
                endId: 'endStatus',
                noDataMessage: 'No hay cambios de estado registrados',
                getExtraParams: () => ({
                    status:   $('filtroStatus')?.value   || '',
                    id_flota: $('filtroUnidad')?.value   || '',
                    desde:    $('filtroDesde')?.value    || '',
                    hasta:    $('filtroHasta')?.value    || '',
                }),
                onDataLoaded: (payload) => {
                    if (payload && payload.counts) {
                        setText('kpi-activo',        payload.counts.activo        ?? 0);
                        setText('kpi-inactivo',      payload.counts.inactivo      ?? 0);
                        setText('kpi-mantenimiento', payload.counts.mantenimiento ?? 0);
                        setText('kpi-critico',       payload.counts.critico       ?? 0);
                    }
                    refreshIcons();
                }
            });

            bindEvents();
            refreshIcons();
        } catch (e) {
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    function setText(id, v) {
        const el = $(id);
        if (el) el.textContent = v;
    }

    /* ============================================
       COMBOS (unidades)
       ============================================ */
    async function cargarCombos() {
        const d = await fetchJSON(API + '/combos');
        if (!d.ok) return;

        const sel = $('filtroUnidad');
        if (!sel) return;

        sel.innerHTML = `<option value="">Todas las unidades</option>` +
            (d.unidades || []).map(u =>
                `<option value="${u.id_flota}">${esc(u.id_unidad)}${u.vim_unidad ? ' · ' + esc(u.vim_unidad) : ''}</option>`
            ).join('');
    }

    /* ============================================
       EVENTOS
       ============================================ */
    function bindEvents() {
        ['filtroStatus', 'filtroUnidad', 'filtroDesde', 'filtroHasta'].forEach(id => {
            $(id)?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        });

        $('btnLimpiar')?.addEventListener('click', () => {
            if ($('filtroBuscar'))  $('filtroBuscar').value  = '';
            if ($('filtroStatus'))  $('filtroStatus').value  = '';
            if ($('filtroUnidad'))  $('filtroUnidad').value  = '';
            if ($('filtroDesde'))   $('filtroDesde').value   = '';
            if ($('filtroHasta'))   $('filtroHasta').value   = '';
            dt?.changePage(1) || dt?.reload();
        });

        document.querySelectorAll('[data-status-kpi]').forEach(card => {
            card.addEventListener('click', () => {
                const kpi = card.dataset.statusKpi;
                const map = {
                    activo:        '1',
                    inactivo:      '0',
                    mantenimiento: '2',
                    critico:       '4',
                };
                const val = map[kpi] ?? '';

                document.querySelectorAll('[data-status-kpi]').forEach(c => {
                    c.classList.remove('active');
                    c.style.boxShadow = '';
                });
                card.classList.add('active');
                card.style.boxShadow = '0 0 0 3px rgba(37,99,235,.25)';

                if ($('filtroStatus')) $('filtroStatus').value = val;
                dt?.changePage(1) || dt?.reload();
            });
        });
    }
});