<?= head($data)?>
<!-- ID de la flota para ser usado por JS -->
<input type="hidden" id="id_flota" value="<?= $data['id_flota'] ?>">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-history"></i> Historial de la Unidad</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>flota">Flota</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Cabecera de la página -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Unidad: <span class="font-weight-bold"><?= $data['unidad']['id_unidad'] ?></span></h3>
                    <div class="card-tools">
                        <a href="<?= base_url() ?>flota" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver a la Flota
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Marca:</strong><p class="text-muted"><?= $data['unidad']['marca_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Modelo:</strong><p class="text-muted"><?= $data['unidad']['modelo_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>VIN:</strong><p class="text-muted"><?= $data['unidad']['vim_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Kilometraje Actual:</strong>
                            <p class="text-muted">
                                <?= isset($data['unidad']['kilometraje_actual']) && is_numeric($data['unidad']['kilometraje_actual'])
                                    ? number_format($data['unidad']['kilometraje_actual'], 0, ',', '.') . ' Km' 
                                    : 'No registrado' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="card">
                <div class="card-body">
                    <form id="formFiltros">
                        <div class="row align-items-end">
                            <div class="col-md-2 form-group">
                                <label for="fechaInicio">Fecha Inicio</label>
                                <input type="date" id="fechaInicio" name="fechaInicio" class="form-control">
                            </div>
                            <div class="col-md-2 form-group">
                                <label for="fechaFin">Fecha Fin</label>
                                <input type="date" id="fechaFin" name="fechaFin" class="form-control">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="filtroTipo">Tipo de Evento</label>
                                <select id="filtroTipo" name="filtroTipo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="despacho">Orden de Despacho</option>
                                    <option value="aceite">Cambio de Aceite</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="status">Cambio de Estado</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="filtroTermino">Orden / Término</label>
                                <input type="text" id="filtroTermino" name="filtroTermino" placeholder="Ej: #123, Repuesto..." class="form-control">
                            </div>
                            <div class="col-md-2 form-group">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                                <button type="button" id="btnLimpiarFiltros" class="btn btn-secondary btn-block mt-2">Limpiar</button>
                                <button type="button" id="btnExportarPDF" class="btn btn-danger btn-block mt-2"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
                            </div>
                        </div>
                        <!-- Contadores de Resultados -->
                        <div class="row mt-3" id="resumenResultados" style="display:none;">
                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center" style="gap: 10px;">
                                    <span class="text-muted mr-2"><strong>Resumen:</strong></span>
                                    <span class="badge badge-primary p-2" style="font-size: 0.9rem;"><i class="fas fa-file-invoice mr-1"></i> Ordenes: <span id="count_despacho">0</span></span>
                                    <span class="badge badge-warning p-2" style="font-size: 0.9rem;"><i class="fas fa-oil-can mr-1"></i> Aceite: <span id="count_aceite">0</span></span>
                                    <span class="badge badge-secondary p-2" style="font-size: 0.9rem;"><i class="fas fa-exchange-alt mr-1"></i> Cambios Estado: <span id="count_status">0</span></span>
                                    <span class="badge badge-dark p-2" style="font-size: 0.9rem;"><i class="fas fa-list-ol mr-1"></i> Total Eventos: <span id="count_total">0</span></span>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Timeline del Historial -->
            <div id="timelineContainer">
                <!-- Los items del timeline se insertarán aquí dinámicamente -->
            </div>

            <!-- Estado de Carga y Sin Resultados -->
            <div id="timeline-loader" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>

            <div id="timeline-empty" class="text-center py-5 d-none">
                <i class="fas fa-box-open fa-3x text-muted"></i>
                <h5 class="mt-3">No se encontraron eventos</h5>
                <p class="text-muted">Intenta con otros filtros o revisa más tarde.</p>
            </div>

            <!-- Paginación -->
            <div id="pagination-container" class="mt-4 d-flex justify-content-center"></div>
        </div>
    </section>
</div>

<!-- Script para actualizar los contadores al realizar la búsqueda -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Interceptamos la función fetch nativa para capturar la respuesta de la búsqueda
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        const response = await originalFetch(...args);
        const clone = response.clone(); // Clonamos la respuesta para no afectarla
        const url = args[0] ? args[0].toString() : '';
        
        // Si la petición es al historial de unidad
        if (url.includes('Flota/getHistorialUnidad')) {
            clone.json().then(data => {
                if (data.success && data.data.counts) {
                    const counts = data.data.counts;
                    // Actualizamos los contadores en la vista
                    document.getElementById('count_despacho').textContent = counts.despacho || 0;
                    document.getElementById('count_aceite').textContent = counts.aceite || 0;
                    document.getElementById('count_status').textContent = counts.status || 0;
                    // Actualizamos el contador total con el valor que ya nos envía el backend
                    document.getElementById('count_total').textContent = data.data.total_items || 0;
                    // Mostramos el contenedor
                    document.getElementById('resumenResultados').style.display = 'block';
                }
            }).catch(e => console.log('Esperando datos...'));
        }
        return response;
    };
});

// Script para el botón de Exportar PDF
document.getElementById('btnExportarPDF').addEventListener('click', async function() {
    const idFlota = document.getElementById('id_flota').value;
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    const filtroTipo = document.getElementById('filtroTipo').value;
    const filtroTermino = document.getElementById('filtroTermino').value;

    // Mostrar alerta de carga
    Swal.fire({
        title: 'Generando PDF...',
        text: 'Por favor espere mientras se procesan los datos.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading() }
    });

    try {
        // 1. Obtener todos los datos (sin paginación) desde el controlador
        const response = await fetch(base_url + 'Flota/getHistorialUnidadPrint/' + idFlota, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ fechaInicio, fechaFin, filtroTipo, filtroTermino })
        });
        const result = await response.json();

        if (result.success) {
            // 2. Crear un formulario invisible para enviar los datos al script PHP del PDF
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = base_url + 'data/flota/historial_flota.php';
            form.target = '_blank'; // Abrir en nueva pestaña

            // Input para los datos del historial
            const inputData = document.createElement('input');
            inputData.type = 'hidden';
            inputData.name = 'reporteData';
            inputData.value = JSON.stringify(result.data);
            form.appendChild(inputData);

            // Input para los datos de la unidad (tomados de PHP en la vista)
            const inputUnidad = document.createElement('input');
            inputUnidad.type = 'hidden';
            inputUnidad.name = 'unidadData';
            inputUnidad.value = JSON.stringify({
                id: '<?= $data['unidad']['id_unidad'] ?>',
                marca: '<?= $data['unidad']['marca_unidad'] ?>',
                modelo: '<?= $data['unidad']['modelo_unidad'] ?>',
                vin: '<?= $data['unidad']['vim_unidad'] ?>'
            });
            form.appendChild(inputUnidad);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            Swal.close();
        } else {
            Swal.fire('Error', 'No se pudieron obtener los datos para el reporte.', 'error');
        }
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Ocurrió un error de conexión.', 'error');
    }
});
</script>
<?= footer($data)?>
