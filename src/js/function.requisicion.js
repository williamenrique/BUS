/**
 * Archivo: function.requisicion.js
 * Descripción: Lógica de JavaScript para la gestión de Requisiciones.
 *              Utiliza DynamicTable en lugar de DataTables tradicional.
 */

let tableRequisicion;

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM cargado - Inicializando requisiciones...');
    
    // Inicializar la tabla dinámica de requisiciones
    inicializarTablaRequisiciones();
    
    // Inicializar Select2 en los campos correspondientes
    inicializarSelects();

    // Cargar datos iniciales para los selects
    cargarDatosParaSelects();

    // Configurar los event listeners del formulario
    configurarEventListenersFormulario();

    // Verificar si se pasó un ID de requisición en la URL
    const idRequisicionUrl = document.getElementById('id_requisicion_url');
    if (idRequisicionUrl && idRequisicionUrl.value && idRequisicionUrl.value > 0) {
        // Ocultar el formulario de creación y cargar la requisición para aprobar
        const formCreacion = document.querySelector('.card-primary');
        if (formCreacion) {
            formCreacion.style.display = 'none';
        }
        fntLoadRequisicionParaAprobar(idRequisicionUrl.value);
    }
});

/**
 * Inicializa la tabla dinámica de requisiciones usando DataTableRefactor
 */
function inicializarTablaRequisiciones() {
    console.log('Inicializando tabla de requisiciones dinámica...');
    
    if (typeof initRequisicionesDynamicTable !== 'undefined') {
        tableRequisicion = initRequisicionesDynamicTable();
        console.log('Tabla de requisiciones inicializada:', tableRequisicion);
    } else {
        console.error('initRequisicionesDynamicTable no está disponible. Verificar carga de DataTableRefactor.js');
    }
}

/**
 * Inicializa los plugins de Select2 en los campos del formulario.
 */
function inicializarSelects() {
    const config = {
        theme: 'bootstrap4',
        language: "es",
        width: '100%'
    };

    ['listUnidad', 'listMecanico', 'listArticulo'].forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;
        
        $(`#${id}`).select2({
            ...config,
            placeholder: $(`#${id}`).data('placeholder') || 'Seleccione una opción'
        }).on('select2:open', function () {
            $('.select2-results__options').css({
                'max-height': '250px',
                'overflow-y': 'auto'
            });
            setTimeout(() => {
                const searchField = document.querySelector('.select2-search__field');
                if (searchField) searchField.focus();
            }, 50);
        }).next('.select2-container').find('.select2-selection').css({
            'min-height': 'calc(2.25rem + 2px)',
            'border': '1px solid #ced4da'
        });
    });
}

/**
 * Carga los datos para las unidades, mecánicos y artículos desde el backend.
 */
async function cargarDatosParaSelects() {
    try {
        const response = await fetch(base_url + 'Orden/getInitialData');
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { flota, mecanicos, articulos } = result.data;

            populateSelect('listUnidad', flota, 'id_flota', item => `${item.id_unidad} - ${item.modelo_unidad}`, 'Buscar y seleccionar una unidad');
            populateSelect('listMecanico', mecanicos, 'personal_cedula', item => `${item.personal_cedula} - ${item.personal_nombre} ${item.personal_apellido}`, 'Buscar y seleccionar un mecánico');
            populateSelect('listArticulo', articulos, 'id_producto', item => `${item.producto} (Stock: ${item.cant_producto})`, 'Buscar y seleccionar un artículo', item => ({ 'data-stock': item.cant_producto || 0 }));
        } else {
            notifi(result.message || 'No se pudieron cargar los datos.', 'error');
        }
    } catch (error) {
        console.error('Error en cargarDatosParaSelects:', error);
        notifi('Error de conexión al cargar datos del formulario.', 'error');
    }
}

/**
 * Función genérica para poblar un <select> con datos.
 */
