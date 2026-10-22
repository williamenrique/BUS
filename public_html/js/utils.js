/* ============================================================
   SITGO · Mayúsculas automáticas globales
   ============================================================ */
document.addEventListener('input', (e) => {
    const el = e.target;
    if (!el || !el.matches) return;
    if (!el.matches('[data-uppercase]')) return;
    if (el.dataset.uppercase === 'off') return;
    if (el.readOnly || el.disabled) return;

    const pos = el.selectionStart;
    const before = el.value;
    const after = before.toUpperCase();

    if (before !== after){
        el.value = after;
        try{ el.setSelectionRange(pos, pos); } catch(_){}
    }
});

/* ============================================================
   Manejo global de errores de BD
   ============================================================ */
window.sitgoError = function(res, fallback){
    return res?.message || fallback || 'Ocurrió un error inesperado.';
};

window.fetchSafe = async function(url, opts = {}){
    try{
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', ...(opts.headers || {}) },
            ...opts
        });

        const ct = r.headers.get('content-type') || '';

        if (!ct.includes('application/json')){
            const text = await r.text();
            console.error('Respuesta no-JSON:', text.substring(0, 500));
            return {
                ok: false,
                message: r.ok
                    ? 'Respuesta inesperada del servidor.'
                    : 'Error ' + r.status + ' del servidor.',
                type: 'unknown'
            };
        }

        return await r.json();
    } catch (e){
        console.error('fetchSafe:', e);
        return {
            ok: false,
            message: 'Error de conexión con el servidor.',
            type: 'network'
        };
    }
};

/* ============================================================
   Toast global
   ============================================================ */
window.sitgoToast = function(message, type = 'success', duration = 4000){
    let c = document.querySelector('.toast-container');
    if (!c){
        c = document.createElement('div');
        c.className = 'toast-container';
        document.body.appendChild(c);
    }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.textContent = message;
    c.appendChild(t);
    setTimeout(() => t.remove(), duration);
};

window.sitgoNotify = function(res){
    if (!res) return;
    const msg = res.message || 'Ocurrió un error inesperado.';
    const tipo = res.type === 'database' ? 'error'
              : res.type === 'network'  ? 'error'
              : res.type === 'app'      ? 'warning'
              : 'error';
    window.sitgoToast(msg, tipo);
};

window.apiFetch = async function(url, opts = {}){
    try{
        const r = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...(opts.headers || {})
            },
            ...opts
        });

        const ct = r.headers.get('content-type') || '';

        if (!ct.includes('application/json')){
            const text = await r.text();
            console.error('❌ Respuesta no-JSON del servidor:', text.substring(0, 500));
            const m = text.match(/<p>(.*?)<\/p>/i);
            const msg = m ? m[1].trim() : `Error ${r.status} del servidor.`;

            return {
                ok: false,
                message: msg,
                type: 'unknown',
                status: r.status
            };
        }

        return await r.json();
    } catch (e){
        console.error('❌ Error de red:', e);
        return {
            ok: false,
            message: 'Error de conexión con el servidor.',
            type: 'network'
        };
    }
};

/* ============================================================
   INTERCEPTOR GLOBAL — Muestra toast en errores de BD
   ============================================================ */
(function(){
    const origFetch = window.fetch;

    window.fetch = async function(url, opts = {}){
        const r = await origFetch(url, opts);

        const wantsJson = (opts.headers?.['X-Requested-With'] === 'XMLHttpRequest')
            || (opts.headers?.['x-requested-with'] === 'XMLHttpRequest')
            || (typeof url === 'string' && (
                url.includes('/listar') ||
                url.includes('/obtener') ||
                url.includes('/guardar') ||
                url.includes('/actualizar') ||
                url.includes('/eliminar') ||
                url.includes('/combos') ||
                url.includes('/arbol') ||
                url.includes('/menus') ||
                url.includes('/contador')
            ));

        if (!wantsJson) return r;

        const clone = r.clone();

        if (r.status >= 500){
            try{
                const ct = clone.headers.get('content-type') || '';
                if (ct.includes('application/json')){
                    const data = await clone.json();
                    if (data && data.ok === false && data.message){
                        if (data.type === 'database'){
                            window.sitgoToast(data.message, 'error');
                        }
                    }
                } else {
                    const text = await clone.text();
                    const m = text.match(/<p>(.*?)<\/p>/i);
                    const msg = m ? m[1].trim() : `Error ${r.status} del servidor.`;
                    window.sitgoToast(msg, 'error');
                }
            } catch (e){
                console.warn('No se pudo parsear la respuesta de error:', e);
            }
        }

        return r;
    };
})();

