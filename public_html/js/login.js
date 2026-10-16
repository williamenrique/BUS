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

    // Toggle ver contraseña
    togglePass?.addEventListener('click', () => {
        passInput.type = passInput.type === 'password' ? 'text' : 'password';
        togglePass.textContent = passInput.type === 'password' ? 'Ver' : 'Ocultar';
    });

    // Login
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        errBox.classList.add('hidden');
        btn.disabled = true;
        btn.textContent = 'Ingresando...';

        try {
            const fd = new FormData(form);
            const r  = await fetch(window.SITGO_BASE + 'login', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();

            if (data.ok) {
                window.location.href = data.redirect;
            } else {
                errBox.textContent = data.message
                    || Object.values(data.errors || {})[0]
                    || 'Error al ingresar.';
                errBox.classList.remove('hidden');
            }
        } catch (err) {
            errBox.textContent = 'Error de conexión con el servidor.';
            errBox.classList.remove('hidden');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Iniciar sesión';
        }
    });

    // Modal
    const openModal  = () => modal.classList.remove('hidden');
    const closeModal = () => { modal.classList.add('hidden'); msgF.classList.add('hidden'); };

    btnForgot?.addEventListener('click', openModal);
    closeF?.addEventListener('click', closeModal);
    cancelF?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });

        // Recuperar
    formF?.addEventListener('submit', async (e) => {
        e.preventDefault();
        msgF.classList.add('hidden');
        btnFSub.disabled = true;
        btnFSub.textContent = 'Enviando...';

        try{
            const fd = new FormData(formF);
            const r = await fetch(window.SITGO_BASE + 'recuperar/solicitar', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();

            msgF.textContent = data.message || (data.ok ? 'Solicitud enviada.' : 'No se pudo procesar.');
            msgF.className = 'text-sm rounded-lg p-2 mb-3 ' +
                (data.ok ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                         : 'bg-red-50 text-red-700 border border-red-200');

            if (data.ok) formF.reset();
        } catch {
            msgF.textContent = 'Error de conexión.';
            msgF.className = 'text-sm rounded-lg p-2 mb-3 bg-red-50 text-red-700 border border-red-200';
        } finally{
            btnFSub.disabled = false;
            btnFSub.textContent = 'Enviar solicitud';
        }
    });
});