document.addEventListener('DOMContentLoaded', () => {
    const ID   = window.SITGO_ID_FLOTA;
    const INST = window.SITGO_INST || 1;
    const API  = (window.SITGO_BASE || '/') + 'flota/i/' + INST;
    const $ = id => document.getElementById(id);

    let state = {
        page: 1,
        limit: 10,
        totalPages: 1,
    };

    const TIPO_MAP = {
        despacho:      { icon: 'truck',       color: 'blue',   label: 'Despacho' },
        aceite:        { icon: 'droplet',     color: 'amber',  label: 'Cambio de aceite' },
        mantenimiento: { icon: 'wrench',      color: 'green',  label: 'Mantenimiento' },
        status:        { icon: 'refresh-cw',  color: 'purple', label: 'Cambio de estado' },
    };

    const STATUS_TEXTO = {
        0: 'Desincorporada',
        1: 'Operativa',
        2: 'Inoperativa',
        3: 'Mantenimiento',
        4: 'Por Desincorporar',
        5: 'Crítica',
    };

    cargar();

    $('formFiltrosHistorial')?.addEventListener('submit', (e) => {
        e.preventDefault();
        state.page = 1;
        cargar();
    });
    $('btnLimpiarFiltros')?.addEventListener('click', () => {
        $('formFiltrosHistorial').reset();
        state.page = 1;
        cargar();
    });

    // Generar PDF
    $('btnGenerarPdfHistorial')?.addEventListener('click', generarPdf);

    async function cargar(){
        const cont = $('timelineContainer');
        cont.innerHTML = `
            <div class="historial-loading">
                <div class="dt-loading">Cargando historial...</div>
            </div>`;

        const payload = {
            desde:  $('hf_desde')?.value || '',
            hasta:  $('hf_hasta')?.value || '',
            tipo:   $('hf_tipo')?.value  || '',
            q:      $('hf_q')?.value     || '',
            limit:  state.limit,
            offset: (state.page - 1) * state.limit,
        };

        try{
            const r = await fetch(`${API}/historial/${ID}/data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.message);

            render(d);
        } catch (e){
            console.error(e);
            cont.innerHTML = `<div class="historial-empty">
                <p style="color:#dc2626;">Error: ${esc(e.message)}</p>
            </div>`;
        }
    }

    async function generarPdf(){
        const btn = $('btnGenerarPdfHistorial');
        btn.disabled = true;

        try {
            // Cargar TODOS los eventos (sin paginación) con los filtros actuales
            const payload = {
                desde:  $('hf_desde')?.value || '',
                hasta:  $('hf_hasta')?.value || '',
                tipo:   $('hf_tipo')?.value  || '',
                q:      $('hf_q')?.value     || '',
                limit:  9999,
                offset: 0,
            };

            const r = await fetch(`${API}/historial/${ID}/data`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            const d = await r.json();

            if (!d.ok) {
                toast(d.message || 'Error al cargar los eventos.', 'error');
                return;
            }

            const items  = d.data || [];
            const counts = d.counts || {};

            if (!items.length) {
                toast('No hay eventos para generar el PDF.', 'error');
                return;
            }

            await SITGO_PDF.generar(
                'flota_hoja_vida',
                {
                    items:      items,
                    counts:     counts,
                    unidadData: window.SITGO_UNIDAD_DATA || {},
                },
                `hoja_vida_${window.SITGO_UNIDAD_DATA?.id_unidad || 'unidad'}.pdf`,
                {
                    titulo_documento: 'HOJA DE VIDA DE UNIDAD',
                    doc_numero:       `#${window.SITGO_UNIDAD_DATA?.id_unidad || ''}`,
                    doc_color:        '#1e40af',
                }
            );
        } catch (e) {
            console.error(e);
            toast('Error de conexión al generar el PDF.', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    function render(d){
        const items = d.data || [];
        const total = d.total || 0;
        const counts = d.counts || {};
        const limit = state.limit;
        state.totalPages = Math.max(1, Math.ceil(total / limit));

        $('cntDespacho').textContent      = counts.despacho      || 0;
        $('cntAceite').textContent        = counts.aceite        || 0;
        $('cntMantenimiento').textContent = counts.mantenimiento || 0;
        $('cntStatus').textContent        = counts.status        || 0;
        $('cntTotal').textContent         = total;
        $('historialResumen').style.display = total > 0 ? '' : 'none';

        if (!items.length){
            $('timelineContainer').innerHTML = `
                <div class="historial-empty">
                    <div class="historial-empty-icon">
                        <i data-lucide="inbox" class="w-16 h-16 mx-auto" style="color:#cbd5e1;"></i>
                    </div>
                    <h4>Sin eventos registrados</h4>
                    <p>No se encontraron eventos para los filtros seleccionados.</p>
                </div>`;
            $('paginationWrap').style.display = 'none';
            if (window.lucide) lucide.createIcons();
            return;
        }

        $('timelineContainer').innerHTML = `<div class="timeline">${items.map(renderEvento).join('')}</div>`;

        const start = (state.page - 1) * limit + 1;
        const end   = Math.min(state.page * limit, total);
        $('startHistorial').textContent = start;
        $('endHistorial').textContent   = end;
        $('totalHistorial').textContent = total;
        $('paginationWrap').style.display = '';
        renderPaginacion(state.page, state.totalPages);

        if (window.lucide) lucide.createIcons();
    }

    function renderEvento(ev){
        const meta = TIPO_MAP[ev.tipo] || { icon: 'circle', color: 'slate', label: 'Evento' };
        const fecha = formatFecha(ev.fecha);
        const usuario = ev.usuario_nombre || 'Sistema';

        return `
            <div class="timeline-item">
                <div class="timeline-dot dot-${meta.color}">
                    <i data-lucide="${meta.icon}" class="w-4 h-4"></i>
                </div>
                <div class="timeline-card">
                    <div class="timeline-card-head">
                        <div>
                            <span class="timeline-tag tag-${meta.color}">${meta.label}</span>
                            <h4 class="timeline-title">${esc(ev.titulo || meta.label)}</h4>
                        </div>
                        <span class="timeline-date">
                            <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                            ${esc(fecha)}
                        </span>
                    </div>

                    <div class="timeline-body">
                        ${renderBody(ev, meta)}
                    </div>

                    <div class="timeline-foot">
                        <i data-lucide="user" class="w-3.5 h-3.5"></i>
                        Registrado por <strong>${esc(usuario)}</strong>
                    </div>
                </div>
            </div>`;
    }

    function renderBody(ev, meta){
        switch (ev.tipo){
            case 'despacho': {
                const arts = ev.articulos || [];
                const list = arts.length
                    ? `<ul class="timeline-list">${arts.map(a => `
                        <li>
                            <span class="timeline-qty">${esc(a.cant_despacho)}x</span>
                            ${esc(a.producto || '—')}
                        </li>`).join('')}</ul>`
                    : `<p class="text-muted">Sin artículos registrados.</p>`;

                const obs = ev.descripcion
                    ? `<p class="timeline-note">${esc(ev.descripcion)}</p>`
                    : '';

                return `${obs}<p class="timeline-label">Artículos despachados:</p>${list}`;
            }

            case 'aceite': {
                const km = parseInt(ev.km_cambio || 0).toLocaleString('es-VE');
                const ant = parseInt(ev.km_anterior || 0);
                const antTxt = ant > 0
                    ? `de <strong>${ant.toLocaleString('es-VE')} km</strong> a `
                    : '';
                const obs = ev.observaciones
                    ? `<p class="timeline-note">${esc(ev.observaciones)}</p>`
                    : '';
                return `<p>Cambio registrado ${antTxt}<strong style="color:#16a34a;">${km} km</strong>.</p>${obs}`;
            }

            case 'mantenimiento': {
                const tipo = ev.tipo_mantenimiento === 'P' ? 'Preventivo' : 'Correctivo';
                return `
                    <p><strong>Tipo:</strong> ${tipo}</p>
                    ${ev.diagnostico ? `<p><strong>Diagnóstico:</strong> ${esc(ev.diagnostico)}</p>` : ''}
                    ${ev.mecanico ? `<p><strong>Mecánico:</strong> ${esc(ev.mecanico)}</p>` : ''}
                    ${ev.recomendacion ? `<p><strong>Recomendación:</strong> ${esc(ev.recomendacion)}</p>` : ''}
                `;
            }

            case 'status': {
                const nuevo = STATUS_TEXTO[ev.status_codigo] || '—';
                return `
                    <p>La unidad cambió su estado a <strong>${esc(nuevo)}</strong>.</p>
                    ${ev.motivo ? `<p><strong>Motivo:</strong> ${esc(ev.motivo)}</p>` : ''}
                `;
            }

            default:
                return '<p>Sin detalles disponibles.</p>';
        }
    }

    function renderPaginacion(current, total){
        const cont = $('paginationHistorial');
        let html = '';

        const btn = (page, icon, disabled, extra = '') => `
            <button class="dt-page-btn ${extra}" data-page="${page}" ${disabled ? 'disabled' : ''}>
                ${icon ? `<i data-lucide="${icon}" class="w-4 h-4"></i>` : page}
            </button>`;

        html += btn(1, 'chevrons-left', current === 1);
        html += btn(current - 1, 'chevron-left', current === 1);

        getPageNumbers(current, total).forEach(n => {
            if (n === '...'){
                html += `<span class="dt-page-dots">…</span>`;
            } else {
                html += btn(n, null, false, n === current ? 'active' : '');
            }
        });

        html += btn(current + 1, 'chevron-right', current === total);
        html += btn(total, 'chevrons-right', current === total);

        cont.innerHTML = html;

        cont.querySelectorAll('.dt-page-btn').forEach(b => {
            b.addEventListener('click', () => {
                if (b.disabled) return;
                const p = parseInt(b.dataset.page);
                if (p >= 1 && p <= state.totalPages){
                    state.page = p;
                    cargar();
                    $('timelineWrap').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });

        if (window.lucide) lucide.createIcons();
    }

    function getPageNumbers(current, total){
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
        if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    function formatFecha(f){
        if (!f) return '—';
        try{
            const d = new Date(f.replace(' ', 'T'));
            return d.toLocaleString('es-VE', {
                day: '2-digit', month: 'short', year: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch { return f; }
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
});