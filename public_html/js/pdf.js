/**
 * SITGO · Helper centralizado para generación de PDFs
 *
 * Flujo:
 *   1. POST /pdf/generar  → devuelve { ok: true, url, filename }
 *   2. window.open(url)   → el navegador pide el PDF y lo muestra inline
 */
window.SITGO_PDF = (function () {

    function base() {
        return (window.SITGO_BASE || '/');
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

        try {
            const res = await fetch(base() + 'pdf/generar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            });

            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                console.error('SITGO_PDF: respuesta no JSON del servidor');
                alert('No se pudo preparar el PDF (respuesta inválida).');
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