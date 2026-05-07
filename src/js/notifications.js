/**
 * Archivo: notifications.js
 * Descripción: Gestiona la carga y visualización de notificaciones del sistema en la barra de navegación.
 *              Centraliza las solicitudes de notificaciones de requisiciones, recuperación de usuarios, etc.
 * Autor: Gemini Code Assist
 * Fecha: [Fecha Actual]
 */

document.addEventListener('DOMContentLoaded', function () {
    // Intentar cargar notificaciones al iniciar la página.
    // No ponemos un IF aquí para permitir que la función se ejecute y verifique la UI internamente.
    loadAllNotifications();

    // Recargar notificaciones automáticamente cada 30 segundos
    setInterval(loadAllNotifications, 30000);

    // Delegación de eventos para los botones de las campanas en la barra de navegación
    $(document).on('click', '#userRecoveryButton, #requisitionNotificationsButton', function () {
        loadAllNotifications();
    });
});

/**
 * Carga todas las notificaciones pendientes desde el controlador principal (HomeController).
 */
async function loadAllNotifications() {
    if (typeof base_url === 'undefined') return;

    try {
        const response = await fetch(base_url + 'Home/getNotifications');
        const result = await response.json();

        // console.log("Respuesta de Notificaciones:", result); // Debug ya verificado

        if (result.success) {
            // Distribuir las notificaciones a la interfaz
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
    // Intentamos buscar por ID específico, y si no existe, buscamos el badge dentro del botón correspondiente
    const $recoveryBadge = $('#userRecoveryCount').length ? $('#userRecoveryCount') : $('#userRecoveryButton .navbar-badge, #userRecoveryButton .badge');
    const $recoveryList = $('#userRecoveryItems');
    const $orderBadge = $('#requisitionNotificationCount').length ? $('#requisitionNotificationCount') : $('#requisitionNotificationsButton .navbar-badge, #requisitionNotificationsButton .badge');
    const $orderList = $('#requisitionNotificationItems');
    const $orderIcon = $('#requisitionNotificationsButton i');

    // Limpiar listas si los contenedores existen
    if ($recoveryList.length) $recoveryList.empty();
    if ($orderList.length) $orderList.empty();

    // Filtrar notificaciones
    const recoveryNotifs = notifications.filter(n => n.tipo_notificacion === 'recuperacion_usuario');
    const orderNotifs = notifications.filter(n => n.tipo_notificacion === 'nueva_requisicion' || n.tipo_notificacion === 'despacho_pendiente');

    // Actualizar UI de Recuperación de Usuario
    if ($recoveryBadge.length) {
        if (recoveryNotifs.length > 0) {
            $recoveryBadge.text(recoveryNotifs.length).removeClass('d-none').css('display', 'inline-block').show();
            recoveryNotifs.forEach(notif => {
                const link = `${base_url}user/recuperar`;
                const itemHTML = `<a href="${link}" class="dropdown-item"><i class="fas fa-user-shield text-info mr-2"></i> ${notif.mensaje}<span class="float-right text-muted text-sm">${notif.fecha_creacion}</span></a><div class="dropdown-divider"></div>`;
                if ($recoveryList.length) $recoveryList.append(itemHTML);
            });
        } else {
            $recoveryBadge.addClass('d-none').hide();
            if ($recoveryList.length) $recoveryList.html('<p class="text-center text-muted p-3">No hay solicitudes nuevas.</p>');
        }
    }

    // Actualizar UI de Requisiciones
    if ($orderBadge.length) {
        if (orderNotifs.length > 0) {
            $orderBadge.text(orderNotifs.length).removeClass('d-none').css('display', 'inline-block').show();
            if ($orderIcon.length) $orderIcon.addClass('icon-pulsate');

            orderNotifs.forEach(notif => {
                let link = '#', icon = 'fa-file-alt text-primary';
                if (notif.tipo_notificacion === 'nueva_requisicion') {
                    link = `${base_url}Requisicion/requisicion/${notif.id_referencia}`;
                    icon = 'fa-file-alt text-primary';
                } else if (notif.tipo_notificacion === 'despacho_pendiente') {
                    link = `${base_url}Orden/despachosPendientes`;
                    icon = 'fa-box-open text-success';
                }

                const itemHTML = `<a href="${link}" class="dropdown-item" style="white-space: normal;"><i class="fas ${icon} mr-2"></i> ${notif.mensaje}<span class="float-right text-muted text-sm">${notif.fecha_creacion}</span></a><div class="dropdown-divider"></div>`;
                if ($orderList.length) $orderList.append(itemHTML);
            });
        } else {
            $orderBadge.addClass('d-none').hide();
            if ($orderIcon.length) $orderIcon.removeClass('icon-pulsate');
            if ($orderList.length) $orderList.html('<p class="text-center text-muted p-3">No hay requisiciones nuevas.</p>');
        }
    }
}