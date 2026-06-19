let tableFlota;
let allFlotaData = []; // Almacenará todos los datos de la flota para los filtros

const statusMap = {
    0: { text: 'Desincorporada', color: 'badge-secondary' },
    1: { text: 'Operativa', color: 'badge-success' },
    2: { text: 'Inoperativa', color: 'badge-warning' },
    3: { text: 'Mantenimiento', color: 'badge-info' },
    4: { text: 'Por Desincorporar', color: 'badge-purple' },
    5: { text: 'Crítica', color: 'badge-danger' }
};

document.addEventListener('DOMContentLoaded', function () {
    // Cargar datos para selects
    loadSelects();

    // Inicializar DataTable
    tableFlota = initFlotaDynamicTable({
        onLoad: function (data) {
            allFlotaData = data || [];
            setupReportSection();
        }
    });

    // Manejar envío del formulario
    const formFlota = document.querySelector("#formFlota");
    formFlota.onsubmit = function (e) {
        e.preventDefault();

        const formData = new FormData(formFlota);
        const url = base_url + "Flota/setUnidad";

        fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(objData => {
                if (objData.success) {
                    $('#modalFlota').modal('hide');
                    if (tableFlota && typeof tableFlota.reload === 'function') {
                        tableFlota.reload();
                    }
                    notifi(objData.message, "success");
                } else {
                    notifi(objData.message, "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notifi("Ocurrió un error en el sistema.", "error");
            });
    };

    // Manejar cambio de estado
    const formStatus = document.querySelector("#formStatus");
    formStatus.onsubmit = function (e) {
        e.preventDefault();

        const formData = new FormData(formStatus);
        const url = base_url + "Flota/setStatus";

        fetch(url, { method: 'POST', body: formData })
            .then(response => response.json())
            .then(objData => {
                if (objData.success) {
                    $('#modalStatus').modal('hide');
                    if (tableFlota && typeof tableFlota.reload === 'function') {
                        tableFlota.reload();
                    }
                    notifi(objData.message, "success");
                } else {
                    notifi(objData.message, "error");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                notifi("Ocurrió un error en el sistema.", "error");
            });
    };

    // --- INICIO: Adición para el reporte de operatividad ---
    const toggleButton = document.getElementById('toggleReportSection');
    if (toggleButton) {
        toggleButton.addEventListener('click', toggleReportSection);
    }
    // --- FIN: Adición para el reporte de operatividad ---
});

function loadSelects() {
    const url = base_url + "Flota/getSelects";
    fetch(url)
        .then(response => response.json())
        .then(objData => {
            if (objData.success) {
                // Marcas
                let htmlMarcas = '<option value="">Seleccione una marca</option>';
                objData.data.marcas.forEach(marca => {
                    htmlMarcas += `<option value="${marca.id_marca}">${marca.marca_unidad}</option>`;
                });
                document.querySelector("#id_marca").innerHTML = htmlMarcas;

                // Modelos
                let htmlModelos = '<option value="">Seleccione un modelo</option>';
                objData.data.modelos.forEach(modelo => {
                    htmlModelos += `<option value="${modelo.id_modelo}">${modelo.modelo_unidad}</option>`;
                });
                document.querySelector("#id_modelo").innerHTML = htmlModelos;
            }
        });
}

function openModal() {
    document.querySelector('#id_flota').value = "";
    document.querySelector('#modalTitle').innerHTML = "Nueva Unidad";
    document.querySelector('#btnText').innerHTML = "Guardar";
    document.querySelector('#formFlota').reset();
    $('#modalFlota').modal('show');
}

function fntViewUnidad(idFlota) {
    const url = base_url + "Flota/getUnidad/" + idFlota;
    fetch(url)
        .then(response => response.json())
        .then(objData => {
            if (objData.success) {
                const unidad = objData.data;
                const status = statusMap[unidad.status_unidad] || { text: 'Desconocido', color: 'badge-light' };

                let historialHtml = '<p class="text-muted">No hay historial de mantenimiento registrado.</p>';
                if (unidad.historial_mantenimiento.length > 0) {
                    historialHtml = unidad.historial_mantenimiento.map(item => `
                        <li class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 font-weight-bold">${item.tipo_mantenimiento === 'c' ? 'Mantenimiento Correctivo' : 'Mantenimiento Preventivo'}</h6>
                                <small class="text-muted">${item.fecha_entrada}</small>
                            </div>
                            <p class="mb-1">${item.diagnostico}</p>
                        </li>
                    `).join('');
                }

                const modalContent = `
                    <div class="modal-header">
                        <h5 class="modal-title">Detalles de la Unidad: ${unidad.id_unidad}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="font-weight-bold mb-3">Información General</h5>
                                <ul class="list-unstyled">
                                <li><strong>Marca:</strong> ${unidad.marca_unidad}</li>
                                <li><strong>Modelo:</strong> ${unidad.modelo_unidad}</li>
                                <li><strong>VIN:</strong> ${unidad.vim_unidad}</li>
                                <li><strong>Año:</strong> ${unidad.fecha_creacion}</li>
                                 <li><strong>Estado:</strong> <span class="badge ${status.color}">${status.text}</span></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="font-weight-bold mb-3">Especificaciones</h5>
                                <ul class="list-unstyled">
                                <li><strong>Pasajeros:</strong> ${unidad.cap_pasajero}</li>
                                <li><strong>Combustible:</strong> ${unidad.tipo_combustible}</li>
                                <li><strong>Transmisión:</strong> ${unidad.transmision}</li>
                                </ul>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h5 class="font-weight-bold mb-3">Historial de Mantenimiento</h5>
                            <div style="max-height: 200px; overflow-y: auto;">
                                <ul class="list-group list-group-flush">
                                ${historialHtml}
                                </ul>
                            </div>
                        </div>
                    </div>
                `;

                document.querySelector('#modalViewUnidad .modal-content').innerHTML = modalContent;
                $('#modalViewUnidad').modal('show');
            } else {
                notifi(objData.message, "error");
            }
        });
}

async function fntEditUnidad(idFlota) {
    document.querySelector('#modalTitle').innerHTML = "Actualizar Unidad";
    document.querySelector('#btnText').innerHTML = "Actualizar";

    try {
        const url = `${base_url}Flota/getUnidad/${idFlota}`;
        const response = await fetch(url);
        const objData = await response.json();

        if (objData.success) {
            const unidad = objData.data;
            document.querySelector("#id_flota").value = unidad.id_flota;
            document.querySelector("#id_unidad").value = unidad.id_unidad;
            document.querySelector("#vim_unidad").value = unidad.vim_unidad;
            document.querySelector("#id_marca").value = unidad.id_marca;
            document.querySelector("#id_modelo").value = unidad.id_modelo;
            document.querySelector("#cap_pasajero").value = unidad.cap_pasajero;
            document.querySelector("#fecha_creacion").value = unidad.fecha_creacion;
            document.querySelector("#tipo_combustible").value = unidad.tipo_combustible;
            document.querySelector("#transmision").value = unidad.transmision;
            $('#modalFlota').modal('show');
        } else {
            notifi(objData.message, "error");
        }
    } catch (error) {
        notifi("Ocurrió un error al cargar los datos para editar.", "error");
    }
}

function fntStatusUnidad(idFlota) {
    const url = base_url + "Flota/getUnidad/" + idFlota;
    fetch(url)
        .then(response => response.json())
        .then(objData => {
            if (objData.success) {
                const unidad = objData.data;
                document.querySelector("#status_id_flota").value = unidad.id_flota;
                document.querySelector("#statusUnidadId").textContent = unidad.id_unidad;

                const currentStatus = statusMap[unidad.status_unidad] || { text: 'Desconocido' };
                document.querySelector("#statusActual").textContent = currentStatus.text;

                let optionsHtml = '';
                for (const key in statusMap) {
                    if (key != unidad.status_unidad) { // No mostrar el estado actual como opción
                        optionsHtml += `<option value="${key}">${statusMap[key].text}</option>`;
                    }
                }
                document.querySelector("#status").innerHTML = optionsHtml;
                document.querySelector("#formStatus").reset();

                $('#modalStatus').modal('show');
            } else {
                notifi(objData.message, "error");
            }
        });
}

// =================================================================================
// FUNCIONES PARA EL REPORTE DE OPERATIVIDAD
// =================================================================================

/**
 * Muestra u oculta la sección de reportes.
 */
function toggleReportSection() {
    // AdminLTE maneja el colapso, pero si el elemento no existe, el código puede fallar.
    // Añadimos una verificación para evitar errores si la sección de reportes no está en la página.
    const reportCard = document.getElementById('report-section-card');
    if (!reportCard) {
        console.warn('La sección de reporte de operatividad no fue encontrada en esta página.');
        return;
    }
}

/**
 * Configura la sección de reportes, principalmente cargando los filtros.
 */
function setupReportSection() {
    loadReportFilters();
    const btnGenerarPdf = document.getElementById('btnGenerarPdfOperatividad');
    if (btnGenerarPdf) {
        btnGenerarPdf.removeEventListener('click', generarPdfOperatividad); // Evitar duplicados
        btnGenerarPdf.addEventListener('click', generarPdfOperatividad);
    }

    // --- INICIO: Lógica para el buscador de grupos ---
    const filtroInput = document.getElementById('filtro-grupos-input');
    if (filtroInput) {
        filtroInput.addEventListener('input', filtrarGrupos);
    }
}

/**
 * Carga los datos de la flota, los agrupa y crea los checkboxes para el filtro.
 */
function loadReportFilters() {
    const filtersContainer = document.getElementById('reporte-filtros');
    if (!filtersContainer) return;

    if (!allFlotaData || allFlotaData.length === 0) {
        filtersContainer.innerHTML = '<p class="text-muted">No hay unidades para mostrar.</p>';
        return;
    }

    // Agrupar unidades por [transmision][combustible]
    const groupedByCriteria = allFlotaData.reduce((acc, unit) => {
        const modelo = unit.modelo_unidad || 'Sin Modelo';
        const transmision = unit.transmision || 'No especificada';
        const combustible = unit.tipo_combustible || 'No especificado';

        const groupKey = `${modelo} / ${combustible} / ${transmision}`;

        if (!acc[groupKey]) {
            acc[groupKey] = [];
        }
        acc[groupKey].push(unit);
        return acc;
    }, {});

    // Renderizar los grupos de checkboxes
    let html = '';
    if (Object.keys(groupedByCriteria).length > 0) {
        html += '<div>';
        for (const groupName in groupedByCriteria) {
            const unitsInGroup = groupedByCriteria[groupName];
            const unitIds = unitsInGroup.map(u => u.id_flota);

            html += `
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="group_${unitIds[0]}"
                           data-unit-ids='${JSON.stringify(unitIds)}'>
                    <label class="custom-control-label" for="group_${unitIds[0]}">${groupName} <span class="font-weight-bold text-primary">(${unitsInGroup.length})</span></label>
                </div>
            `;
        }
        html += '</div>';
    }

    filtersContainer.innerHTML = html || '<p class="text-muted">No se pudieron agrupar las unidades.</p>';

    // Añadir event listeners a todos los nuevos checkboxes
    filtersContainer.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', updatePreview);
    });
}

/**
 * Filtra la lista de grupos de checkboxes según el texto introducido.
 */
function filtrarGrupos() {
    const searchTerm = document.getElementById('filtro-grupos-input').value.toLowerCase();
    const groups = document.querySelectorAll('#reporte-filtros .custom-control');

    groups.forEach(group => {
        const groupName = group.querySelector('label').textContent.toLowerCase();
        if (groupName.includes(searchTerm)) {
            group.style.display = 'block';
        } else {
            label.style.display = 'none';
        }
    });
}

/**
 * Actualiza la vista previa cada vez que se marca/desmarca un checkbox.
 */
function updatePreview() {
    // Elementos de la UI
    const tablaPreview = document.getElementById('tabla-unidades-seleccionadas');
    const tbodyPreview = tablaPreview.querySelector('tbody');
    const placeholder = document.getElementById('placeholder-vista-previa');
    const btnGenerarPdf = document.getElementById('btnGenerarPdfOperatividad');

    // Contadores del resumen
    const totalCounter = document.getElementById('total-unidades-seleccionadas');
    const operativasCounter = document.getElementById('total-operativas');
    const inoperativasCounter = document.getElementById('total-inoperativas');
    const criticasCounter = document.getElementById('total-criticas');

    const checkedBoxes = document.querySelectorAll('#reporte-filtros input[type="checkbox"]:checked');
    const summaryData = [];
    tbodyPreview.innerHTML = '';

    let counts = { operativas: 0, inoperativas: 0, criticas: 0 };

    if (checkedBoxes.length === 0) {
        tablaPreview.classList.add('d-none');
        placeholder.classList.remove('d-none');
        btnGenerarPdf.disabled = true;
    } else {
        tablaPreview.classList.remove('d-none');
        placeholder.classList.add('d-none');
        btnGenerarPdf.disabled = false;

        // 1. Procesar cada grupo seleccionado para crear una fila de resumen
        checkedBoxes.forEach(groupCheckbox => {
            const groupName = groupCheckbox.nextElementSibling.textContent.replace(/\s\(\d+\)$/, ''); // "Modelo / Comb / Trans (5)" -> "Modelo / Comb / Trans"
            const unitIdsInGroup = JSON.parse(groupCheckbox.dataset.unitIds);

            const groupCounts = { cantidad: 0, operativas: 0, inoperativas: 0, criticas: 0 };

            unitIdsInGroup.forEach(unitId => {
                const unitData = allFlotaData.find(u => u.id_flota == unitId);
                if (!unitData) return;

                const statusInfo = statusMap[unitData.status_unidad] || { text: 'Desconocido' };
                groupCounts.cantidad++;
                if (statusInfo.text === 'Operativa') groupCounts.operativas++;
                else if (statusInfo.text === 'Inoperativa') groupCounts.inoperativas++;
                else if (statusInfo.text === 'Crítica') groupCounts.criticas++;
            });

            // Añadir los datos del grupo al resumen general y a la tabla
            summaryData.push({ groupName, ...groupCounts });

            const row = `
                <tr>
                    <td>${groupName}</td>
                    <td class="text-center font-weight-bold">${groupCounts.cantidad}</td>
                    <td class="text-center text-success">${groupCounts.operativas}</td>
                    <td class="text-center text-warning">${groupCounts.inoperativas}</td>
                    <td class="text-center text-danger">${groupCounts.criticas}</td>
                </tr>
            `;
            tbodyPreview.innerHTML += row;

            // Sumar al total general
            counts.operativas += groupCounts.operativas;
            counts.inoperativas += groupCounts.inoperativas;
            counts.criticas += groupCounts.criticas;
        });
    }

    // Actualizar contadores
    totalCounter.textContent = summaryData.reduce((acc, group) => acc + group.cantidad, 0);
    operativasCounter.textContent = counts.operativas;
    inoperativasCounter.textContent = counts.inoperativas;
    criticasCounter.textContent = counts.criticas;
}

/**
 * Recopila los IDs de las unidades seleccionadas y las envía para generar el PDF.
 */
async function generarPdfOperatividad() {
    const checkedBoxes = document.querySelectorAll('#reporte-filtros input[type="checkbox"]:checked');
    if (checkedBoxes.length === 0) return notifi('Debe seleccionar al menos una unidad.', 'warning');

    // 1. Construir el objeto de datos de resumen, igual que en la vista previa
    const summaryData = [];
    checkedBoxes.forEach(groupCheckbox => {
        const groupName = groupCheckbox.nextElementSibling.textContent.replace(/\s\(\d+\)$/, '');
        const unitIdsInGroup = JSON.parse(groupCheckbox.dataset.unitIds);
        const groupCounts = { cantidad: 0, operativas: 0, inoperativas: 0, criticas: 0 };
        unitIdsInGroup.forEach(unitId => {
            const unitData = allFlotaData.find(u => u.id_flota == unitId);
            if (!unitData) return;
            const statusInfo = statusMap[unitData.status_unidad] || { text: 'Desconocido' };
            groupCounts.cantidad++;
            if (statusInfo.text === 'Operativa') groupCounts.operativas++;
            else if (statusInfo.text === 'Inoperativa') groupCounts.inoperativas++;
            else if (statusInfo.text === 'Crítica') groupCounts.criticas++;
        });
        summaryData.push({ groupName, ...groupCounts });
    });

    notifi('Generando reporte...', 'info');

    // 2. Enviar los datos de resumen directamente al script del PDF
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `${base_url}data/flota/operatividad.php`;
    form.target = '_blank';
    form.innerHTML = `<input type="hidden" name="reporteData" value='${JSON.stringify(summaryData)}'>`;
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function notifi(message, type) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}