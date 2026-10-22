// Disponible globalmente para todas las vistas
window.SITGO_BASE = window.SITGO_BASE || '/';

document.addEventListener('DOMContentLoaded', () => {

    /* ============================================================
       SIDEBAR · Toggle de grupos (menús desplegables)
       ============================================================ */
    document.querySelectorAll('.sidebar-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const parent = btn.closest('.sidebar-group');
            if (!parent) return;

            const submenu = parent.querySelector('.sidebar-submenu');
            if (!submenu) return;

            const isOpen = parent.classList.contains('open');

            if (isOpen) {
                submenu.style.maxHeight = submenu.scrollHeight + 'px';
                requestAnimationFrame(() => {
                    submenu.style.maxHeight = '0px';
                });
                parent.classList.remove('open');
            } else {
                parent.classList.add('open');
                submenu.style.maxHeight = 'none';
                const alto = submenu.scrollHeight;
                submenu.style.maxHeight = '0px';
                void submenu.offsetHeight;
                requestAnimationFrame(() => {
                    submenu.style.maxHeight = alto + 'px';
                });
            }
        });
    });

    /* ============================================================
       SIDEBAR · Marcar activo y abrir grupo según URL actual
       Estrategia: encontrar la coincidencia MÁS LARGA y marcar solo esa
       ============================================================ */
    activarMenuPorUrl();

    function activarMenuPorUrl() {
        const pathActual = normalizarPath(window.location.pathname);
        if (!pathActual) return;

        // 1. Recopilar TODOS los links candidatos (submenús + links directos)
        const candidatos = [];

        document.querySelectorAll('.sidebar-submenu .sidebar-link, .sidebar-sublink').forEach(link => {
            const href = link.getAttribute('href') || '';
            const pathLink = normalizarPath(href);
            if (!pathLink) return;
            candidatos.push({
                el: link,
                pathLink,
                tipo: 'sublink',
                group: link.closest('.sidebar-group'),
            });
        });

        document.querySelectorAll('.sidebar-nav > .sidebar-menu > li > .sidebar-link:not(.sidebar-toggle)').forEach(link => {
            const href = link.getAttribute('href') || '';
            const pathLink = normalizarPath(href);
            if (!pathLink) return;
            candidatos.push({
                el: link,
                pathLink,
                tipo: 'directo',
                group: null,
            });
        });

        if (!candidatos.length) return;

        // 2. Filtrar candidatos que coincidan y quedarnos con el de path MÁS LARGO
        let mejor = null;
        candidatos.forEach(c => {
            if (!coincideRuta(pathActual, c.pathLink)) return;
            if (!mejor || c.pathLink.length > mejor.pathLink.length) {
                mejor = c;
            }
        });

        // 3. Marcar solo el mejor (o ninguno si no hay match)
        if (mejor) {
            mejor.el.classList.add('active');

            // Si es sublink, abrir el grupo padre
            if (mejor.group) {
                mejor.group.classList.add('open');
                const submenu = mejor.group.querySelector('.sidebar-submenu');
                if (submenu) {
                    submenu.style.maxHeight = 'none';
                    const alto = submenu.scrollHeight;
                    submenu.style.maxHeight = alto + 'px';
                }
            }
        } else {
            // Si no hay coincidencia exacta, igual abrimos el grupo cuyo prefijo
            // coincida con la URL actual (para que se vea desplegado)
            const gruposPosibles = new Map();

            candidatos.forEach(c => {
                if (!c.group) return;
                if (pathActual === c.pathLink || pathActual.startsWith(c.pathLink + '/')) {
                    // Solo considera el grupo, sin marcar sublink
                    if (!gruposPosibles.has(c.group)) {
                        gruposPosibles.set(c.group, c.pathLink);
                    } else {
                        const prev = gruposPosibles.get(c.group);
                        if (c.pathLink.length < prev.length) {
                            gruposPosibles.set(c.group, c.pathLink);
                        }
                    }
                }
            });

            gruposPosibles.forEach((pathLink, group) => {
                group.classList.add('open');
                const submenu = group.querySelector('.sidebar-submenu');
                if (submenu) {
                    submenu.style.maxHeight = 'none';
                    const alto = submenu.scrollHeight;
                    submenu.style.maxHeight = alto + 'px';
                }
            });
        }
    }

    /* ------------------------------------------------------------
       Compara la URL actual con la de un link.
       ------------------------------------------------------------ */
    function coincideRuta(pathActual, pathLink) {
        if (!pathLink) return false;
        if (pathActual === pathLink) return true;
        if (pathActual.startsWith(pathLink + '/')) return true;
        return false;
    }

    /* ------------------------------------------------------------
       Normaliza una ruta: quita BASE_URL del principio,
       quita query/hash, quita barras iniciales y finales.
       ------------------------------------------------------------ */
    function normalizarPath(raw) {
        if (!raw) return '';

        let p = raw;

        try {
            const base = document.createElement('a');
            base.href = raw;
            p = base.pathname || raw;
        } catch (_) {}

        const baseUrl = (window.SITGO_BASE || '/');
        try {
            const baseUrlObj = new URL(baseUrl, window.location.origin);
            const basePath = baseUrlObj.pathname.replace(/\/+$/, '');
            if (basePath && basePath !== '/' && p.startsWith(basePath)) {
                p = p.substring(basePath.length);
            }
        } catch (_) {}

        p = p.replace(/^\/+/, '').replace(/\/+$/, '');
        p = p.split('?')[0].split('#')[0];

        return p;
    }

    /* ============================================================
       SIDEBAR · Colapsar / expandir
       ============================================================ */
    const toggleSidebar = document.getElementById('toggleSidebar');

    if (toggleSidebar) {
        if (window.innerWidth > 767 && sessionStorage.getItem('sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
        }

        toggleSidebar.addEventListener('click', () => {
            if (window.innerWidth <= 767) {
                document.body.classList.toggle('sidebar-open');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
                const collapsed = document.body.classList.contains('sidebar-collapsed');
                sessionStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
            }
        });
    }

    const overlay = document.getElementById('sidebarOverlay');
    if (overlay) {
        overlay.addEventListener('click', () => {
            document.body.classList.remove('sidebar-open');
        });
    }

    /* ============================================================
       NAVBAR · Dropdowns (notificaciones, usuario)
       ============================================================ */
    const dropdowns = [
        { btn: 'btnNotifications', panel: 'dropdownNotifications' },
        { btn: 'btnUser',          panel: 'dropdownUser' },
    ];

    function cerrarTodosLosDropdowns(excepto = null) {
        dropdowns.forEach(d => {
            const p = document.getElementById(d.panel);
            if (p && p !== excepto) p.classList.remove('open');
        });
    }

    dropdowns.forEach(d => {
        const btn   = document.getElementById(d.btn);
        const panel = document.getElementById(d.panel);
        if (!btn || !panel) return;

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const estabaAbierto = panel.classList.contains('open');
            cerrarTodosLosDropdowns();
            if (!estabaAbierto) panel.classList.add('open');
        });

        panel.addEventListener('click', (e) => e.stopPropagation());
    });

    document.addEventListener('click', () => cerrarTodosLosDropdowns());
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') cerrarTodosLosDropdowns();
    });

    /* ============================================================
       NAVBAR DASHBOARD · Scroll suave + botón activo
       ============================================================ */
    const dashNav = document.getElementById('navbarDashNav');

    if (dashNav) {
        const links = dashNav.querySelectorAll('.navbar-dash-link');

        links.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.dataset.dashTarget;
                const target   = document.getElementById(targetId);
                if (!target) return;

                const navHeight = document.querySelector('.navbar')?.offsetHeight || 64;
                const extraPad  = 16;
                const y = target.getBoundingClientRect().top + window.pageYOffset - navHeight - extraPad;

                window.scrollTo({ top: y, behavior: 'smooth' });

                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            });
        });

        const secciones = ['sec-flota','sec-almacen','sec-compras','sec-bienes','sec-estacion','sec-sesiones']
            .map(id => document.getElementById(id))
            .filter(Boolean);

        if (secciones.length && 'IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        links.forEach(l => {
                            l.classList.toggle('active', l.dataset.dashTarget === id);
                        });
                    }
                });
            }, {
                rootMargin: '-40% 0px -55% 0px',
                threshold: 0,
            });

            secciones.forEach(sec => observer.observe(sec));
        }
    }

    /* ============================================================
       Resize · recalcular max-height de grupos abiertos
       ============================================================ */
    window.addEventListener('resize', () => {
        document.querySelectorAll('.sidebar-group.open > .sidebar-submenu').forEach(sub => {
            sub.style.maxHeight = 'none';
            const h = sub.scrollHeight;
            sub.style.maxHeight = h + 'px';
        });
    });
});