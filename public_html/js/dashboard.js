document.addEventListener('DOMContentLoaded', () => {

    const app = document.getElementById('app');
    const body = document.body;
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('toggleSidebar');
    const STORAGE_KEY = 'sitgo_sidebar_collapsed';

    const isMobile = () => window.innerWidth < 768;

    /* ============================================
       TOGGLE ÚNICO: comportamiento según pantalla
       ============================================ */
    toggleBtn?.addEventListener('click', () => {
        if (isMobile()) {
            // Móvil: abrir/cerrar drawer
            body.classList.toggle('sidebar-open');
        } else {
            // Desktop: colapsar/expandir
            app.classList.toggle('sidebar-collapsed');
            localStorage.setItem(
                STORAGE_KEY,
                app.classList.contains('sidebar-collapsed') ? '1' : '0'
            );
            updateToggleIcon();
        }
    });

    // Cerrar al hacer clic en overlay (móvil)
    overlay?.addEventListener('click', () => {
        body.classList.remove('sidebar-open');
    });

    // Cerrar al navegar (móvil)
    document.querySelectorAll('#sidebar .sidebar-link').forEach(link => {
        link.addEventListener('click', () => {
            if (isMobile() && link.tagName === 'A') {
                body.classList.remove('sidebar-open');
            }
        });
    });

    /* ============================================
       Restaurar estado colapsado (solo desktop)
       ============================================ */
    if (!isMobile() && localStorage.getItem(STORAGE_KEY) === '1') {
        app.classList.add('sidebar-collapsed');
    }
    updateToggleIcon();

    function updateToggleIcon() {
        if (!toggleBtn) return;
        const collapsed = app.classList.contains('sidebar-collapsed');
        const name = collapsed ? 'panel-left-open' : 'panel-left-close';
        const span = toggleBtn.querySelector('.icon-desktop');
        if (span) {
            span.innerHTML = `<i data-lucide="${name}" class="w-5 h-5"></i>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }
    }

    /* ============================================
       SUBMENÚS acordeón
       ============================================ */
    document.querySelectorAll('.sidebar-group').forEach(group => {
        const btn = group.querySelector('.sidebar-toggle');
        const submenu = group.querySelector('.sidebar-submenu');
        if (!btn || !submenu) return;

        btn.addEventListener('click', (e) => {
            e.preventDefault();

            // Si está colapsado en desktop, no abrir submenús
            if (!isMobile() && app.classList.contains('sidebar-collapsed')) return;

            const open = group.classList.toggle('open');
            submenu.style.maxHeight = open ? submenu.scrollHeight + 'px' : '0';
        });

        // Auto-abrir si contiene el link activo
        const currentPath = window.location.pathname;
        const activeLink = submenu.querySelector(`a[href*="${currentPath}"]`);
        if (activeLink) {
            group.classList.add('open');
            submenu.style.maxHeight = submenu.scrollHeight + 'px';
        }
    });

    /* ============================================
       Marcar link activo
       ============================================ */
    const currentPath = window.location.pathname;
    document.querySelectorAll('#sidebar a.sidebar-link').forEach(link => {
        try {
            const href = new URL(link.href).pathname;
            if (href === currentPath ||
                (href !== '/' && currentPath.startsWith(href) && href.length > 1)) {
                link.classList.add('active');
            }
        } catch {}
    });

    /* ============================================
       DROPDOWNS navbar
       ============================================ */
    const btnN = document.getElementById('btnNotifications');
    const dropN = document.getElementById('dropdownNotifications');
    const btnU = document.getElementById('btnUser');
    const dropU = document.getElementById('dropdownUser');

    function closeAll() {
        dropN?.classList.remove('open');
        dropU?.classList.remove('open');
    }

    btnN?.addEventListener('click', (e) => {
        e.stopPropagation();
        const wasOpen = dropN.classList.contains('open');
        closeAll();
        if (!wasOpen) dropN.classList.add('open');
    });

    btnU?.addEventListener('click', (e) => {
        e.stopPropagation();
        const wasOpen = dropU.classList.contains('open');
        closeAll();
        if (!wasOpen) dropU.classList.add('open');
    });

    document.addEventListener('click', closeAll);
    document.querySelectorAll('.dropdown-panel').forEach(p => {
        p.addEventListener('click', e => e.stopPropagation());
    });

    /* ============================================
       Buscador (placeholder)
       ============================================ */
    document.getElementById('globalSearch')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            console.log('Buscar:', e.target.value);
        }
    });

    /* ============================================
       Al cambiar tamaño: limpiar estados móviles
       ============================================ */
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (!isMobile()) {
                body.classList.remove('sidebar-open');
            }
        }, 150);
    });

    /* ============================================
       Contador de recuperaciones (navbar)
       ============================================ */
    (function initContadorRecuperar(){
        const item        = document.getElementById('notifItemRecuperar');
        const badge       = document.getElementById('notifBadgeRecuperar');
        const badgeGlobal = document.getElementById('notifBadgeGlobal');
        const btnBell     = document.getElementById('btnNotifications');

        if (badge)       badge.style.display = 'none';
        if (badgeGlobal) badgeGlobal.style.display = 'none';
        if (item)        item.style.display = 'none';
        if (btnBell)     btnBell.classList.remove('has-notif');

        // Si no hay item de recuperación, el usuario no es de Sistema
        if (!item) {
            // Exponer una función no-op para evitar errores desde otros módulos
            window.sitgoUpdateContador = () => {};
            return;
        }

        const update = async () => {
            try{
                const r = await fetch((window.SITGO_BASE || '/') + 'recuperar/contador', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const d = await r.json();
                const count = d.ok ? Number(d.count) || 0 : 0;

                if (count > 0){
                    const txt = count > 9 ? '9+' : String(count);
                    if (badge){
                        badge.textContent = txt;
                        badge.style.display = 'flex';
                    }
                    if (badgeGlobal){
                        badgeGlobal.textContent = txt;
                        badgeGlobal.style.display = 'flex';
                    }
                    item.style.display = '';
                    if (btnBell) btnBell.classList.add('has-notif');
                } else {
                    if (badge)       badge.style.display = 'none';
                    if (badgeGlobal) badgeGlobal.style.display = 'none';
                    item.style.display = 'none';
                    if (btnBell) btnBell.classList.remove('has-notif');
                }
            } catch (e){ /* silencioso */ }
        };

        // 👇 Exponer globalmente para que recuperar.js la invoque
        window.sitgoUpdateContador = update;

        update();
        setInterval(update, 20000);
    })();
});