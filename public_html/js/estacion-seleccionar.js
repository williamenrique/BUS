console.log('✅ estacion-seleccionar.js cargado');

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formEstacion');
    const btn  = document.getElementById('btnContinuar');
    const err  = document.getElementById('errBox');

    if (!form || !btn || !err){
        console.warn('estacion-seleccionar.js: elementos no encontrados');
        return;
    }

    let enviando = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (enviando) return;
        enviando = true;

        err.classList.add('hidden');
        err.textContent = '';
        btn.disabled = true;
        const textoOriginal = btn.textContent;
        btn.textContent = 'Guardando...';

        const idEstacion = document.getElementById('id_estacion').value;

        if (!idEstacion){
            err.textContent = 'Debes seleccionar una estación.';
            err.classList.remove('hidden');
            btn.disabled = false;
            btn.textContent = textoOriginal;
            enviando = false;
            return;
        }

        try{
            const r = await fetch((window.SITGO_BASE || '/') + 'estacion/seleccionar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ id_estacion: parseInt(idEstacion) })
            });

            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                throw new Error('Respuesta no JSON del servidor (HTTP ' + r.status + ')');
            }

            const d = await r.json();

            if (d.ok && d.redirect){
                // Navegación limpia: replace evita que el botón "atrás"
                // regrese a la selección ya completada.
                window.location.replace(d.redirect);
                return;
            }

            err.textContent = d.message || 'Error al guardar la estación.';
            err.classList.remove('hidden');
        } catch (ex){
            err.textContent = ex.message || 'Error de conexión.';
            err.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
            enviando = false;
        }
    });
});