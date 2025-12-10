/**
 * Archivo: notifications.js
 * Descripción: Gestiona la carga y visualización de notificaciones del sistema en la barra de navegación.
 *              Centraliza las solicitudes de notificaciones de requisiciones, recuperación de usuarios, etc.
 * Autor: Gemini Code Assist
 * Fecha: [Fecha Actual]
 */

document.addEventListener('DOMContentLoaded', function () {
    // Solo ejecutar si existe al menos uno de los contenedores de notificaciones
    if ($('#userRecoveryNotificationsContainer').length || $('#orderNotificationsContainer').length) {
        loadAllNotifications(); // Cargar al inicio

        // Opcional: Recargar notificaciones cada 2 minutos
        setInterval(loadAllNotifications, 30000);

        // Forzar recarga al hacer clic en cualquiera de las campanas
        $('#userRecoveryButton, #requisitionNotificationsButton').on('click', function () {
            loadAllNotifications();
        });
    }
});

/**
 * Carga todas las notificaciones pendientes desde el controlador principal (HomeController).
 */
async function loadAllNotifications() {
    try {
        const response = await fetch(base_url + 'Home/getNotifications');
        const result = await response.json();

        if (result.success) {
            // Ahora esta función distribuirá las notificaciones
            distributeNotificationsUI(result.notifications);
        }
    } catch (error) {
        console.error('Error al cargar las notificaciones:', error);
    }
}

/**
 * Distribuye las notificaciones a sus respectivas campanas en la UI.
 * @param {Array} notifications - La lista de notificaciones para mostrar.
 */
function distributeNotificationsUI(notifications) {
    // Contenedores y contadores específicos
    const recoveryCountBadge = $('#userRecoveryCount');
    const recoveryListContainer = $('#userRecoveryItems');
    const requisitionCountBadge = $('#requisitionNotificationCount');
    const requisitionListContainer = $('#requisitionNotificationItems');
    const requisitionIcon = $('#requisitionNotificationsButton i'); // Seleccionar el icono

    // Limpiar listas
    recoveryListContainer.empty();
    requisitionListContainer.empty();

    // Filtrar notificaciones por tipo
    const recoveryNotifs = notifications.filter(n => n.tipo_notificacion === 'recuperacion_usuario');
    // Unificamos requisiciones y despachos pendientes en la misma campana de "órdenes"
    const orderNotifs = notifications.filter(n => n.tipo_notificacion === 'nueva_requisicion' || n.tipo_notificacion === 'despacho_pendiente');

    // Actualizar UI de Recuperación de Usuario
    if (recoveryNotifs.length > 0) {
        // (La lógica de recuperación de usuario no cambia)
        recoveryCountBadge.text(recoveryNotifs.length).show();
        recoveryNotifs.forEach(notif => {
            const link = `${base_url}user/recuperar`;
            const icon = 'fa-user-shield text-info';
            const itemHTML = `<a href="${link}" class="dropdown-item"><i class="fas ${icon} mr-2"></i> ${notif.mensaje}<span class="float-right text-muted text-sm">${notif.fecha_creacion}</span></a><div class="dropdown-divider"></div>`;
            recoveryListContainer.append(itemHTML);
        });
    } else {
        recoveryCountBadge.hide();
        recoveryListContainer.html('<p class="text-center text-muted p-3">No hay solicitudes nuevas.</p>');
    }

    // Actualizar UI de Requisiciones
    if (orderNotifs.length > 0) {
        requisitionCountBadge.text(orderNotifs.length).show();
        requisitionIcon.addClass('icon-pulsate'); // Añadir animación si hay notificaciones
        orderNotifs.forEach(notif => {
            let link = '#';
            let icon = 'fa-file-alt text-primary'; // Icono por defecto

            if (notif.tipo_notificacion === 'nueva_requisicion') {
                link = `${base_url}Requisicion/requisicion/${notif.id_referencia}`;
                icon = 'fa-file-alt text-primary'; // Icono para requisición
            } else if (notif.tipo_notificacion === 'despacho_pendiente') {
                link = `${base_url}Orden/despachosPendientes`; // Apunta a la nueva página de despachos pendientes
                icon = 'fa-box-open text-success'; // Icono para despacho
            }

            const itemHTML = `<a href="${link}" class="dropdown-item" style="white-space: normal;"><i class="fas ${icon} mr-2"></i> ${notif.mensaje}<span class="float-right text-muted text-sm">${notif.fecha_creacion}</span></a><div class="dropdown-divider"></div>`;
            requisitionListContainer.append(itemHTML);
        });
    } else {
        requisitionCountBadge.hide();
        requisitionIcon.removeClass('icon-pulsate'); // Quitar animación si no hay notificaciones
        requisitionListContainer.html('<p class="text-center text-muted p-3">No hay requisiciones nuevas.</p>');
    }
}