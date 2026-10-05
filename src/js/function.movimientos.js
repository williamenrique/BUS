/**
 * Public Movimientos Dashboard - JavaScript
 * Dashboard público de consulta de movimientos
 * CON SOPORTE MULTI-INSTITUCIÓN
 */

// ============================================
// ESTADO GLOBAL DE LA APLICACIÓN
// ============================================

let allMovimientos = [];
let filteredMovimientos = [];
let movimientosTable = null;
let selectedRow = null;

let allFleetSummary = [];
let filteredFleetSummary = [];
let selectedFleetGroups = new Set();

let currentFlotaStatus = null;
let flotaUnidadesData = [];
let flotaUnidadesFiltered = [];

let currentAceiteStatus = null;
let aceiteUnidadesData = [];
let aceiteUnidadesFiltered = [];

let currentPage = 1;
let recordsPerPage = 10;

// ============================================
// ESTADO MULTI-INSTITUCIÓN
// ============================================

let allInstituciones = [];

const instSeleccionada = {
    flota: null,
    aceite: null,
    resumen: null,
    movimientos: null
};

// ============================================
// FUNCIONES DE INSTITUCIÓN
// ============================================

/**
 * Carga la lista de instituciones desde el backend y llena los 4 selectores.
 */
async function cargarInstituciones() {
    try {
        const response = await fetch('?url=Publico/getInstituciones');
        const data = await response.json();

        if (!data.success || !Array.isArray(data.data)) {
            console.error('No se pudieron cargar las instituciones');
            return;
        }

        allInstituciones = data.data;

        ['flota', 'aceite', 'resumen', 'movimientos'].forEach(seccion => {
            const select = document.getElementById(`instSelect${capitalize(seccion)}`);
            if (!select) return;

            select.innerHTML = '';

            allInstituciones.forEach(inst => {
                const option = document.createElement('option');
                option.value = inst.id_institucion;
                option.textContent = abreviarNombreInstitucion(inst.nombre);
                option.title = inst.nombre;
                select.appendChild(option);
            });

            if (allInstituciones.length > 0) {
                instSeleccionada[seccion] = parseInt(allInstituciones[0].id_institucion, 10);
                select.value = instSeleccionada[seccion];
            }

            select.addEventListener('change', function () {
                instSeleccionada[seccion] = parseInt(this.value, 10);
                if (typeof recargarSeccion === 'function') {
                    recargarSeccion(seccion);
                }
            });
        });

    } catch (error) {
        console.error('Error cargando instituciones:', error);
    }
}

/**
 * Abrevia el nombre de la institución a iniciales si es muy largo.
 */
function abreviarNombreInstitucion(nombre) {
    if (!nombre) return 'N/D';

    if (nombre.length <= 20) {
        return nombre;
    }

    const ignorar = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'e', 'o', 'a', 'en', 'con'];
    const palabras = nombre.split(/\s+/);

    let iniciales = '';
    palabras.forEach(p => {
        const lower = p.toLowerCase();
        if (!ignorar.includes(lower) && p.length > 0) {
            iniciales += p[0].toUpperCase();
        }
    });

    if (iniciales.length < 3) {
        return nombre;
    }

    return iniciales;
}

/**
 * Devuelve el nombre completo de la institución según el id.
 */
function getNombreInstitucionPorId(id) {
    const inst = allInstituciones.find(i => parseInt(i.id_institucion, 10) === parseInt(id, 10));
    return inst ? inst.nombre : '';
}

/**
 * Capitaliza la primera letra.
 */
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Recarga los datos de la sección seleccionada.
 * Se ejecuta desde los selectores (change) y desde la carga inicial.
 */
function recargarSeccion(seccion) {
    switch (seccion) {
        case 'flota':
            if (typeof cargarEstadoFlota === 'function') cargarEstadoFlota();
            if (currentFlotaStatus && typeof cargarUnidadesPorEstadoFlota === 'function') {
                cargarUnidadesPorEstadoFlota(currentFlotaStatus);
            }
            break;
        case 'aceite':
            if (typeof cargarEstadoAceite === 'function') cargarEstadoAceite();
            if (currentAceiteStatus && typeof cargarUnidadesPorEstadoAceite === 'function') {
                cargarUnidadesPorEstadoAceite(currentAceiteStatus);
            }
            break;
        case 'resumen':
            if (typeof cargarResumenFlota === 'function') cargarResumenFlota();
            break;
        case 'movimientos':
            if (typeof initMovimientosDataTable === 'function') initMovimientosDataTable();
            if (typeof cargarMovimientos === 'function') cargarMovimientos();
            break;
    }
}

// ============================================
// FUNCIONES GLOBALES - NAVEGACIÓN DE LINKS
// ============================================

function cargarDetalleOrden(idDespacho) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-file-invoice me-2"></i>Detalle de Despacho';
    resumenDiv.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Cargando despacho #${idDespacho}...</p></div>`;

    fetch('?url=Publico/getDetalleOrden', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ idDespacho: idDespacho })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarDetalleOrden(data.data);
            else resumenDiv.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>${data.message}</div>`;
        })
        .catch(error => {
            resumenDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function cargarDetalleAceite(idAceite) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-oil-can me-2"></i>Detalle de Cambio de Aceite';
    resumenDiv.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-warning"></div><p class="mt-2 text-muted">Cargando cambio de aceite #${idAceite}...</p></div>`;

    fetch('?url=Publico/getDetalleAceite', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idAceite: idAceite,
            id_institucion: instSeleccionada.movimientos
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarDetalleAceite(data.data);
            else resumenDiv.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>${data.message}</div>`;
        })
        .catch(error => {
            resumenDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function cargarDetalleMantenimiento(idMantenimiento) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-tools me-2"></i>Detalle de Mantenimiento';
    resumenDiv.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-secondary"></div><p class="mt-2 text-muted">Cargando mantenimiento #${idMantenimiento}...</p></div>`;

    fetch('?url=Publico/getDetalleMantenimiento', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idMantenimiento: idMantenimiento,
            id_institucion: instSeleccionada.movimientos
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarDetalleMantenimiento(data.data);
            else resumenDiv.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>${data.message}</div>`;
        })
        .catch(error => {
            resumenDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function cargarDetalleKilometraje(idKilometraje) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-tachometer-alt me-2"></i>Detalle de Kilometraje';
    resumenDiv.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-info"></div><p class="mt-2 text-muted">Cargando kilometraje #${idKilometraje}...</p></div>`;

    fetch('?url=Publico/getDetalleKilometraje', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idKilometraje: idKilometraje,
            id_institucion: instSeleccionada.movimientos
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarDetalleKilometraje(data.data);
            else resumenDiv.innerHTML = `<div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>${data.message}</div>`;
        })
        .catch(error => {
            resumenDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

function cargarHistorialUnidad(idFlota, idUnidad) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-history me-2"></i>Hoja de Vida de Unidad';
    resumenDiv.innerHTML = `<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Cargando hoja de vida de ${idUnidad}...</p></div>`;

    fetch('?url=Publico/getHistorialUnidad', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idFlota: idFlota,
            id_institucion: instSeleccionada.movimientos
        })
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) renderizarHistorialUnidad(data.data.unidad, data.data.historial);
            else resumenDiv.innerHTML = `<div class="alert alert-warning">${data.message}</div>`;
        })
        .catch(error => {
            resumenDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
        });
}

// ============================================
// RENDERIZADORES DE DETALLE
// ============================================

function renderizarDetalleOrden(orden) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    if (!resumenDiv) return;

    const fechaFormateada = formatearFechaCompleta(orden.fecha_despacho);
    const estadoClass = getEstadoClass(orden.estado_orden);
    const estadoTexto = getEstadoDespachoTexto(orden.estado_orden);

    let html = `
        <div class="card border-primary">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Despacho #${orden.id_despacho}</h6>
                <button type="button" class="btn btn-sm btn-light btn-despacho-pdf" data-id-despacho="${orden.id_despacho}" title="Descargar PDF">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </button>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Fecha</small><strong>${fechaFormateada}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Estado</small><span class="badge ${estadoClass}">${estadoTexto}</span></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Unidad</small><strong>${orden.id_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Operador</small><strong>${orden.operador_nombre || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Mecánico</small><strong>${orden.mecanico_nombre || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Despachador</small><strong>${orden.despachador_nombre || 'N/A'}</strong></div></div></div>
                </div>
                ${orden.observacion ? `<div class="mb-3"><label class="form-label fw-bold">Observación:</label><div class="p-2 bg-light rounded">${orden.observacion}</div></div>` : ''}
                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-boxes me-1"></i>Productos Despachados</h6>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-dark"><tr><th style="width:50px;">#</th><th>Producto</th><th>Presentación</th><th style="width:100px;">Cantidad</th></tr></thead>
                        <tbody>
    `;
    if (!orden.productos || orden.productos.length === 0) {
        html += `<tr><td colspan="4" class="text-center text-muted py-3"><i class="fas fa-info-circle me-1"></i>Sin productos registrados</td></tr>`;
    } else {
        orden.productos.forEach((p, i) => {
            html += `<tr><td>${i + 1}</td><td>${p.producto}</td><td>${p.present_producto || 'N/A'}</td><td class="text-center fw-bold">${p.cant_despacho}</td></tr>`;
        });
    }
    html += `</tbody></table></div></div></div>`;
    resumenDiv.innerHTML = html;

    const btnPDF = resumenDiv.querySelector('.btn-despacho-pdf');
    if (btnPDF) {
        btnPDF.addEventListener('click', () => {
            const reporteData = {
                id_despacho: orden.id_despacho,
                fecha_despacho: orden.fecha_despacho,
                id_unidad: orden.id_unidad,
                marca_unidad: orden.marca_unidad || '',
                modelo_unidad: orden.modelo_unidad || '',
                vim_unidad: orden.vim_unidad || '',
                operador_nombre: orden.operador_nombre || '',
                mecanico_nombre: orden.mecanico_nombre || '',
                despachador_nombre: orden.despachador_nombre || '',
                observacion: orden.observacion || '',
                usuario_registro: orden.usuario_registro || orden.despachador_nombre || 'Sistema',
                id_institucion: instSeleccionada.movimientos,
                articulos: (orden.productos || []).map(prod => ({
                    id_producto: prod.id_producto || '',
                    producto: prod.producto,
                    cant_despacho: prod.cant_despacho,
                    ubicacion: prod.ubicacion || prod.present_producto || 'N/A'
                }))
            };
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../data/almacen/reportePDFdesp.php';
            form.target = '_blank';
            form.style.display = 'none';
            const inputReporte = document.createElement('input');
            inputReporte.type = 'hidden';
            inputReporte.name = 'reporteData';
            inputReporte.value = JSON.stringify(reporteData);
            form.appendChild(inputReporte);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        });
    }
}

