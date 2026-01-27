let dailyChart = null;
let monthlyChart = null;

// Espera a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {

    // Verificar si el selector de estación existe en la página
    const selectEstacion = document.querySelector('#selectEstacion');

    if (selectEstacion) {
        selectEstacion.addEventListener('change', function () {
            const stationId = this.value;

            if (!stationId || stationId === "") {
                return; // No hacer nada si se selecciona la opción por defecto
            }

            // Mostrar una alerta de carga
            Swal.fire({
                title: 'Actualizando Estación',
                text: 'Por favor, espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            // La variable `userId` debe estar definida globalmente en tu vista (home.php)
            // junto con `userDepartment` y `userRole`.
            formData.append('idEstacion', stationId);
            formData.append('idUsuario', userId);

            // Petición AJAX para actualizar la estación, apuntando al controlador correcto
            fetch(base_url + 'home/setStation', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Actualizado!',
                            text: data.msg,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            // Redireccionar a la página de registro de estación
                            window.location.href = base_url + 'estacion/registrar';
                        });
                    } else {
                        Swal.fire('Error', data.msg, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Ocurrió un problema de conexión.', 'error');
                });
        });
    }

    // --- LÓGICA PARA EL AVISO DE SELECCIÓN DE ESTACIÓN ---
    // Las variables `userDepartment` y `userEstacionId` se definen globalmente en footer.php.
    // Si el usuario es de Estación y no tiene una asignada (ID 0), se muestra la alerta.
    if (userDepartment === 'ESTACION') {
        Swal.fire({
            title: 'Seleccione su Estación',
            text: 'Para continuar, por favor elija la estación en la que está trabajando.',
            icon: 'question',
            showConfirmButton: true,
            confirmButtonText: 'E/S Táchira',
            showDenyButton: true,
            denyButtonText: 'E/S Gran Parada',
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            let stationId = null;
            if (result.isConfirmed) {
                stationId = 1; // ID para E/S Táchira
            } else if (result.isDenied) {
                stationId = 2; // ID para E/S Gran Parada
            }

            if (stationId) {
                // Muestra la alerta de carga
                Swal.fire({
                    title: 'Configurando Estación',
                    text: 'Por favor, espere...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                const formData = new FormData();
                formData.append('idEstacion', stationId);
                formData.append('idUsuario', userId);

                // Petición AJAX para guardar la estación
                fetch(base_url + 'home/setStation', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            // Si es exitoso, redirecciona a la página de registro
                            window.location.href = base_url + 'estacion/registrar';
                        } else {
                            Swal.fire('Error', data.msg, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Ocurrió un problema de conexión.', 'error');
                    });
            }
        });
    }
    // --- FIN DE LA LÓGICA DE AVISO ---

});

document.addEventListener('DOMContentLoaded', function () {
    // La variable `userDepartment` se define en home.php
    // y nos dice qué dashboard se está mostrando. La variable `userRole` nos da el rol.
    if (typeof userDepartment === 'undefined' || typeof userRole === 'undefined') {
        return; // Salir si las variables no están definidas
    }

    // --- INICIO DE LA CORRECCIÓN ---
    // Lógica unificada para cargar los datos del dashboard correspondiente.
    // El administrador puede ver varios dashboards, así que comprobamos cada uno.

    if (userDepartment === 'ALMACEN' || userRole === 'ADMINISTRADOR') {
        if (document.querySelector('#dashboard-almacen')) loadAlmacenData();
    }
    if (userDepartment === 'OPERACIONES' || userRole === 'ADMINISTRADOR') {
        if (document.querySelector('#dashboard-operaciones')) loadOperacionesData();
    }
    if (userDepartment === 'ESTACION' || userRole === 'ADMINISTRADOR') {
        if (document.querySelector('#dashboard-estacion')) {
            loadEstacionData();
            populateMonthSelects();
            loadDailySales();
        }
    }
    if (userDepartment === 'BIENES' || userRole === 'ADMINISTRADOR') {
        if (document.querySelector('#dashboard-bienes')) loadBienesData();
    }
    if (userDepartment === 'COMPRAS' || userRole === 'ADMINISTRADOR') {
        if (document.querySelector('#dashboard-compras')) loadComprasData();
    }

    // Funciones que solo se ejecutan para el rol de Administrador
    if (userRole === 'ADMINISTRADOR') {
        setupActiveUsersSection();
        setupInactiveUsersSection();
    }
    // --- FIN DE LA CORRECCIÓN ---
});



