/**
 * publico-bienes.js
 * Vista pública de Bienes por Departamento (multi-institución).
 * - Sin select de departamento: el depto viene de la URL (?depto=)
 * - DataTable propia
 * - Modal propia para detalle
 * - Botón PDF
 */
document.addEventListener('DOMContentLoaded', () => {

    // ============================================================
    // CONFIG
    // ============================================================
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const idxInst   = pathParts.indexOf('bienes-publico');
    const ID_INST   = (idxInst >= 0 && pathParts[idxInst + 2]) ? pathParts[idxInst + 2] : '1';
    const DEPTO_URL = new URLSearchParams(window.location.search).get('depto') || '';

    const BASE_API  = (window.SITGO_BASE || '/') + 'bienes-publico/i/' + ID_INST;
    const $         = id => document.getElementById(id);

    let bienesPagina = [];
    let dtBienes     = null;

    // ============================================================
    // INIT
    // ============================================================
    (async () => {
        if (typeof DataTable === 'undefined') {
            mostrarError('El componente DataTable no está cargado.');
            return;
        }

        initDataTable();
        bindEvents();
        refreshIcons();

        // Timeout defensivo (8s)
        setTimeout(() => {
            const tbody = $('bnBody');
            if (tbody && tbody.querySelector('.dt-loading')) {
                mostrarError('No se pudo cargar el inventario. Revisa la consola (F12) → Network.');
            }
        }, 8000);
    })();

    // ============================================================
    // DATATABLE PROPIA
    // ============================================================
    function initDataTable() {
        dtBienes = new DataTable({
            tbodyId:         'bnBody',
            endpoint:        BASE_API + '/data',
            columns:         ['ID', 'Descripción', 'Grupo', 'Subgrupo', 'Sección', 'Estado', 'Adquisición', 'Ver'],
            searchInputId:   'bnSearch',
            limitSelectorId: 'bnLimit',
            paginationId:    'bnPagination',
            totalId:         'bnTotalFiltrados',
            startId:         'bnStart',
            endId:           'bnEnd',
            noDataMessage:   'No hay bienes registrados en este departamento',
            defaultLimit:    10,
            getExtraParams:  () => DEPTO_URL ? { depto: DEPTO_URL } : {},
            onDataLoaded: (res) => {
                bienesPagina = res.data || [];
                if ($('bnTotal')) $('bnTotal').textContent = res.totalFiltrados ?? res.total ?? 0;
            },
            renderRow: (row, i) => {
                return `
                    <tr>
                        <td><strong>${esc(row.id_bien)}</strong></td>
                        <td style="max-width:340px;">${esc(row.descripcion_bien || '—')}</td>
                        <td>${esc(row.grupo || '—')}</td>
                        <td>${esc(row.subgrupo || '—')}</td>
                        <td>${esc(row.seccion || '—')}</td>
                        <td>${statusBadge(row.status_bien, row.status_bien_texto)}</td>
                        <td class="text-center" style="font-size:.75rem;">${esc(row.fecha_adquisicion_fmt || '—')}</td>
                        <td class="text-center">
                            <button class="btn btn-ghost btn-sm" data-ver-idx="${i}" title="Ver detalle">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }
        });
    }

    // ============================================================
    // EVENTOS
    // ============================================================
    function bindEvents() {
        $('btnBnPdf')?.addEventListener('click', descargarPdf);

        $('bnBody')?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-ver-idx]');
            if (!btn) return;
            const idx = parseInt(btn.dataset.verIdx);
            const bien = bienesPagina[idx];
            if (bien) abrirDetalle(bien);
        });

        const modal = $('modalDetalleBien');
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target.closest('[data-close]')) {
                    modal.classList.remove('open');
                    document.body.style.overflow = '';
                }
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && modal.classList.contains('open')) {
                    modal.classList.remove('open');
                    document.body.style.overflow = '';
                }
            });
        }
    }

    // ============================================================
    // DETALLE
    // ============================================================
    function abrirDetalle(b) {
        if (!$('modalDetalleBody')) return;

        $('modalDetalleBody').innerHTML = `
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:.5rem; padding:.75rem;">
                    <div style="font-size:.65rem; color:#64748b; text-transform:uppercase; font-weight:700;">ID del bien</div>
                    <div style="font-size:1.05rem; font-weight:800; color:#0f172a; margin-top:.25rem;">${esc(b.id_bien)}</div>
                </div>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:.5rem; padding:.75rem;">
                    <div style="font-size:.65rem; color:#64748b; text-transform:uppercase; font-weight:700;">Estado</div>
                    <div style="margin-top:.35rem;">${statusBadge(b.status_bien, b.status_bien_texto)}</div>
                </div>
            </div>
            <div style="margin-top:.75rem;">
                <div style="font-size:.7rem; color:#64748b; text-transform:uppercase; font-weight:700; margin-bottom:.25rem;">Descripción</div>
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:.5rem; padding:.75rem; font-size:.85rem; color:#334155; line-height:1.5;">
                    ${esc(b.descripcion_bien || '—')}
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:.5rem; margin-top:.75rem; font-size:.8rem;">
                <div style="background:#f8fafc; padding:.55rem .7rem; border-radius:.4rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Departamento</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(b.departamento_bien || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.55rem .7rem; border-radius:.4rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Grupo</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(b.grupo || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.55rem .7rem; border-radius:.4rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Subgrupo</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(b.subgrupo || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.55rem .7rem; border-radius:.4rem;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Sección</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(b.seccion || '—')}</div>
                </div>
                <div style="background:#f8fafc; padding:.55rem .7rem; border-radius:.4rem; grid-column:1/-1;">
                    <div style="font-size:.6rem; color:#64748b; text-transform:uppercase; font-weight:700;">Fecha de adquisición</div>
                    <div style="color:#334155; font-weight:600; margin-top:.15rem;">${esc(b.fecha_adquisicion_fmt || '—')}</div>
                </div>
            </div>
        `;

        $('modalDetalleBien').classList.add('open');
        document.body.style.overflow = 'hidden';
        refreshIcons();
    }

    // ============================================================
    // PDF
    // ============================================================
    async function descargarPdf() {
        const btn = $('btnBnPdf');
        if (btn) { btn.disabled = true; btn.style.opacity = '.6'; }

        try {
            const r = await fetch(BASE_API + '/pdf', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ depto: DEPTO_URL })
            });
            const d = await r.json();

            if (!d.ok) {
                alert(d.message || 'No se pudo generar el PDF');
                return;
            }

            window.open(d.url, '_blank');
        } catch (e) {
            console.error(e);
            alert('Error al generar el PDF');
        } finally {
            if (btn) { btn.disabled = false; btn.style.opacity = ''; }
        }
    }

    // ============================================================
    // HELPERS
    // ============================================================
    function esc(s) {
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function statusBadge(raw, texto) {
        const t = (texto || raw || '').toString().toLowerCase();
        let cls = 'badge badge-secondary';
        if (t.includes('uso') || t === 'activo' || t === '1')          cls = 'badge badge-success';
        else if (t.includes('repar') || t === '3')                     cls = 'badge badge-warning';
        else if (t.includes('dañ') || t.includes('dan') || t === '4')  cls = 'badge badge-danger';
        else if (t.includes('extravi'))                                cls = 'badge badge-danger';
        return `<span class="${cls}">${esc(texto || raw || '—')}</span>`;
    }

    function mostrarError(msg) {
        const tbody = $('bnBody');
        if (tbody) {
            tbody.innerHTML = `<tr><td colspan="8" class="dt-error">${esc(msg)}</td></tr>`;
        }
    }

    function refreshIcons() {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            try { window.lucide.createIcons(); } catch (_) {}
        }
    }
});