function renderizarDetalleAceite(a) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    if (!resumenDiv) return;

    const kmCambio = parseInt(a.kilometraje_cambio || 0);
    const kmAnterior = parseInt(a.kilometraje_anterior || 0);
    const kmProximo = parseInt(a.kilometraje_proximo_cambio || 0);
    const diferencia = kmProximo > 0 && kmCambio > 0 ? kmProximo - kmCambio : 0;

    resumenDiv.innerHTML = `
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="fas fa-oil-can me-2"></i>Cambio de Aceite ACE-${String(a.id_aceite_historial).padStart(6, '0')}</h6>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Fecha</small><strong>${formatearFechaCompleta(a.fecha_cambio)}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Unidad</small><strong>${a.id_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">VIN</small><strong class="font-monospace small">${a.vim_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Realizado por</small><strong>${a.responsable_nombre || 'Sistema'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Usuario</small><strong>${a.usuario_nick || '-'}</strong></div></div></div>
                </div>
                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-tachometer-alt me-1"></i>Kilometrajes</h6>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-3"><div class="card border-secondary"><div class="card-body text-center p-2"><small class="text-muted d-block">KM Anterior</small><strong class="text-secondary">${kmAnterior > 0 ? kmAnterior.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-warning"><div class="card-body text-center p-2"><small class="text-muted d-block">KM del Cambio</small><strong class="text-warning">${kmCambio > 0 ? kmCambio.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-info"><div class="card-body text-center p-2"><small class="text-muted d-block">Próximo Cambio</small><strong class="text-info">${kmProximo > 0 ? kmProximo.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-3"><div class="card border-${diferencia <= 0 ? 'danger' : (diferencia <= 1000 ? 'warning' : 'success')}"><div class="card-body text-center p-2"><small class="text-muted d-block">Faltante</small><strong class="text-${diferencia <= 0 ? 'danger' : (diferencia <= 1000 ? 'warning' : 'success')}">${diferencia > 0 ? diferencia.toLocaleString('es-VE') : '0'} KM</strong></div></div></div>
                </div>
                ${a.observaciones ? `<div class="mb-3"><label class="form-label fw-bold">Observaciones:</label><div class="p-2 bg-light rounded">${a.observaciones}</div></div>` : ''}
            </div>
        </div>
    `;
}

function renderizarDetalleMantenimiento(m) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    if (!resumenDiv) return;

    const tipoMant = m.tipo_mantenimiento === 'c' ? 'Correctivo' : (m.tipo_mantenimiento === 'p' ? 'Preventivo' : (m.tipo_mantenimiento || 'N/A'));
    const km = parseInt(m.km_unidad || 0);

    resumenDiv.innerHTML = `
        <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0"><i class="fas fa-tools me-2"></i>Mantenimiento MANT-${String(m.id_unidad_mantenimiento).padStart(6, '0')}</h6>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Unidad</small><strong>${m.id_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Tipo</small><strong>${tipoMant}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">KM</small><strong>${km > 0 ? km.toLocaleString('es-VE') : 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Entrada</small><strong>${m.fecha_entrada ? formatearFechaCompleta(m.fecha_entrada) : 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Salida</small><strong>${m.fecha_salida ? formatearFechaCompleta(m.fecha_salida) : 'Pendiente'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Estado</small><strong>${m.status_mantenimiento || '-'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Operador</small><strong>${m.operardor_unidad || '-'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Mecánico</small><strong>${m.nomb_mecanico || '-'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Registrado por</small><strong>${m.responsable_nombre || 'Sistema'}</strong></div></div></div>
                </div>
                ${m.diagnostico ? `<div class="mb-2"><label class="form-label fw-bold">Diagnóstico:</label><div class="p-2 bg-light rounded">${m.diagnostico}</div></div>` : ''}
                ${m.recomendacion ? `<div class="mb-2"><label class="form-label fw-bold">Recomendación:</label><div class="p-2 bg-light rounded">${m.recomendacion}</div></div>` : ''}
                ${m.obsOperador ? `<div class="mb-2"><label class="form-label fw-bold">Obs. Operador:</label><div class="p-2 bg-light rounded">${m.obsOperador}</div></div>` : ''}
                ${m.obsSupervisor ? `<div class="mb-2"><label class="form-label fw-bold">Obs. Supervisor:</label><div class="p-2 bg-light rounded">${m.obsSupervisor}</div></div>` : ''}
                ${m.obsSalida ? `<div class="mb-2"><label class="form-label fw-bold">Obs. Salida:</label><div class="p-2 bg-light rounded">${m.obsSalida}</div></div>` : ''}
            </div>
        </div>
    `;
}

function renderizarDetalleKilometraje(k) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    if (!resumenDiv) return;

    const kmActual = parseInt(k.kilometraje_actual || 0);
    const kmAnterior = parseInt(k.kilometraje_anterior || 0);
    const recorrido = kmActual > 0 && kmAnterior > 0 ? kmActual - kmAnterior : 0;

    resumenDiv.innerHTML = `
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Actualización KM-${String(k.id_kilometraje).padStart(6, '0')}</h6>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Unidad</small><strong>${k.id_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">VIN</small><strong class="font-monospace small">${k.vim_unidad || 'N/A'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Fecha</small><strong>${formatearFechaCompleta(k.fecha_actualizacion)}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Registrado por</small><strong>${k.responsable_nombre || 'Sistema'}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Usuario</small><strong>${k.usuario_nick || '-'}</strong></div></div></div>
                </div>
                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-road me-1"></i>Lecturas de Kilometraje</h6>
                <div class="row g-2">
                    <div class="col-6 col-md-4"><div class="card border-secondary"><div class="card-body text-center p-2"><small class="text-muted d-block">KM Anterior</small><strong class="text-secondary">${kmAnterior > 0 ? kmAnterior.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card border-info"><div class="card-body text-center p-2"><small class="text-muted d-block">KM Actual</small><strong class="text-info">${kmActual > 0 ? kmActual.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card border-success"><div class="card-body text-center p-2"><small class="text-muted d-block">Recorrido</small><strong class="text-success">${recorrido > 0 ? recorrido.toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                </div>
            </div>
        </div>
    `;
}