/**
 * Carga los datos para el dashboard de Almacén.
 */
function loadAlmacenData() {
    fetch(base_url + 'Home/getAlmacenData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const almacen = data.data;
                document.querySelector('#lubricantes-actual').textContent = `${parseFloat(almacen.consumibles.mes_actual || 0).toFixed(2)} Lts.`;
                // Selectores para las nuevas tarjetas de órdenes
                document.querySelector('#ordenes-aprobadas').textContent = almacen.orders_aprobadas.total_aprobadas || 0;
                document.querySelector('#ordenes-despachadas').textContent = `${almacen.orders_despachadas.total_despachadas || 0} Ord.`;

                const topProductElem = document.querySelector('#top-producto-mes');
                if (topProductElem && almacen.top_product) {
                    topProductElem.textContent = `${almacen.top_product.producto} (${almacen.top_product.total_despachado} Und.)`;
                } else if (topProductElem) {
                    topProductElem.textContent = 'N/A';
                }
            }
        })
        .catch(error => console.error('Error cargando datos de almacén:', error));
}

/**
 * Carga los datos para el dashboard de Operaciones.
 */
function loadOperacionesData() {
    fetch(base_url + 'Home/getOperacionesData')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const operaciones = data.data;
                // Cards de estado
                document.querySelector('#unidades-operativas').textContent = operaciones.status.operativas || 0;
                document.querySelector('#unidades-inoperativas').textContent = operaciones.status.inoperativas || 0;
                document.querySelector('#unidades-mantenimiento').textContent = operaciones.status.mantenimiento || 0;
                document.querySelector('#unidades-criticas').textContent = operaciones.status.criticas || 0;

                // Cards de estado de aceite
                if (operaciones.aceite_status) {
                    document.querySelector('#aceite-requerido').textContent = operaciones.aceite_status.requerido || 0;
                    document.querySelector('#aceite-proximo').textContent = operaciones.aceite_status.proximo || 0;
                    document.querySelector('#aceite-ok').textContent = operaciones.aceite_status.ok || 0;
                }
                // Tabla de resumen
                const tablaBody = document.querySelector('#tabla-resumen-flota');

                // Inyectar botón para ir a Flota en el header de la tarjeta
                if (tablaBody) {
                    const card = tablaBody.closest('.card');
                    if (card) {
                        const cardHeader = card.querySelector('.card-header');
                        if (cardHeader && !cardHeader.querySelector('#btnLinkFlota')) {
                            const linkHtml = `<div class="card-tools">
                                                <a href="${base_url}flota" class="btn btn-primary btn-xs" id="btnLinkFlota" title="Ir a Gestión de Flota">
                                                    <i class="fas fa-bus"></i> Ir a Flota
                                                </a>
                                              </div>`;
                            cardHeader.insertAdjacentHTML('beforeend', linkHtml);
                        }
                    }
                }

                let html = '';
                if (operaciones.grouped.length > 0) {
                    operaciones.grouped.forEach(item => {
                        html += `
                            <tr>
                                <td>${item.marca_unidad} / ${item.modelo_unidad}</td>
                                <td>${item.transmision}</td>
                                <td>${item.tipo_combustible}</td>
                                <td class="text-center">${item.total}</td>
                                <td class="text-center font-weight-bold text-success">${item.operativas || 0}</td>
                                <td class="text-center font-weight-bold text-danger">${item.inoperativas || 0}</td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center py-4">No hay datos de flota para mostrar.</td></tr>';
                }
                tablaBody.innerHTML = html;
            }
        })
        .catch(error => console.error('Error cargando datos de operaciones:', error));
}

/**
 * Carga los datos para el dashboard de Estación.
 * Aquí puedes mover la lógica que tenías en `function.estacion.js`.
 */
async function loadEstacionData() {
    try {
        const response = await fetch(base_url + 'Home/getEstacionDashboardData');
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            const totalVentasElem = document.getElementById('totalVentasHoy');
            const totalLitrosElem = document.getElementById('totalLitrosHoy');
            const totalUser = document.getElementById('user_activo');

            if (totalVentasElem) totalVentasElem.textContent = data.total_ventas || 0;
            if (totalLitrosElem) totalLitrosElem.textContent = `${parseFloat(data.total_litros || 0).toFixed(2)} Lts`;
            if (totalUser) totalUser.textContent = `${parseFloat(data.user_activo || 0)}`;
        }
    } catch (error) {
        console.error('Error cargando datos de estación:', error);
    }
}

/**
 * Carga y muestra los datos para el dashboard de Compras.
 */
async function loadComprasData() {
    try {
        const response = await fetch(base_url + 'Home/getComprasData');
        const result = await response.json();

        if (result.success) {
            const data = result.data;
            // Actualizar las tarjetas con los datos recibidos
            document.getElementById('nuevas-requisiciones').textContent = data.requisiciones_pendientes || 0;
            document.getElementById('articulos-sin-stock').textContent = data.articulos_sin_stock || 0;
        } else {
            console.error('Error al cargar datos de Compras:', result.message);
        }
    } catch (error) {
        console.error('Error de conexión al cargar datos de Compras:', error);
    }
}

/**
 * Carga los datos para el dashboard de Bienes.
 */
async function loadBienesData() {
    try {
        const response = await fetch(base_url + 'Home/getBienesDashboardData');
        const result = await response.json();

        if (result.success && result.data) {
            const { summary, recent } = result.data;

            // Actualizar tarjetas
            document.getElementById('bienes-total').textContent = summary.total_bienes || 0;
            document.getElementById('bienes-activos').textContent = summary.total_activos || 0;
            document.getElementById('bienes-reparacion').textContent = summary.total_reparacion || 0;
            document.getElementById('bienes-baja').textContent = summary.total_baja || 0;

            // Actualizar tabla de bienes recientes
            const tablaBody = document.getElementById('tabla-bienes-recientes');
            tablaBody.innerHTML = ''; // Limpiar tabla

            if (recent.length > 0) {
                recent.forEach(item => {
                    const row = `
                        <tr class="bg-white dark:bg-gray-800 border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">${item.descripcion_bien}</td>
                            <td class="px-6 py-4">${item.departamento_bien}</td>
                            <td class="px-6 py-4">${item.fecha_adquisicion}</td>
                        </tr>
                    `;
                    tablaBody.innerHTML += row;
                });
            } else {
                tablaBody.innerHTML = '<tr><td colspan="3" class="text-center py-4">No hay bienes registrados recientemente.</td></tr>';
            }
        } else {
            console.error('Error en la respuesta del servidor para Bienes:', result.message);
        }
    } catch (error) {
        console.error('Error cargando datos de bienes:', error);
    }
}

/**
 * Configura la sección de usuarios activos, incluyendo el toggle y la carga de datos.
 */
function setupActiveUsersSection() {
    const activeUsersCard = $('#active-users-card');

    if (activeUsersCard.length > 0) {
        // Usamos el evento 'expanded.lte.cardwidget' que AdminLTE dispara
        // DESPUÉS de que la tarjeta se ha expandido. Esto es mucho más fiable.
        activeUsersCard.on('expanded.lte.cardwidget', function () {
            // Solo cargamos los datos si la tarjeta está visible.
            loadActiveSessions();
        });
    }
}

/**
 * Configura la sección de gestión de todos los usuarios.
 */
function setupInactiveUsersSection() {
    // CORRECCIÓN: Usar el evento 'expanded.lte.cardwidget' de AdminLTE para cargar la tabla.
    // El selector apunta a la tarjeta de gestión de usuarios.
    $('.card-purple').on('expanded.lte.cardwidget', function () {
        // Verificamos que sea la tarjeta correcta antes de cargar la tabla.
        if ($(this).find('#all-users-table').length > 0) {
            loadAllUsersForAdmin();
        }
    });
}


/**
 * Carga y muestra la lista de todos los usuarios en una DataTable.
 */
function loadAllUsersForAdmin() {
    // Si la tabla ya es una DataTable, simplemente la recargamos y salimos.
    if ($.fn.DataTable.isDataTable('#all-users-table')) {
        $('#all-users-table').DataTable().ajax.reload();
        return;
    }

    $('#all-users-table').DataTable({
        "processing": true,
        "serverSide": false, // Como cargamos todos los datos de una vez, es false
        "ajax": {
            "url": base_url + "Home/getAllUsersForAdmin",
            "dataSrc": "data" // Asegura que DataTables busque los datos en la propiedad "data" de la respuesta JSON.
        },
        "columns": [
            { "data": "usuario_id" },
            {
                "data": null, "render": function (data, type, row) {
                    return `${row.usuario_nombres || ''} ${row.usuario_apellidos || ''}`;
                }
            },
            { "data": "usuario_nick" },
            { "data": "rol_nombre" },
            { "data": "departamento_nombre" },
            {
                "data": "usuario_status",
                "render": function (data, type, row) {
                    return data == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>';
                }
            },
            {
                "data": null,
                "orderable": false,
                "render": function (data, type, row) {
                    let actionButton;
                    if (row.usuario_status == 1) {
                        actionButton = `<button onclick="disableUser(${row.usuario_id})" class="btn btn-danger btn-xs" title="Desactivar Usuario"><i class="fas fa-user-slash"></i></button>`;
                    } else if (row.usuario_status == 0) {
                        actionButton = `<button onclick="enableUser(${row.usuario_id})" class="btn btn-success btn-xs" title="Reactivar Usuario"><i class="fas fa-user-check"></i></button>`;
                    }
                    return `<div class="text-center">${actionButton}</div>`;
                }
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "order": [[0, "asc"]],
        "language": {
            "url": base_url + "src/plugins/js/es_es.json"
        }
    });
}

/**
 * Reactiva la cuenta de un usuario.
 * @param {number} userId - El ID del usuario.
 */
function enableUser(userId) {
    Swal.fire({
        title: '¿Reactivar Usuario?',
        text: `¿Estás seguro de que quieres reactivar a este usuario?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, reactivar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(base_url + 'User/updateStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario_id: userId, usuario_status: 1 }) // 1 para Activo
                });
                const res = await response.json();
                notifi(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    $('#all-users-table').DataTable().ajax.reload(); // Recargar la tabla
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}

/**
 * Carga y muestra la lista de usuarios con sesiones activas.
 * Esta función solo se ejecuta para el dashboard de Sistema/Admin.
 */
async function loadActiveSessions() {
    console.log('Ejecutando loadActiveSessions()...'); // <-- AQUÍ ESTÁ LA PRUEBA

    const listContainer = document.getElementById('active-users-list');
    const noUsersMessage = document.getElementById('no-active-users');

    if (!listContainer || !noUsersMessage) return;

    // Mostrar estado de carga
    listContainer.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    noUsersMessage.style.display = 'none';

    try {
        const response = await fetch(base_url + 'Home/getActiveUsers');
        const result = await response.json();

        listContainer.innerHTML = ''; // Limpiar lista

        if (result.success && result.data.length > 0) {
            noUsersMessage.style.display = 'none';
            result.data.forEach(user => {
                // CORRECCIÓN: Nuevo diseño de tarjeta de usuario más limpio y robusto.
                // --- INICIO DE LA MODIFICACIÓN ---
                const userCard = `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="${base_url}${user.usuario_imagen}" alt="User Image" class="img-circle img-sm mr-3">
                            <div>
                                <div class="font-weight-bold text-truncate" style="max-width: 150px;" title="${user.usuario_nombres} ${user.usuario_apellidos}">(${user.usuario_nick}) ${user.usuario_nombres} ${user.usuario_apellidos}</div>
                                <div class="text-muted text-xs mt-1">
                                    <i class="fas fa-network-wired mr-1"></i> IP: ${user.ip_address}
                                </div>
                                <div class="text-muted text-xs">
                                    <i class="fas fa-clock mr-1"></i> Inicio: ${user.session_start_formatted || 'N/A'}
                                </div>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button onclick="forceLogout('${user.usuario_nick}')" class="btn btn-warning btn-xs" title="Cerrar Sesión"><i class="fas fa-sign-out-alt"></i></button>
                            <button onclick="disableUser(${user.usuario_id})" class="btn btn-danger btn-xs" title="Desactivar Usuario"><i class="fas fa-user-slash"></i></button>
                        </div>
                    </li>
                `;
                // --- FIN DE LA MODIFICACIÓN ---
                listContainer.innerHTML += userCard;
            });
        } else {
            noUsersMessage.textContent = 'No hay usuarios con sesiones activas en este momento.';
            noUsersMessage.style.display = 'block';
        }
    } catch (error) {
        console.error('Error cargando sesiones activas:', error);
        noUsersMessage.innerHTML = '<p class="text-danger">Error al cargar las sesiones. Intente de nuevo.</p>';
        noUsersMessage.style.display = 'block';
    }
}

/**
 * Fuerza el cierre de sesión de un usuario.
 * @param {string} userNick - El nick del usuario.
 */
function forceLogout(userNick) {
    Swal.fire({
        title: '¿Cerrar Sesión?',
        text: `¿Estás seguro de que quieres forzar el cierre de sesión para el usuario ${userNick}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('userId', userNick);
                const response = await fetch(base_url + 'Login/forceLogout', { method: 'POST', body: formData });
                const res = await response.json();
                notifi(res.msg, res.status ? 'success' : 'error');
                if (res.status) {
                    loadActiveSessions(); // Recargar la lista
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}

/**
 * Desactiva la cuenta de un usuario.
 * @param {number} userId - El ID del usuario.
 */
function disableUser(userId) {
    Swal.fire({
        title: '¿Desactivar Usuario?',
        text: `Esta acción impedirá que el usuario inicie sesión. ¿Continuar?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, desactivar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(base_url + 'User/updateStatus', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ usuario_id: userId, usuario_status: 0 }) // 0 para Inactivo
                });
                const res = await response.json();
                notifi(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    $('#all-users-table').DataTable().ajax.reload(); // Recargar la tabla de usuarios
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}

/**
 * Carga los meses disponibles en los selectores de fecha.
 */
async function populateMonthSelects() {
    const startMonthSelect = document.getElementById('startMonth');
    const endMonthSelect = document.getElementById('endMonth');
    const generateChartBtn = document.getElementById('generateChartBtn');

    if (!startMonthSelect || !endMonthSelect || !generateChartBtn) return;

    try {
        const response = await fetch(base_url + 'Home/getAvailableMonths');
        const result = await response.json();
        if (result.success && result.data.length > 0) {
            result.data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.mes;
                option.textContent = item.mes;
                startMonthSelect.appendChild(option.cloneNode(true));
                endMonthSelect.appendChild(option);
            });
            endMonthSelect.value = result.data[result.data.length - 1].mes;
            generateChartBtn.addEventListener('click', generateMonthlyChart);
        } else {
            //notifi('No se encontraron meses con ventas.', 'warning');
        }
    } catch (error) {
        console.error('Error al cargar los meses:', error);
        notifi('Error de conexión al cargar meses.', 'error');
    }
}

/**
 * Carga y renderiza el gráfico de ventas diarias por usuario.
 */
async function loadDailySales() {
    try {
        const response = await fetch(base_url + 'Home/getDailySales', { method: 'POST' });
        const result = await response.json();
        if (result.success) {
            renderDailySalesChart(result.data);
        } else {
            notifi(result.message || 'Error al cargar ventas del día', 'error');
        }
    } catch (error) {
        console.error('Error al cargar ventas del día:', error);
        notifi('Error de conexión al cargar ventas del día', 'error');
    }
}

/**
 * Renderiza el gráfico de barras de ventas diarias.
 * @param {object} data - Datos de ventas.
 */
function renderDailySalesChart(data) {
    const ctx = document.getElementById('dailySalesChart')?.getContext('2d');
    if (!ctx) return;

    if (dailyChart) {
        dailyChart.destroy();
    }

    const users = data.sales_by_user.map(item => item.usuario);
    const liters = data.sales_by_user.map(item => parseFloat(item.total_litros) || 0);

    dailyChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: users,
            datasets: [{
                label: 'Litros Vendidos',
                data: liters,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: {
                title: { display: true, text: `Ventas del Día (${data.fecha})` },
                legend: { display: false }
            }
        }
    });
}

