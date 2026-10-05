// Variables globales
let articulosAgregados = [];

// Institución activa
const idInstitucionOrden = document.getElementById('id_institucion')?.value || 1;

document.addEventListener('DOMContentLoaded', function () {
    inicializarComponentes();
    cargarDatosIniciales();
    configurarEventListeners();
});

function inicializarComponentes() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('txtdate').value = today;
    document.getElementById('fechaDespacho').textContent = formatFecha(today);
}

async function cargarDatosIniciales() {
    try {
        const response = await fetch(base_url + 'Orden/getInitialData?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error al cargar datos iniciales.');

        const result = await response.json();
        if (result.success) {
            const { flota, operadores, mecanicos, despachadores, articulos } = result.data;
            populateSelect('listUnidad', flota, 'id_flota', item => `${item.id_unidad} - ${item.modelo_unidad}`, 'Seleccione una unidad');
            populateSelect('listOperador', operadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un operador');
            populateSelect('listMecanico', mecanicos, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un mecánico');
            populateSelect('listDespachador', despachadores, 'id_personal', item => `${item.personal_cedula} - ${item.personal_nombre}`, 'Seleccione un despachador');
            populateSelect('listArticulo', articulos, 'id_producto', item => `${item.producto} (Stock: ${item.cant_producto})`, 'Seleccione un artículo', item => ({ 'data-stock': item.cant_producto }));

            inicializarDataTable();
            await actualizarProgresoOrdenes();
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error('Error cargando datos iniciales:', error);
        notifi('Error al cargar datos iniciales', 'error');
    }
}

function configurarEventListeners() {
    document.getElementById('txtdate').addEventListener('change', function () {
        const fecha = this.value;
        document.getElementById('fechaDespacho').textContent = formatFecha(fecha);
        document.getElementById('strDate').value = fecha;
    });

    document.getElementById('txtCant').addEventListener('input', validarCantidad);

    document.getElementById('lista').addEventListener('click', function (e) {
        if (e.target.closest('.eliminarRow')) {
            const fila = e.target.closest('tr');
            const idArticulo = fila.querySelector('input[name="cod[]"]').value;
            articulosAgregados = articulosAgregados.filter(art => art.id != idArticulo);
            fila.remove();
            actualizarEstadoBotonGenerar();
            actualizarResumenOrden();
        }
    });

    $('#listUnidad').on('select2:select', function (e) {
        const idUnidad = e.params.data.id;
        if (idUnidad > 0) fntGetUnidad(idUnidad);
    });

    $('#listOperador').on('select2:select', function (e) {
        const nombre = e.params.data.id > 0 ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#operador").textContent = nombre;
    });

    $('#listMecanico').on('select2:select', function (e) {
        const nombre = e.params.data.id > 0 ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#mecanico").textContent = nombre;
    });

    $('#listDespachador').on('select2:select', function (e) {
        const nombre = e.params.data.id !== "0" ? e.params.data.text.split(' - ')[1].trim() : 'No seleccionado';
        document.querySelector("#despachador").textContent = nombre;
    });

    $('#listArticulo').on('select2:select', function (e) {
        const idArticulo = e.params.data.id;
        if (idArticulo > 0) fntGetArt(idArticulo);
    });

    document.getElementById('btnAgrega').addEventListener('click', agregarArticulo);
    document.getElementById('formDespacho').addEventListener('submit', enviarFormulario);
    document.getElementById('formBuscarDesp').addEventListener('submit', buscarOrdenes);
    document.getElementById('btnImprimirLote').addEventListener('click', fntImprimirLote);

    $('#tblOrdenes tbody').on('change', '.select-orden', function () {
        const checkboxes = document.querySelectorAll('.select-orden:checked');
        const btn = document.getElementById('btnImprimirLote');
        btn.innerHTML = `<i class="fas fa-print"></i> Imprimir Seleccionados (${checkboxes.length}/2)`;
        if (checkboxes.length === 2) {
            btn.disabled = false;
            document.querySelectorAll('.select-orden:not(:checked)').forEach(cb => cb.disabled = true);
        } else {
            btn.disabled = true;
            document.querySelectorAll('.select-orden').forEach(cb => cb.disabled = false);
        }
    });
}

function formatFecha(fecha) {
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
}

async function fntGetUnidad(idUnidad) {
    try {
        const response = await fetch(base_url + 'Orden/getUnidad/' + idUnidad + '?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();
        if (objData.success) {
            document.querySelector("#id_unidad").textContent = objData.data.id_unidad || '-';
            document.querySelector("#vim_unidad").textContent = objData.data.vim_unidad || '-';
            document.querySelector("#marca_unidad").textContent = objData.data.marca_unidad || '-';
            document.querySelector("#modelo_unidad").textContent = objData.data.modelo_unidad || '-';
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error obteniendo unidad:', error);
        notifi('Error al obtener datos de la unidad', 'error');
    }
}

function populateSelect(selectId, data, valueField, textFieldFn, defaultOptionText, dataAttributesFn) {
    const select = document.getElementById(selectId);
    if (!select) return;

    select.innerHTML = `<option value="0">${defaultOptionText}</option>`;

    if (data && Array.isArray(data)) {
        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item[valueField];
            option.textContent = textFieldFn(item);

            if (selectId === 'listArticulo') {
                option.setAttribute('data-stock', item.cant_producto);
            }

            if (dataAttributesFn) {
                const attributes = dataAttributesFn(item);
                for (const key in attributes) {
                    option.setAttribute(key, attributes[key]);
                }
            }
            select.appendChild(option);
        });

        const select2Instance = $(select).select2({
            placeholder: defaultOptionText,
            language: "es",
            theme: "bootstrap4",
        }).on('select2:open', function (e) {
            $('.select2-results__options').css({
                'max-height': '250px',
                'overflow-y': 'auto'
            });
            setTimeout(function () {
                document.querySelector('.select2-search__field').focus();
            }, 10);
        });

        select2Instance.next('.select2-container').find('.select2-selection').css({
            'min-height': 'calc(2.25rem + 2px)',
            'border': '1px solid #ced4da'
        });
    }
}

async function fntGetArt(idArt) {
    try {
        const response = await fetch(base_url + 'Orden/getArt/' + idArt + '?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();
        if (objData.success) {
            document.getElementById("cantDispo").value = objData.data.cant_producto;
            document.getElementById("stockDisponible").textContent = objData.data.cant_producto;
            document.getElementById("txtCant").value = "";
            validarCantidad();
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error obteniendo artículo:', error);
        notifi('Error al obtener datos del artículo', 'error');
    }
}

function validarCantidad() {
    const cantidadInput = document.getElementById('txtCant');
    const cantidad = parseInt(cantidadInput.value);
    const stockDisponible = parseInt(document.getElementById('cantDispo').value);
    const validationElement = document.getElementById('stockValidation');

    if (isNaN(cantidad) || cantidad <= 0) {
        validationElement.textContent = 'Cantidad no válida';
        validationElement.className = 'text-danger text-sm mt-1';
        validationElement.style.display = 'block';
        return false;
    }

    if (cantidad > stockDisponible) {
        validationElement.textContent = `No hay suficiente stock. Disponible: ${stockDisponible}`;
        validationElement.className = 'text-danger text-sm mt-1';
        validationElement.style.display = 'block';
        return false;
    }

    validationElement.textContent = 'Cantidad válida';
    validationElement.className = 'text-success text-sm mt-1';
    validationElement.style.display = 'block';
    return true;
}

function agregarArticulo() {
    const select = document.getElementById('listArticulo');
    const selectedOption = select.options[select.selectedIndex];
    const cantidadInput = document.getElementById('txtCant');
    const cantidad = parseInt(cantidadInput.value);

    if (selectedOption.value == "0" || isNaN(cantidad) || cantidad <= 0) {
        notifi("Debe seleccionar un artículo y especificar una cantidad válida", 'info');
        return;
    }

    if (!validarCantidad()) {
        notifi("La cantidad no es válida", 'error');
        return;
    }

    if (articulosAgregados.some(art => art.id == selectedOption.value)) {
        notifi("Este artículo ya fue agregado a la orden", 'warning');
        return;
    }

    articulosAgregados.push({
        id: selectedOption.value,
        nombre: selectedOption.text,
        cantidad: cantidad
    });

    if (document.querySelector('#lista tr td[colspan]')) {
        document.querySelector('#lista').innerHTML = '';
    }

    const item = `
        <tr class="articulo-item">
            <td>
                <input type="hidden" name="cod[]" value="${selectedOption.value}"/>
                <span>COD ${selectedOption.value.toString().padStart(4, '0')}</span>
            </td>
            <td>
                <input type="hidden" name="articulo[]" value="${selectedOption.text}"/>
                <span>${selectedOption.text}</span>
            </td>
            <td>
                <input type="hidden" name="cantidad[]" value="${cantidad}"/>
                <span>${cantidad}</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm eliminarRow">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        </tr>
    `;

    document.getElementById("lista").insertAdjacentHTML('beforeend', item);
    cantidadInput.value = "";
    document.getElementById('stockValidation').style.display = 'none';

    actualizarEstadoBotonGenerar();
    actualizarResumenOrden();
}

function actualizarEstadoBotonGenerar() {
    const btnGenerar = document.getElementById('btnGenerar');
    btnGenerar.disabled = articulosAgregados.length === 0;
}

function actualizarResumenOrden() {
    document.getElementById('totalArticulos').textContent = articulosAgregados.length;
    const totalUnidades = articulosAgregados.reduce((total, art) => total + art.cantidad, 0);
    document.getElementById('totalUnidades').textContent = totalUnidades;
}

async function enviarFormulario(e) {
    e.preventDefault();

    const unidad = document.getElementById('listUnidad').value;
    const operador = document.getElementById('listOperador').value;
    const mecanico = document.getElementById('listMecanico').value;
    const despachador = document.getElementById('listDespachador').value;
    const fecha = document.getElementById('txtdate').value;
    const idDespacho = document.getElementById('idDespacho').value;

    if (unidad == "0" || operador == "0" || mecanico == "0" || despachador == "0" || !fecha) {
        notifi("Debe completar todos los campos obligatorios", 'error');
        return;
    }

    if (articulosAgregados.length === 0) {
        notifi("Debe agregar al menos un artículo a la orden", 'error');
        return;
    }

    const btnGenerar = document.getElementById('btnGenerar');
    const originalText = btnGenerar.innerHTML;
    btnGenerar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (idDespacho ? 'Actualizando...' : 'Procesando...');
    btnGenerar.disabled = true;

    try {
        const formData = new FormData(document.getElementById('formDespacho'));
        formData.append('id_institucion', idInstitucionOrden);

        const response = await fetch(base_url + 'Orden/setOrdenD', {
            method: 'POST',
            body: formData
        });

        const objData = await response.json();

        if (objData.success) {
            notifi(objData.message, 'success');
            document.getElementById('formDespacho').reset();
            document.getElementById('idDespacho').value = '';
            document.getElementById('btnGenerar').innerHTML = '<i class="fas fa-paper-plane"></i> Generar Orden';
            document.getElementById('lista').innerHTML = '';
            articulosAgregados = [];

            $('#listUnidad').val('0').trigger('change');
            $('#listOperador').val('0').trigger('change');
            $('#listMecanico').val('0').trigger('change');
            $('#listDespachador').val('0').trigger('change');
            $('#listArticulo').val('0').trigger('change');

            document.querySelector("#id_unidad").textContent = '-';
            document.querySelector("#vim_unidad").textContent = '-';
            document.querySelector("#marca_unidad").textContent = '-';
            document.querySelector("#modelo_unidad").textContent = '-';
            document.querySelector("#operador").textContent = '-';
            document.querySelector("#mecanico").textContent = '-';
            document.querySelector("#despachador").textContent = '-';
            document.querySelector("#totalArticulos").textContent = '0';
            document.querySelector("#totalUnidades").textContent = '0';

            if ($.fn.DataTable.isDataTable('#tblOrdenes')) {
                $('#tblOrdenes').DataTable().ajax.reload();
            }
            await actualizarProgresoOrdenes();

            const today = new Date().toISOString().split('T')[0];
            document.getElementById('txtdate').value = today;
            document.getElementById('fechaDespacho').textContent = formatFecha(today);
            document.getElementById('strDate').value = today;
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error enviando formulario:', error);
        notifi('Error al procesar la orden', 'error');
    } finally {
        btnGenerar.innerHTML = originalText;
        btnGenerar.disabled = false;
    }
}

async function actualizarProgresoOrdenes() {
    try {
        const response = await fetch(base_url + 'Orden/getMonthlyStats?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error al obtener estadísticas mensuales.');

        const result = await response.json();
        if (result.success) {
            const { current_orders, target_orders, percentage } = result.data;
            const percentageEl = document.getElementById('progressPercentage');
            const fillEl = document.getElementById('progressBarFill');
            const currentEl = document.getElementById('progressCurrent');

            if (percentageEl) percentageEl.textContent = `${percentage}%`;
            if (fillEl) fillEl.style.width = `${percentage}%`;
            if (currentEl) currentEl.textContent = `${current_orders} órdenes`;
        }
    } catch (error) {
        console.error('Error actualizando progreso de órdenes:', error);
    }
}

function inicializarDataTable() {
    if ($.fn.DataTable.isDataTable('#tblOrdenes')) {
        $('#tblOrdenes').DataTable().destroy();
    }

    $('#tblOrdenes').DataTable({
        "processing": true,
        "serverSide": false,
        language: { url: base_url + 'src/plugins/js/es_es.json' },
        "ajax": {
            "url": base_url + "Orden/getOrdenes?id_institucion=" + idInstitucionOrden,
            "dataSrc": "data"
        },
        "columns": [
            {
                "data": "id_despacho",
                "orderable": false,
                "className": "text-center",
                "render": function (data, type, row) {
                    return `<input type="checkbox" class="select-orden" value="${data}">`;
                }
            },
            { "data": "numero_orden" },
            { "data": "fecha_despacho" },
            {
                "data": null, "render": function (data, type, row) {
                    return `${row.id_unidad} - ${row.modelo_unidad}`;
                }
            },
            { "data": "operador_nombre" },
            {
                "data": "total_articulos", "className": "text-center", "render": function (data, type, row) {
                    return `<span class="badge badge-info">${data} artículos</span>`;
                }
            },
            {
                "data": null, "orderable": false, "className": "text-center", "render": function (data, type, row) {
                    return `
                    <div class="btn-group">
                        <button onclick="fntViewOrden(${row.id_despacho})" class="btn btn-info btn-sm" title="Ver detalles"><i class="fas fa-eye"></i></button>
                        <button onclick="fntEditOrden(${row.id_despacho})" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="fntImpDespacho(${row.id_despacho})" class="btn btn-primary btn-sm" title="Imprimir PDF"><i class="fas fa-print"></i></button>
                        <button onclick="fntdelDesp(${row.id_despacho})" class="btn btn-danger btn-sm" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </div>
                `;
                }
            }
        ],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
        order: [[1, 'desc']],
        "responsive": true,
        "bDestroy": true,
        dom: 'lBfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i> Excel', className: 'btn btn-success' },
            { extend: 'pdfHtml5', text: '<i class="fas fa-file-pdf"></i> PDF', className: 'btn btn-danger' },
            { extend: 'print', text: '<i class="fas fa-print"></i> Imprimir', className: 'btn btn-info' }
        ]
    });
}

async function buscarOrdenes(e) {
    e.preventDefault();

    try {
        const formData = new FormData(document.getElementById('formBuscarDesp'));
        formData.append('id_institucion', idInstitucionOrden);

        const response = await fetch(base_url + 'Orden/getBuscarOrden', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        const searchResultsContainer = document.getElementById('searchResultsContainer');
        searchResultsContainer.innerHTML = '';

        if (result.success && result.data.length > 0) {
            result.data.forEach(orden => {
                const resultItem = `
                    <div onclick="fntViewOrden(${orden.id_despacho})" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Orden #${orden.numero_orden}</h5>
                            <small>${orden.fecha_despacho}</small>
                        </div>
                        <p class="mb-1 small">
                            <i class="fas fa-bus-alt mr-1"></i> ${orden.id_unidad} - ${orden.modelo_unidad}<br>
                            <i class="fas fa-user-tie mr-1"></i> ${orden.operador_nombre}
                        </p>
                    </div>
                `;
                if (searchResultsContainer.querySelector('.list-group') === null) {
                    searchResultsContainer.innerHTML = '<div class="list-group"></div>';
                }
                searchResultsContainer.querySelector('.list-group').insertAdjacentHTML('beforeend', resultItem);
            });
        } else {
            searchResultsContainer.innerHTML = `
                <div class="text-center p-3 text-muted">
                    <i class="fas fa-search fa-2x mb-2"></i>
                    <p>${result.message || 'No se encontraron resultados.'}</p>
                </div>
            `;
            if (result.message) notifi(result.message, 'info');
        }
    } catch (error) {
        console.error('Error buscando órdenes:', error);
        notifi('Error al realizar la búsqueda', 'error');
    }
}

async function fntViewOrden(idDespacho) {
    try {
        const response = await fetch(base_url + 'Orden/getOrdenDetalle/' + idDespacho + '?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();

        if (objData.success) {
            mostrarModalOrden(objData.data);
        } else {
            notifi(objData.message || 'No se pudo cargar el detalle.', 'error');
        }
    } catch (error) {
        console.error('Error obteniendo detalles de la orden:', error);
        notifi('Error al obtener detalles de la orden', 'error');
    }
}

async function fntEditOrden(idDespacho) {
    try {
        const response = await fetch(base_url + 'Orden/getOrden/' + idDespacho + '?id_institucion=' + idInstitucionOrden);
        if (!response.ok) throw new Error('Error en la respuesta del servidor');

        const objData = await response.json();
        if (objData.success) {
            const { orden, articulos } = objData.data;

            document.getElementById('idDespacho').value = orden.id_despacho;
            document.getElementById('txtdate').value = orden.fecha_despacho;
            document.getElementById('strDate').value = orden.fecha_despacho;
            document.getElementById('fechaDespacho').textContent = formatFecha(orden.fecha_despacho);
            document.getElementById('txtObs').value = orden.observacion;

            $('#listUnidad').val(orden.id_flota).trigger('change');
            setTimeout(() => fntGetUnidad(orden.id_flota), 100);

            $('#listOperador').val(orden.operador_id).trigger('change');
            $('#listMecanico').val(orden.mecanico_id).trigger('change');
            $('#listDespachador').val(orden.despachador_id).trigger('change');

            articulosAgregados = [];
            document.getElementById('lista').innerHTML = '';

            articulos.forEach(art => {
                articulosAgregados.push({
                    id: art.id_producto,
                    nombre: art.producto,
                    cantidad: parseFloat(art.cant_despacho)
                });

                const item = `
                    <tr class="articulo-item">
                        <td>
                            <input type="hidden" name="cod[]" value="${art.id_producto}"/>
                            <span>COD ${art.id_producto.toString().padStart(4, '0')}</span>
                        </td>
                        <td>
                            <input type="hidden" name="articulo[]" value="${art.producto}"/>
                            <span>${art.producto}</span>
                        </td>
                        <td>
                            <input type="hidden" name="cantidad[]" value="${art.cant_despacho}"/>
                            <span>${art.cant_despacho}</span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm eliminarRow">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                `;
                document.getElementById("lista").insertAdjacentHTML('beforeend', item);
            });

            actualizarEstadoBotonGenerar();
            actualizarResumenOrden();

            document.getElementById('btnGenerar').innerHTML = '<i class="fas fa-sync"></i> Actualizar Orden';
            document.querySelector('.content-wrapper').scrollTo({ top: 0, behavior: 'smooth' });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            notifi(objData.message, 'error');
        }
    } catch (error) {
        console.error('Error obteniendo orden para editar:', error);
        notifi('Error al cargar la orden para edición', 'error');
    }
}

function mostrarModalOrden(orden) {
    const modalContent = `
        <div class="modal fade" id="ordenDetailModal" tabindex="-1" role="dialog" aria-labelledby="ordenDetailModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ordenDetailModalLabel">Detalles de Orden #${orden.numero_orden}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Información de la Orden</h5>
                                <ul class="list-unstyled">
                                    <li><strong>N° Orden:</strong> ${orden.numero_orden}</li>
                                    <li><strong>Fecha:</strong> ${orden.fecha_despacho}</li>
                                    <li><strong>Unidad:</strong> ${orden.id_unidad} - ${orden.marca_unidad} ${orden.modelo_unidad}</li>
                                    <li><strong>VIN:</strong> ${orden.vim_unidad || 'N/A'}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Personal</h5>
                                <ul class="list-unstyled">
                                    <li><strong>Operador:</strong> ${orden.operador_nombre}</li>
                                    <li><strong>Mecánico:</strong> ${orden.mecanico_nombre}</li>
                                    <li><strong>Despachador:</strong> ${orden.despachador_nombre}</li>
                                </ul>
                            </div>
                        </div>
                        <hr>
                        <h5>Artículos Despachados</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Artículo</th>
                                        <th>Cantidad</th>
                                        <th>Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${orden.articulos && orden.articulos.length > 0 ?
            orden.articulos.map(art => `
                                            <tr>
                                                <td>${art.producto}</td>
                                                <td>${art.cant_despacho}</td>
                                                <td>${art.ubicacion || 'N/A'}</td>
                                            </tr>
                                        `).join('') :
            '<tr><td colspan="3" class="text-center">No hay artículos</td></tr>'
        }
                                </tbody>
                            </table>
                        </div>
                        ${orden.observacion ? `
                            <div class="mt-3">
                                <h5>Observaciones</h5>
                                <p class="text-muted">${orden.observacion}</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button onclick="fntImpDespacho(${orden.id_despacho})" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir PDF</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalContent);
    const modalElement = $('#ordenDetailModal');
    modalElement.modal('show');
    modalElement.on('hidden.bs.modal', function () {
        $(this).remove();
    });
}

function cerrarModal(modalId) {
    $('#' + modalId).modal('hide');
}

async function fntdelDesp(idDesp) {
    const { value: text } = await Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción no se puede deshacer",
        input: 'textarea',
        inputPlaceholder: 'Motivo de la eliminación...',
        inputAttributes: { 'aria-label': 'Motivo de la eliminación' },
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) return 'Debe especificar un motivo';
        }
    });

    if (text) {
        try {
            const params = new URLSearchParams();
            params.append('idDesp', idDesp);
            params.append('srtText', text);
            params.append('id_institucion', idInstitucionOrden);

            const response = await fetch(base_url + 'Orden/delOrden', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });

            const objData = await response.json();

            if (objData.success) {
                notifi(objData.message, 'success');
                $('#tblOrdenes').DataTable().ajax.reload();
            } else {
                notifi(objData.message, 'error');
            }
        } catch (error) {
            console.error('Error eliminando orden:', error);
            notifi('Error al eliminar la orden', 'error');
        }
    }
}