function populateSelect(selectId, data, valueField, textFieldFn, defaultOptionText, dataAttributesFn = null) {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = '';
    select.innerHTML = `<option value=""></option>`;

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField] || '';
            option.textContent = textFieldFn(item);

            if (dataAttributesFn) {
                const attributes = dataAttributesFn(item);
                for (const key in attributes) {
                    option.setAttribute(key, attributes[key]);
                }
            }
            select.appendChild(option);
        });
    }
    
    $(select).data('placeholder', defaultOptionText);

    if ($(select).data('select2')) {
        $(select).trigger('change');
    }
}

/**
 * Configura todos los listeners para los botones y el formulario de creación.
 */
function configurarEventListenersFormulario() {
    const formRequisicion = document.getElementById('formRequisicion');
    if (!formRequisicion) return;

    const btnAgregar = document.getElementById('btnAgregaArticulo');
    const cantidadInput = document.getElementById('txtCant');
    const tablaArticulos = document.getElementById('tblArticulosAgregados').querySelector('tbody');

    if (cantidadInput) {
        cantidadInput.addEventListener('input', validarCantidadRequisicion);
    }

    if (btnAgregar) {
        btnAgregar.addEventListener('click', agregarArticuloATabla);
    }

    if (tablaArticulos) {
        tablaArticulos.addEventListener('click', function (e) {
            if (e.target.closest('.btn-eliminar-articulo')) {
                e.target.closest('tr').remove();
                actualizarResumenArticulos();
            }
        });
    }

    // Eventos de Select2 para resumen
    $('#listUnidad').on('select2:select', function (e) {
        const data = e.params.data;
        const resumenUnidad = document.getElementById('resumenUnidad');
        if (resumenUnidad) resumenUnidad.textContent = data.text || 'No seleccionada';
    });

    $('#listMecanico').on('select2:select', function (e) {
        const data = e.params.data;
        const nombre = data.text.split(' - ')[1] || 'No seleccionado';
        const resumenMecanico = document.getElementById('resumenMecanico');
        if (resumenMecanico) resumenMecanico.textContent = nombre;
    });

    // Botón Cancelar
    const btnCancel = document.getElementById('btnCancel');
    if (btnCancel) {
        btnCancel.addEventListener('click', () => {
            formRequisicion.reset();
            $('#listUnidad, #listMecanico, #listArticulo').val(null).trigger('change');
            if (tablaArticulos) tablaArticulos.innerHTML = '';
            if (cantidadInput) {
                cantidadInput.value = '';
                cantidadInput.disabled = true;
            }
            const stockValidation = document.getElementById('stockValidationRequisicion');
            if (stockValidation) stockValidation.textContent = '';
            resetResumen();
        });
    }

    // Evento de selección de artículo
    $('#listArticulo').on('select2:select', function (e) {
        const selectedData = e.params.data;
        fntGetArtRequisicion(selectedData);
    });

    // Submit del formulario
    formRequisicion.addEventListener('submit', async function (e) {
        e.preventDefault();

        const idUnidad = document.getElementById('listUnidad').value;
        const idMecanico = document.getElementById('listMecanico').value;

        if (!idUnidad || !idMecanico) {
            notifi('Debe seleccionar la Unidad y el Mecánico.', 'warning');
            return;
        }

        const articulos = [];
        if (tablaArticulos) {
            tablaArticulos.querySelectorAll('tr').forEach(fila => {
                articulos.push({
                    id: fila.dataset.idArticulo,
                    cantidad: parseFloat(fila.cells[2].textContent) || 0
                });
            });
        }

        if (articulos.length === 0) {
            notifi('Debe agregar al menos un artículo a la requisición.', 'warning');
            return;
        }

        actualizarResumenArticulos();

        const formData = new FormData(formRequisicion);
        formData.append('articulos', JSON.stringify(articulos));

        const btnSubmit = document.getElementById('btnActionForm');
        if (btnSubmit) {
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Guardando...`;
        }

        try {
            const response = await fetch(base_url + 'Requisicion/setRequisicion', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                notifi(result.msg, 'success');
                formRequisicion.reset();
                $('#listUnidad, #listMecanico, #listArticulo').val(null).trigger('change');
                if (tablaArticulos) tablaArticulos.innerHTML = '';
                resetResumen();
                if (tableRequisicion && typeof tableRequisicion.reload === 'function') {
                    tableRequisicion.reload();
                }
            } else {
                notifi(result.msg, 'error');
            }
        } catch (error) {
            console.error('Error al guardar requisición:', error);
            notifi('Error de conexión al guardar la requisición.', 'error');
        } finally {
            if (btnSubmit) {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = `<i class="fas fa-save"></i> Guardar Requisición`;
            }
        }
    });

    // Configurar la sección de APROBACIÓN
    const btnAprobar = document.querySelector('#btnAprobarUrl');
    if (btnAprobar) {
        btnAprobar.addEventListener('click', function () {
            fntAprobarRequisicion(this.dataset.idDespacho);
        });
    }
}

/**
 * Obtiene el stock de un artículo seleccionado y lo muestra.
 */
async function fntGetArtRequisicion(selectedData) {
    const cantidadInput = document.getElementById('txtCant');
    const stockValidationElement = document.getElementById('stockValidationRequisicion');
    
    if (!cantidadInput || !stockValidationElement) return;
    
    cantidadInput.value = '';

    if (!selectedData || !selectedData.id || selectedData.id === "0") {
        stockValidationElement.textContent = '';
        cantidadInput.disabled = true;
        return;
    }

    try {
        const selectedOptionElement = $(selectedData.element);
        const stockDisponible = parseFloat(selectedOptionElement.data('stock')) || 0;

        cantidadInput.dataset.stockDisponible = stockDisponible;
        stockValidationElement.textContent = `Stock disponible: ${stockDisponible}`;
        stockValidationElement.className = 'form-text text-muted';
        cantidadInput.disabled = false;
        validarCantidadRequisicion();
    } catch (error) {
        console.error('Error obteniendo stock del artículo:', error);
        stockValidationElement.textContent = 'Error al cargar stock.';
        stockValidationElement.className = 'form-text text-danger';
        cantidadInput.disabled = true;
    }
}

/**
 * Valida la cantidad ingresada en tiempo real contra el stock disponible.
 */
function validarCantidadRequisicion() {
    const cantidadInput = document.getElementById('txtCant');
    const validationElement = document.getElementById('stockValidationRequisicion');
    
    if (!cantidadInput || !validationElement) return false;
    
    const cantidad = parseInt(cantidadInput.value);
    const stockDisponible = parseFloat(cantidadInput.dataset.stockDisponible) || 0;

    if (isNaN(cantidad) || cantidad <= 0) {
        validationElement.textContent = `Stock disponible: ${stockDisponible}. Cantidad no válida.`;
        validationElement.className = 'form-text text-danger';
        return false;
    }

    if (cantidad > stockDisponible) {
        validationElement.textContent = `Stock insuficiente. Disponible: ${stockDisponible}. Se solicitará a Compras.`;
        validationElement.className = 'form-text text-warning';
        return true;
    }

    validationElement.textContent = `Stock disponible: ${stockDisponible}. Cantidad válida.`;
    validationElement.className = 'form-text text-success';
    return true;
}

/**
 * Agrega el artículo seleccionado a la tabla de visualización.
 */
function agregarArticuloATabla() {
    const selectArticulo = document.getElementById('listArticulo');
    const cantidadInput = document.getElementById('txtCant');
    
    if (!selectArticulo || !cantidadInput) return;
    
    const idArticulo = selectArticulo.value;
    const stockDisponible = parseFloat(cantidadInput.dataset.stockDisponible) || 0;
    const cantidad = parseFloat(cantidadInput.value);

    if (!idArticulo || idArticulo === "0") {
        notifi('Seleccione un artículo.', 'warning');
        return;
    }

    if (isNaN(cantidad) || cantidad <= 0) {
        notifi('La cantidad solicitada debe ser mayor a 0.', 'warning');
        return;
    }

    const tablaBody = document.getElementById('tblArticulosAgregados')?.querySelector('tbody');
    if (!tablaBody) return;

    if (tablaBody.querySelector(`tr[data-id-articulo="${idArticulo}"]`)) {
        notifi('Este artículo ya ha sido agregado.', 'info');
        return;
    }

    const stockBadge = stockDisponible <= 0 ? '<span class="badge badge-danger ml-2">Sin Stock</span>' : '';
    const nombreArticulo = selectArticulo.options[selectArticulo.selectedIndex].text.split(' (Stock:')[0];

    const fila = `
        <tr data-id-articulo="${idArticulo}">
            <td>${idArticulo}</td>
            <td>${nombreArticulo} ${stockBadge}</td>
            <td class="text-center">${cantidad}</td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-eliminar-articulo" title="Eliminar">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    tablaBody.insertAdjacentHTML('beforeend', fila);

    cantidadInput.value = '';
    cantidadInput.disabled = true;
    const stockValidation = document.getElementById('stockValidationRequisicion');
    if (stockValidation) stockValidation.textContent = '';
    $('#listArticulo').val(null).trigger('change');
    actualizarResumenArticulos();
}

/**
 * Actualiza el conteo de artículos y unidades en la tarjeta de resumen.
 */
function actualizarResumenArticulos() {
    const tablaBody = document.getElementById('tblArticulosAgregados')?.querySelector('tbody');
    if (!tablaBody) return;

    const filas = tablaBody.querySelectorAll('tr');
    const totalArticulos = filas.length;
    let totalUnidades = 0;

    filas.forEach(fila => {
        const celdaCantidad = fila.cells[2];
        if (celdaCantidad) {
            totalUnidades += parseFloat(celdaCantidad.textContent) || 0;
        }
    });

    const resumenTotalArticulos = document.getElementById('resumenTotalArticulos');
    const resumenTotalUnidades = document.getElementById('resumenTotalUnidades');
    
    if (resumenTotalArticulos) resumenTotalArticulos.textContent = totalArticulos;
    if (resumenTotalUnidades) resumenTotalUnidades.textContent = totalUnidades;
}

/**
 * Resetea la tarjeta de resumen a su estado inicial.
 */
function resetResumen() {
    const resumenUnidad = document.getElementById('resumenUnidad');
    const resumenMecanico = document.getElementById('resumenMecanico');
    
    if (resumenUnidad) resumenUnidad.textContent = 'No seleccionada';
    if (resumenMecanico) resumenMecanico.textContent = 'No seleccionado';
    actualizarResumenArticulos();
}

/**
 * Muestra los detalles de una requisición en un modal.
 */
async function fntViewRequisicion(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/getRequisicionDetails/' + idDespacho);
        if (!response.ok) throw new Error('Error en la respuesta del servidor.');

        const result = await response.json();

        if (result.success) {
            const req = result.data;

            document.getElementById('modalViewIdRequisicion').textContent = req.id_despacho_flujo;
            document.getElementById('modalViewFechaRequisicion').textContent = req.fecha_requisicion_formatted;
            document.getElementById('modalViewUnidad').textContent = `${req.id_unidad} - ${req.modelo_unidad}`;
            document.getElementById('modalViewTipoOrden').textContent = req.tipo_orden;
            document.getElementById('modalViewStatusRequisicion').innerHTML = req.status_display;

            document.getElementById('modalViewJefePatio').textContent = req.jefe_patio_nombre;
            document.getElementById('modalViewMecanico').textContent = req.mecanico_nombre || req.mecanico_cedula;

            const operadorFinalContainer = document.getElementById('modalOperadorFinalContainer');
            const despachadorFinalContainer = document.getElementById('modalDespachadorFinalContainer');

            if (req.status_requisicion == 3) {
                document.getElementById('modalViewOperadorFinal').textContent = req.operador_final || 'N/A';
                document.getElementById('modalViewDespachadorFinal').textContent = req.despachador_final || 'N/A';
                if (operadorFinalContainer) operadorFinalContainer.style.display = 'list-item';
                if (despachadorFinalContainer) despachadorFinalContainer.style.display = 'list-item';
            } else {
                if (operadorFinalContainer) operadorFinalContainer.style.display = 'none';
                if (despachadorFinalContainer) despachadorFinalContainer.style.display = 'none';
            }

            document.getElementById('modalViewDiagnostico').textContent = req.diagnostico || 'Sin observaciones.';

            const tablaArticulosBody = document.getElementById('modalViewTablaArticulosReq');
            if (tablaArticulosBody) {
                tablaArticulosBody.innerHTML = '';

                if (req.articulos && req.articulos.length > 0) {
                    req.articulos.forEach(articulo => {
                        const isOutOfStock = parseFloat(articulo.stock_actual) < parseFloat(articulo.cant_despacho);
                        const nameHTML = isOutOfStock
                            ? `<a href="${base_url}Producto/producto?id_producto=${articulo.id_producto}" class="text-danger font-weight-bold" title="Haga clic para agregar stock">${articulo.producto} (${articulo.present_producto}) <i class="fas fa-external-link-alt fa-xs ml-1"></i></a>`
                            : `${articulo.producto} (${articulo.present_producto})`;
                        const stockStatus = !isOutOfStock 
                            ? `<span class="badge badge-success">Hay Stock (Disp: ${articulo.stock_actual})</span>`
                            : `<span class="badge badge-danger">Sin Stock (Disp: ${articulo.stock_actual})</span>`;
                        const row = `
                            <tr>
                                <td>${nameHTML}</td>
                                <td class="text-center">${articulo.cant_despacho}</td>
                                <td class="text-center">${stockStatus}</td>
                            </tr>
                        `;
                        tablaArticulosBody.insertAdjacentHTML('beforeend', row);
                    });
                } else {
                    tablaArticulosBody.innerHTML = '<tr><td colspan="3" class="text-center">No hay artículos solicitados.</td></tr>';
                }
            }

            $('#modalViewRequisicion').modal('show');

        } else {
            notifi(result.msg, 'error');
        }
    } catch (error) {
        console.error('Error al ver requisición:', error);
        notifi('Error al cargar los detalles de la requisición.', 'error');
    }
}

/**
 * Carga dinámicamente los detalles de una requisición en la sección de aprobación.
 */
async function fntLoadRequisicionParaAprobar(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/getRequisicionDetails/' + idDespacho);
        if (!response.ok) throw new Error('Error al cargar los datos de la requisición.');

        const result = await response.json();
        if (result.success) {
            const req = result.data;
            const container = document.getElementById('viewRequisicionUrl');
            if (!container) return;

            container.querySelector('#viewIdRequisicionUrl').textContent = req.id_despacho_flujo;
            container.querySelector('#viewFechaRequisicion').textContent = req.fecha_requisicion_formatted;
            container.querySelector('#viewUnidad').textContent = `${req.id_unidad} - ${req.modelo_unidad}`;
            container.querySelector('#viewTipoOrden').textContent = req.tipo_orden;
            container.querySelector('#viewStatusRequisicion').innerHTML = req.status_display;
            container.querySelector('#viewJefePatio').textContent = req.jefe_patio_nombre;
            container.querySelector('#viewMecanico').textContent = req.mecanico_cedula;
            container.querySelector('#viewDiagnostico').textContent = req.diagnostico || 'N/A';

            const tablaBody = container.querySelector('#viewTablaArticulosReq');
            if (tablaBody) {
                tablaBody.innerHTML = '';
                if (req.articulos && req.articulos.length > 0) {
                    req.articulos.forEach(articulo => {
                        const isOutOfStock = parseFloat(articulo.stock_actual) < parseFloat(articulo.cant_despacho);
                        const nameHTML = isOutOfStock
                            ? `<a href="${base_url}Producto/producto?id_producto=${articulo.id_producto}" class="text-danger font-weight-bold" title="Haga clic para agregar stock">${articulo.producto} <i class="fas fa-external-link-alt fa-xs ml-1"></i></a>`
                            : `${articulo.producto}`;
                        const stockStatus = !isOutOfStock 
                            ? `<span class="badge badge-success">Hay Stock (Disp: ${articulo.stock_actual})</span>`
                            : `<span class="badge badge-danger">Sin Stock (Disp: ${articulo.stock_actual})</span>`;
                        tablaBody.innerHTML += `
                            <tr>
                                <td>${nameHTML}</td>
                                <td class="text-center">${articulo.cant_despacho}</td>
                                <td class="text-center">${stockStatus}</td>
                            </tr>
                        `;
                    });
                } else {
                    tablaBody.innerHTML = '<tr><td colspan="3" class="text-center">No hay artículos.</td></tr>';
                }
            }

            const btnAprobar = container.querySelector('#btnAprobarUrl');
            if (btnAprobar) {
                btnAprobar.dataset.idDespacho = idDespacho;
            }

            const btnEnProceso = container.querySelector('#btnEnProcesoUrl');
            if (btnEnProceso) {
                btnEnProceso.onclick = () => fntNotificarEnProcesoReq(idDespacho);
            }

            let canApprove = true;
            if (req.articulos && req.articulos.length > 0) {
                req.articulos.forEach(articulo => {
                    if (articulo.stock_actual < articulo.cant_despacho) {
                        canApprove = false;
                    }
                });
            }

            if (btnAprobar) {
                btnAprobar.disabled = !canApprove;
                if (!canApprove) notifi('No se puede aprobar: Hay artículos con stock insuficiente.', 'warning');
            }

            container.style.display = 'block';
            const btnOcultar = container.querySelector('#btnOcultarUrl');
            if (btnOcultar) {
                btnOcultar.onclick = () => container.style.display = 'none';
            }

            container.scrollIntoView({ behavior: 'smooth' });

        } else {
            notifi(result.msg, 'error');
        }
    } catch (error) {
        console.error('Error en fntLoadRequisicionParaAprobar:', error);
        notifi('No se pudieron cargar los detalles para aprobación.', 'error');
    }
}

/**
 * Envía la solicitud para aprobar la requisición.
 */
async function fntAprobarRequisicion(idDespacho) {
    const result = await Swal.fire({
        title: '¿Aprobar Requisición?',
        text: "Esta acción notificará a Almacén para que prepare el despacho. ¿Continuar?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, Aprobar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('id_despacho_aprobar', idDespacho);
        
        try {
            const response = await fetch(base_url + 'Requisicion/aprobarRequisicion', { 
                method: 'POST', 
                body: formData 
            });
            const res = await response.json();
            notifi(res.msg, res.success ? 'success' : 'error');
            
            if (res.success) {
                const paramsNotif = new URLSearchParams({ id_despacho: idDespacho, tipo: 'aprobada' });
                await fetch(base_url + 'Orden/notificarOperaciones', { 
                    method: 'POST', 
                    body: paramsNotif 
                });
                
                const container = document.getElementById('viewRequisicionUrl');
                if (container) container.style.display = 'none';
                
                if (tableRequisicion && typeof tableRequisicion.reload === 'function') {
                    tableRequisicion.reload();
                }
            }
        } catch (error) {
            console.error('Error al aprobar:', error);
            notifi('Error al procesar la aprobación.', 'error');
        }
    }
}

/**
 * Notifica a Operaciones que una orden está en proceso.
 */
async function fntNotificarEnProcesoReq(idDespacho) {
    const result = await Swal.fire({
        title: 'Notificar a Operaciones',
        text: `¿Desea notificar a Operaciones que la Requisición #${idDespacho} está en proceso de compra?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, notificar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const params = new URLSearchParams({ id_despacho: idDespacho });
        const response = await fetch(base_url + 'Orden/notificarEnProceso', { 
            method: 'POST', 
            body: params 
        });
        const data = await response.json();
        notifi(data.message, data.success ? 'success' : 'error');
    } catch (error) {
        console.error('Error al notificar:', error);
        notifi('Error al enviar la notificación.', 'error');
    }
}

/**
 * Prepara y envía los datos para generar el PDF de la requisición.
 */
async function fntImprimirRequisicion(idDespacho) {
    try {
        const response = await fetch(base_url + 'Requisicion/generarReporteRequisicion/' + idDespacho);
        if (!response.ok) {
            throw new Error('Error al obtener los datos para el reporte.');
        }
        const result = await response.json();

        if (result.success) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = base_url + 'data/almacen/requisicion.php';
            form.target = '_blank';

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

/**
 * Muestra una notificación tipo "toast".
 */
function notifi(msg, tipo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: msg,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}