/**
 * Genera el gráfico de dona con las ventas mensuales.
 */
async function generateMonthlyChart() {
    const startMonth = document.getElementById('startMonth').value;
    const endMonth = document.getElementById('endMonth').value;
    const chartCanvas = document.getElementById('monthlyLitersChart');
    const noDataMessage = document.getElementById('noLitersDataMessage');

    if (!startMonth || !endMonth) {
        notifi('Por favor, selecciona un rango de fechas válido.', 'warning');
        return;
    }
    if (new Date(startMonth) > new Date(endMonth)) {
        notifi('La fecha de inicio no puede ser mayor a la fecha final.', 'warning');
        return;
    }

    if (monthlyChart) {
        monthlyChart.destroy();
    }

    noDataMessage.classList.add('hidden');
    chartCanvas.classList.add('hidden');

    try {
        const formData = new FormData();
        formData.append('start_month', startMonth);
        formData.append('end_month', endMonth);

        const response = await fetch(base_url + 'Home/getMonthlyLiters', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            renderDoughnutChart(result.data);
            chartCanvas.classList.remove('hidden');
            notifi('Gráfico generado correctamente.', 'success');
        } else {
            noDataMessage.textContent = result.message || 'No hay datos para el rango seleccionado.';
            noDataMessage.classList.remove('hidden');
            notifi(result.message || 'No hay datos para el rango seleccionado.', 'info');
        }
    } catch (error) {
        console.error('Error al generar el gráfico:', error);
        notifi('Error de conexión al generar el gráfico.', 'error');
    }
}

