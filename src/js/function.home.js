let dailyChart = null;
let monthlyChart = null;

// Institución activa en el dashboard (para admin)
let dashboardInstitucionActual = null;
let esAdminDashboard = false;
let institucionesDisponibles = [];

// Espera a que el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {

    // Verificar si el selector de estación existe en la página
    const selectEstacion = document.querySelector('#selectEstacion');

    if (selectEstacion) {
        selectEstacion.addEventListener('change', function () {
            const stationId = this.value;

            if (!stationId || stationId === "") {
                return;
            }

            Swal.fire({
                title: 'Actualizando Estación',
                text: 'Por favor, espere...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append('idEstacion', stationId);
            formData.append('idUsuario', userId);

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
                stationId = 1;
            } else if (result.isDenied) {
                stationId = 2;
            }

            if (stationId) {
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

                fetch(base_url + 'home/setStation', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
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

/**
 * Segunda inicialización: carga de datos del dashboard por departamento.
 */
document.addEventListener('DOMContentLoaded', async function () {
    if (typeof userDepartment === 'undefined' || typeof userRole === 'undefined') {
        return;
    }

    // Configurar el selector de institución si es admin de Sistema
    await setupInstitucionSelector();

    // LÓGICA DE CARGA DE DASHBOARDS
    if (document.querySelector('#dashboard-almacen')) loadAlmacenData();
    if (document.querySelector('#dashboard-operaciones')) loadOperacionesData();
    if (document.querySelector('#dashboard-estacion')) {
        loadEstacionData();
        populateMonthSelects();
        loadDailySales();
    }
    if (document.querySelector('#dashboard-bienes')) loadBienesData();
    if (document.querySelector('#dashboard-compras')) loadComprasData();

    // Funciones que solo se ejecutan para el rol de Administrador
    if (userRole === 'ADMINISTRADOR') {
        setupActiveUsersSection();
        setupInactiveUsersSection();
    }
});

/**
 * Configura el selector de institución global.
 * SOLO el departamento de Sistema/Sistemas ve el selector.
 * Un Administrador de otro departamento NO lo ve.
 */
async function setupInstitucionSelector() {
    const departamentoActual = (typeof userDepartment !== 'undefined') ? userDepartment.toUpperCase() : '';

    // SOLO el departamento de Sistema/Sistemas ve el selector de institución.
    const esAdmin = (departamentoActual === 'SISTEMA' || departamentoActual === 'SISTEMAS');

    if (!esAdmin) {
        dashboardInstitucionActual = 1;
        esAdminDashboard = false;
        return;
    }

    esAdminDashboard = true;

    try {
        const response = await fetch(base_url + 'Home/getInstituciones');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            institucionesDisponibles = result.data;
            dashboardInstitucionActual = parseInt(result.data[0].id_institucion, 10);

            const selectorHtml = `
                <div class="card card-outline card-primary mb-3" id="dashboard-institucion-card">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-building fa-2x text-primary mr-3"></i>
                                <div>
                                    <h5 class="mb-0">Dashboard Institucional</h5>
                                    <small class="text-muted">Seleccione la institución a visualizar</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <label class="mr-2 mb-0 font-weight-bold">Institución:</label>
                                <select id="dashboard-institucion-selector" class="form-control" style="min-width: 250px;">
                                    ${result.data.map(inst =>
                `<option value="${inst.id_institucion}">${inst.nombre}</option>`
            ).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            const contentWrapper = document.querySelector('.content-wrapper');
            if (contentWrapper) {
                const firstSection = contentWrapper.querySelector('section.content');
                if (firstSection) {
                    firstSection.insertAdjacentHTML('afterbegin', selectorHtml);
                } else {
                    contentWrapper.insertAdjacentHTML('afterbegin', selectorHtml);
                }
            }

            const selector = document.getElementById('dashboard-institucion-selector');
            if (selector) {
                selector.addEventListener('change', function () {
                    dashboardInstitucionActual = parseInt(this.value, 10);
                    recargarDashboard();
                });
            }
        }
    } catch (error) {
        console.error('Error cargando instituciones para el selector:', error);
    }
}

/**
 * Recarga los datos de todos los dashboards visibles.
 */
function recargarDashboard() {
    if (document.querySelector('#dashboard-almacen')) loadAlmacenData();
    if (document.querySelector('#dashboard-operaciones')) loadOperacionesData();
    if (document.querySelector('#dashboard-bienes')) loadBienesData();
    if (document.querySelector('#dashboard-compras')) loadComprasData();
    // Estación no aplica institución
}


/**
 * =====================================================================
 * DASHBOARD DE ALMACÉN
 * =====================================================================
 */

function loadAlmacenData() {
    const idInst = dashboardInstitucionActual || 1;

    fetch(base_url + 'Home/getAlmacenData?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const almacen = data.data;

                setText('almacen-productos-sin-stock', almacen.productos_sin_stock ?? 0);
                setText('almacen-productos-stock-bajo', almacen.productos_stock_bajo ?? 0);
                setText('almacen-ordenes-pendientes', almacen.orders_aprobadas ? almacen.orders_aprobadas.total_aprobadas : 0);
                setText('almacen-total-productos', almacen.total_productos ?? 0);

                const lubActual = almacen.consumibles ? parseFloat(almacen.consumibles.mes_actual || 0) : 0;
                setText('almacen-lubricantes-actual', lubActual.toFixed(2) + ' Lts');

                const lubAnterior = almacen.consumibles ? parseFloat(almacen.consumibles.mes_anterior || 0) : 0;
                setText('almacen-lubricantes-anterior', lubAnterior.toFixed(2) + ' Lts');

                const ordDesp = almacen.orders_despachadas ? almacen.orders_despachadas.total_despachadas : 0;
                setText('almacen-ordenes-despacho', ordDesp);
            }
        })
        .catch(error => console.error('Error cargando datos de almacén:', error));

    // Cargar las tablas
    loadTopProductosAlmacen();
    loadUltimasOrdenesAlmacen();
}

function loadTopProductosAlmacen() {
    const idInst = dashboardInstitucionActual || 1;
    const tbody = document.getElementById('tabla-top-productos');

    if (!tbody) return;

    fetch(base_url + 'Home/getTopProductosAlmacen?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                let html = '';
                data.data.forEach((item, index) => {
                    html += `
                        <tr>
                            <td class="text-center font-weight-bold">${index + 1}</td>
                            <td>${item.producto}</td>
                            <td><small class="text-muted">${item.enlace_producto || '-'}</small></td>
                            <td><small class="text-muted">${item.ubicacion || '-'}</small></td>
                            <td class="text-center font-weight-bold text-primary">
                                ${parseFloat(item.total_despachado).toFixed(2)} ${item.present_producto || 'Und'}
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mr-1"></i>No hay productos despachados este mes.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando top productos:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Error al cargar los datos.
                    </td>
                </tr>
            `;
        });
}

function loadUltimasOrdenesAlmacen() {
    const idInst = dashboardInstitucionActual || 1;
    const tbody = document.getElementById('tabla-ultimas-ordenes');

    if (!tbody) return;

    fetch(base_url + 'Home/getUltimasOrdenesAlmacen?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    const estadoBadge = getEstadoOrdenBadge(item.estado_orden);
                    html += `
                        <tr>
                            <td class="text-center font-weight-bold">#${item.numero_orden}</td>
                            <td>${item.fecha_despacho}</td>
                            <td>${item.id_unidad} - ${item.modelo_unidad}</td>
                            <td><small>${item.operador_nombre || 'N/A'}</small></td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.total_articulos} art.</span>
                            </td>
                            <td class="text-center">${estadoBadge}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mr-1"></i>No hay órdenes registradas.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando últimas órdenes:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Error al cargar los datos.
                    </td>
                </tr>
            `;
        });
}


/**
 * =====================================================================
 * DASHBOARD DE COMPRAS
 * =====================================================================
 */

function loadComprasData() {
    const idInst = dashboardInstitucionActual || 1;

    // 1. Cargar los contadores generales
    fetch(base_url + 'Home/getComprasData?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const compras = data.data;

                // Cards de alerta
                setText('compras-requisiciones-pendientes', compras.requisiciones_pendientes ?? 0);
                setText('compras-ordenes-aprobadas', compras.ordenes_aprobadas ?? 0);
                setText('compras-articulos-sin-stock', compras.articulos_sin_stock ?? 0);
                setText('compras-articulos-stock-bajo', compras.articulos_stock_bajo ?? 0);

                // Cards de movimientos del mes
                setText('compras-ordenes-despachadas-mes', compras.ordenes_despachadas_mes ?? 0);
                setText('compras-ordenes-costeadas-mes', compras.ordenes_costeadas_mes ?? 0);

                // Montos
                const totalDivisa = parseFloat(compras.total_divisa_mes || 0);
                setText('compras-total-divisa-mes', '$' + totalDivisa.toLocaleString('es-VE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));

                const totalBs = parseFloat(compras.total_bs_mes || 0);
                setText('compras-total-bs-mes', 'Bs. ' + totalBs.toLocaleString('es-VE', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            }
        })
        .catch(error => console.error('Error cargando datos de compras:', error));

    // 2. Cargar las tablas
    loadTopProductosCosteados();
    loadUltimasRequisiciones();
    loadUltimasComprasCosteadas();
}

/**
 * Top 10 productos costeados del mes.
 */
function loadTopProductosCosteados() {
    const idInst = dashboardInstitucionActual || 1;
    const tbody = document.getElementById('tabla-top-productos-costeados');

    if (!tbody) return;

    fetch(base_url + 'Home/getTopProductosCosteados?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                let html = '';
                data.data.forEach((item, index) => {
                    const totalDivisa = parseFloat(item.total_divisa || 0);
                    const totalBs = parseFloat(item.total_bs || 0);

                    html += `
                        <tr>
                            <td class="text-center font-weight-bold">${index + 1}</td>
                            <td>${item.producto}</td>
                            <td><small class="text-muted">${item.proveedor || '-'}</small></td>
                            <td><small class="text-muted">${item.enlace_producto || '-'}</small></td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.veces_costeadas}</span>
                            </td>
                            <td class="text-center font-weight-bold text-primary">
                                $${totalDivisa.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                            <td class="text-center font-weight-bold text-success">
                                Bs. ${totalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mr-1"></i>No hay productos costeados este mes.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando top productos costeados:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Error al cargar los datos.
                    </td>
                </tr>
            `;
        });
}

/**
 * Últimas 10 requisiciones.
 */
function loadUltimasRequisiciones() {
    const idInst = dashboardInstitucionActual || 1;
    const tbody = document.getElementById('tabla-ultimas-requisiciones');

    if (!tbody) return;

    fetch(base_url + 'Home/getUltimasRequisiciones?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    const estadoBadge = getEstadoOrdenBadge(item.estado_orden);
                    html += `
                        <tr>
                            <td class="text-center font-weight-bold">#${item.numero_orden}</td>
                            <td>${item.fecha_despacho}</td>
                            <td>${item.id_unidad} - ${item.modelo_unidad}</td>
                            <td><small>${item.solicitante || 'N/A'}</small></td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.total_articulos} art.</span>
                            </td>
                            <td class="text-center">${estadoBadge}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mr-1"></i>No hay requisiciones registradas.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando últimas requisiciones:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Error al cargar los datos.
                    </td>
                </tr>
            `;
        });
}

/**
 * Últimas 10 compras costeadas.
 */
function loadUltimasComprasCosteadas() {
    const idInst = dashboardInstitucionActual || 1;
    const tbody = document.getElementById('tabla-ultimas-compras-costeadas');

    if (!tbody) return;

    fetch(base_url + 'Home/getUltimasComprasCosteadas?id_institucion=' + idInst)
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                let html = '';
                data.data.forEach(item => {
                    const totalDivisa = parseFloat(item.total_divisa || 0);
                    const totalBs = parseFloat(item.total_bs || 0);

                    html += `
                        <tr>
                            <td class="text-center font-weight-bold">#${item.numero_orden}</td>
                            <td>${item.fecha_despacho}</td>
                            <td>${item.id_unidad} - ${item.modelo_unidad}</td>
                            <td class="text-center">
                                <span class="badge badge-info">${item.articulos_costeados} art.</span>
                            </td>
                            <td class="text-center font-weight-bold text-primary">
                                $${totalDivisa.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                            <td class="text-center font-weight-bold text-success">
                                Bs. ${totalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">
                            <i class="fas fa-info-circle mr-1"></i>No hay compras costeadas registradas.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando últimas compras costeadas:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-danger py-3">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Error al cargar los datos.
                    </td>
                </tr>
            `;
        });
}


/**
 * Helper: devuelve el badge del estado de la orden.
 */
function getEstadoOrdenBadge(estado) {
    switch (parseInt(estado)) {
        case 1: return '<span class="badge badge-secondary">Requisición</span>';
        case 2: return '<span class="badge badge-warning text-dark">Aprobada</span>';
        case 3: return '<span class="badge badge-success">Despachada</span>';
        case 4: return '<span class="badge badge-danger">Rechazada</span>';
        default: return '<span class="badge badge-light">N/A</span>';
    }
}

/**
 * Helper: asigna texto a un elemento por ID (con null check).
 */
function setText(elementId, value) {
    const el = document.getElementById(elementId);
    if (el) el.textContent = value;
}


/**
 * =====================================================================
 * DASHBOARD DE OPERACIONES (FLOTA)
 * =====================================================================
 */

function loadOperacionesData() {
    const url = base_url + 'Home/getOperacionesData?id_institucion=' + (dashboardInstitucionActual || 1);
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const operaciones = data.data;

                setText('unidades-operativas', operaciones.status.operativas || 0);
                setText('unidades-inoperativas', operaciones.status.inoperativas || 0);
                setText('unidades-mantenimiento', operaciones.status.mantenimiento || 0);
                setText('unidades-criticas', operaciones.status.criticas || 0);

                if (operaciones.aceite_status) {
                    setText('aceite-requerido', operaciones.aceite_status.requerido || 0);
                    setText('aceite-proximo', operaciones.aceite_status.proximo || 0);
                    setText('aceite-ok', operaciones.aceite_status.ok || 0);
                }

                const tablaBody = document.querySelector('#tabla-resumen-flota');

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
                if (tablaBody) tablaBody.innerHTML = html;
            }
        })
        .catch(error => console.error('Error cargando datos de operaciones:', error));
}


/**
 * =====================================================================
 * DASHBOARD DE ESTACIÓN
 * =====================================================================
 */

async function loadEstacionData() {
    try {
        const response = await fetch(base_url + 'Home/getEstacionDashboardData');
        const result = await response.json();

        if (result.success && result.data) {
            const data = result.data;
            setText('totalVentasHoy', data.total_ventas || 0);
            setText('totalLitrosHoy', `${parseFloat(data.total_litros || 0).toFixed(2)} Lts`);
            setText('user_activo', `${parseFloat(data.user_activo || 0)}`);
        }
    } catch (error) {
        console.error('Error cargando datos de estación:', error);
    }
}


/**
 * =====================================================================
 * DASHBOARD DE BIENES
 * =====================================================================
 */

async function loadBienesData() {
    try {
        const response = await fetch(base_url + 'Home/getBienesDashboardData');
        const result = await response.json();

        if (result.success && result.data) {
            const { summary, recent } = result.data;

            setText('bienes-total', summary.total_bienes || 0);
            setText('bienes-activos', summary.total_activos || 0);
            setText('bienes-reparacion', summary.total_reparacion || 0);
            setText('bienes-baja', summary.total_baja || 0);

            const tablaBody = document.getElementById('tabla-bienes-recientes');
            if (tablaBody) {
                tablaBody.innerHTML = '';

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
            }
        } else {
            console.error('Error en la respuesta del servidor para Bienes:', result.message);
        }
    } catch (error) {
        console.error('Error cargando datos de bienes:', error);
    }
}


/**
 * =====================================================================
 * SECCIONES DE USUARIOS (SOLO ADMIN)
 * =====================================================================
 */

function setupActiveUsersSection() {
    const activeUsersCard = $('#active-users-card');

    if (activeUsersCard.length > 0) {
        activeUsersCard.on('expanded.lte.cardwidget', function () {
            loadActiveSessions();
        });
    }
}

function setupInactiveUsersSection() {
    $('.card-purple').on('expanded.lte.cardwidget', function () {
        if ($(this).find('#all-users-table').length > 0) {
            loadAllUsersForAdmin();
        }
    });
}

function loadAllUsersForAdmin() {
    if ($.fn.DataTable.isDataTable('#all-users-table')) {
        $('#all-users-table').DataTable().ajax.reload();
        return;
    }

    $('#all-users-table').DataTable({
        "processing": true,
        "serverSide": false,
        "ajax": {
            "url": base_url + "Home/getAllUsersForAdmin",
            "dataSrc": "data"
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
                    body: JSON.stringify({ usuario_id: userId, usuario_status: 1 })
                });
                const res = await response.json();
                notifi(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    $('#all-users-table').DataTable().ajax.reload();
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}

async function loadActiveSessions() {
    const listContainer = document.getElementById('active-users-list');
    const noUsersMessage = document.getElementById('no-active-users');

    if (!listContainer || !noUsersMessage) return;

    listContainer.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';
    noUsersMessage.style.display = 'none';

    try {
        const response = await fetch(base_url + 'Home/getActiveUsers');
        const result = await response.json();

        listContainer.innerHTML = '';

        if (result.success && result.data.length > 0) {
            noUsersMessage.style.display = 'none';
            result.data.forEach(user => {
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
                    loadActiveSessions();
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}

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
                    body: JSON.stringify({ usuario_id: userId, usuario_status: 0 })
                });
                const res = await response.json();
                notifi(res.message, res.success ? 'success' : 'error');
                if (res.success) {
                    $('#all-users-table').DataTable().ajax.reload();
                }
            } catch (error) {
                notifi('Ocurrió un error en la operación.', 'error');
            }
        }
    });
}


/**
 * =====================================================================
 * GRÁFICOS DE ESTACIÓN
 * =====================================================================
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
        }
    } catch (error) {
        console.error('Error al cargar los meses:', error);
        notifi('Error de conexión al cargar meses.', 'error');
    }
}

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

function renderDoughnutChart(data) {
    const chartCanvas = document.getElementById('monthlyLitersChart');
    if (!chartCanvas) return;

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