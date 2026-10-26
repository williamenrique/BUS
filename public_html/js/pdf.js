/**
 * SITGO · Helper centralizado para generación de PDFs
 *
 * Flujo:
 *   1. POST → endpoint correspondiente → devuelve { ok: true, url, filename }
 *   2. window.open(url) → el navegador pide el PDF y lo muestra inline
 *
 * Endpoints:
 *   - Sistema (con sesión): /pdf/generar
 *   - Público (sin sesión): /movimientos/pdf  (detectado por URL actual)
 */
window.SITGO_PDF = (function () {

    function base() {
        return (window.SITGO_BASE || '/');
    }

    /**
     * Detecta si estamos en una vista pública mirando el path actual.
     * - /movimientos  → público
     * - /movimientos/... → público
     * - cualquier otra → sistema (con sesión)
     */
    function esContextoPublico() {
        const path = (window.location.pathname || '').replace(/^\/+/, '');
        // Limpiar prefijo del BASE_URL si existe (ej: prueba.test/movimientos)
        const basePath = (base() || '/').replace(/^https?:\/\/[^\/]+/, '').replace(/^\/+/, '');
        const rel = basePath && path.startsWith(basePath) ? path.substring(basePath.length) : path;
        return rel === 'movimientos' || rel.startsWith('movimientos/');
    }

    /**
     * Devuelve el endpoint de generación según el contexto.
     */
    function endpointGenerar() {
        const b = base();
        if (esContextoPublico()) {
            return b.replace(/\/?$/, '/') + 'movimientos/pdf';
        }
        return b.replace(/\/?$/, '/') + 'pdf/generar';
    }

    async function generar(view, data, filename, meta) {
        if (!view) {
            console.error('SITGO_PDF: falta "view"');
            return false;
        }

        const body = {
            view: view,
            data: data || {},
            filename: filename || 'documento.pdf',
            meta: meta || {}
        };

        const url = endpointGenerar();
        console.log('SITGO_PDF → POST', url, '· view:', view);

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            });

            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                console.error('SITGO_PDF: respuesta no JSON del servidor', res.status, url);
                alert('No se pudo preparar el PDF (respuesta inválida). Revisa la consola.');
                return false;
            }

            const json = await res.json();

            if (!json.ok || !json.url) {
                console.error('SITGO_PDF error:', json);
                alert(json.message || 'No se pudo generar el PDF.');
                return false;
            }

            const win = window.open(json.url, '_blank');
            if (!win) {
                window.location.href = json.url;
            }

            return true;
        } catch (e) {
            console.error('SITGO_PDF excepción:', e);
            alert('Error de conexión al generar el PDF.');
            return false;
        }
    }

    return { generar };
})();