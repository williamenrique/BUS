document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'recuperar';
    const $ = id => document.getElementById(id);
    const grid = $('gridSolicitudes');
    const vacio = $('estadoVacio');

    /* ============================================
       INIT
       ============================================ */
    cargar();
    $('btnRefrescar')?.addEventListener('click', () => {
        const icon = $('btnRefrescar').querySelector('svg');
        if (icon) icon.style.animation = 'spin 1s linear infinite';
        cargar().finally(() => {
            if (icon) icon.style.animation = '';
        });
    });

    /* ============================================
       CARGAR SOLICITUDES
       ============================================ */
    async function cargar(){
        try{
            const r = await fetch(API + '/listar', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.message);

            render(d.data);
        } catch (e){
            console.error(e);
            toast('Error al cargar: ' + e.message, 'error');
        }
    }

    /* ============================================
       RENDER
       ============================================ */
    function render(lista){
        if (!lista || lista.length === 0){
            grid.innerHTML = '';
            vacio.style.display = '';
            return;
        }
        vacio.style.display = 'none';
        grid.innerHTML = lista.map(renderCard).join('');
        if (window.lucide) lucide.createIcons();
    }

    function renderCard(s){
        const nombre = `${s.personal_nombre || ''} ${s.personal_apellido || ''}`.trim() || '—';
        const inicial = (s.usuario_nick || '?').charAt(0).toUpperCase();
        const fecha = formatFecha(s.request_date);

        const avatarUrl = s.usuario_imagen
            ? (window.SITGO_BASE || '/') + 'assets/uploads/storage/' + esc(s.usuario_imagen)
            : null;

        const avatarHtml = avatarUrl
            ? `<img src="${avatarUrl}" alt="Avatar" class="recuperar-avatar"
                    onerror="this.outerHTML='<div class=\\'recuperar-avatar-fallback\\'>${inicial}</div>'">`
            : `<div class="recuperar-avatar-fallback">${inicial}</div>`;

        return `
            <div class="recuperar-card" data-id="${s.id}">
                <div class="recuperar-card-header">
                    ${avatarHtml}
                    <div class="recuperar-card-title">
                        <h4>${esc(nombre)}</h4>
                        <span class="recuperar-nick">${esc(s.usuario_nick)}</span>
                        <span class="recuperar-depto">${esc(s.departamento_nombre || 'Sin departamento')}</span>
                    </div>
                </div>

                <div class="recuperar-info">
                    <div class="recuperar-info-row">
                        <?= '' ?>
                        <span class="recuperar-info-icon">
                            <i data-lucide="id-card" class="w-4 h-4"></i>
                        </span>
                        <span class="recuperar-info-value">${esc(s.personal_cedula || '—')}</span>
                    </div>
                    <div class="recuperar-info-row">
                        <span class="recuperar-info-icon">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                        </span>
                        <span class="recuperar-info-value">${esc(s.personal_tlf || '—')}</span>
                    </div>
                    <div class="recuperar-info-row">
                        <span class="recuperar-info-icon">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </span>
                        <span class="recuperar-info-value">${esc(s.personal_email || '—')}</span>
                    </div>
                    <div class="recuperar-info-row">
                        <span class="recuperar-info-icon">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </span>
                        <span class="recuperar-info-value">${esc(fecha)}</span>
                    </div>
                </div>

                <div class="recuperar-actions">
                    <button class="btn btn-primary btn-sm" data-action="reset" data-id="${s.id}">
                        <i data-lucide="key-round" class="w-4 h-4"></i>
                        <span>Resetear a 123456</span>
                    </button>
                    <button class="btn btn-ghost btn-sm" data-action="resolver" data-id="${s.id}">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Marcar como listo</span>
                    </button>
                </div>
            </div>`;
    }

    /* ============================================
       ACCIONES (delegación)
       ============================================ */
    grid?.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const { action, id } = btn.dataset;

        if (action === 'reset'){
            const ok = await sitgoConfirm({
                title: 'Restablecer contraseña',
                message: '¿Restablecer la contraseña a "123456"? El usuario deberá cambiarla al iniciar sesión.',
                okText: 'Sí, restablecer',
                cancelText: 'Cancelar',
                type: 'warning',
                icon: 'key-round',
            });
            if (!ok) return;
            await accion(`/resetear/${id}`, 'Contraseña restablecida.');
        }

        if (action === 'resolver'){
            const ok = await sitgoConfirm({
                title: 'Marcar como atendida',
                message: '¿Marcar esta solicitud como atendida?',
                okText: 'Sí, marcar',
                cancelText: 'Cancelar',
                type: 'success',
                icon: 'check-circle',
            });
            if (!ok) return;
            await accion(`/resolver/${id}`, 'Solicitud marcada como atendida.');
        }
    });

    async function accion(url, okMsg){
        try{
            const r = await fetch(API + url, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const d = await r.json();
            if (!d.ok) throw new Error(d.message);
            toast(d.message || okMsg, 'success');

            // Recargar la lista de tarjetas
            await cargar();

            // 👇 Actualizar el badge del navbar al instante
            if (typeof window.sitgoUpdateContador === 'function'){
                window.sitgoUpdateContador();
            }
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================
       HELPERS
       ============================================ */
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

    function actualizarContadorNavbar(){
        const badge = document.getElementById('notifBadgeRecuperar');
        const item  = document.getElementById('notifItemRecuperar');
        if (!badge || !item) return;

        fetch((window.SITGO_BASE || '/') + 'recuperar/contador', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok && d.count > 0){
                badge.textContent = d.count > 9 ? '9+' : d.count;
                badge.style.display = 'flex';
                item.style.display = '';
            } else {
                badge.style.display = 'none';
                item.style.display = 'none';
            }
        })
        .catch(() => {});
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