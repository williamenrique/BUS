/* ============================================================
   SITGO · Mayúsculas automáticas globales
   ------------------------------------------------------------
   Cualquier input/textarea con atributo [data-uppercase]
   convierte su valor a MAYÚSCULAS mientras el usuario escribe.
   Funciona con elementos agregados dinámicamente (AJAX).
   
   Excluir un campo: no poner el atributo
   Forzar minúsculas: [data-uppercase="off"]
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
    const msg = res?.message || fallback || 'Ocurrió un error inesperado.';
    // Si viene con type=database, muestra mensaje más amigable
    if (res?.type === 'database'){
        // El backend ya envía mensaje amigable, solo lo mostramos
    }
    return msg;
};

/**
 * Envuelve un fetch y devuelve siempre JSON.
 * Si hay error 500 del servidor con HTML, devuelve un objeto de error.
 */
window.fetchSafe = async function(url, opts = {}){
    try{
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', ...(opts.headers || {}) },
            ...opts
        });

        const ct = r.headers.get('content-type') || '';

        // Si no es JSON, algo salió mal a nivel servidor
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

        const data = await r.json();
        return data;
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
   Manejo global de errores de BD — SITGO
   Intercepta cualquier fetch que devuelva {ok:false, type:'database'}
   y muestra un toast amigable automáticamente.
   ============================================================ */

/**
 * Muestra un toast en pantalla.
 * Compatible con el toast de los módulos (usa .toast-container).
 */
window.sitgoToast = function(message, type = 'success', duration = 4000){
    // Si existe un toast() global (usado por módulos), lo reutilizamos
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

/**
 * Notifica un objeto de respuesta de error.
 * Uso: sitgoNotify(res)
 */
window.sitgoNotify = function(res){
    if (!res) return;
    const msg = res.message || 'Ocurrió un error inesperado.';
    const tipo = res.type === 'database' ? 'error'
              : res.type === 'network'  ? 'error'
              : res.type === 'app'      ? 'warning'
              : 'error';
    window.sitgoToast(msg, tipo);
};

/**
 * Wrapper de fetch que SIEMPRE devuelve un objeto.
 * - Si la respuesta es JSON válido → lo devuelve
 * - Si es HTML/error 500 → devuelve {ok:false, message, type:'unknown'}
 * - Si hay error de red → {ok:false, message, type:'network'}
 */
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

        // Si no es JSON → el servidor devolvió HTML de error (500 no controlado)
        if (!ct.includes('application/json')){
            const text = await r.text();
            console.error('❌ Respuesta no-JSON del servidor:', text.substring(0, 500));

            // Intenta extraer el mensaje si es una página de error de SITGO
            const m = text.match(/<p>(.*?)<\/p>/i);
            const msg = m ? m[1].trim() : `Error ${r.status} del servidor.`;

            return {
                ok: false,
                message: msg,
                type: 'unknown',
                status: r.status
            };
        }

        const data = await r.json();
        return data;

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

    // Solo interceptamos llamadas AJAX, NO las que ya usan apiFetch
    window.fetch = async function(url, opts = {}){
        const r = await origFetch(url, opts);

        // ¿Es una llamada AJAX esperada como JSON?
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

        // Clonamos para leer sin consumir el original
        const clone = r.clone();

        // Solo actúa en errores 500
        if (r.status >= 500){
            try{
                const ct = clone.headers.get('content-type') || '';
                if (ct.includes('application/json')){
                    const data = await clone.json();
                    if (data && data.ok === false && data.message){
                        // Mostrar toast automático solo si type=database
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

console.log('✅ SITGO · Manejo global de errores de BD activado');