/* ============================================================
   MODAL DE CONFIRMACIÓN · SITGO (GLOBAL)
   ------------------------------------------------------------
   Uso:
       const ok = await sitgoConfirm({
           title:     'Cerrar sesión',
           message:   '¿Deseas cerrar la sesión?',
           okText:    'Sí, cerrar',
           cancelText:'Cancelar',
           type:      'danger',   // warning | danger | info | success
           icon:      'log-out',
       });
       if (!ok) return;
   ============================================================ */
window.sitgoConfirm = function(options = {}){
    return new Promise((resolve) => {
        const {
            title     = '¿Confirmar acción?',
            message   = 'Esta acción no se puede deshacer.',
            okText    = 'Confirmar',
            cancelText= 'Cancelar',
            type      = 'warning',
            icon      = 'alert-triangle'
        } = options;

        const $ = id => document.getElementById(id);
        const modal     = $('sitgoConfirmModal');
        const iconWrap  = $('sitgoConfirmIconWrap');
        const titleEl   = $('sitgoConfirmTitle');
        const msgEl     = $('sitgoConfirmMessage');
        const okBtn     = $('sitgoConfirmOk');
        const cancelBtn = $('sitgoConfirmCancel');
        const okTextEl  = $('sitgoConfirmOkText');
        const cancelTextEl = $('sitgoConfirmCancelText');

        if (!modal){
            resolve(window.confirm(`${title}\n\n${message}`));
            return;
        }

        titleEl.textContent  = title;
        msgEl.textContent    = message;
        okTextEl.textContent = okText;
        cancelTextEl.textContent = cancelText;

        iconWrap.classList.remove('danger','info','success','warning');
        if (type !== 'warning') iconWrap.classList.add(type);

        iconWrap.innerHTML = `<i data-lucide="${icon}" class="w-6 h-6"></i>`;
        if (window.lucide && typeof window.lucide.createIcons === 'function'){
            try{ window.lucide.createIcons(); } catch(_) {}
        }

        okBtn.classList.toggle('btn-danger', type === 'danger');
        okBtn.classList.toggle('btn-primary', type !== 'danger');

        modal.classList.add('open');

        const cleanup = (result) => {
            modal.classList.remove('open');
            okBtn.removeEventListener('click', onOk);
            cancelBtn.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKey);
            resolve(result);
        };

        const onOk       = () => cleanup(true);
        const onCancel   = () => cleanup(false);
        const onBackdrop = (e) => { if (e.target === modal) cleanup(false); };
        const onKey      = (e) => {
            if (e.key === 'Escape') cleanup(false);
            if (e.key === 'Enter')  cleanup(true);
        };

        okBtn.addEventListener('click', onOk);
        cancelBtn.addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKey);

        setTimeout(() => cancelBtn.focus(), 50);
    });
};

/* ============================================================
   MODAL DE ELECCIÓN · SITGO (GLOBAL)
   ------------------------------------------------------------
   Muestra N opciones (botones). Devuelve el `value` elegido
   o null si el usuario cancela.
   Uso:
       const modo = await sitgoChoice({
           title:   '¿Cómo deseas el detallado?',
           message: 'Elige el formato del reporte.',
           options: [
               { value: 'unificado', label: 'Unificado',  hint: 'Todo en Bs' },
               { value: 'divisa',    label: 'Con divisa', hint: 'Columna $ + Bs' },
           ],
           cancelText: 'Cancelar',
           type:  'info',
           icon:  'printer',
       });
       if (!modo) return;
   ============================================================ */
