/**
 * Public Movimientos Dashboard - JavaScript
 * Funcionalidad para el dashboard público de consulta de movimientos
 */

let movimientosData = [];
let fleetSummaryData = [];
let currentFilter = 'todos';
let currentFechaInicio = '';
let currentFechaFin = '';

document.addEventListener('DOMContentLoaded', function () {
    // Set default dates (last 30 days)
    const today = new Date();
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    currentFechaInicio = formatDateForInput(thirtyDaysAgo);
    currentFechaFin = formatDateForInput(today);

    document.getElementById('fechaInicio').value = currentFechaInicio;
    document.getElementById('fechaFin').value = currentFechaFin;

    // Load initial data
    cargarMovimientos();
    cargarEstadoFlota();
    cargarEstadoAceite();
    cargarResumenFlota();

    // Event listeners
    document.getElementById('btnFiltrar').addEventListener('click', cargarMovimientos);
    document.getElementById('btnExportarPDF').addEventListener('click', exportarPDF);
    document.getElementById('tipoMovimiento').addEventListener('change', function (e) {
        currentFilter = e.target.value;
        cargarMovimientos();
    });

    // Fleet search
    document.getElementById('fleetSearch').addEventListener('input', function (e) {
        filtrarTablaFlota(e.target.value);
    });

    // Enter key on date inputs
    document.getElementById('fechaInicio').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') cargarMovimientos();
    });
    document.getElementById('fechaFin').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') cargarMovimientos();
    });
});

function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function cargarMovimientos() {
    currentFechaInicio = document.getElementById('fechaInicio').value;
    currentFechaFin = document.getElementById('fechaFin').value;
    currentFilter = document.getElementById('tipoMovimiento').value;

    if (!currentFechaInicio || !currentFechaFin) {
        mostrarAlerta('Por favor seleccione un rango de fechas válido', 'warning');
        return;
    }

    if (new Date(currentFechaInicio) > new Date(currentFechaFin)) {
        mostrarAlerta('La fecha de inicio no puede ser mayor a la fecha fin', 'warning');
        return;
    }

    // Show loading
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('tablaMovimientos').style.display = 'none';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('btnExportarPDF').disabled = true;

    const data = {
        fechaInicio: currentFechaInicio,
        fechaFin: currentFechaFin,
        tipoMovimiento: currentFilter
    };

    fetch('?url=Publico/getMovimientosData', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(result => {
            document.getElementById('loadingSpinner').style.display = 'none';

            if (result.success) {
                movimientosData = result.data;
                actualizarResumenMovimientos(result.resumen);
                renderizarTabla(movimientosData);

                if (movimientosData.length > 0) {
                    document.getElementById('tablaMovimientos').style.display = 'table';
                    document.getElementById('btnExportarPDF').disabled = false;
                } else {
                    document.getElementById('emptyState').style.display = 'block';
                    document.getElementById('btnExportarPDF').disabled = true;
                }
            } else {
                mostrarAlerta('Error al cargar datos: ' + result.message, 'danger');
                document.getElementById('emptyState').style.display = 'block';
            }
        })
        .catch(error => {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
            mostrarAlerta('Error de conexión: ' + error.message, 'danger');
            console.error('Error:', error);
        });
}

function cargarEstadoFlota() {
    fetch('?url=Publico/getEstadoFlota', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                document.getElementById('unidadesTotal').textContent = result.data.total || 0;
                document.getElementById('unidadesOperativas').textContent = result.data.operativas || 0;
                document.getElementById('unidadesInoperativas').textContent = result.data.inoperativas || 0;
                document.getElementById('unidadesMantenimiento').textContent = result.data.en_mantenimiento || 0;
                document.getElementById('unidadesCriticas').textContent = result.data.criticas || 0;
            }
        })
        .catch(error => {
            console.error('Error cargando estado de flota:', error);
        });
}

function cargarEstadoAceite() {
    fetch('?url=Publico/getEstadoAceite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                document.getElementById('aceiteRequerido').textContent = result.data.requerido || 0;
                document.getElementById('aceiteProximo').textContent = result.data.proximo || 0;
                document.getElementById('aceiteOK').textContent = result.data.ok || 0;
            }
        })
        .catch(error => {
            console.error('Error cargando estado de aceite:', error);
        });
}

function cargarResumenFlota() {
    fetch('?url=Publico/getResumenFlota', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                fleetSummaryData = result.data;
                renderizarTablaFlota(fleetSummaryData);
            }
        })
        .catch(error => {
            console.error('Error cargando resumen de flota:', error);
        });
}

function actualizarResumenMovimientos(resumen) {
    // Old stat cards removed - fleet status and oil change cards are updated by separate functions
    // This function is kept for compatibility but does nothing
    console.log('Movimientos resumen:', resumen);
}

