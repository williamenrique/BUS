document.addEventListener('DOMContentLoaded', () => {
    const form       = document.getElementById('formLogin');
    const btn        = document.getElementById('btnLogin');
    const errBox     = document.getElementById('loginError');
    const togglePass = document.getElementById('togglePass');
    const passInput  = document.getElementById('password');

    // Modal recuperar
    const modal      = document.getElementById('modalForgot');
    const btnForgot  = document.getElementById('btnForgot');
    const closeF     = document.getElementById('closeForgot');
    const cancelF    = document.getElementById('cancelForgot');
    const formF      = document.getElementById('formForgot');
    const msgF       = document.getElementById('forgotMsg');
    const btnFSub    = document.getElementById('btnForgotSubmit');

    /* ============================================================
       HELPERS
       ============================================================ */
    function setLoading(boton, activo){
        if (!boton) return;
        boton.disabled = !!activo;
        boton.classList.toggle('is-loading', !!activo);
    }

    function mostrarError(msg){
        if (!errBox) return;
        errBox.textContent = msg || 'Error al ingresar.';
        errBox.style.display = '';
        errBox.classList.remove('hidden');
    }

    function ocultarError(){
        if (!errBox) return;
        errBox.textContent = '';
        errBox.style.display = 'none';
        errBox.classList.add('hidden');
    }

    /* ============================================================
       MODAL · Abrir con transición suave
       ============================================================ */
    function abrirModal(){
        if (!modal) return;

        // Limpiar mensajes previos
        if (msgF){
            msgF.textContent = '';
            msgF.style.display = 'none';
            msgF.className = 'login-alert';
        }

        // Quitar clase closing si quedó de un cierre previo
        modal.classList.remove('closing');

        // Mostrar
        modal.style.display = 'flex';
        void modal.offsetWidth; // forzar reflow
        modal.classList.add('open');

        document.body.style.overflow = 'hidden';
    }

    /* ============================================================
       MODAL · Cerrar con animación de salida
       ============================================================ */
    function cerrarModal(){
        if (!modal || !modal.classList.contains('open')) return;

        // Si ya está cerrando, no repetir
        if (modal.classList.contains('closing')) return;

        modal.classList.add('closing');
        modal.classList.remove('open');

        setTimeout(() => {
            modal.style.display = 'none';
            modal.classList.remove('closing');
            document.body.style.overflow = '';

            if (msgF){
                msgF.textContent = '';
                msgF.style.display = 'none';
                msgF.className = 'login-alert';
            }
        }, 300);
    }

    /* ============================================================
       TOGGLE VER CONTRASEÑA
       ============================================================ */
    togglePass?.addEventListener('click', () => {
        if (!passInput) return;
        const esPassword = passInput.type === 'password';
        passInput.type = esPassword ? 'text' : 'password';
        togglePass.textContent = esPassword ? 'Ocultar' : 'Ver';
    });

    /* ============================================================
       LOGIN
       ============================================================ */
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        ocultarError();

        const nick = document.getElementById('nick')?.value.trim();
        const pass = passInput?.value || '';

        if (!nick || !pass){
            mostrarError('Ingresa tu usuario y contraseña.');
            return;
        }

        setLoading(btn, true);

        try{
            const fd = new FormData(form);

            const r = await fetch((window.SITGO_BASE || '/') + 'login', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                mostrarError(`Respuesta inválida del servidor (HTTP ${r.status}).`);
                return;
            }

            const data = await r.json();

            if (data.ok){
                window.location.href = data.redirect;
                return;
            }

            const primerError =
                data.message ||
                (data.errors && Object.values(data.errors)[0]) ||
                'Error al ingresar.';

            mostrarError(primerError);

        } catch (err){
            console.error('Login error:', err);
            mostrarError('Error de conexión con el servidor. Intenta de nuevo.');
        } finally {
            setLoading(btn, false);
        }
    });

    /* ============================================================
       MODAL · Eventos
       ============================================================ */
    btnForgot?.addEventListener('click', (e) => {
        e.preventDefault();
        abrirModal();
    });

    closeF?.addEventListener('click', cerrarModal);
    cancelF?.addEventListener('click', cerrarModal);

    modal?.addEventListener('click', (e) => {
        if (e.target === modal) cerrarModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal && modal.classList.contains('open')){
            cerrarModal();
        }
    });

    /* ============================================================
       RECUPERAR CONTRASEÑA
       ============================================================ */
    formF?.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!msgF) return;

        msgF.textContent = '';
        msgF.style.display = 'none';

        const ident = document.getElementById('identificador')?.value.trim();
        if (!ident){
            msgF.textContent = 'Ingresa tu cédula o correo.';
            msgF.className = 'login-alert login-alert-error';
            msgF.style.display = '';
            return;
        }

        setLoading(btnFSub, true);

        try{
            const fd = new FormData(formF);

            const r = await fetch((window.SITGO_BASE || '/') + 'recuperar/solicitar', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')){
                msgF.textContent = `Respuesta inválida del servidor (HTTP ${r.status}).`;
                msgF.className = 'login-alert login-alert-error';
                msgF.style.display = '';
                return;
            }

            const data = await r.json();

            // Mostrar el mensaje (éxito o error)
            msgF.textContent = data.message || (data.ok ? 'Solicitud enviada.' : 'No se pudo procesar.');
            msgF.className = 'login-alert ' + (data.ok ? 'login-alert-success' : 'login-alert-error');
            msgF.style.display = '';

            if (data.ok){
                formF.reset();

                // Cerrar el modal después de 2.5 s con animación suave
                setTimeout(cerrarModal, 2500);
            }

        } catch (err){
            console.error('Recuperar error:', err);
            msgF.textContent = 'Error de conexión.';
            msgF.className = 'login-alert login-alert-error';
            msgF.style.display = '';
        } finally {
            setLoading(btnFSub, false);
        }
    });
});