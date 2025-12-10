let tblDespachos; // Variable global para la instancia de la DataTable

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar la tabla de despachos pendientes
    inicializarTablaDespachos();

    // Configurar los listeners del formulario de despacho
    configurarFormularioDespacho();

    // Punto A: Verificar si se pasó un ID de despacho en la URL
    const idDespachoUrl = document.getElementById('id_despacho_url')?.value;
    if (idDespachoUrl && idDespachoUrl > 0) {
        fntCargarParaDespachar(idDespachoUrl);
    }

    // --- INICIO DE LA CORRECCIÓN ---
    // Punto C: Hacer funcional el buscador de órdenes
    const formBuscar = document.getElementById('formBuscarDesp');
    if (formBuscar) {
        formBuscar.addEventListener('submit', buscarOrdenes);
    }
});

/**
 * Inicializa la DataTable para mostrar la lista de órdenes aprobadas pendientes de despacho.
 */
function inicializarTablaDespachos() {
    tblDespachos = $('#tblDespachos').DataTable({
        "aProcessing": true,
        "aServerSide": false, // La paginación será del lado del cliente
        "language": { "url": `${base_url}src/plugins/js/es_es.json` },
        "ajax": { // CORRECCIÓN: Cargar todas las órdenes relevantes para Almacén
            "url": `${base_url}Orden/getOrdenes`,
            "dataSrc": "data"
        },
        "columns": [
            { "data": "id_despacho" },
            { "data": "fecha_aprobacion" }, // Fecha en que Compras aprobó
            {
                "data": null, "render": function (data, type, row) {
                    return `${row.id_unidad} - ${row.modelo_unidad}`;
                }
            },
            { "data": "creador_nombre" },
            {
                "data": "estado_badge", "className": "text-center"
            },
            { "data": "total_articulos", "className": "text-center" },
            {
                "data": "acciones", "orderable": false, "className": "text-center"
            }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "dom": "lfrtip"
    });
}

/**
 * Configura los event listeners para el formulario de despacho.
 */
function configurarFormularioDespacho() {
    const form = document.getElementById('formDespachoFinal');
    if (form) {
        form.addEventListener('submit', fntConfirmarDespacho);
    }

    const btnOcultar = document.getElementById('btnOcultarFormDespacho');
    if (btnOcultar) {
        btnOcultar.addEventListener('click', () => {
            document.getElementById('formCompletarDespacho').style.display = 'none';
        });
    }
}

/**
 * Carga los datos de una orden aprobada en el formulario para completar el despacho.
 * @param {number} idDespacho - El ID del despacho a cargar.
 */
async function fntCargarParaDespachar(idDespacho) {
    const formContainer = document.getElementById('formCompletarDespacho');
    formContainer.style.display = 'block';
    formContainer.scrollIntoView({ behavior: 'smooth' });

    try {
        // 1. Obtener los detalles de la requisición original
        const response = await fetch(`${base_url}Requisicion/getRequisicionDetails/${idDespacho}`);
        const result = await response.json();

        if (result.success) {
            const req = result.data;

            // 2. Poblar los campos de solo lectura del formulario
            document.getElementById('despachoIdTitulo').textContent = req.id_requisicion_interna;
            document.getElementById('id_despacho_completar').value = idDespacho;
            document.getElementById('despachoUnidad').textContent = `${req.id_unidad} - ${req.modelo_unidad}`;
            document.getElementById('despachoFecha').textContent = req.fecha_requisicion_formatted;
            document.getElementById('despachoMecanico').textContent = req.mecanico_nombre || req.mecanico_cedula;
            document.getElementById('despachoDiagnostico').textContent = req.diagnostico || 'Sin observaciones.';

            // 3. Poblar la tabla de artículos
            const tablaArticulosBody = document.getElementById('tblArticulosDespacho').querySelector('tbody');
            tablaArticulosBody.innerHTML = '';
            if (req.articulos && req.articulos.length > 0) {
                req.articulos.forEach(art => {
                    tablaArticulosBody.innerHTML += `<tr><td>${art.producto}</td><td class="text-center">${art.cant_despacho}</td></tr>`;
                });
            } else {
                tablaArticulosBody.innerHTML = '<tr><td colspan="2" class="text-center">No hay artículos.</td></tr>';
            }

            // 4. Cargar los selects de Operador y Despachador
            const responsePersonal = await fetch(`${base_url}Orden/getInitialData`);
            const resultPersonal = await responsePersonal.json();
            if (resultPersonal.success) {
                const { operadores, despachadores } = resultPersonal.data;
                populateSelectDespacho('listOperadorDespacho', operadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un operador');
                populateSelectDespacho('listDespachadorAlmacen', despachadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un despachador');
            }

            // --- INICIO DE LA CORRECCIÓN ---
            // Lógica inteligente para mostrar/ocultar campos y botones según el estado de la orden.
            const btnConfirmar = document.getElementById('btnConfirmarDespacho');
            const footerForm = btnConfirmar.closest('.card-footer'); // Contenedor del botón

            const operadorSelect = document.getElementById('operadorSelectContainer');
            const operadorText = document.getElementById('operadorText');
            const despachadorSelect = document.getElementById('despachadorSelectContainer');
            const despachadorText = document.getElementById('despachadorText');

            if (req.status_requisicion == 2) { // 2 = Aprobada (Lista para despachar)
                footerForm.style.display = 'block'; // Mostrar pie de página con botones
                operadorSelect.style.display = 'block';
                operadorText.style.display = 'none';
                despachadorSelect.style.display = 'block';
                despachadorText.style.display = 'none';
                // Limpiar los selects por si se cargó una orden despachada antes
                $('#listOperadorDespacho, #listDespachadorAlmacen').val(null).trigger('change');

            } else { // Para cualquier otro estado (Pendiente, Despachada, etc.)
                footerForm.style.display = 'none'; // Ocultar pie de página con botones
                operadorSelect.style.display = 'none';
                operadorText.style.display = 'block';
                despachadorSelect.style.display = 'none';
                despachadorText.style.display = 'block';

                // Mostrar los nombres si la orden ya fue despachada
                operadorText.textContent = req.operador_final || 'N/A';
                despachadorText.textContent = req.despachador_final || 'N/A';

                if (req.status_requisicion != 2) {
                    notifi('Esta orden no está disponible para despacho.', 'info');
                }
            }
            // --- FIN DE LA CORRECCIÓN ---

        } else {
            notifi(result.message, 'error');
            formContainer.style.display = 'none';
        }
    } catch (error) {
        console.error('Error en fntViewOrden:', error);
        notifi('Error al cargar los detalles de la orden.', 'error');
        formContainer.style.display = 'none';
    }
}

/**
 * Envía el formulario para confirmar el despacho final.
 */
async function fntConfirmarDespacho(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    if (!formData.get('listOperadorDespacho') || !formData.get('listDespachadorAlmacen')) {
        notifi('Debe seleccionar el Operador que recibe y el Despachador de almacén.', 'warning');
        return;
    }

    const btnSubmit = document.getElementById('btnConfirmarDespacho');
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Procesando...`;

    try {
        const response = await fetch(`${base_url}Orden/despacharOrden`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            notifi(result.message, 'success');
            document.getElementById('formCompletarDespacho').style.display = 'none';
            if (typeof loadAllNotifications === 'function') {
                loadAllNotifications();
            }
            tblDespachos.ajax.reload();
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error al confirmar despacho:', error);
        notifi('Error de conexión al confirmar el despacho.', 'error');
    } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = `<i class="fas fa-check-circle"></i> Confirmar y Despachar`;
    }
}

/**
 * Busca órdenes según los criterios del formulario de búsqueda y muestra los resultados.
 * @param {Event} e - El evento de submit del formulario.
 */
async function buscarOrdenes(e) {
    e.preventDefault();
    try {
        const formData = new FormData(document.getElementById('formBuscarDesp'));
        const response = await fetch(`${base_url}Orden/getBuscarOrden`, { method: 'POST', body: formData });
        const result = await response.json();
        const container = document.getElementById('searchResultsContainer');
        container.innerHTML = ''; // Limpiar resultados anteriores

        if (result.success && result.data.length > 0) {
            let html = '<div class="list-group">';
            result.data.forEach(orden => {
                // Al hacer clic en un resultado, se llama a la misma función que el botón "Despachar"
                html += `<a href="#" onclick="fntCargarParaDespachar(${orden.id_despacho})" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Orden #${orden.id_despacho}</h5>
                                <small>${orden.fecha_despacho}</small>
                            </div>
                            <p class="mb-1 small">
                                <i class="fas fa-bus-alt mr-1"></i> ${orden.id_unidad} - ${orden.modelo_unidad}
                            </p>
                         </a>`;
            });
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="text-muted text-center mt-3">No se encontraron resultados.</p>';
        }
    } catch (error) {
        console.error('Error en buscarOrdenes:', error);
        notifi('Error al realizar la búsqueda.', 'error');
    }
}
// --- FIN DE LA CORRECCIÓN ---

/**
 * Función auxiliar para poblar los selects del formulario de despacho.
 */
function populateSelectDespacho(selectId, data, valueField, textFieldFn, defaultOptionText) {
    const selectElement = document.getElementById(selectId);
    if (!selectElement) return;

    selectElement.innerHTML = `<option value="">${defaultOptionText}</option>`;
    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = textFieldFn(item);
            selectElement.appendChild(option);
        });
    }

    // --- INICIO DE LA CORRECCIÓN ---
    // Inicializar Select2 con el tema de Bootstrap 4 y campo de búsqueda
    const select2Instance = $(selectElement).select2({
        theme: 'bootstrap4',
        placeholder: defaultOptionText,
        language: "es"
    }).on('select2:open', function () {
        // --- INICIO DE LA CORRECCIÓN ---
        // Aplicar altura máxima y scroll a la lista de opciones
        $('.select2-results__options').css({
            'max-height': '250px',
            'overflow-y': 'auto'
        });
        // Enfocar el campo de búsqueda automáticamente
        setTimeout(() => {
            document.querySelector('.select2-search__field').focus();
        }, 50);
        // --- FIN DE LA CORRECCIÓN ---
    });

    // Ajustar la altura y el borde del contenedor de Select2 para que coincida con los otros inputs
    select2Instance.next('.select2-container').find('.select2-selection').css({
        'min-height': 'calc(2.25rem + 2px)',
        'border': '1px solid #ced4da' // Añadir borde estándar de Bootstrap
    });
}

function fntViewOrden(idDespacho) {
    // CORRECCIÓN: Reutilizar la función `fntCargarParaDespachar` que ya carga
    // los detalles de la orden en modo de solo lectura si ya está despachada.
    // Esto es más eficiente que crear una nueva función de vista.
    fntCargarParaDespachar(idDespacho);
}

function notifi(msg, tipo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: msg,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });
}

/**
 * Prepara y envía los datos para generar el PDF de la requisición/orden.
 * @param {number} idDespacho - El ID del despacho a imprimir.
 */
async function fntImprimirRequisicion(idDespacho) {
    try {
        // Reutilizamos el endpoint del controlador de Requisicion que ya prepara los datos.
        const response = await fetch(base_url + 'Requisicion/generarReporteRequisicion/' + idDespacho);
        if (!response.ok) {
            throw new Error('Error al obtener los datos para el reporte.');
        }
        const result = await response.json();

        if (result.success) {
            // Crear un formulario oculto para enviar los datos por POST
            const form = document.createElement('form');
            form.method = 'POST';
            // La URL apunta al script PHP que genera el PDF
            form.action = base_url + 'data/almacen/requisicion.php';
            form.target = '_blank'; // Abrir en una nueva pestaña

            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'reporteData';
            hiddenField.value = JSON.stringify(result.data);

            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

        } else {
            notifi(result.message || 'No se pudieron cargar los datos para el reporte.', 'error');
        }
    } catch (error) {
        console.error('Error en fntImprimirRequisicion:', error);
        notifi('Error de conexión al generar el reporte.', 'error');
    }
}