function renderizarHistorialUnidad(unidad, historial) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    const panelTitle = document.getElementById('panelTitle');
    if (!resumenDiv) return;

    if (panelTitle) panelTitle.innerHTML = '<i class="fas fa-history me-2"></i>Hoja de Vida de Unidad';

    const kmActual = parseInt(unidad.km_actual) || 0;
    const ultimoCambio = parseInt(unidad.ultimo_cambio_aceite) || 0;
    const proximoCambio = ultimoCambio > 0 ? ultimoCambio + 5000 : 0;
    const faltanCambio = proximoCambio > 0 ? proximoCambio - kmActual : 0;

    const counts = { despacho: 0, aceite: 0, mantenimiento: 0, status: 0 };
    if (historial) {
        historial.forEach(mov => {
            if (counts.hasOwnProperty(mov.tipo)) counts[mov.tipo]++;
        });
    }

    let html = `
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-bus me-2"></i>Hoja de Vida - Unidad ${unidad.id_unidad}</h6>
                <button type="button" class="btn btn-sm btn-light btn-descargar-pdf" data-id-flota="${unidad.id_flota}" data-id-unidad="${unidad.id_unidad}" title="Descargar PDF">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </button>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Marca / Modelo</small><strong>${unidad.marca} ${unidad.modelo}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Transmisión</small><strong>${unidad.transmision}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Combustible</small><strong>${unidad.combustible}</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Kilometraje Actual</small><strong class="text-primary">${Number(kmActual).toLocaleString('es-VE')} KM</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Próximo Cambio Aceite</small><strong class="text-warning">${proximoCambio > 0 ? Number(proximoCambio).toLocaleString('es-VE') : 'N/A'} KM</strong></div></div></div>
                    <div class="col-6 col-md-4"><div class="card bg-light border-0"><div class="card-body text-center p-2"><small class="text-muted d-block">Faltan para Cambio</small><strong class="${faltanCambio <= 0 ? 'text-danger' : (faltanCambio <= 1000 ? 'text-warning' : 'text-success')}">${faltanCambio > 0 ? Number(faltanCambio).toLocaleString('es-VE') : '0'} KM</strong></div></div></div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3" style="font-size: 0.85rem;">
                    <span class="badge bg-primary p-2"><i class="fas fa-file-invoice me-1"></i> Órdenes: ${counts.despacho || 0}</span>
                    <span class="badge bg-warning text-dark p-2"><i class="fas fa-oil-can me-1"></i> Aceite: ${counts.aceite || 0}</span>
                    <span class="badge bg-secondary p-2"><i class="fas fa-tools me-1"></i> Mantenimiento: ${counts.mantenimiento || 0}</span>
                    <span class="badge bg-dark p-2"><i class="fas fa-exchange-alt me-1"></i> Cambios Estado: ${counts.status || 0}</span>
                    <span class="badge bg-info text-dark p-2"><i class="fas fa-list-ol me-1"></i> Total: ${historial ? historial.length : 0}</span>
                </div>
                <div id="timelineContainer" class="timeline-container" style="max-height: 500px; overflow-y: auto;">
    `;

    if (!historial || historial.length === 0) {
        html += `<div class="text-center py-5 text-muted"><i class="fas fa-box-open fa-3x mb-3"></i><h6>No se encontraron eventos</h6><p class="text-muted small">No hay historial registrado.</p></div>`;
    } else {
        historial.forEach((mov) => {
            const fechaFormateada = formatearFechaCompleta(mov.fecha);
            const tipoIcon = getTipoIcon(mov.tipo);
            const tipoLabel = getTipoLabel(mov.tipo);
            const tipoColor = getTipoColor(mov.tipo);
            const usuario = mov.usuario || 'Sistema';
            const descripcion = mov.descripcion || mov.observacion || '';

            html += `
                <div class="timeline-item mb-4" data-tipo="${mov.tipo}">
                    <div class="d-flex">
                        <div class="timeline-icon me-3" style="width: 40px; height: 40px; border-radius: 50%; background: ${tipoColor}; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="${tipoIcon}"></i>
                        </div>
                        <div class="timeline-content flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 fw-bold" style="color: ${tipoColor};">${tipoLabel} #${mov.id_evento}</h6>
                                <small class="text-muted">${fechaFormateada}</small>
                            </div>
                            <div class="timeline-description" style="font-size: 0.9rem; line-height: 1.5;">${descripcion}</div>
                            <div class="timeline-meta mt-2" style="font-size: 0.8rem; color: #6c757d;">
                                <i class="fas fa-user me-1"></i><strong>Registrado por:</strong> ${usuario}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
    }

    html += `</div></div></div>`;
    resumenDiv.innerHTML = html;

    const btnDescargarPDF = resumenDiv.querySelector('.btn-descargar-pdf');
    if (btnDescargarPDF) {
        btnDescargarPDF.addEventListener('click', function () {
            descargarHojaVidaPDF(this.dataset.idFlota, this.dataset.idUnidad);
        });
    }
}

function mostrarDetalleMovimiento(mov) {
    const resumenDiv = document.getElementById('resumenMovimientos');
    if (!resumenDiv || !mov) return;

    const tipoBadge = getTipoBadge(mov.tipo);
    const estadoBadge = getEstadoBadge(mov.estado);
    const fechaFormateada = formatearFecha(mov.fecha);

    let detallesHtml = '';
    if (mov.detalles && mov.detalles.length > 0) {
        detallesHtml = `
            <div class="mt-3">
                <h6 class="fw-bold border-bottom pb-2"><i class="fas fa-boxes me-1"></i>Productos Despachados</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light"><tr><th>Producto</th><th class="text-center">Presentación</th><th class="text-center">Cantidad</th></tr></thead>
                        <tbody>
        `;
        mov.detalles.forEach(det => {
            detallesHtml += `<tr><td><small>${det.producto}</small></td><td class="text-center"><small>${det.present_producto || '-'}</small></td><td class="text-center"><small>${det.cant_despacho}</small></td></tr>`;
        });
        detallesHtml += `</tbody></table></div></div>`;
    }

    resumenDiv.innerHTML = `
        <div class="p-3 bg-light rounded border">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>${tipoBadge}</div>
                <div>${estadoBadge}</div>
            </div>
            <div class="row g-2 small">
                <div class="col-6"><label class="text-muted mb-0">Referencia</label><p class="mb-0 fw-bold font-monospace">${mov.referencia || 'N/A'}</p></div>
                <div class="col-6"><label class="text-muted mb-0">Fecha</label><p class="mb-0">${fechaFormateada}</p></div>
                <div class="col-6"><label class="text-muted mb-0">Unidad</label><p class="mb-0 fw-bold">${mov.unidad || 'N/A'}</p></div>
                <div class="col-6"><label class="text-muted mb-0">Operador</label><p class="mb-0">${mov.operador || 'N/A'}</p></div>
                <div class="col-6"><label class="text-muted mb-0">Mecánico</label><p class="mb-0">${mov.mecanico || 'N/A'}</p></div>
                <div class="col-6"><label class="text-muted mb-0">Despachador</label><p class="mb-0">${mov.despachador || 'N/A'}</p></div>
                <div class="col-12 mt-2"><label class="text-muted mb-0">Observación</label><p class="mb-0 bg-white p-2 border rounded">${mov.observacion || 'Sin observaciones'}</p></div>
            </div>
            ${detallesHtml}
        </div>
    `;
}

function descargarHojaVidaPDF(idFlota, idUnidad) {
    const btn = document.querySelector('.btn-descargar-pdf');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando...'; }

    fetch('?url=Publico/getHistorialUnidad', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            idFlota: idFlota,
            id_institucion: instSeleccionada.movimientos
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const unidad = result.data.unidad;
                const historial = result.data.historial;
                const reporteData = {
                    items: historial,
                    counts: {
                        despacho: historial.filter(h => h.tipo === 'despacho').length,
                        mantenimiento: historial.filter(h => h.tipo === 'mantenimiento').length,
                        aceite: historial.filter(h => h.tipo === 'aceite').length,
                        status: historial.filter(h => h.tipo === 'status').length
                    }
                };
                const unidadData = {
                    id: unidad.id_unidad,
                    marca: unidad.marca,
                    modelo: unidad.modelo,
                    vin: unidad.vim_unidad || 'N/A',
                    km_actual: unidad.km_actual,
                    ultimo_cambio_aceite: unidad.ultimo_cambio_aceite,
                    proximo_cambio_aceite: unidad.proximo_cambio_aceite,
                    institucion: unidad.nombre_institucion || getNombreInstitucionPorId(instSeleccionada.movimientos)
                };
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../data/flota/historial_flota.php';
                form.target = '_blank';
                form.style.display = 'none';
                const inputReporte = document.createElement('input');
                inputReporte.type = 'hidden';
                inputReporte.name = 'reporteData';
                inputReporte.value = JSON.stringify(reporteData);
                form.appendChild(inputReporte);
                const inputUnidad = document.createElement('input');
                inputUnidad.type = 'hidden';
                inputUnidad.name = 'unidadData';
                inputUnidad.value = JSON.stringify(unidadData);
                form.appendChild(inputUnidad);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } else {
                throw new Error(result.message || 'Error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (typeof mostrarAlerta === 'function') mostrarAlerta('Error al generar PDF: ' + error.message, 'danger');
        })
        .finally(() => {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-file-pdf me-1"></i>Descargar PDF'; }
        });
}

// ============================================
// FUNCIONES AUXILIARES GLOBALES
// ============================================

function formatearFechaCompleta(fecha) {
    if (!fecha) return 'N/A';
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return fecha;
    return date.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' }) + ' ' +
        date.toLocaleTimeString('es-VE', { hour: '2-digit', minute: '2-digit' });
}

function formatearFecha(fechaStr) {
    if (!fechaStr) return 'N/A';
    try {
        const fecha = new Date(fechaStr + 'T00:00:00');
        if (isNaN(fecha.getTime())) return fechaStr;
        return fecha.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit', year: 'numeric' });
    } catch (e) { return fechaStr; }
}

function getTipoIcon(tipo) {
    const icons = { 'despacho': 'fas fa-file-invoice', 'aceite': 'fas fa-oil-can', 'mantenimiento': 'fas fa-tools', 'status': 'fas fa-exchange-alt' };
    return icons[tipo] || 'fas fa-circle';
}

function getTipoLabel(tipo) {
    const labels = { 'despacho': 'Orden de Despacho', 'aceite': 'Cambio de Aceite', 'mantenimiento': 'Mantenimiento', 'status': 'Cambio de Estado' };
    return labels[tipo] || tipo;
}

function getTipoColor(tipo) {
    const colors = { 'despacho': '#0d6efd', 'aceite': '#ffc107', 'mantenimiento': '#6c757d', 'status': '#6f42c1' };
    return colors[tipo] || '#0d6efd';
}

function getEstadoDespachoTexto(estado) {
    const estados = { 1: 'Requisición', 2: 'Aprobada por Compras', 3: 'Despachada', 4: 'Rechazada' };
    return estados[estado] ?? 'Desconocido (' + estado + ')';
}

function getEstadoClass(estado) {
    switch (parseInt(estado)) {
        case 1: return 'bg-success';
        case 2: return 'bg-danger';
        case 3: return 'bg-warning text-dark';
        case 4: return 'bg-danger';
        case 5: return 'bg-secondary';
        default: return 'bg-secondary';
    }
}

function getTipoBadge(tipo) {
    const badges = {
        'Despacho Almacén': '<span class="badge bg-primary">Despacho Almacén</span>',
        'Mantenimiento Flota': '<span class="badge bg-warning text-dark">Mantenimiento Flota</span>',
        'Cambio Aceite': '<span class="badge bg-info">Cambio Aceite</span>',
        'Actualización KM': '<span class="badge bg-secondary">Actualización KM</span>'
    };
    return badges[tipo] || `<span class="badge bg-light text-dark">${tipo || 'General'}</span>`;
}

function getEstadoBadge(estado) {
    const estados = {
        'Completado': '<span class="badge bg-success">Completado</span>',
        'Pendiente': '<span class="badge bg-warning text-dark">Pendiente</span>',
        'En Proceso': '<span class="badge bg-info">En Proceso</span>',
        'Cancelado': '<span class="badge bg-danger">Cancelado</span>',
        'Registrado': '<span class="badge bg-secondary">Registrado</span>'
    };
    return estados[estado] || `<span class="badge bg-light text-dark">${estado || 'N/A'}</span>`;
}

// ============================================
// FUNCIONES GLOBALES - RESUMEN FLOTA
// ============================================

function toggleFleetGroup(index, checkbox) {
    if (!filteredFleetSummary || !filteredFleetSummary[index]) return;

    const item = filteredFleetSummary[index];
    const key = item.marca_modelo + '|' + item.transmision + '|' + item.combustible;

    if (checkbox.checked) {
        selectedFleetGroups.add(key);
    } else {
        selectedFleetGroups.delete(key);
    }

    actualizarCheckboxSelectAll();
    actualizarResumenSeleccionados();
}

function toggleSelectAllFleet(checkbox) {
    const isChecked = checkbox.checked;

    const selectAllFleet = document.getElementById('selectAllFleet');
    const selectAllFleetHeader = document.getElementById('selectAllFleetHeader');
    if (selectAllFleet) selectAllFleet.checked = isChecked;
    if (selectAllFleetHeader) selectAllFleetHeader.checked = isChecked;

    const checkboxes = document.querySelectorAll('#tbodyFleetSummary input[type="checkbox"]');
    checkboxes.forEach(cb => { cb.checked = isChecked; });

    selectedFleetGroups.clear();
    if (isChecked) {
        allFleetSummary.forEach(item => {
            const key = item.marca_modelo + '|' + item.transmision + '|' + item.combustible;
            selectedFleetGroups.add(key);
        });
    }

    actualizarResumenSeleccionados();
}

function actualizarCheckboxSelectAll() {
    const checkboxes = document.querySelectorAll('#tbodyFleetSummary input[type="checkbox"]');
    const total = checkboxes.length;
    const checked = document.querySelectorAll('#tbodyFleetSummary input[type="checkbox"]:checked').length;
    const allChecked = total > 0 && checked === total;
    const noneChecked = checked === 0;

    const selectAllFleet = document.getElementById('selectAllFleet');
    const selectAllFleetHeader = document.getElementById('selectAllFleetHeader');
    if (selectAllFleet) selectAllFleet.checked = allChecked;
    if (selectAllFleetHeader) selectAllFleetHeader.checked = allChecked;

    if (noneChecked) {
        if (selectAllFleet) selectAllFleet.checked = false;
        if (selectAllFleetHeader) selectAllFleetHeader.checked = false;
    }
}

function actualizarResumenSeleccionados() {
    const tbodySelectedGroups = document.getElementById('tbodySelectedGroups');
    const selTotalCant = document.getElementById('selTotalCant');
    const selTotalOp = document.getElementById('selTotalOp');
    const selTotalInop = document.getElementById('selTotalInop');
    const selTotalCrit = document.getElementById('selTotalCrit');
    const selectedFleetCount = document.getElementById('selectedFleetCount');
    const btnGenerarPdfFlota = document.getElementById('btnGenerarPdfFlota');

    let totalCant = 0;
    let totalOp = 0;
    let totalInop = 0;
    let totalCrit = 0;

    const selectedItems = [];

    allFleetSummary.forEach(item => {
        const key = item.marca_modelo + '|' + item.transmision + '|' + item.combustible;
        if (selectedFleetGroups.has(key)) {
            selectedItems.push(item);
            totalCant += parseInt(item.total || 0);
            totalOp += parseInt(item.operativas || 0);
            totalInop += parseInt(item.inoperativas || 0);
            totalCrit += 0;
        }
    });

    if (selTotalCant) selTotalCant.textContent = totalCant;
    if (selTotalOp) selTotalOp.textContent = totalOp;
    if (selTotalInop) selTotalInop.textContent = totalInop;
    if (selTotalCrit) selTotalCrit.textContent = totalCrit;

    if (selectedFleetCount) {
        selectedFleetCount.textContent = selectedFleetGroups.size + ' seleccionados';
    }

    if (btnGenerarPdfFlota) {
        btnGenerarPdfFlota.disabled = selectedFleetGroups.size === 0;
    }

    if (!tbodySelectedGroups) return;

    if (selectedItems.length === 0) {
        tbodySelectedGroups.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle me-1"></i>Seleccione grupos de la tabla izquierda
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    selectedItems.forEach(item => {
        html += `
            <tr class="text-center">
                <td class="text-start"><small class="fw-bold">${item.marca_modelo}</small><br><small class="text-muted">${item.transmision || '-'} / ${item.combustible || '-'}</small></td>
                <td><small>${item.total || 0}</small></td>
                <td><small class="text-success">${item.operativas || 0}</small></td>
                <td><small class="text-danger">${item.inoperativas || 0}</small></td>
                <td><small class="text-dark">0</small></td>
            </tr>
        `;
    });
    tbodySelectedGroups.innerHTML = html;
}

function generarPdfFlota() {
    if (selectedFleetGroups.size === 0) {
        alert('Seleccione al menos un grupo para generar el PDF');
        return;
    }

    const btn = document.getElementById('btnGenerarPdfFlota');
    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando...';
    }

    const reporteData = [];
    allFleetSummary.forEach(item => {
        const key = item.marca_modelo + '|' + item.transmision + '|' + item.combustible;
        if (selectedFleetGroups.has(key)) {
            reporteData.push({
                groupName: item.marca_modelo + ' / ' + item.transmision + ' / ' + item.combustible,
                cantidad: parseInt(item.total || 0),
                operativas: parseInt(item.operativas || 0),
                inoperativas: parseInt(item.inoperativas || 0),
                criticas: 0
            });
        }
    });

    const nombreInstitucion = getNombreInstitucionPorId(instSeleccionada.resumen);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../data/flota/operatividad.php';
    form.target = '_blank';
    form.style.display = 'none';

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'reporteData';
    inputData.value = JSON.stringify(reporteData);
    form.appendChild(inputData);

    const inputInst = document.createElement('input');
    inputInst.type = 'hidden';
    inputInst.name = 'nombreInstitucion';
    inputInst.value = nombreInstitucion;
    form.appendChild(inputInst);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);

    setTimeout(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    }, 2000);
}

// ============================================
// INICIALIZACIÓN Y EVENTOS DOM
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definido.');
        return;
    }

    const movElements = {
        fechaInicio: document.getElementById('fechaInicio'),
        fechaFin: document.getElementById('fechaFin'),
        tipoMovimiento: document.getElementById('tipoMovimiento'),
        movimientosSearch: document.getElementById('movimientosSearch'),
        btnFiltrar: document.getElementById('btnFiltrar'),
        btnExportPDF: document.getElementById('btnExportPDF'),
        btnExportExcel: document.getElementById('btnExportExcel')
    };

    const flotaElements = {
        unidadesTotal: document.getElementById('unidadesTotal'),
        unidadesOperativas: document.getElementById('unidadesOperativas'),
        unidadesInoperativas: document.getElementById('unidadesInoperativas'),
        unidadesMantenimiento: document.getElementById('unidadesMantenimiento'),
        unidadesCriticas: document.getElementById('unidadesCriticas'),
        estadoFlotaUnidadesSearch: document.getElementById('estadoFlotaUnidadesSearch'),
        estadoFlotaUnidadesTable: document.getElementById('estadoFlotaUnidadesTable')
    };

    const aceiteElements = {
        aceiteOK: document.getElementById('aceiteOK'),
        aceiteProximo: document.getElementById('aceiteProximo'),
        aceiteRequerido: document.getElementById('aceiteRequerido'),
        estadoAceiteUnidadesSearch: document.getElementById('estadoAceiteUnidadesSearch'),
        estadoAceiteUnidadesTable: document.getElementById('estadoAceiteUnidadesTable'),
        btnGenerarReporteAceite: document.getElementById('btnGenerarReporteAceite'),
        aceiteFiltroRequerido: document.getElementById('aceiteFiltroRequerido'),
        aceiteFiltroProximo: document.getElementById('aceiteFiltroProximo'),
        aceiteFiltroBien: document.getElementById('aceiteFiltroBien'),
        aceiteFiltroSinRegistro: document.getElementById('aceiteFiltroSinRegistro')
    };

    const fleetElements = {
        tbodyFleetSummary: document.getElementById('tbodyFleetSummary'),
        btnGenerarPdfFlota: document.getElementById('btnGenerarPdfFlota'),
        fleetSearch: document.getElementById('fleetSearch')
    };

    const today = new Date();
    const firstDayOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
    const todayStr = today.toISOString().split('T')[0];
    const firstDayStr = firstDayOfMonth.toISOString().split('T')[0];

    if (movElements.fechaInicio && movElements.fechaFin) {
        movElements.fechaFin.value = todayStr;
        movElements.fechaInicio.value = firstDayStr;
    }

    if (fleetElements.btnGenerarPdfFlota) {
        fleetElements.btnGenerarPdfFlota.addEventListener('click', generarPdfFlota);
    }

    if (fleetElements.fleetSearch) {
        fleetElements.fleetSearch.addEventListener('input', function () {
            filtrarResumenFlota();
        });
    }

    // ============================================
    // FUNCIONES INTERNAS (definidas ANTES de usarlas)
    // ============================================

    function initMovimientosDataTable() {
        if (typeof $ === 'undefined' || !$.fn.DataTable) return;

        const tableElement = document.getElementById('movimientosTable');
        if (!tableElement) return;

        if (movimientosTable) {
            try { movimientosTable.destroy(); } catch (e) { console.warn(e); }
            movimientosTable = null;
        }

        const thead = tableElement.querySelector('thead');
        const tbody = tableElement.querySelector('tbody');
        if (thead) thead.innerHTML = '';
        if (tbody) tbody.innerHTML = '';

        const tipoMovimiento = document.getElementById('tipoMovimiento')?.value || 'todos';
        const isDespachos = tipoMovimiento === 'despachos';
        const isAceite = tipoMovimiento === 'aceite';
        const isFleet = ['mantenimientos', 'kilometraje'].includes(tipoMovimiento);

        let headerHtml = '<tr>';
        if (isAceite) {
            headerHtml += '<th style="width: 40px;">#</th><th>Tipo</th><th style="width: 100px;">Fecha</th><th style="width: 110px;">Referencia</th><th style="width: 90px;">Unidad</th><th style="width: 100px;">KM Actual</th><th style="width: 100px;">Últ. Cambio</th><th style="width: 100px;">Próx. Cambio</th><th style="width: 120px;">Estado Aceite</th>';
        } else if (isDespachos) {
            headerHtml += '<th style="width: 40px;">#</th><th>Tipo</th><th style="width: 100px;">Fecha</th><th style="width: 110px;">Referencia</th><th style="width: 90px;">Unidad</th><th>Operador</th><th>Mecánico</th><th>Despachador</th><th>Observación</th><th style="width: 100px;">Estado</th>';
        } else if (isFleet) {
            headerHtml += '<th style="width: 40px;">#</th><th>Tipo</th><th style="width: 100px;">Fecha</th><th style="width: 110px;">Referencia</th><th style="width: 90px;">Unidad</th><th>Operador</th><th>Mecánico</th><th>Observación</th><th style="width: 100px;">Estado</th>';
        } else {
            headerHtml += '<th style="width: 40px;">#</th><th>Tipo</th><th style="width: 100px;">Fecha</th><th style="width: 110px;">Referencia</th><th style="width: 90px;">Unidad</th><th>Operador</th><th>Mecánico</th><th>Despachador</th><th>Observación</th><th style="width: 100px;">Estado</th>';
        }
        headerHtml += '</tr>';
        if (thead) { thead.innerHTML = headerHtml; thead.className = 'table-dark'; }

        let columns = [];
        let columnDefs = [];

        const renderReferencia = function (data, type, row) {
            if (row.id_despacho) {
                return '<a href="javascript:void(0)" class="link-action link-despacho" data-id-despacho="' + row.id_despacho + '" title="Ver detalle del despacho"><i class="fas fa-file-invoice me-1"></i>' + (data || 'N/A') + '</a>';
            }
            if (row.id_aceite) {
                return '<a href="javascript:void(0)" class="link-action link-aceite" data-id-aceite="' + row.id_aceite + '" title="Ver detalle del cambio de aceite"><i class="fas fa-oil-can me-1"></i>' + (data || 'N/A') + '</a>';
            }
            if (row.id_mantenimiento) {
                return '<a href="javascript:void(0)" class="link-action link-mantenimiento" data-id-mantenimiento="' + row.id_mantenimiento + '" title="Ver detalle del mantenimiento"><i class="fas fa-tools me-1"></i>' + (data || 'N/A') + '</a>';
            }
            if (row.id_kilometraje) {
                return '<a href="javascript:void(0)" class="link-action link-kilometraje" data-id-kilometraje="' + row.id_kilometraje + '" title="Ver detalle del kilometraje"><i class="fas fa-tachometer-alt me-1"></i>' + (data || 'N/A') + '</a>';
            }
            return '<small class="fw-bold font-monospace">' + (data || 'N/A') + '</small>';
        };

        const renderUnidad = function (data, type, row) {
            if (row.id_flota) {
                return '<a href="javascript:void(0)" class="link-action link-unidad" data-id-flota="' + row.id_flota + '" data-id-unidad="' + (data || '') + '" title="Ver hoja de vida"><i class="fas fa-bus me-1"></i>' + (data || 'N/A') + '</a>';
            }
            return '<span class="badge bg-light text-dark border">' + (data || 'N/A') + '</span>';
        };

        if (isAceite) {
            columns = [
                { data: null, title: '#', width: '40px', className: 'text-center' },
                { data: 'tipo', title: 'Tipo' },
                { data: 'fecha', title: 'Fecha', width: '100px' },
                { data: 'referencia', title: 'Referencia', width: '110px' },
                { data: 'unidad', title: 'Unidad', width: '90px' },
                { data: 'km_actual', title: 'KM Actual', width: '100px' },
                { data: 'ultimo_cambio_km', title: 'Últ. Cambio', width: '100px' },
                { data: 'proximo_cambio_km', title: 'Próx. Cambio', width: '100px' },
                { data: 'estado_aceite', title: 'Estado Aceite', width: '120px' }
            ];
            columnDefs = [
                { targets: 0, render: (d, t, r, m) => m.row + 1 },
                { targets: 1, render: (d) => getTipoBadge(d) },
                { targets: 2, render: (d) => '<small>' + formatearFecha(d) + '</small>' },
                { targets: 3, render: renderReferencia },
                { targets: 4, render: renderUnidad },
                { targets: 5, render: (d) => { const km = parseInt(d || 0); return km > 0 ? '<small class="text-end">' + km.toLocaleString('es-VE') + ' KM</small>' : '<small class="text-muted">N/A</small>'; } },
                { targets: 6, render: (d) => { const km = parseInt(d || 0); return km > 0 ? '<small class="text-end">' + km.toLocaleString('es-VE') + ' KM</small>' : '<small class="text-muted">N/A</small>'; } },
                { targets: 7, render: (d) => { const km = parseInt(d || 0); return km > 0 ? '<small class="text-end">' + km.toLocaleString('es-VE') + ' KM</small>' : '<small class="text-muted">N/A</small>'; } },
                {
                    targets: 8,
                    render: function (data) {
                        const estado = data || 'Sin registro';
                        let badgeClass = 'bg-secondary';
                        if (estado === 'OK') badgeClass = 'bg-success';
                        else if (estado === 'Requerido') badgeClass = 'bg-danger';
                        else if (estado === 'Próximo') badgeClass = 'bg-warning text-dark';
                        return '<span class="badge ' + badgeClass + '">' + estado + '</span>';
                    }
                }
            ];
        } else if (isDespachos) {
            columns = [
                { data: null, title: '#', width: '40px', className: 'text-center' },
                { data: 'tipo', title: 'Tipo' },
                { data: 'fecha', title: 'Fecha', width: '100px' },
                { data: 'referencia', title: 'Referencia', width: '110px' },
                { data: 'unidad', title: 'Unidad', width: '90px' },
                { data: 'operador', title: 'Operador' },
                { data: 'mecanico', title: 'Mecánico' },
                { data: 'despachador', title: 'Despachador' },
                { data: 'observacion', title: 'Observación' },
                { data: 'estado', title: 'Estado', width: '100px' }
            ];
            columnDefs = [
                { targets: 0, render: (d, t, r, m) => m.row + 1 },
                { targets: 1, render: (d) => getTipoBadge(d) },
                { targets: 2, render: (d) => '<small>' + formatearFecha(d) + '</small>' },
                { targets: 3, render: renderReferencia },
                { targets: 4, render: renderUnidad },
                { targets: 5, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 6, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 7, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 8, render: (d) => '<small class="text-truncate" style="max-width: 200px;">' + (d || '-') + '</small>' },
                { targets: 9, render: (d) => getEstadoBadge(d) }
            ];
        } else if (isFleet) {
            columns = [
                { data: null, title: '#', width: '40px', className: 'text-center' },
                { data: 'tipo', title: 'Tipo' },
                { data: 'fecha', title: 'Fecha', width: '100px' },
                { data: 'referencia', title: 'Referencia', width: '110px' },
                { data: 'unidad', title: 'Unidad', width: '90px' },
                { data: 'operador', title: 'Operador' },
                { data: 'mecanico', title: 'Mecánico' },
                { data: 'observacion', title: 'Observación' },
                { data: 'estado', title: 'Estado', width: '100px' }
            ];
            columnDefs = [
                { targets: 0, render: (d, t, r, m) => m.row + 1 },
                { targets: 1, render: (d) => getTipoBadge(d) },
                { targets: 2, render: (d) => '<small>' + formatearFecha(d) + '</small>' },
                { targets: 3, render: renderReferencia },
                { targets: 4, render: renderUnidad },
                { targets: 5, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 6, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 7, render: (d) => '<small class="text-truncate" style="max-width: 200px;">' + (d || '-') + '</small>' },
                { targets: 8, render: (d) => getEstadoBadge(d) }
            ];
        } else {
            columns = [
                { data: null, title: '#', width: '40px', className: 'text-center' },
                { data: 'tipo', title: 'Tipo' },
                { data: 'fecha', title: 'Fecha', width: '100px' },
                { data: 'referencia', title: 'Referencia', width: '110px' },
                { data: 'unidad', title: 'Unidad', width: '90px' },
                { data: 'operador', title: 'Operador' },
                { data: 'mecanico', title: 'Mecánico' },
                { data: 'despachador', title: 'Despachador' },
                { data: 'observacion', title: 'Observación' },
                { data: 'estado', title: 'Estado', width: '100px' }
            ];
            columnDefs = [
                { targets: 0, render: (d, t, r, m) => m.row + 1 },
                { targets: 1, render: (d) => getTipoBadge(d) },
                { targets: 2, render: (d) => '<small>' + formatearFecha(d) + '</small>' },
                { targets: 3, render: renderReferencia },
                { targets: 4, render: renderUnidad },
                { targets: 5, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 6, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 7, render: (d) => '<small>' + (d || 'N/A') + '</small>' },
                { targets: 8, render: (d) => '<small class="text-truncate" style="max-width: 200px;">' + (d || '-') + '</small>' },
                { targets: 9, render: (d) => getEstadoBadge(d) }
            ];
        }

        movimientosTable = $('#movimientosTable').DataTable({
            data: [],
            columns: columns,
            columnDefs: columnDefs,
            order: [[2, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
            responsive: true,
            autoWidth: false
        });

        if (movElements.btnExportPDF) {
            movElements.btnExportPDF.onclick = function () {
                if (movimientosTable) movimientosTable.button('.buttons-pdf').trigger();
            };
        }
        if (movElements.btnExportExcel) {
            movElements.btnExportExcel.onclick = function () {
                if (movimientosTable) movimientosTable.button('.buttons-excel').trigger();
            };
        }
    }

    function cargarEstadoFlota() {
        fetch('?url=Publico/getEstadoFlota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_institucion: instSeleccionada.flota
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    if (flotaElements.unidadesTotal) flotaElements.unidadesTotal.textContent = data.data.total || 0;
                    if (flotaElements.unidadesOperativas) flotaElements.unidadesOperativas.textContent = data.data.operativas || 0;
                    if (flotaElements.unidadesInoperativas) flotaElements.unidadesInoperativas.textContent = data.data.inoperativas || 0;
                    if (flotaElements.unidadesMantenimiento) flotaElements.unidadesMantenimiento.textContent = data.data.en_mantenimiento || 0;
                    if (flotaElements.unidadesCriticas) flotaElements.unidadesCriticas.textContent = data.data.criticas || 0;
                }
            })
            .catch(err => console.error('Error estado flota:', err));
    }

    function cargarEstadoAceite() {
        fetch('?url=Publico/getEstadoAceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_institucion: instSeleccionada.aceite
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    if (aceiteElements.aceiteOK) aceiteElements.aceiteOK.textContent = data.data.ok || 0;
                    if (aceiteElements.aceiteProximo) aceiteElements.aceiteProximo.textContent = data.data.proximo || 0;
                    if (aceiteElements.aceiteRequerido) aceiteElements.aceiteRequerido.textContent = data.data.requerido || 0;
                }
            })
            .catch(err => console.error('Error estado aceite:', err));
    }

    function cargarResumenFlota() {
        fetch('?url=Publico/getResumenFlota', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_institucion: instSeleccionada.resumen
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data) {
                    allFleetSummary = data.data;
                    filteredFleetSummary = [...allFleetSummary];
                    renderFleetSummaryTable();
                }
            })
            .catch(err => console.error('Error resumen flota:', err));
    }

    function filtrarResumenFlota() {
        const query = fleetElements.fleetSearch?.value?.toLowerCase().trim() || '';
        if (!query) {
            filteredFleetSummary = [...allFleetSummary];
        } else {
            filteredFleetSummary = allFleetSummary.filter(item => {
                const searchStr = `${item.marca_modelo || ''} ${item.transmision || ''} ${item.combustible || ''}`.toLowerCase();
                return searchStr.includes(query);
            });
        }
        renderFleetSummaryTable();
    }

    function renderFleetSummaryTable() {
        if (!fleetElements.tbodyFleetSummary) return;
        fleetElements.tbodyFleetSummary.innerHTML = '';

        selectedFleetGroups.clear();

        filteredFleetSummary.forEach((item, index) => {
            const tr = document.createElement('tr');
            const key = item.marca_modelo + '|' + item.transmision + '|' + item.combustible;
            const isSelected = selectedFleetGroups.has(key);
            tr.innerHTML = `
                <td class="text-center">
                    <input class="form-check-input" type="checkbox" ${isSelected ? 'checked' : ''} onchange="toggleFleetGroup(${index}, this)">
                </td>
                <td><small class="fw-bold">${item.marca_modelo}</small></td>
                <td><small>${item.transmision || '-'}</small></td>
                <td><small>${item.combustible || '-'}</small></td>
                <td class="text-center"><small class="fw-bold">${item.total || 0}</small></td>
                <td class="text-center"><small class="text-success">${item.operativas || 0}</small></td>
                <td class="text-center"><small class="text-danger">${item.inoperativas || 0}</small></td>
            `;
            fleetElements.tbodyFleetSummary.appendChild(tr);
        });

        actualizarResumenSeleccionados();
    }

    function cargarMovimientos() {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.style.display = 'flex';
        const emptyState = document.getElementById('emptyState');
        if (emptyState) emptyState.style.display = 'none';
        const tipoMovimiento = document.getElementById('tipoMovimiento')?.value || 'todos';
        const requestData = {
            fechaInicio: (document.getElementById('fechaInicio')?.value) || '',
            fechaFin: (document.getElementById('fechaFin')?.value) || '',
            tipoMovimiento: tipoMovimiento,
            id_institucion: instSeleccionada.movimientos
        };
        fetch('?url=Publico/getMovimientosData', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(requestData)
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                const spinner = document.getElementById('loadingSpinner');
                if (spinner) spinner.style.display = 'none';
                if (data.success && Array.isArray(data.data)) {
                    let allData = data.data;
                    if (tipoMovimiento === 'aceite') {
                        allData = allData.map(mov => {
                            if (mov.tipo === 'Cambio Aceite' && mov.observacion) {
                                const obs = mov.observacion;
                                const kmMatch = obs.match(/KM\s*[:=]\s*([\d.,]+)/i);
                                const proxMatch = obs.match(/Pr[óo]x\s*[:=]\s*([\d.,]+)/i);
                                const kmActual = kmMatch ? parseFloat(kmMatch[1].replace(/[.,]/g, '')) : 0;
                                let kmProximo = proxMatch ? parseFloat(proxMatch[1].replace(/[.,]/g, '')) : 0;
                                if (kmProximo <= 0 && kmActual > 0) kmProximo = kmActual + 5000;
                                const kmFaltaAceite = kmProximo > 0 ? kmProximo - kmActual : 0;
                                let estadoAceite = 'Sin registro';
                                if (kmActual > 0) {
                                    if (kmFaltaAceite <= 0) estadoAceite = 'Requerido';
                                    else if (kmFaltaAceite <= 1000) estadoAceite = 'Próximo';
                                    else estadoAceite = 'OK';
                                }
                                return { ...mov, km_actual: kmActual, ultimo_cambio_km: kmActual > 0 ? kmActual : 0, proximo_cambio_km: kmProximo, estado_aceite: estadoAceite };
                            }
                            return mov;
                        });
                        allData = allData.filter(mov => {
                            if (mov.tipo === 'Cambio Aceite') {
                                const km = parseInt(mov.km_actual || 0);
                                const ultimoCambio = parseInt(mov.ultimo_cambio_km || 0);
                                return km > 0 || ultimoCambio > 0;
                            }
                            return true;
                        });
                    }
                    allMovimientos = allData;
                    if (movimientosTable) {
                        movimientosTable.clear();
                        movimientosTable.rows.add(allMovimientos);
                        movimientosTable.draw();
                    }
                    if (allMovimientos.length === 0) showMovimientosEmptyState();
                } else {
                    showMovimientosEmptyState();
                }
            })
            .catch(error => {
                console.error('Error cargando movimientos:', error);
                const spinner = document.getElementById('loadingSpinner');
                if (spinner) spinner.style.display = 'none';
                showMovimientosEmptyState();
            })
            .finally(() => {
                const spinner = document.getElementById('loadingSpinner');
                if (spinner) spinner.style.display = 'none';
            });
    }

    function applyMovimientosFilters() {
        const searchTerm = movElements.movimientosSearch?.value?.toLowerCase().trim() || '';
        if (movimientosTable) movimientosTable.search(searchTerm).draw();
    }

    function showMovimientosEmptyState() {
        try {
            const spinner = document.getElementById('loadingSpinner');
            if (spinner) spinner.style.display = 'none';
        } catch (e) { console.warn(e); }
        try {
            if (movimientosTable) movimientosTable.clear().draw();
        } catch (e) { console.error(e); }
    }

    // ============================================
    // EXPONER FUNCIONES AL ÁMBITO GLOBAL
    // ============================================
    // Esto permite que cargarInstituciones() (que se ejecuta antes) las encuentre
    window.cargarEstadoFlota = cargarEstadoFlota;
    window.cargarEstadoAceite = cargarEstadoAceite;
    window.cargarResumenFlota = cargarResumenFlota;
    window.cargarMovimientos = cargarMovimientos;
    window.initMovimientosDataTable = initMovimientosDataTable;

    // ============================================
    // FLOTA - Event listeners de cards
    // ============================================
    const flotaStatusMap = {
        '1': { titulo: 'Unidades Operativas', badgeClass: 'bg-success' },
        '2': { titulo: 'Unidades Inoperativas', badgeClass: 'bg-danger' },
        '3': { titulo: 'En Mantenimiento', badgeClass: 'bg-warning text-dark' },
        '4': { titulo: 'Unidades Críticas', badgeClass: 'bg-dark' }
    };

    document.querySelectorAll('#estadoFlota .status-card-clickable').forEach(card => {
        card.addEventListener('click', function () {
            const status = this.getAttribute('data-status');
            document.querySelectorAll('#estadoFlota .status-card-clickable').forEach(c => {
                c.classList.remove('shadow', 'border-3', 'bg-light');
            });
            this.classList.add('shadow', 'bg-light');
            currentFlotaStatus = status;
            cargarUnidadesPorEstadoFlota(status);
        });
    });

    function cargarUnidadesPorEstadoFlota(status) {
        if (flotaElements.estadoFlotaUnidadesTable) {
            flotaElements.estadoFlotaUnidadesTable.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="small text-muted mt-2">Cargando...</p></div>';
        }
        fetch('?url=Publico/getUnidadesPorEstado', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: status,
                id_institucion: instSeleccionada.flota
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    flotaUnidadesData = data.data;
                    filtrarYRenderizarUnidadesFlota();
                } else {
                    renderizarTablaFlotaVacia('No se encontraron unidades.');
                }
            })
            .catch(err => { console.error(err); renderizarTablaFlotaVacia('Error al cargar.'); });
    }

    window.cargarUnidadesPorEstadoFlota = cargarUnidadesPorEstadoFlota;

    flotaElements.estadoFlotaUnidadesSearch?.addEventListener('input', function () {
        filtrarYRenderizarUnidadesFlota();
    });

    function filtrarYRenderizarUnidadesFlota() {
        const query = flotaElements.estadoFlotaUnidadesSearch?.value?.toLowerCase().trim() || '';
        flotaUnidadesFiltered = flotaUnidadesData.filter(u => {
            if (!query) return true;
            const searchStr = `${u.id_unidad || ''} ${u.marca_unidad || ''} ${u.modelo_unidad || ''} ${u.vim_unidad || ''}`.toLowerCase();
            return searchStr.includes(query);
        });
        renderizarTablaUnidadesFlota();
    }

    function renderizarTablaUnidadesFlota() {
        if (!flotaElements.estadoFlotaUnidadesTable) return;
        if (flotaUnidadesFiltered.length === 0) {
            renderizarTablaFlotaVacia('Sin datos.');
            return;
        }
        const infoEstado = flotaStatusMap[currentFlotaStatus || '1'] || { badgeClass: 'bg-secondary', titulo: 'Unidades' };
        let html = `
            <div class="table-responsive border rounded bg-white" style="max-height: 350px;">
                <div class="table-title bg-light p-2 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-bus me-2"></i>${infoEstado.titulo}</h6>
                    <span class="badge ${infoEstado.badgeClass}">${flotaUnidadesFiltered.length} unidades</span>
                </div>
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark sticky-top"><tr><th style="width: 50px;">#</th><th>Unidad</th><th>Marca / Modelo</th><th>Placa</th><th>Transmisión</th><th>Combustible</th></tr></thead>
                    <tbody>
        `;
        flotaUnidadesFiltered.forEach((u, index) => {
            html += `<tr><td class="fw-bold text-center">${index + 1}</td><td><span class="badge bg-light text-dark border">${u.id_unidad || 'N/A'}</span></td><td><small>${u.marca_unidad || ''} ${u.modelo_unidad || ''}</small></td><td><small class="font-monospace">${u.vim_unidad || '-'}</small></td><td><small>${u.transmision || '-'}</small></td><td><small>${u.tipo_combustible || '-'}</small></td></tr>`;
        });
        html += `</tbody></table></div>`;
        flotaElements.estadoFlotaUnidadesTable.innerHTML = html;
    }

    function renderizarTablaFlotaVacia(mensaje) {
        if (!flotaElements.estadoFlotaUnidadesTable) return;
        flotaElements.estadoFlotaUnidadesTable.innerHTML = `<div class="text-center py-4 text-muted border rounded bg-light"><i class="fas fa-info-circle fa-2x mb-2 text-secondary"></i><p class="small mb-0">${mensaje}</p></div>`;
    }

    // ============================================
    // ACEITE - Event listeners de cards
    // ============================================
    const aceiteStatusMap = {
        'ok': { titulo: 'Mantenimiento OK', badgeClass: 'bg-success' },
        'proximo': { titulo: 'Próximo a Cambio', badgeClass: 'bg-warning text-dark' },
        'requerido': { titulo: 'Cambio Requerido', badgeClass: 'bg-danger' }
    };

    document.querySelectorAll('#estadoAceite .status-card-clickable').forEach(card => {
        card.addEventListener('click', function () {
            const status = this.getAttribute('data-status');
            document.querySelectorAll('#estadoAceite .status-card-clickable').forEach(c => {
                c.classList.remove('shadow', 'border-3', 'bg-light');
            });
            this.classList.add('shadow', 'bg-light');
            currentAceiteStatus = status;
            cargarUnidadesPorEstadoAceite(status);
        });
    });

    function cargarUnidadesPorEstadoAceite(status) {
        if (aceiteElements.estadoAceiteUnidadesTable) {
            aceiteElements.estadoAceiteUnidadesTable.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="small text-muted mt-2">Cargando...</p></div>';
        }
        fetch('?url=Publico/getUnidadesPorEstadoAceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: status,
                id_institucion: instSeleccionada.aceite
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    aceiteUnidadesData = data.data;
                    filtrarYRenderizarUnidadesAceite();
                } else {
                    renderizarTablaAceiteVacia('No se encontraron unidades.');
                }
            })
            .catch(err => { console.error(err); renderizarTablaAceiteVacia('Error al cargar.'); });
    }

    window.cargarUnidadesPorEstadoAceite = cargarUnidadesPorEstadoAceite;

    aceiteElements.estadoAceiteUnidadesSearch?.addEventListener('input', function () {
        filtrarYRenderizarUnidadesAceite();
    });

    function filtrarYRenderizarUnidadesAceite() {
        const query = aceiteElements.estadoAceiteUnidadesSearch?.value?.toLowerCase().trim() || '';
        aceiteUnidadesFiltered = aceiteUnidadesData.filter(u => {
            if (!query) return true;
            const searchStr = `${u.id_unidad || ''} ${u.marca_unidad || ''} ${u.modelo_unidad || ''} ${u.vim_unidad || ''}`.toLowerCase();
            return searchStr.includes(query);
        });
        renderizarTablaUnidadesAceite();
    }

    function renderizarTablaUnidadesAceite() {
        if (!aceiteElements.estadoAceiteUnidadesTable) return;
        if (aceiteUnidadesFiltered.length === 0) {
            renderizarTablaAceiteVacia('Sin datos.');
            return;
        }
        const infoEstado = aceiteStatusMap[currentAceiteStatus] || { badgeClass: 'bg-secondary', titulo: 'Unidades' };
        let html = `
            <div class="table-responsive border rounded bg-white" style="max-height: 350px;">
                <div class="table-title bg-light p-2 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-oil-can me-2"></i>${infoEstado.titulo}</h6>
                    <span class="badge ${infoEstado.badgeClass}">${aceiteUnidadesFiltered.length} unidades</span>
                </div>
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-dark sticky-top"><tr><th style="width: 50px;">#</th><th>Unidad</th><th>Marca / Modelo</th><th>Placa</th><th>Transmisión</th><th>Combustible</th><th class="text-end">KM Actual</th><th class="text-end">Últ. Cambio</th><th class="text-end">Próx. Cambio</th><th class="text-center">Estado</th></tr></thead>
                    <tbody>
        `;
        aceiteUnidadesFiltered.forEach((u, index) => {
            const kmActual = parseInt(u.km_actual || 0);
            const ultimoCambio = parseInt(u.ultimo_cambio_aceite || 0);
            const proximoCambio = parseInt(u.proximo_cambio_aceite || 0);
            const faltante = proximoCambio > 0 ? proximoCambio - kmActual : 0;
            let estadoAceite = 'Sin registro';
            let estadoClass = 'text-secondary';
            if (ultimoCambio > 0) {
                if (faltante <= 0) { estadoAceite = 'Requerido'; estadoClass = 'text-danger fw-bold'; }
                else if (faltante <= 1000) { estadoAceite = 'Próximo'; estadoClass = 'text-warning fw-bold'; }
                else { estadoAceite = 'OK'; estadoClass = 'text-success fw-bold'; }
            }
            html += `
                <tr>
                    <td class="fw-bold text-center">${index + 1}</td>
                    <td><span class="badge bg-light text-dark border">${u.id_unidad || 'N/A'}</span></td>
                    <td><small>${u.marca_unidad || ''} ${u.modelo_unidad || ''}</small></td>
                    <td><small class="font-monospace">${u.vim_unidad || '-'}</small></td>
                    <td><small>${u.transmision || '-'}</small></td>
                    <td><small>${u.tipo_combustible || '-'}</small></td>
                    <td class="text-end"><small>${kmActual > 0 ? kmActual.toLocaleString('es-VE') : 'N/A'}</small></td>
                    <td class="text-end"><small>${ultimoCambio > 0 ? ultimoCambio.toLocaleString('es-VE') : 'N/A'}</small></td>
                    <td class="text-end"><small>${proximoCambio > 0 ? proximoCambio.toLocaleString('es-VE') : 'N/A'}</small></td>
                    <td class="text-center"><span class="${estadoClass}">${estadoAceite}</span></td>
                </tr>
            `;
        });
        html += `</tbody></table></div>`;
        aceiteElements.estadoAceiteUnidadesTable.innerHTML = html;
    }

    function renderizarTablaAceiteVacia(mensaje) {
        if (!aceiteElements.estadoAceiteUnidadesTable) return;
        aceiteElements.estadoAceiteUnidadesTable.innerHTML = `<div class="text-center py-4 text-muted border rounded bg-light"><i class="fas fa-oil-can fa-2x mb-2 text-secondary"></i><p class="small mb-0">${mensaje}</p></div>`;
    }

    // ============================================
    // REPORTE PDF ACEITE
    // ============================================
    aceiteElements.btnGenerarReporteAceite?.addEventListener('click', function () {
        const filtros = [];
        if (aceiteElements.aceiteFiltroRequerido?.checked) filtros.push('Requerido');
        if (aceiteElements.aceiteFiltroProximo?.checked) filtros.push('Próximo');
        if (aceiteElements.aceiteFiltroBien?.checked) filtros.push('Bien');
        if (aceiteElements.aceiteFiltroSinRegistro?.checked) filtros.push('Sin Registro');
        if (filtros.length === 0) { alert('Seleccione al menos un filtro'); return; }
        generarReporteAceitePDF(filtros);
    });

    function generarReporteAceitePDF(filtros) {
        const btn = aceiteElements.btnGenerarReporteAceite;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Generando...';
        btn.disabled = true;

        fetch('?url=Publico/getUnidadesPorEstadoAceite', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                status: 'todos',
                id_institucion: instSeleccionada.aceite
            })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.data) {
                    alert('Error al obtener datos');
                    btn.innerHTML = originalText; btn.disabled = false;
                    return;
                }
                const allUnits = data.data;
                const categorized = { 'Requerido': [], 'Próximo': [], 'Bien': [], 'Sin Registro': [] };
                allUnits.forEach(unidad => {
                    let categoria = 'Sin Registro';
                    if (unidad.ultimo_cambio_aceite > 0) {
                        const diff = parseInt(unidad.proximo_cambio_aceite) - parseInt(unidad.km_actual);
                        if (diff <= 0) categoria = 'Requerido';
                        else if (diff <= 1000) categoria = 'Próximo';
                        else categoria = 'Bien';
                    }
                    if (filtros.includes(categoria)) categorized[categoria].push(unidad);
                });
                const items = [];
                Object.entries(categorized).forEach(([categoria, unidades]) => {
                    unidades.forEach(unidad => {
                        const kmActual = parseInt(unidad.km_actual || 0);
                        const ultimoCambio = parseInt(unidad.ultimo_cambio_aceite || 0);
                        const proximoCambio = parseInt(unidad.proximo_cambio_aceite || 0);
                        const faltante = proximoCambio > 0 ? proximoCambio - kmActual : 0;
                        let estadoAceite = 'Sin Registro';
                        if (ultimoCambio > 0) {
                            if (faltante <= 0) estadoAceite = 'Requerido';
                            else if (faltante <= 1000) estadoAceite = 'Próximo';
                            else estadoAceite = 'Bien';
                        }
                        items.push({
                            id_unidad: unidad.id_unidad, marca_unidad: unidad.marca_unidad, modelo_unidad: unidad.modelo_unidad,
                            kilometraje_actual: kmActual, ultimo_cambio_km: ultimoCambio > 0 ? ultimoCambio : null,
                            proximo_cambio_km: proximoCambio > 0 ? proximoCambio : null, estado: estadoAceite
                        });
                    });
                });
                const counts = {
                    'Requerido': categorized['Requerido'].length, 'Próximo': categorized['Próximo'].length,
                    'Bien': categorized['Bien'].length, 'Sin Registro': categorized['Sin Registro'].length
                };

                const nombreInstitucion = getNombreInstitucionPorId(instSeleccionada.aceite);

                const reporteData = {
                    items: items,
                    counts: counts,
                    filtro: filtros.join(', '),
                    nombre_institucion: nombreInstitucion
                };
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '../../data/flota/reporteaceite.php';
                form.target = '_blank';
                form.style.display = 'none';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reporteData';
                input.value = JSON.stringify(reporteData);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
                btn.innerHTML = originalText;
                btn.disabled = false;
            })
            .catch(error => {
                console.error(error);
                alert('Error al generar el reporte');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    // ============================================
    // EVENT LISTENERS GENERALES
    // ============================================
    const tipoMovimientoSelect = document.getElementById('tipoMovimiento');
    if (tipoMovimientoSelect) {
        tipoMovimientoSelect.addEventListener('change', function () {
            allMovimientos = [];
            filteredMovimientos = [];
            initMovimientosDataTable();
            cargarMovimientos();
        });
    }

    movElements.btnFiltrar?.addEventListener('click', function () {
        initMovimientosDataTable();
        cargarMovimientos();
    });

    movElements.movimientosSearch?.addEventListener('input', applyMovimientosFilters);

    // ============================================
    // ESTACIÓN - VENTAS (NO MODIFICADO)
    // ============================================
    const estacionElements = {
        fechaInicio: document.getElementById('estacionFechaInicio'),
        fechaFin: document.getElementById('estacionFechaFin'),
        estacionSelect: document.getElementById('estacionSelect'),
        estacionSearch: document.getElementById('estacionSearch'),
        btnFiltrar: document.getElementById('btnFiltrarEstacion')
    };

    let estacionTable = null;
    let allEstacionData = [];
    let estacionChart = null;

    if (estacionElements.fechaInicio && estacionElements.fechaFin) {
        estacionElements.fechaFin.value = todayStr;
        estacionElements.fechaInicio.value = firstDayStr;
    }

    function cargarEstacionesSelect() {
        fetch('?url=Publico/getVentasEstacionData', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fechaInicio: firstDayStr, fechaFin: todayStr, estacionId: 'todas' })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.estaciones) {
                    const select = estacionElements.estacionSelect;
                    if (select) {
                        data.estaciones.forEach(est => {
                            const option = document.createElement('option');
                            option.value = est.id_estacion;
                            option.textContent = est.estacion;
                            select.appendChild(option);
                        });
                    }
                }
            })
            .catch(err => console.error('Error cargando estaciones:', err));
    }

    function initEstacionDataTable() {
        if (typeof $ === 'undefined' || !$.fn.DataTable) return;

        const tableElement = document.getElementById('estacionTable');
        if (!tableElement) return;

        if (estacionTable) {
            try { estacionTable.destroy(); } catch (e) { console.warn(e); }
            estacionTable = null;
        }

        const columns = [
            { data: null, title: '#', width: '40px', className: 'text-center' },
            { data: 'fecha', title: 'Fecha', width: '100px' },
            { data: 'estacion', title: 'Estación' },
            { data: 'total_litros', title: 'Total Litros', width: '100px' },
            { data: 'total_ventas', title: 'Total Ventas', width: '100px' },
            { data: 'vendedores', title: 'Vendedores' },
            { data: 'tipos_vehiculo', title: 'Tipos de Vehículo' }
        ];

        const columnDefs = [
            { targets: 0, render: (d, t, r, m) => m.row + 1 },
            { targets: 1, render: (d) => '<small>' + formatearFecha(d) + '</small>' },
            { targets: 2, render: (d) => '<strong>' + (d || 'N/A') + '</strong>' },
            { targets: 3, render: (d) => '<small class="text-end fw-bold">' + (parseFloat(d || 0)).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L</small>' },
            { targets: 4, render: (d) => '<small class="text-center fw-bold">' + (d || 0) + '</small>' },
            {
                targets: 5,
                render: function (data) {
                    if (!data || data.length === 0) return '<small class="text-muted">-</small>';
                    let html = '<div class="small">';
                    data.forEach(v => {
                        html += '<div><strong>' + v.nombre + ':</strong> ' + v.litros.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L (' + v.ventas + ' ventas)</div>';
                    });
                    html += '</div>';
                    return html;
                }
            },
            {
                targets: 6,
                render: function (data) {
                    if (!data || data.length === 0) return '<small class="text-muted">-</small>';
                    let html = '<div class="small">';
                    data.forEach(v => {
                        html += '<div><strong>' + v.tipo + ':</strong> ' + v.cantidad + ' unidades, ' + v.litros.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L</div>';
                    });
                    html += '</div>';
                    return html;
                }
            }
        ];

        estacionTable = $('#estacionTable').DataTable({
            data: [],
            columns: columns,
            columnDefs: columnDefs,
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: { url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json' },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>rtip',
            responsive: true,
            autoWidth: false
        });
    }

    function cargarVentasEstacion() {
        const fechaInicio = estacionElements.fechaInicio?.value || firstDayStr;
        const fechaFin = estacionElements.fechaFin?.value || todayStr;
        const estacionId = estacionElements.estacionSelect?.value || 'todas';

        fetch('?url=Publico/getVentasEstacionData', {
            method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ fechaInicio, fechaFin, estacionId })
        })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    allEstacionData = data.data;
                    if (estacionTable) {
                        estacionTable.clear();
                        estacionTable.rows.add(allEstacionData);
                        estacionTable.draw();
                    }
                    renderEstacionChart();
                } else {
                    if (estacionTable) {
                        estacionTable.clear().draw();
                    }
                    renderEstacionChart();
                }
            })
            .catch(error => {
                console.error('Error cargando ventas de estación:', error);
                if (estacionTable) {
                    estacionTable.clear().draw();
                }
                renderEstacionChart();
            });
    }

    function applyEstacionFilters() {
        const searchTerm = estacionElements.estacionSearch?.value?.toLowerCase().trim() || '';
        if (estacionTable) estacionTable.search(searchTerm).draw();
    }

    function renderEstacionChart() {
        const ctx = document.getElementById('estacionChart');
        const emptyMsg = document.getElementById('estacionChartEmpty');
        const chartType = document.getElementById('estacionChartType')?.value || 'litros_dia';

        if (!ctx || !allEstacionData || allEstacionData.length === 0) {
            if (ctx) ctx.style.display = 'none';
            if (emptyMsg) emptyMsg.style.display = 'block';
            return;
        }

        if (emptyMsg) emptyMsg.style.display = 'none';
        ctx.style.display = 'block';

        if (estacionChart) {
            estacionChart.destroy();
        }

        let labels = [];
        let data = [];
        let label = '';
        let backgroundColor = [];
        let borderColor = [];

        switch (chartType) {
            case 'litros_dia':
                const litrosPorDia = {};
                allEstacionData.forEach(item => {
                    const fecha = item.fecha;
                    if (!litrosPorDia[fecha]) litrosPorDia[fecha] = 0;
                    litrosPorDia[fecha] += parseFloat(item.total_litros || 0);
                });
                labels = Object.keys(litrosPorDia).sort();
                data = labels.map(f => litrosPorDia[f]);
                label = 'Litros Vendidos';
                backgroundColor = 'rgba(54, 162, 235, 0.6)';
                borderColor = 'rgba(54, 162, 235, 1)';
                break;

            case 'ventas_dia':
                const ventasPorDia = {};
                allEstacionData.forEach(item => {
                    const fecha = item.fecha;
                    if (!ventasPorDia[fecha]) ventasPorDia[fecha] = 0;
                    ventasPorDia[fecha] += parseInt(item.total_ventas || 0);
                });
                labels = Object.keys(ventasPorDia).sort();
                data = labels.map(f => ventasPorDia[f]);
                label = 'Cantidad de Ventas';
                backgroundColor = 'rgba(75, 192, 192, 0.6)';
                borderColor = 'rgba(75, 192, 192, 1)';
                break;

            case 'litros_tipo_vehiculo':
                const litrosPorTipo = {};
                allEstacionData.forEach(item => {
                    if (item.tipos_vehiculo) {
                        item.tipos_vehiculo.forEach(tv => {
                            const tipo = tv.tipo;
                            if (!litrosPorTipo[tipo]) litrosPorTipo[tipo] = 0;
                            litrosPorTipo[tipo] += parseFloat(tv.litros || 0);
                        });
                    }
                });
                labels = Object.keys(litrosPorTipo);
                data = labels.map(t => litrosPorTipo[t]);
                label = 'Litros por Tipo de Vehículo';
                backgroundColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%, 0.6)`);
                borderColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 40%, 1)`);
                break;

            case 'ventas_vendedor':
                const ventasPorVendedor = {};
                allEstacionData.forEach(item => {
                    if (item.vendedores) {
                        item.vendedores.forEach(v => {
                            const nombre = v.nombre;
                            if (!ventasPorVendedor[nombre]) ventasPorVendedor[nombre] = 0;
                            ventasPorVendedor[nombre] += parseInt(v.ventas || 0);
                        });
                    }
                });
                labels = Object.keys(ventasPorVendedor);
                data = labels.map(v => ventasPorVendedor[v]);
                label = 'Ventas por Vendedor';
                backgroundColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%, 0.6)`);
                borderColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 40%, 1)`);
                break;

            case 'comparativo_estaciones':
                const litrosPorEstacion = {};
                allEstacionData.forEach(item => {
                    const estacion = item.estacion;
                    if (!litrosPorEstacion[estacion]) litrosPorEstacion[estacion] = 0;
                    litrosPorEstacion[estacion] += parseFloat(item.total_litros || 0);
                });
                labels = Object.keys(litrosPorEstacion);
                data = labels.map(e => litrosPorEstacion[e]);
                label = 'Litros por Estación';
                backgroundColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 60%, 0.6)`);
                borderColor = labels.map((_, i) => `hsl(${i * 360 / labels.length}, 70%, 40%, 1)`);
                break;
        }

        const formattedLabels = labels.map(l => {
            if (chartType === 'litros_dia' || chartType === 'ventas_dia') {
                const d = new Date(l + 'T00:00:00');
                return d.toLocaleDateString('es-VE', { day: '2-digit', month: '2-digit' });
            }
            return l;
        });

        const isBar = ['litros_tipo_vehiculo', 'ventas_vendedor', 'comparativo_estaciones'].includes(chartType);

        estacionChart = new Chart(ctx, {
            type: isBar ? 'bar' : 'line',
            data: {
                labels: formattedLabels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: Array.isArray(backgroundColor) ? backgroundColor : backgroundColor,
                    borderColor: Array.isArray(borderColor) ? borderColor : borderColor,
                    borderWidth: 2,
                    fill: !isBar,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let value = context.raw;
                                if (chartType === 'litros_dia' || chartType === 'litros_tipo_vehiculo' || chartType === 'comparativo_estaciones') {
                                    return value.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L';
                                }
                                return value + ' ventas';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                if (chartType === 'litros_dia' || chartType === 'litros_tipo_vehiculo' || chartType === 'comparativo_estaciones') {
                                    return value.toLocaleString('es-VE') + ' L';
                                }
                                return value;
                            }
                        }
                    }
                }
            }
        });
    }

    document.getElementById('estacionChartType')?.addEventListener('change', renderEstacionChart);

    estacionElements.btnFiltrar?.addEventListener('click', cargarVentasEstacion);
    estacionElements.estacionSearch?.addEventListener('input', applyEstacionFilters);
    estacionElements.estacionSelect?.addEventListener('change', cargarVentasEstacion);

    cargarEstacionesSelect();
    initEstacionDataTable();
    cargarVentasEstacion();

    // ============================================
    // CARGA INICIAL: PRIMERO INSTITUCIONES, LUEGO EL RESTO
    // ============================================
    (async function () {
        await cargarInstituciones();
        cargarEstadoFlota();
        cargarEstadoAceite();
        cargarResumenFlota();
        initMovimientosDataTable();
        cargarMovimientos();
    })();
});