/**
 * Renderiza el gráfico de dona.
 * @param {Array} data - Datos de ventas mensuales.
 */
function renderDoughnutChart(data) {
    const chartCanvas = document.getElementById('monthlyLitersChart');
    if (!chartCanvas) return;

    // Formateador para números (es-ES usa puntos para miles y comas para decimales)
    const numberFormatter = new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    const labels = data.map(item => {
        const [year, month] = item.mes_venta.split('-');
        const monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${monthNames[parseInt(month) - 1]} ${year} - ${numberFormatter.format(parseFloat(item.total_litros))} Lts`;
    });
    const liters = data.map(item => parseFloat(item.total_litros));
    const totalLiters = liters.reduce((sum, current) => sum + current, 0);

    const ctx = chartCanvas.getContext('2d');
    monthlyChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                label: 'Litros Vendidos',
                data: liters,
                backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#F97316', '#BE185D', '#14B8A6', '#78716C'],
                borderColor: '#ffffff',
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: function (tooltipItem) {
                            const currentValue = tooltipItem.raw;
                            const percentage = ((currentValue / totalLiters) * 100).toFixed(1);
                            return `${tooltipItem.label} (${percentage}%)`;
                        }
                    }
                },
                title: { display: true, text: `Total: ${numberFormatter.format(totalLiters)} Litros` }
            }
        }
    });
}






// Espera a que el DOM esté completamente cargado
// document.addEventListener('DOMContentLoaded', function () {

//     // Verificar si el selector de estación existe en la página
//     const selectEstacion = document.querySelector('#selectEstacion');

//     if (selectEstacion) {
//         selectEstacion.addEventListener('change', function () {
//             const stationId = this.value;

//             if (!stationId || stationId === "") {
//                 return; // No hacer nada si se selecciona la opción por defecto
//             }

//             // Mostrar una alerta de carga
//             Swal.fire({
//                 title: 'Actualizando Estación',
//                 text: 'Por favor, espere...',
//                 allowOutsideClick: false,
//                 didOpen: () => {
//                     Swal.showLoading();
//                 }
//             });

//             const formData = new FormData();
//             // La variable `userId` debe estar definida globalmente en tu vista (home.php)
//             // junto con `userDepartment` y `userRole`.
//             formData.append('idEstacion', stationId);
//             formData.append('idUsuario', userId);

//             // Petición AJAX para actualizar la estación, apuntando al controlador correcto
//             fetch(base_url + 'home/setStation', {
//                 method: 'POST',
//                 body: formData
//             })
//                 .then(response => response.json())
//                 .then(data => {
//                     if (data.status) {
//                         Swal.fire({
//                             icon: 'success',
//                             title: '¡Actualizado!',
//                             text: data.msg,
//                             showConfirmButton: false,
//                             timer: 2000
//                         }).then(() => {
//                             // Recargar la página para reflejar los cambios en toda la UI
//                             location.reload();
//                         });
//                     } else {
//                         Swal.fire('Error', data.msg, 'error');
//                     }
//                 })
//                 .catch(error => {
//                     console.error('Error:', error);
//                     Swal.fire('Error', 'Ocurrió un problema de conexión.', 'error');
//                 });
//         });
//     }

// });