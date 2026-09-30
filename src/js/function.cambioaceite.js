let tableAceite;

// ID de la institución activa (leído del hidden input en la vista)
const idInstitucionAceite = document.getElementById('id_institucion_aceite')?.value || 1;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar la DataTable
    if (!$.fn.DataTable.isDataTable('#tableAceite')) {
        initAceiteDataTable();
    }

    // Event Listeners para los formularios de los modales
    document.querySelector('#formKilometraje').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(this, 'Flota/setKilometraje', 'Kilometraje actualizado correctamente.', 'modalKilometraje');
    });

    document.querySelector('#formAceite').addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(this, 'Flota/setCambioAceite', 'Cambio de aceite registrado correctamente.', 'modalAceite');
    });

    // Event Listeners para las tarjetas de estado
    document.querySelectorAll('.status-card').forEach(card => {
        card.addEventListener('click', function () {
            const statusToFilter = this.dataset.status;
            const smallBox = this.querySelector('.small-box');

            // Si la tarjeta ya está activa, limpiar el filtro
            if (smallBox.classList.contains('active-filter')) {
                smallBox.classList.remove('active-filter');
                tableAceite.column(6).search('').draw();
            } else {
                // Si no, aplicar el filtro
                filterTableByStatus(statusToFilter);
            }
        });
    });

    // Event Listeners para el modal de eliminación
    $('#tableAceite tbody').on('click', 'button[data-target="#modalDeleteRecord"]', function () {
        const idFlota = $(this).data('id-flota');
        const idUnidad = $(this).data('id-unidad');

        const modalDelete = document.getElementById('modalDeleteRecord');
        modalDelete.querySelector('#unidad_delete_label').textContent = idUnidad;
        modalDelete.querySelector('#unidad_delete_label_confirm').textContent = idUnidad;
        modalDelete.querySelector('#btnDeleteKilometraje').dataset.idFlota = idFlota;
        modalDelete.querySelector('#btnDeleteAceite').dataset.idFlota = idFlota;
    });

    document.getElementById('btnDeleteKilometraje').addEventListener('click', function () {
        submitDeleteRecord(this.dataset.idFlota, 'kilometraje');
    });

    document.getElementById('btnDeleteAceite').addEventListener('click', function () {
        submitDeleteRecord(this.dataset.idFlota, 'aceite');
    });
});

function initAceiteDataTable() {
    tableAceite = $('#tableAceite').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": {
            "url": base_url + "src/plugins/js/es_es.json"
        },
        "ajax": {
            "url": base_url + "Flota/getAceiteStatus",
            "data": function (d) {
                d.id_institucion = idInstitucionAceite;
            },
            "dataSrc": function (json) {
                document.querySelector('#card-requeridas').textContent = json.requeridas || 0;
                document.querySelector('#card-proximas').textContent = json.proximas || 0;
                document.querySelector('#card-optimas').textContent = json.ok || 0;
                return json.data;
            }
        },
        "columns": [
            { 
                "data": "id_unidad", 
                "render": (data, type, row) => {
                    // Detectar si estamos en el Taller para redirigir el historial
                    const esTaller = (idInstitucionAceite == 2);
                    const urlHistorial = esTaller 
                        ? `${base_url}flota/tallerhistorial/${row.id_flota}` 
                        : `${base_url}flota/historialunidad/${row.id_flota}`;
                    
                    return `<a href="${urlHistorial}" class="font-weight-bold">${row.id_unidad}</a><br><small class="text-muted">${row.marca_unidad} ${row.modelo_unidad}</small>`;
                } 
            },
            { "data": "kilometraje_actual", "render": data => (data || 0).toLocaleString('es-VE') },
            { "data": "ultimo_cambio_km", "render": data => (data || 0).toLocaleString('es-VE') },
            { "data": "fecha_ultimo_cambio", "render": data => (!data || data === '0000-00-00') ? '<span class="text-muted">N/A</span>' : new Date(data + 'T00:00:00').toLocaleDateString('es-VE') },
            { "data": "proximo_cambio_km", "render": data => (data || 0).toLocaleString('es-VE') },
            {
                "data": "km_restantes",
                "className": "font-weight-bold",
                "render": function (data) {
                    const km = parseInt(data, 10);
                    const colorClass = km < 0 ? 'text-danger' : '';
                    return `<span class="${colorClass}">${km.toLocaleString('es-VE')}</span>`;
                }
            },
            { "data": "estado", "className": "text-center", "render": data => getStatusBadge(data) },
            { "data": null, "orderable": false, "className": "text-center", "render": (data, type, row) => getActionButtons(row) }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[5, "asc"]],
        "dom": "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    });
}

function getStatusBadge(status) {
    switch (status) {
        case 'Requerido':
            return '<span class="badge badge-danger">Requerido</span>';
        case 'Próximo':
            return '<span class="badge badge-warning">Próximo</span>';
        case 'Bien':
            return '<span class="badge badge-success">Bien</span>';
        case 'sin_registro':
            return '<span class="badge badge-secondary">Sin Registro</span>';
        default:
            return '<span class="badge badge-light">N/A</span>';
    }
}

