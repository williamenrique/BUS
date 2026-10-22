document.addEventListener('DOMContentLoaded', () => {
    // URL base de la API de perfil
    const API = (window.SITGO_BASE || '/') + 'perfil';

    const $ = id => document.getElementById(id);

    /* ============================================
       HELPER · fetch JSON con headers
       ============================================ */
    async function fetchJSON(url, opts = {}){
        const r = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            ...opts
        });
        return r.json();
    }

    /* ============================================
       TOAST · notificación flotante
       ============================================ */
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

    /* ============================================
       LIMPIAR ERRORES DE UN FORM
       ============================================ */
    function limpiarErrores(form){
        form.querySelectorAll('.error').forEach(el => el.textContent = '');
        form.querySelectorAll('input.error').forEach(el => el.classList.remove('error'));
    }

    /* ============================================
       MOSTRAR ERRORES DEVUELTOS POR EL BACKEND
       ============================================ */
    function mostrarErrores(form, errors){
        Object.entries(errors).forEach(([field, msg]) => {
            const errSpan = form.querySelector(`[data-error="${field}"]`);
            const input   = form.querySelector(`[name="${field}"]`);
            if (errSpan) errSpan.textContent = msg;
            if (input)   input.classList.add('error');
        });
    }

    /* ============================================
       SUBIR IMAGEN DE PERFIL
       ============================================ */
    $('perfilFile')?.addEventListener('change', async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        // Validar cliente (tamaño y tipo)
        const tipos = ['image/jpeg', 'image/png', 'image/webp'];
        if (!tipos.includes(file.type)){
            toast('Solo se permiten JPG, PNG o WEBP.', 'error');
            e.target.value = '';
            return;
        }
        if (file.size > 2 * 1024 * 1024){
            toast('La imagen no puede superar 2MB.', 'error');
            e.target.value = '';
            return;
        }

        // Preview inmediato
        const previewUrl = URL.createObjectURL(file);
        const avatar     = $('perfilAvatar');
        if (avatar){
            avatar.innerHTML = `<img src="${previewUrl}" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
        }

        // Enviar al backend
        const fd = new FormData();
        fd.append('imagen', file);

        try{
            const d = await fetchJSON(API + '/imagen', { method: 'POST', body: fd });
            if (!d.ok) throw new Error(d.message || 'Error al subir.');

            toast(d.message, 'success');

            // Actualizar avatar con URL definitiva
            if (d.url && avatar){
                avatar.innerHTML = `<img src="${d.url}?t=${Date.now()}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
            }

            // Actualizar también el avatar del navbar y del sidebar si existen
            actualizarAvataresGlobales(d.url);
        } catch (err){
            toast('Error: ' + err.message, 'error');
            // Si falló, restaurar el avatar anterior recargando
            setTimeout(() => location.reload(), 800);
        } finally{
            e.target.value = '';
        }
    });

    /* ============================================
       ELIMINAR IMAGEN DE PERFIL
       ============================================ */
    $('btnEliminarImagen')?.addEventListener('click', async () => {
        if (!confirm('¿Quitar tu imagen? Se restaurará la predeterminada.')) return;

        try{
            const d = await fetchJSON(API + '/imagen/eliminar', { method: 'POST' });
            if (!d.ok) throw new Error(d.message);

            toast(d.message, 'success');

            const avatar = $('perfilAvatar');
            if (avatar){
                // Reemplazar por imagen default
                avatar.innerHTML = `<img src="${d.url}?t=${Date.now()}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
            }

            actualizarAvataresGlobales(d.url);
        } catch (err){
            toast('Error: ' + err.message, 'error');
        }
    });

    /**
     * Actualiza los avatares del navbar y sidebar (si están en pantalla)
     * para que el cambio se refleje sin recargar.
     */
    function actualizarAvataresGlobales(url){
        if (!url) return;
        // Navbar
        document.querySelectorAll('.navbar-avatar').forEach(img => {
            img.src = url + '?t=' + Date.now();
        });
        // Sidebar
        document.querySelectorAll('.sidebar-avatar').forEach(img => {
            img.src = url + '?t=' + Date.now();
        });
    }

    /* ============================================
       GUARDAR DATOS PERSONALES
       ============================================ */
    $('formPerfilDatos')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        limpiarErrores(form);

        const btn = $('btnGuardarDatos');
        const txt = btn.querySelector('.btn-text');
        const original = txt.textContent;
        btn.disabled = true;
        txt.textContent = 'Guardando...';

        const payload = {
            personal_nombre:   $('personal_nombre').value.trim(),
            personal_apellido: $('personal_apellido').value.trim(),
            personal_email:    $('personal_email').value.trim(),
            personal_tlf:      $('personal_tlf').value.trim(),
        };

        try{
            const d = await fetchJSON(API + '/actualizar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok){
                if (d.errors) mostrarErrores(form, d.errors);
                else toast(d.message || 'Error al guardar', 'error');
                return;
            }

            toast(d.message, 'success');

            // Actualizar nombre en sidebar/navbar (texto)
            const nombreCompleto = `${payload.personal_nombre} ${payload.personal_apellido}`.trim();
            document.querySelectorAll('.sidebar-user-name').forEach(el => {
                el.textContent = payload.personal_nombre;
            });
            document.querySelectorAll('.sidebar-user-lastname').forEach(el => {
                el.textContent = payload.personal_apellido;
            });
            document.querySelectorAll('.navbar-username').forEach(el => {
                el.textContent = payload.personal_nombre;
            });
        } catch (err){
            console.error(err);
            toast('Error de conexión.', 'error');
        } finally{
            btn.disabled = false;
            txt.textContent = original;
        }
    });

    /* ============================================
       CAMBIAR CONTRASEÑA
       ============================================ */
    $('formPerfilPassword')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        limpiarErrores(form);

        const btn = $('btnGuardarPassword');
        const txt = btn.querySelector('.btn-text');
        const original = txt.textContent;
        btn.disabled = true;
        txt.textContent = 'Actualizando...';

        const payload = {
            password_actual:    $('password_actual').value,
            password_nueva:     $('password_nueva').value,
            password_confirmar: $('password_confirmar').value,
        };

        try{
            const d = await fetchJSON(API + '/password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!d.ok){
                if (d.errors) mostrarErrores(form, d.errors);
                else toast(d.message || 'Error al actualizar', 'error');
                return;
            }

            toast(d.message, 'success');
            form.reset();
        } catch (err){
            console.error(err);
            toast('Error de conexión.', 'error');
        } finally{
            btn.disabled = false;
            txt.textContent = original;
        }
    });
});