function renderizarTabla(data) {
    const tbody = document.getElementById('tbodyMovimientos');
    tbody.innerHTML = '';

    data.forEach((mov, index) => {
        const row = document.createElement('tr');
        row.className = getTipoClass(mov.tipo);

        const estadoClass = getEstadoClass(mov.estado);
        const fechaFormateada = formatearFecha(mov.fecha);

        row.innerHTML = `
            <td>${index + 1}</td>
            <td class="tipo-movimiento">${mov.tipo}</td>
            <td>${fechaFormateada}</td>
            <td>${mov.referencia}</td>
            <td>${mov.unidad}</td>
            <td>${mov.operador}</td>
            <td>${mov.mecanico}</td>
            <td>${mov.despachador}</td>
            <td class="observacion-cell">${mov.observacion}</td>
            <td><span class="estado-badge ${estadoClass}">${mov.estado}</span></td>
        `;

        tbody.appendChild(row);
    });
}

function renderizarTablaFlota(data) {
    const tbody = document.getElementById('tbodyFleetSummary');
    tbody.innerHTML = '';

    data.forEach((item, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.marca_modelo}</td>
            <td>${item.transmision}</td>
            <td>${item.combustible}</td>
            <td class="text-center fw-bold">${item.total}</td>
            <td class="text-center text-success fw-bold">${item.operativas}</td>
            <td class="text-center text-danger fw-bold">${item.inoperativas}</td>
        `;
        tbody.appendChild(row);
    });
}

function filtrarTablaFlota(searchTerm) {
    const term = searchTerm.toLowerCase().trim();
    const filtered = fleetSummaryData.filter(item =>
        item.marca_modelo.toLowerCase().includes(term) ||
        item.transmision.toLowerCase().includes(term) ||
        item.combustible.toLowerCase().includes(term) ||
        item.estado.toLowerCase().includes(term)
    );
    renderizarTablaFlota(filtered);
}

function getTipoClass(tipo) {
    const clases = {
        'Despacho Almacén': 'tipo-despacho',
        'Venta Estación': 'tipo-venta',
        'Mantenimiento Flota': 'tipo-mantenimiento',
        'Compra/Orden': 'tipo-compra',
        'Cambio Aceite': 'tipo-aceite',
        'Actualización KM': 'tipo-kilometraje'
    };
    return clases[tipo] || '';
}

function getEstadoClass(estado) {
    const estadoLower = estado.toLowerCase();
    if (estadoLower.includes('complet') || estadoLower.includes('despachada') || estadoLower.includes('terminado') || estadoLower.includes('aprobada') || estadoLower === 'registrado') {
        return 'estado-completado';
    }
    if (estadoLower.includes('pendiente') || estadoLower.includes('proceso') || estadoLower.includes('requisicion')) {
        return 'estado-pendiente';
    }
    if (estadoLower.includes('rechazad') || estadoLower.includes('cancelad') || estadoLower.includes('anulad')) {
        return 'estado-rechazado';
    }
    return '';
}

function formatearFecha(fecha) {
    const date = new Date(fecha);
    if (isNaN(date.getTime())) return fecha;
    return date.toLocaleDateString('es-VE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

function exportarPDF() {
    if (movimientosData.length === 0) {
        mostrarAlerta('No hay datos para exportar', 'warning');
        return;
    }

    // Show loading on button
    const btn = document.getElementById('btnExportarPDF');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando PDF...';
    btn.disabled = true;

    const data = {
        movimientos: movimientosData,
        fechaInicio: currentFechaInicio,
        fechaFin: currentFechaFin,
        tipoMovimiento: currentFilter
    };

    fetch('?url=Publico/exportarPDF', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
        .then(response => {
            btn.innerHTML = originalText;
            btn.disabled = false;

            if (response.ok) {
                // It's a PDF stream, create blob and download
                return response.blob();
            } else {
                return response.json().then(err => { throw new Error(err.message || 'Error al generar PDF'); });
            }
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `movimientos_${currentFechaInicio}_a_${currentFechaFin}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            mostrarAlerta('PDF generado y descargado correctamente', 'success');
        })
        .catch(error => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            mostrarAlerta('Error al generar PDF: ' + error.message, 'danger');
            console.error('Error PDF:', error);
        });
}

function mostrarAlerta(mensaje, tipo = 'info') {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-dynamic');
    existingAlerts.forEach(a => a.remove());

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show alert-dynamic`;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.style.maxWidth = '500px';
    alertDiv.innerHTML = `
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.body.appendChild(alertDiv);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Utility function to format numbers
function formatearNumero(num, decimales = 2) {
    if (num === null || num === undefined || num === '') return '0';
    return Number(num).toLocaleString('es-VE', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales
    });
}