function getActionButtons(row) {
    const kmActual = row.kilometraje_actual || 0;
    return `
        <div class="btn-group btn-group-sm">
            <button onclick="openKmModal(${row.id_flota}, '${row.id_unidad}', ${kmActual})" class="btn btn-info" title="Actualizar KM">
                <i class="fas fa-tachometer-alt"></i>
            </button>
            <button onclick="openAceiteModal(${row.id_flota}, '${row.id_unidad}', ${kmActual})" class="btn btn-success" title="Registrar Cambio Aceite">
                <i class="fas fa-oil-can"></i>
            </button>
            <button type="button" class="btn btn-danger" title="Eliminar Registro"
                    data-toggle="modal" data-target="#modalDeleteRecord"
                    data-id-flota="${row.id_flota}" data-id-unidad="${row.id_unidad}">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    `;
}

function filterTableByStatus(status) {
    document.querySelectorAll('.status-card').forEach(card => {
        card.classList.remove('active-filter');
    });

    const selectedCard = document.querySelector(`.status-card[data-status="${status}"] .small-box`);
    if (selectedCard) {
        selectedCard.classList.add('active-filter');
    }

    tableAceite.column(6).search(status, true, false).draw();
}

function openKmModal(idFlota, idUnidad, kmActual) {
    const modal = document.querySelector('#modalKilometraje');
    modal.querySelector('#id_flota_km').value = idFlota;
    modal.querySelector('#unidad_km_label').textContent = idUnidad;
    modal.querySelector('#kilometraje_actual').value = kmActual > 0 ? kmActual : '';
    $('#modalKilometraje').modal('show');
}

function openAceiteModal(idFlota, idUnidad, kmActual) {
    const modal = document.querySelector('#modalAceite');
    modal.querySelector('#id_flota_aceite').value = idFlota;
    modal.querySelector('#unidad_aceite_label').textContent = idUnidad;
    modal.querySelector('#kilometraje_anterior_aceite').value = kmActual;
    modal.querySelector('#kilometraje_cambio').value = kmActual > 0 ? kmActual : '';
    modal.querySelector('#kilometraje_cambio').min = kmActual;
    modal.querySelector('#fecha_cambio_aceite').valueAsDate = new Date();
    $('#modalAceite').modal('show');
}

async function submitForm(form, url, successMessage, modalId) {
    const modal = document.getElementById(modalId);
    const button = modal.querySelector(`button[form="${form.id}"]`);
    const originalButtonText = button.innerHTML;
    button.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Guardando...`;
    button.disabled = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(base_url + url, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            notifi(successMessage, 'success');
            $(`#${modalId}`).modal('hide');
            form.reset();
            tableAceite.ajax.reload(null, false);
        } else {
            notifi(result.message || 'Ocurrió un error.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        notifi('Error de conexión al guardar.', 'error');
    } finally {
        button.innerHTML = originalButtonText;
        button.disabled = false;
    }
}

async function submitDeleteRecord(idFlota, recordType) {
    const modal = document.querySelector('#modalDeleteRecord');
    const btnKilometraje = modal.querySelector('#btnDeleteKilometraje');
    const btnAceite = modal.querySelector('#btnDeleteAceite');
    const originalTextKm = btnKilometraje.innerHTML;
    const originalTextAceite = btnAceite.innerHTML;

    Swal.fire({
        title: '¿Estás seguro?',
        text: `Esta acción eliminará el último registro de ${recordType} y no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            btnKilometraje.disabled = true;
            btnAceite.disabled = true;
            const activeBtn = recordType === 'kilometraje' ? btnKilometraje : btnAceite;
            activeBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Eliminando...`;

            try {
                const formData = new FormData();
                formData.append('id_flota', idFlota);
                formData.append('record_type', recordType);

                const response = await fetch(base_url + 'Flota/deleteRecord', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    notifi(result.message, 'success');
                    $('#modalDeleteRecord').modal('hide');
                    tableAceite.ajax.reload(null, false);
                } else {
                    notifi(result.message, 'error');
                }
            } catch (error) {
                notifi('Error de conexión al eliminar.', 'error');
            } finally {
                btnKilometraje.innerHTML = originalTextKm;
                btnAceite.innerHTML = originalTextAceite;
                btnKilometraje.disabled = false;
                btnAceite.disabled = false;
            }
        }
    });
}

function notifi(message, type) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}

/**
 * Función para generar el reporte de estado de aceite en PDF.
 */
function fntGenerarReporteAceite() {
    // 1. Obtener los valores de los checkboxes seleccionados
    const filtros = [];
    if (document.getElementById('checkRequerido').checked) filtros.push('Requerido');
    if (document.getElementById('checkProximo').checked) filtros.push('Próximo');
    if (document.getElementById('checkBien').checked) filtros.push('Bien');
    if (document.getElementById('checkSinRegistro').checked) filtros.push('Sin Registro');

    if (filtros.length === 0) {
        notifi('Debe seleccionar al menos un estado para generar el reporte.', 'warning');
        return;
    }

    const filtroString = filtros.join(',');

    Swal.fire({
        title: 'Generando Reporte...',
        text: 'Por favor espere mientras se procesan los datos.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    // 2. Enviar el filtro y el id_institucion al backend
    const params = new URLSearchParams();
    params.append('filtro', filtroString);
    params.append('id_institucion', idInstitucionAceite);

    fetch(base_url + 'Flota/getReporteAceiteData', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 3. Crear formulario dinámico para enviar los datos al PDF
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = base_url + 'data/flota/reporteaceite.php';
                form.target = '_blank';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reporteData';
                input.value = JSON.stringify(data);

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);

                Swal.close();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(error => {
            console.error("Error en fntGenerarReporteAceite:", error);
            Swal.fire('Error', 'Ocurrió un error inesperado al generar el reporte.', 'error');
        });
}