// ============================================
// EVENT DELEGATION GLOBAL - Cada link a su acción
// ============================================

$(document).off('click', '.link-despacho').on('click', '.link-despacho', function (e) {
    e.preventDefault(); e.stopPropagation();
    const id = $(this).data('id-despacho');
    if (id) cargarDetalleOrden(id);
    return false;
});

$(document).off('click', '.link-aceite').on('click', '.link-aceite', function (e) {
    e.preventDefault(); e.stopPropagation();
    const id = $(this).data('id-aceite');
    if (id) cargarDetalleAceite(id);
    return false;
});

$(document).off('click', '.link-mantenimiento').on('click', '.link-mantenimiento', function (e) {
    e.preventDefault(); e.stopPropagation();
    const id = $(this).data('id-mantenimiento');
    if (id) cargarDetalleMantenimiento(id);
    return false;
});

$(document).off('click', '.link-kilometraje').on('click', '.link-kilometraje', function (e) {
    e.preventDefault(); e.stopPropagation();
    const id = $(this).data('id-kilometraje');
    if (id) cargarDetalleKilometraje(id);
    return false;
});

$(document).off('click', '.link-unidad').on('click', '.link-unidad', function (e) {
    e.preventDefault(); e.stopPropagation();
    const idFlota = $(this).data('id-flota');
    const idUnidad = $(this).data('id-unidad');
    if (idFlota) cargarHistorialUnidad(idFlota, idUnidad);
    return false;
});

