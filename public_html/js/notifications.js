document.addEventListener('DOMContentLoaded', () => {
    const BASE = window.SITGO_BASE || '/';
    const API  = BASE + 'recuperar';

    const $ = id => document.getElementById(id);

    const badgeGlobal    = $('notifBadgeGlobal');
    const itemRecuperar  = $('notifItemRecuperar');
    const badgeRecuperar = $('notifBadgeRecuperar');
    const btnNotif       = $('btnNotifications');

    /* ============================================================
       FETCH
       ============================================================ */
    async function fetchJSON(url) {
        try {
            const r = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) return { ok: false };
            return await r.json();
        } catch {
            return { ok: false };
        }
    }

    /* ============================================================
       ACTUALIZAR BADGES
       ============================================================ */
    let ultimoTotal = 0;

    async function actualizarNotificaciones() {
        const d = await fetchJSON(API + '/contador');

        if (!d || !d.ok) {
            // Si el endpoint falla, ocultar badges y salir
            if (badgeGlobal)    badgeGlobal.style.display = 'none';
            if (itemRecuperar)  itemRecuperar.style.display = 'none';
            return;
        }

        const total = parseInt(d.total ?? d.count ?? d.pendientes ?? 0, 10);

        // ---- Badge global (campana) ----
        if (badgeGlobal) {
            if (total > 0) {
                badgeGlobal.textContent = total > 99 ? '99+' : String(total);
                badgeGlobal.style.display = 'flex';
            } else {
                badgeGlobal.style.display = 'none';
            }
        }

        // ---- Item "Solicitudes de recuperación" ----
        // Solo se muestra si el usuario es SISTEMA (el elemento ya existe solo para ellos)
        if (itemRecuperar && badgeRecuperar) {
            if (total > 0) {
                itemRecuperar.style.display = 'flex';
                badgeRecuperar.textContent = total > 99 ? '99+' : String(total);
                badgeRecuperar.style.display = 'flex';
            } else {
                itemRecuperar.style.display = 'none';
                badgeRecuperar.style.display = 'none';
            }
        }

        // ---- Ring de la campana si hay nuevas ----
        if (btnNotif && total > ultimoTotal) {
            btnNotif.classList.add('has-notif');
            setTimeout(() => btnNotif.classList.remove('has-notif'), 3000);
        }
        ultimoTotal = total;
    }

    /* ============================================================
       INIT + AUTO-REFRESH
       ============================================================ */
    // Solo ejecutar si hay campana en el DOM
    if (btnNotif || badgeGlobal) {
        actualizarNotificaciones();
        setInterval(actualizarNotificaciones, 30000); // cada 30s

        // Refrescar también al abrir el dropdown de notificaciones
        btnNotif?.addEventListener('click', () => {
            setTimeout(actualizarNotificaciones, 100);
        });
    }
});