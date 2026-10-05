document.addEventListener('DOMContentLoaded', function () {
    // Solo ejecutar si el contenedor de notificaciones existe en la página
    if (document.getElementById('notification-bell-container')) {
        loadAdminNotifications();

        // Opcional: Recargar notificaciones cada cierto tiempo (ej. cada 2 minutos)
        setInterval(loadAdminNotifications, 120000);
    }
});

async function loadAdminNotifications() {
    try {
        const response = await fetch(base_url + 'User/getAdminNotifications');
        const result = await response.json();

        if (result.success) {
            updateNotificationsUI(result.count, result.notifications);
        } else {
            // Si la respuesta es falsa (no autorizado), ocultamos el icono
            const container = document.getElementById('notification-bell-container');
            if (container) container.style.display = 'none';
        }

    } catch (error) {
        console.error('Error al cargar notificaciones:', error);
    }
}

function updateNotificationsUI(count, notifications) {
    const countBadge = document.getElementById('notification-count');
    const header = document.querySelector('#notification-panel .dropdown-header');
    const listContainer = document.getElementById('notification-list');
    const noNotificationsMessage = listContainer ? listContainer.querySelector('.text-muted') : null;
    // Verificación de seguridad: si alguno de los elementos no existe, no hacer nada.
    if (!countBadge || !header || !listContainer) {
        return;
    }

    if (count > 0) {
        // Actualizar contador
        countBadge.textContent = count;
        countBadge.style.display = 'inline';
        header.textContent = `${count} Notificacion(es)`;

        // Limpiar lista y ocultar el mensaje de "no hay notificaciones"
        listContainer.innerHTML = '';
        // --- INICIO DE LA MODIFICACIÓN ---
        // Añadir un encabezado único para las solicitudes
        const headerTitle = `<a href="#" class="dropdown-item disabled text-sm"><i class="fas fa-user-lock mr-2"></i> Solicitudes de Recuperación</a>`;
        listContainer.innerHTML += headerTitle;
        listContainer.innerHTML += '<div class="dropdown-divider"></div>';

        notifications.forEach(notif => {
            const notificationItem = `
                <a href="${base_url}user/recuperar" class="dropdown-item">
                    <i class="fas fa-user-circle mr-2 text-info"></i> ${notif.nombre}
                    <span class="float-right text-muted text-sm">${notif.fecha}</span>
                </a>
            `;
            listContainer.innerHTML += notificationItem;
        });
        // --- FIN DE LA MODIFICACIÓN ---
    } else {
        // Ocultar contador y resetear textos
        countBadge.style.display = 'none';
        header.textContent = 'No hay notificaciones';
        // Mostrar mensaje de que no hay notificaciones
        listContainer.innerHTML = '<p class="text-center text-muted p-3">No hay notificaciones nuevas.</p>';
    }
}