$(document).off('click', '#movimientosTable tbody tr').on('click', '#movimientosTable tbody tr', function (e) {
    if ($(e.target).is('a') || $(e.target).closest('a').length) return;
    if (!movimientosTable) return;
    const data = movimientosTable.row(this).data();
    if (data) {
        $('#movimientosTable tbody tr').removeClass('table-active');
        $(this).addClass('table-active');
        mostrarDetalleMovimiento(data);
    }
});

$(document).off('click', '#fleetSummaryTable tbody tr').on('click', '#fleetSummaryTable tbody tr', function (e) {
    if ($(e.target).is('input[type="checkbox"]') || $(e.target).closest('input[type="checkbox"]').length) return;

    const checkbox = $(this).find('input[type="checkbox"]').first();
    if (checkbox.length) {
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    }
});

$(document).off('click', '.nav-btn[data-target]').on('click', '.nav-btn[data-target]', function (e) {
    e.preventDefault(); e.stopPropagation();
    const targetId = $(this).attr('data-target');
    const targetSection = document.querySelector(targetId);
    $('.nav-btn').removeClass('active');
    $(this).addClass('active');
    if (targetSection) {
        const yOffset = -80;
        const y = targetSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
        window.scrollTo({ top: y, behavior: 'smooth' });
    }
    return false;
});