window.sitgoChoice = function(options = {}){
    return new Promise((resolve) => {
        const {
            title      = '¿Qué opción deseas?',
            message    = '',
            options: opts = [],
            cancelText = 'Cancelar',
            type       = 'info',
            icon       = 'list-ordered'
        } = options;

        const $ = id => document.getElementById(id);
        const modal = $('sitgoConfirmModal');
        const iconWrap = $('sitgoConfirmIconWrap');
        const titleEl  = $('sitgoConfirmTitle');
        const msgEl    = $('sitgoConfirmMessage');
        const actions  = document.querySelector('#sitgoConfirmModal .confirm-actions');

        if (!modal || !actions){
            // Fallback si no está el modal
            const labels = opts.map(o => o.label).join(' / ');
            const v = window.prompt(`${title}\n(${labels})`, opts[0]?.value || '');
            resolve(v || null);
            return;
        }

        // Guardar HTML original de los botones para restaurarlo luego
        if (!actions._originalHtml){
            actions._originalHtml = actions.innerHTML;
        }

        // Título y mensaje
        titleEl.textContent = title;
        msgEl.textContent   = message;
        msgEl.style.display = message ? '' : 'none';

        // Icono + color
        iconWrap.classList.remove('danger','info','success','warning');
        if (type !== 'warning') iconWrap.classList.add(type);
        iconWrap.innerHTML = `<i data-lucide="${icon}" class="w-6 h-6"></i>`;
        if (window.lucide && typeof window.lucide.createIcons === 'function'){
            try{ window.lucide.createIcons(); } catch(_) {}
        }

        // Construir botones de opción + Cancelar
        const buttonsHtml = [
            ...opts.map((o, i) => `
                <button type="button"
                        class="btn ${i === 0 ? 'btn-primary' : 'btn-ghost'} sitgo-choice-opt"
                        data-value="${String(o.value).replace(/"/g,'&quot;')}"
                        style="min-width:140px; justify-content:center; flex-direction:column; align-items:flex-start; gap:.15rem; padding:.55rem .9rem;">
                    <span style="font-weight:700;">${o.label}</span>
                    ${o.hint ? `<small style="font-size:.7rem; opacity:.75; font-weight:400;">${o.hint}</small>` : ''}
                </button>
            `),
            `<button type="button" class="btn btn-ghost" id="sitgoConfirmCancel">
                <i data-lucide="x" class="w-4 h-4"></i>
                <span>${cancelText}</span>
            </button>`
        ].join('');

        actions.innerHTML = buttonsHtml;

        // Listener de opciones
        const optBtns = actions.querySelectorAll('.sitgo-choice-opt');
        const cancelBtn = actions.querySelector('#sitgoConfirmCancel');

        modal.classList.add('open');

        const cleanup = (result, restore = true) => {
            modal.classList.remove('open');
            optBtns.forEach(b => b.removeEventListener('click', onOpt));
            if (cancelBtn) cancelBtn.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKey);

            if (restore && actions._originalHtml){
                actions.innerHTML = actions._originalHtml;
                if (window.lucide && typeof window.lucide.createIcons === 'function'){
                    try{ window.lucide.createIcons(); } catch(_) {}
                }
            }

            resolve(result);
        };

        const onOpt = (e) => {
            const v = e.currentTarget.dataset.value;
            cleanup(v);
        };
        const onCancel = () => cleanup(null);
        const onBackdrop = (e) => { if (e.target === modal) cleanup(null); };
        const onKey = (e) => {
            if (e.key === 'Escape') cleanup(null);
        };

        optBtns.forEach(b => b.addEventListener('click', onOpt));
        if (cancelBtn) cancelBtn.addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKey);

        setTimeout(() => optBtns[0]?.focus(), 50);
    });
};