async function fntImprimirLote() {
    const checkboxes = document.querySelectorAll('.select-orden:checked');
    if (checkboxes.length !== 2) {
        notifi("Debe seleccionar exactamente 2 órdenes.", "warning");
        return;
    }

    const ids = Array.from(checkboxes).map(cb => cb.value);

    try {
        const response = await fetch(base_url + 'Orden/getOrdenesPrint?id_institucion=' + idInstitucionOrden, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids: ids, id_institucion: idInstitucionOrden })
        });

        const result = await response.json();

        if (result.success) {
            // Asegurar que id_institucion esté en los datos para el PDF
            const reporteData = {
                ...result.data,
                id_institucion: idInstitucionOrden
            };

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = base_url + "data/almacen/reporte.php";
            form.target = '_blank';

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'reporteData';
            input.value = JSON.stringify(reporteData);
            form.appendChild(input);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        } else {
            notifi(result.message, 'error');
        }
    } catch (error) {
        console.error(error);
        notifi("Error al procesar la solicitud.", "error");
    }
}

function fntImpDespacho(idDespacho) {
    fetch(base_url + 'Orden/getOrdenDetalle/' + idDespacho + '?id_institucion=' + idInstitucionOrden)
        .then(response => response.json())
        .then(result => {
            if (result.success && result.data) {
                // Asegurar que id_institucion esté en los datos para el PDF
                const reporteData = {
                    ...result.data,
                    id_institucion: idInstitucionOrden
                };
                generarPDFOrden(reporteData);
            } else {
                notifi(result.message || 'No se pudieron obtener los datos para el reporte.', 'error');
            }
        })
        .catch(error => {
            console.error('Error al obtener datos para el PDF:', error);
            notifi('Error de conexión al generar el reporte.', 'error');
        });
}

function generarPDFOrden(reporteData) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/almacen/reportePDFdesp.php";
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    input.value = JSON.stringify(reporteData);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

function notifi(message, tipo) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: tipo,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}