console.log('✅ aceite.js cargado. SITGO_BASE =', window.SITGO_BASE);

document.addEventListener('DOMContentLoaded', () => {
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'flota/aceite/i/' + INST;
    const BASE = (window.SITGO_BASE || '/');
    const $ = id => document.getElementById(id);

    let dt = null;

    const STATUS_BADGE = {
        'Requerido':    'badge-danger',
        'Próximo':      'badge-warning',
        'Bien':         'badge-success',
        'Sin Registro': 'badge-secondary',
    };

    function renderRow(u){
        const badge = STATUS_BADGE[u.estado] || 'badge-secondary';
        const km     = Number(u.kilometraje_actual  || 0);
        const ultimo = Number(u.ultimo_cambio_km    || 0);
        const prox   = Number(u.proximo_cambio_km   || 0);
        const resta  = Number(u.km_restantes        || 0);
        const fecha  = u.fecha_ultimo_cambio || '';

        const restColor = resta < 0 ? 'style="color:#dc2626; font-weight:700;"' :
                          (prox > 0 && resta <= 1000) ? 'style="color:#b45309; font-weight:700;"' : '';

        const fechaTxt = fecha && fecha !== '0000-00-00'
            ? formatFecha(fecha)
            : '<span class="text-muted">N/A</span>';

        const idUnidadLink = `
            <a href="${BASE}flota/i/${INST}/historial/${u.id_flota}"
               class="font-semibold" style="color:#1d4ed8;">
                ${esc(u.id_unidad)}
            </a>
            <div style="font-size:.72rem; color:#94a3b8; margin-top:.1rem;">
                ${esc(u.marca_unidad || '—')} ${esc(u.modelo_unidad || '')}
            </div>`;

        return `
            <tr>
                <td>${idUnidadLink}</td>
                <td class="text-right">${km.toLocaleString('es-VE')}</td>
                <td class="text-right">${ultimo ? ultimo.toLocaleString('es-VE') : '<span class="text-muted">—</span>'}</td>
                <td>${fechaTxt}</td>
                <td class="text-right">${prox ? prox.toLocaleString('es-VE') : '<span class="text-muted">—</span>'}</td>
                <td class="text-right" ${restColor}>${prox ? resta.toLocaleString('es-VE') : '<span class="text-muted">—</span>'}</td>
                <td class="text-center"><span class="badge ${badge}">${esc(u.estado)}</span></td>
                <td class="text-right">
                    <div class="actions">
                        <button class="action-btn" data-action="km" data-id="${u.id_flota}" data-unidad="${esc(u.id_unidad)}" data-km="${km}" title="Actualizar KM">
                            <i data-lucide="gauge" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn edit" data-action="aceite" data-id="${u.id_flota}" data-unidad="${esc(u.id_unidad)}" data-km="${km}" title="Registrar cambio de aceite">
                            <i data-lucide="droplet" class="w-4 h-4"></i>
                        </button>
                        <button class="action-btn delete" data-action="eliminar" data-id="${u.id_flota}" data-unidad="${esc(u.id_unidad)}" title="Eliminar registro">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    dt = new DataTable({
        tbodyId: 'tbodyAceite',
        endpoint: API + '/status',
        columns: ['Unidad','KM Actual','Último Cambio','Fecha','Próximo','Restantes','Estado','Acciones'],
        renderRow: renderRow,
        searchInputId: 'filtroBuscar',
        limitSelectorId: 'filtroLimit',
        paginationId: 'paginationAceite',
        totalId: 'totalAceite',
        startId: 'startAceite',
        endId: 'endAceite',
        noDataMessage: 'No hay unidades registradas',
        defaultLimit: 10,
        getExtraParams: () => ({
            estado: $('filtroEstado')?.value || '',
        }),
        onDataLoaded: (res) => {
            pintarContadores(res.counts || {});
            bindTableActions();
        }
    });

    $('filtroEstado')?.addEventListener('change', () => {
        document.querySelectorAll('.aceite-card').forEach(c => c.classList.remove('active'));
        dt.changePage(1);
    });

    document.querySelectorAll('.aceite-card').forEach(card => {
        card.addEventListener('click', () => {
            const status = card.dataset.status;
            const activo = card.classList.contains('active');

            document.querySelectorAll('.aceite-card').forEach(c => c.classList.remove('active'));

            if (activo){
                $('filtroEstado').value = '';
            } else {
                card.classList.add('active');
                $('filtroEstado').value = status;
            }

            dt.changePage(1);
        });
    });

    function pintarContadores(counts){
        $('cntRequerido').textContent   = counts['Requerido']    || 0;
        $('cntProximo').textContent     = counts['Próximo']      || 0;
        $('cntBien').textContent        = counts['Bien']         || 0;
        $('cntSinRegistro').textContent = counts['Sin Registro'] || 0;
    }

    function bindTableActions(){
        const tbody = $('tbodyAceite');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, id, unidad, km } = btn.dataset;
            if (action === 'km')       abrirModalKm(id, unidad, km);
            if (action === 'aceite')   abrirModalCambio(id, unidad, km);
            if (action === 'eliminar') abrirModalEliminar(id, unidad);
        });
    }

    function abrirModalKm(id, unidad, km){
        $('km_id_flota').value = id;
        $('km_unidad_label').textContent = unidad;
        $('km_valor').value = km > 0 ? km : '';
        $('modalKm').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function abrirModalCambio(id, unidad, km){
        $('cambio_id_flota').value = id;
        $('cambio_km_anterior').value = km;
        $('cambio_unidad_label').textContent = unidad;
        $('cambio_km').value = km > 0 ? km : '';
        $('cambio_fecha').valueAsDate = new Date();
        $('modalCambio').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function abrirModalEliminar(id, unidad){
        $('del_id_flota').value = id;
        $('del_unidad_label').textContent = unidad;
        $('modalEliminar').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    window.cerrarModalAceite = function(idModal){
        $(idModal).classList.remove('open');
        document.body.style.overflow = '';
    };

    $('formKm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());
        await post(`${API}/km`, payload, 'modalKm', e.target);
    });

    $('formCambio')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = Object.fromEntries(new FormData(e.target).entries());
        await post(`${API}/cambio`, payload, 'modalCambio', e.target);
    });

    document.querySelectorAll('[data-del]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const tipo = btn.dataset.del;
            const id   = $('del_id_flota').value;
            if (!confirm(`¿Eliminar el último registro de ${tipo}?`)) return;
            await post(`${API}/eliminar`, { id_flota: id, tipo }, 'modalEliminar');
        });
    });

    async function post(url, payload, closeModalId, form = null){
        try{
            const r = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            });
            const d = await r.json();
            if (!d.ok){ toast(d.message || 'Error', 'error'); return; }
            toast(d.message, 'success');
            if (closeModalId) window.cerrarModalAceite(closeModalId);
            if (form) form.reset();
            dt.refresh();
        } catch (err){
            console.error(err);
            toast('Error de conexión', 'error');
        }
    }

    /* ============================================================
       GENERAR PDF DE ACEITE
       ============================================================ */
    $('btnGenerarPdfAceite')?.addEventListener('click', generarPdfAceite);

    async function generarPdfAceite(){
        const btn = $('btnGenerarPdfAceite');
        btn.disabled = true;

        try {
            const estado = $('filtroEstado')?.value || '';

            // Cargar todas las unidades con el filtro actual
            const params = new URLSearchParams({
                estado: estado,
                limit: 9999,
                offset: 0,
                q: '',
            });

            const r = await fetch(`${API}/status?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const d = await r.json();

            if (!d.ok) {
                toast(d.message || 'Error al cargar las unidades.', 'error');
                return;
            }

            const items  = d.data || [];
            const counts = d.counts || {};

            if (!items.length) {
                toast('No hay unidades para generar el PDF.', 'error');
                return;
            }

            await SITGO_PDF.generar(
                'flota_aceite',
                {
                    items:  items,
                    counts: counts,
                    filtro: estado || 'Todos',
                },
                `estado_aceite_${new Date().toISOString().slice(0,10)}.pdf`,
                {
                    titulo_documento: 'REPORTE DE ESTADO DE ACEITE',
                    doc_color:        '#0f172a',
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error de conexión al generar el PDF.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function formatFecha(f){
        if (!f) return '';
        try{
            const d = new Date(f.replace(' ', 'T'));
            return d.toLocaleDateString('es-VE', { day: '2-digit', month: 'short', year: 'numeric' });
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